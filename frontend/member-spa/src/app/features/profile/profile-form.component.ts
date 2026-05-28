import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { finalize } from 'rxjs';
import { MemberApiService } from '../../core/member/member-api.service';
import { MemberProfile, MemberProfileUpdate } from '../../core/member/models/member.model';

@Component({
  selector: 'app-profile-form',
  standalone: true,
  imports: [ReactiveFormsModule],
  templateUrl: './profile-form.component.html',
  styleUrl: './profile-form.component.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ProfileFormComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly success = signal<string | null>(null);

  protected readonly form = this.fb.nonNullable.group({
    firstname: ['', [Validators.required, Validators.maxLength(255)]],
    name:      ['', [Validators.required, Validators.maxLength(255)]],
    email:     ['', [Validators.required, Validators.email]],
    phone:     ['', [Validators.maxLength(15)]],
    street:    ['', [Validators.maxLength(255)]],
    number:    ['', [Validators.maxLength(10)]],
    bus:       ['', [Validators.maxLength(10)]],
    postcode:  ['', [Validators.maxLength(10)]],
    city:      ['', [Validators.maxLength(50)]],
    password:  ['', [Validators.maxLength(255)]],
    password2: ['', [Validators.maxLength(255)]],
  });

  constructor(
    private readonly api: MemberApiService,
    private readonly fb: FormBuilder,
  ) {}

  ngOnInit(): void {
    this.api.getCurrentMember().subscribe({
      next: m => {
        this.patchForm(m);
        this.loading.set(false);
      },
      error: (err: unknown) => {
        this.error.set(this.toMessage(err));
        this.loading.set(false);
      },
    });
  }

  protected save(): void {
    this.success.set(null);
    this.error.set(null);

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const v = this.form.getRawValue();
    const payload: MemberProfileUpdate = {
      firstname: v.firstname.trim(),
      name:      v.name.trim(),
      email:     v.email.trim(),
      phone:     v.phone.trim(),
      street:    v.street.trim(),
      number:    v.number.trim(),
      bus:       v.bus.trim(),
      postcode:  v.postcode.trim(),
      city:      v.city.trim(),
    };

    if (v.password.trim() !== '' || v.password2.trim() !== '') {
      payload.password  = v.password;
      payload.password2 = v.password2;
    }

    this.saving.set(true);
    this.api.updateCurrentMember(payload)
      .pipe(finalize(() => this.saving.set(false)))
      .subscribe({
        next: m => {
          this.patchForm(m);
          this.form.patchValue({ password: '', password2: '' });
          this.success.set('Je profiel is opgeslagen.');
        },
        error: (err: unknown) => {
          this.error.set(this.toMessage(err, 'Opslaan mislukt. Controleer je gegevens.'));
        },
      });
  }

  private patchForm(m: MemberProfile): void {
    this.form.patchValue({
      firstname: m.firstname ?? '',
      name:      m.name ?? '',
      email:     m.email ?? '',
      phone:     m.phone ?? '',
      street:    m.street ?? '',
      number:    m.number ?? '',
      bus:       m.bus ?? '',
      postcode:  m.postcode ?? '',
      city:      m.city ?? '',
    });
  }

  private toMessage(err: unknown, fallback = 'Er is een fout opgetreden.'): string {
    const e = err as { status?: number; error?: { message?: string }; message?: string };
    if (e?.status === 401) return 'Authenticatie mislukt. Log in op de website en vernieuw de pagina.';
    return e?.error?.message ?? e?.message ?? fallback;
  }
}
