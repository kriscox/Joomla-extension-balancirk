import { CommonModule, DatePipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, OnInit, computed, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { catchError, finalize, forkJoin, of } from 'rxjs';
import { MemberApiService } from './core/api/member-api.service';
import { LessonSummary } from './core/models/lesson.model';
import { MemberProfile, MemberProfileUpdate } from './core/models/member.model';
import { ParentStudentRelation } from './core/models/relation.model';
import { SubscriptionSummary } from './core/models/subscription.model';
import { StudentSummary } from './core/models/student.model';

type PortalSection = 'dashboard' | 'students' | 'subscriptions' | 'profile' | 'staff';
type StaffSection = 'overview' | 'lessons' | 'presence' | 'relations' | 'exports' | 'analytics';

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
  protected readonly staffLoading = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly success = signal<string | null>(null);
  protected readonly activeSection = signal<PortalSection>('dashboard');
  protected readonly member = signal<MemberProfile | null>(null);
  protected readonly students = signal<StudentSummary[]>([]);
  protected readonly subscriptions = signal<SubscriptionSummary[]>([]);
  protected readonly lessons = signal<LessonSummary[]>([]);
  protected readonly relations = signal<ParentStudentRelation[]>([]);
  protected readonly relationsLoading = signal(false);
  protected readonly relationsError = signal<string | null>(null);
  protected readonly staffNotice = signal<string | null>(null);
  protected readonly staffError = signal<string | null>(null);
  protected readonly staffSection = signal<StaffSection>('overview');
  protected readonly selectedLessonId = signal<number>(0);
  protected readonly presenceDate = signal<string>(this.defaultPresenceDate());
  protected readonly presentStudentIds = signal<number[]>([]);
  protected readonly exportYear = signal<string>(`${new Date().getFullYear()}`);
  protected readonly selectedSubscriptionYear = signal<string>('');
  protected readonly subscriptionYears = computed(() => {
    const years = [...new Set(this.subscriptions().map((s) => String(s.year ?? '')).filter(Boolean))].sort((a, b) =>
      b.localeCompare(a)
    );
    return years;
  });
  protected readonly filteredSubscriptions = computed(() => {
    const year = this.selectedSubscriptionYear();
    if (!year) {
      return this.subscriptions();
    }
    return this.subscriptions().filter((s) => String(s.year) === year);
  });
  protected readonly exportingFormat = signal<'csv' | 'xls' | null>(null);
  protected readonly now = new Date();
  protected readonly adminVariant = this.getOptionBoolean('adminVariant', false);
  protected readonly portalMode = this.getOptionValue('portalMode', this.adminVariant ? 'staff' : 'member');
  protected readonly canViewRelations = this.getOptionBoolean('canViewRelations', false);
  protected readonly canExportAccounting = this.getOptionBoolean('canExportAccounting', false);
  protected readonly allowAdminInMemberPortal = this.getOptionBoolean('allowAdminInMemberPortal', false);
  protected readonly canAdminPortal =
    this.adminVariant ||
    this.canViewRelations ||
    this.canExportAccounting ||
    this.getOptionBoolean('canAdminPortal', false);
  protected readonly subscriptionCreateUrl = this.getOptionValue('subscriptionCreateUrl', '/index.php?option=com_balancirk&view=subscription&id=0');
  protected readonly isStaffPortal = this.portalMode === 'staff';
  protected readonly showAdminEntry = this.isStaffPortal || (this.allowAdminInMemberPortal && this.canAdminPortal);
  protected readonly canOpenStaffArea = this.canAdminPortal;
  protected readonly subscriptionsByYear = computed(() => {
    const map = new Map<string, number>();
    this.subscriptions().forEach((subscription) => {
      const year = String(subscription.year || 'Onbekend');
      map.set(year, (map.get(year) ?? 0) + 1);
    });
    return Array.from(map.entries())
      .map(([year, count]) => ({ year, count }))
      .sort((a, b) => b.year.localeCompare(a.year));
  });
  protected readonly maxYearSubscriptionCount = computed(() =>
    Math.max(1, ...this.subscriptionsByYear().map((row) => row.count))
  );

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
  ) { }

  ngOnInit(): void {
    if (this.isStaffPortal && this.canOpenStaffArea) {
      this.activeSection.set('staff');
    }

    this.loadPage();
  }

  protected goToSection(section: PortalSection): void {
    this.activeSection.set(section);
    this.success.set(null);

    if (section === 'staff' && this.staffSection() === 'relations') {
      this.loadRelations(true);
    }
  }

  protected goToStaffSection(section: StaffSection): void {
    this.staffSection.set(section);
    this.staffNotice.set(null);
    this.staffError.set(null);

    if (section === 'relations') {
      this.loadRelations(true);
    }

    if (section === 'lessons' || section === 'presence') {
      this.loadLessons();
    }

    if (section === 'presence' && this.selectedLessonId() > 0) {
      this.loadPresence();
    }
  }

  protected setExportYear(year: string): void {
    this.exportYear.set(year.trim());
  }

  protected downloadAccountingExport(format: 'csv' | 'xls'): void {
    if (!this.canExportAccounting) {
      return;
    }

    this.staffNotice.set(null);
    this.staffError.set(null);
    this.exportingFormat.set(format);

    this.api
      .downloadAccountingExport(format, this.exportYear())
      .pipe(finalize(() => this.exportingFormat.set(null)))
      .subscribe({
        next: ({ blob, filename }) => {
          this.triggerDownload(blob, filename);
          this.staffNotice.set(`Export ${format.toUpperCase()} is gedownload.`);
        },
        error: (err: unknown) => {
          this.staffError.set(this.toErrorMessage(err, 'Exporteren mislukt. Probeer later opnieuw.'));
        }
      });
  }

  protected selectLesson(lessonId: number): void {
    this.selectedLessonId.set(lessonId);
    this.presentStudentIds.set([]);
    if (lessonId > 0) {
      this.loadPresence();
    }
  }

  protected setPresenceDate(date: string): void {
    this.presenceDate.set(date);
    if (this.selectedLessonId() > 0) {
      this.loadPresence();
    }
  }

  protected togglePresence(studentId: number): void {
    const set = new Set(this.presentStudentIds());
    if (set.has(studentId)) {
      set.delete(studentId);
    } else {
      set.add(studentId);
    }
    this.presentStudentIds.set(Array.from(set));
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
          const currentYear = `${new Date().getFullYear()}`;
          const years = [...new Set(subscriptions.map((s) => String(s.year ?? '')).filter(Boolean))].sort((a, b) =>
            b.localeCompare(a)
          );
          this.selectedSubscriptionYear.set(years.includes(currentYear) ? currentYear : (years[0] ?? ''));
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

  private loadLessons(): void {
    if (!this.canOpenStaffArea || this.staffLoading()) {
      return;
    }

    this.staffLoading.set(true);
    this.staffError.set(null);
    this.api
      .getLessons()
      .pipe(finalize(() => this.staffLoading.set(false)))
      .subscribe({
        next: (lessons) => {
          this.lessons.set(lessons);
          if (lessons.length > 0 && this.selectedLessonId() === 0) {
            this.selectedLessonId.set(lessons[0].id);
            this.loadPresence();
          }
        },
        error: (err: unknown) => {
          this.staffError.set(this.toErrorMessage(err, 'Lessen konden niet geladen worden.'));
        }
      });
  }

  private loadPresence(): void {
    const lessonId = this.selectedLessonId();
    const date = this.presenceDate();

    if (!this.canOpenStaffArea || lessonId <= 0 || date.trim() === '') {
      return;
    }

    this.staffLoading.set(true);
    this.staffError.set(null);
    this.api
      .getPresenceByLessonAndDate(lessonId, date)
      .pipe(finalize(() => this.staffLoading.set(false)))
      .subscribe({
        next: (presence) => {
          this.presentStudentIds.set(presence.entries.map((entry) => entry.student));
        },
        error: (err: unknown) => {
          this.presentStudentIds.set([]);
          this.staffError.set(this.toErrorMessage(err, 'Aanwezigheden konden niet geladen worden.'));
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

  protected isPresent(studentId: number): boolean {
    return this.presentStudentIds().includes(studentId);
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

  private defaultPresenceDate(): string {
    return new Date().toISOString().slice(0, 10);
  }
}
