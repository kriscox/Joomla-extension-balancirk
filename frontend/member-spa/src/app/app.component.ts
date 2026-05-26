import { CommonModule, DatePipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { catchError, finalize, forkJoin, of } from 'rxjs';
import { MemberApiService } from './core/api/member-api.service';
import { MemberProfile, MemberProfileUpdate } from './core/models/member.model';
import { ParentStudentRelation } from './core/models/relation.model';
import { SubscriptionSummary } from './core/models/subscription.model';
import { StudentSummary } from './core/models/student.model';

type PortalSection = 'home' | 'notices' | 'students' | 'subscriptions' | 'profile' | 'admin';
type AdminSection = 'overview' | 'relations' | 'exports';

@Component({
  selector: 'app-member-root',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, DatePipe],
  templateUrl: './app.component.html',
  styleUrl: './app.component.css',
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class AppComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly success = signal<string | null>(null);
  protected readonly activeSection = signal<PortalSection>('home');
  protected readonly member = signal<MemberProfile | null>(null);
  protected readonly students = signal<StudentSummary[]>([]);
  protected readonly subscriptions = signal<SubscriptionSummary[]>([]);
  protected readonly relations = signal<ParentStudentRelation[]>([]);
  protected readonly relationsLoading = signal(false);
  protected readonly relationsError = signal<string | null>(null);
  protected readonly adminNotice = signal<string | null>(null);
  protected readonly adminError = signal<string | null>(null);
  protected readonly adminSection = signal<AdminSection>('overview');
  protected readonly exportYear = signal<string>(`${new Date().getFullYear()}`);
  protected readonly exportingFormat = signal<'csv' | 'xls' | null>(null);
  protected readonly now = new Date();
  protected readonly adminVariant = this.getOptionBoolean('adminVariant', false);
  protected readonly canViewRelations = this.getOptionBoolean('canViewRelations', false);
  protected readonly canExportAccounting = this.getOptionBoolean('canExportAccounting', false);
  protected readonly canAdminPortal =
    this.adminVariant ||
    this.canViewRelations ||
    this.canExportAccounting ||
    this.getOptionBoolean('canAdminPortal', false);
  protected readonly subscriptionCreateUrl = this.getOptionValue('subscriptionCreateUrl', '/index.php?option=com_balancirk&view=subscription&id=0');

  protected readonly profileForm = this.fb.nonNullable.group({
    firstname: ['', [Validators.required, Validators.maxLength(255)]],
    name: ['', [Validators.required, Validators.maxLength(255)]],
    email: ['', [Validators.required, Validators.email]],
    phone: ['', [Validators.maxLength(15)]],
    street: ['', [Validators.maxLength(255)]],
    number: ['', [Validators.maxLength(10)]],
    bus: ['', [Validators.maxLength(10)]],
    postcode: ['', [Validators.maxLength(10)]],
    city: ['', [Validators.maxLength(50)]],
    password: ['', [Validators.maxLength(255)]],
    password2: ['', [Validators.maxLength(255)]]
  });

  constructor(
    private readonly api: MemberApiService,
    private readonly fb: FormBuilder
  ) {}

  ngOnInit(): void {
    if (this.adminVariant && this.canAdminPortal) {
      this.activeSection.set('admin');
    }

    this.loadPage();
  }

  protected goToSection(section: PortalSection): void {
    this.activeSection.set(section);
    this.success.set(null);

    if (section === 'admin' && this.adminSection() === 'relations') {
      this.loadRelations();
    }
  }

  protected goToAdminSection(section: AdminSection): void {
    this.adminSection.set(section);
    this.adminNotice.set(null);
    this.adminError.set(null);

    if (section === 'relations') {
      this.loadRelations();
    }
  }

  protected setExportYear(year: string): void {
    this.exportYear.set(year.trim());
  }

  protected downloadAccountingExport(format: 'csv' | 'xls'): void {
    if (!this.canExportAccounting) {
      return;
    }

    this.adminNotice.set(null);
    this.adminError.set(null);
    this.exportingFormat.set(format);

    this.api
      .downloadAccountingExport(format, this.exportYear())
      .pipe(finalize(() => this.exportingFormat.set(null)))
      .subscribe({
        next: ({ blob, filename }) => {
          this.triggerDownload(blob, filename);
          this.adminNotice.set(`Export ${format.toUpperCase()} is gedownload.`);
        },
        error: (err: unknown) => {
          this.adminError.set(this.toErrorMessage(err, 'Exporteren mislukt. Probeer later opnieuw.'));
        }
      });
  }

  protected saveProfile(): void {
    this.success.set(null);
    this.error.set(null);

    if (this.profileForm.invalid) {
      this.profileForm.markAllAsTouched();
      return;
    }

    const value = this.profileForm.getRawValue();
    const payload: MemberProfileUpdate = {
      firstname: value.firstname.trim(),
      name: value.name.trim(),
      email: value.email.trim(),
      phone: value.phone.trim(),
      street: value.street.trim(),
      number: value.number.trim(),
      bus: value.bus.trim(),
      postcode: value.postcode.trim(),
      city: value.city.trim()
    };

    if (value.password.trim() !== '' || value.password2.trim() !== '') {
      payload.password = value.password;
      payload.password2 = value.password2;
    }

    this.saving.set(true);
    this.api
      .updateCurrentMember(payload)
      .pipe(finalize(() => this.saving.set(false)))
      .subscribe({
        next: (member) => {
          this.member.set(member);
          this.profileForm.patchValue({ password: '', password2: '' });
          this.success.set('Je profiel is opgeslagen.');
        },
        error: (err: unknown) => {
          this.error.set(this.toErrorMessage(err, 'Opslaan mislukt. Controleer je gegevens en probeer opnieuw.'));
        }
      });
  }

  private loadPage(): void {
    this.loading.set(true);
    this.error.set(null);

    forkJoin({
      member: this.api.getCurrentMember(),
      students: this.api.getMyStudents().pipe(catchError(() => of([]))),
      subscriptions: this.api.getMySubscriptions().pipe(catchError(() => of([])))
    })
      .pipe(finalize(() => this.loading.set(false)))
      .subscribe({
        next: ({ member, students, subscriptions }) => {
          this.member.set(member);
          this.students.set(students);
          this.subscriptions.set(subscriptions);
          this.profileForm.patchValue({
            firstname: member.firstname ?? '',
            name: member.name ?? '',
            email: member.email ?? '',
            phone: member.phone ?? '',
            street: member.street ?? '',
            number: member.number ?? '',
            bus: member.bus ?? '',
            postcode: member.postcode ?? '',
            city: member.city ?? '',
            password: '',
            password2: ''
          });
        },
        error: (err: unknown) => {
          this.error.set(
            this.toErrorMessage(
              err,
              'Gegevens konden niet geladen worden. Controleer of je bent ingelogd in Joomla.'
            )
          );
        }
      });
  }

  private loadRelations(force = false): void {
    if (!this.canViewRelations || this.relationsLoading()) {
      return;
    }

    if (!force && this.relations().length > 0) {
      return;
    }

    this.relationsLoading.set(true);
    this.relationsError.set(null);

    this.api
      .getParentStudentRelations()
      .pipe(finalize(() => this.relationsLoading.set(false)))
      .subscribe({
        next: (rows) => {
          this.relations.set(rows);
        },
        error: (err: unknown) => {
          this.relationsError.set(this.toErrorMessage(err, 'Relaties konden niet geladen worden.'));
        }
      });
  }

  private triggerDownload(blob: Blob, filename: string): void {
    const objectUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = objectUrl;
    link.download = filename;
    link.rel = 'noopener';
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(objectUrl), 0);
  }

  private toErrorMessage(err: unknown, fallback: string): string {
    if (!err || typeof err !== 'object') {
      return fallback;
    }

    const candidate = err as { status?: number; error?: { message?: string }; message?: string };

    if (candidate.status === 401) {
      return 'Authenticatie mislukt. Log in op de website en vernieuw de pagina.';
    }

    return candidate.error?.message ?? candidate.message ?? fallback;
  }

  protected getSubscriptionStateLabel(value: number): string {
    return value === 0 ? 'Ingeschreven' : 'Wachtlijst';
  }

  private getOptionValue(key: string, fallback: string): string {
    const joomla = (globalThis as { Joomla?: { getOptions?: (name: string) => unknown } }).Joomla;
    const options = joomla?.getOptions?.('balancirk-member-spa') as Record<string, unknown> | undefined;
    const value = options?.[key];

    return typeof value === 'string' && value.trim() !== '' ? value : fallback;
  }

  private getOptionBoolean(key: string, fallback: boolean): boolean {
    const joomla = (globalThis as { Joomla?: { getOptions?: (name: string) => unknown } }).Joomla;
    const options = joomla?.getOptions?.('balancirk-member-spa') as Record<string, unknown> | undefined;
    const value = options?.[key];

    if (typeof value === 'boolean') {
      return value;
    }

    return fallback;
  }
}
