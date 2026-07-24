import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { finalize } from 'rxjs';
import { MemberApiService } from '../../../core/services/member-api.service';
import { AuthService } from '../../../core/auth/auth.service';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './register.component.html',
  styleUrl: './register.component.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RegisterComponent {
  private readonly api = inject(MemberApiService);
  private readonly auth = inject(AuthService);
  private readonly fb = inject(FormBuilder);

  protected readonly saving = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly success = signal<string | null>(null);

  protected readonly form = this.fb.nonNullable.group({
    firstname: ['', [Validators.required, Validators.maxLength(255)]],
    name: ['', [Validators.required, Validators.maxLength(255)]],
    username: ['', [Validators.required, Validators.maxLength(255)]],
    email: ['', [Validators.required, Validators.email]],
    phone: ['', [Validators.maxLength(15)]],
    street: ['', [Validators.maxLength(255)]],
    number: ['', [Validators.maxLength(10)]],
    bus: ['', [Validators.maxLength(10)]],
    postcode: ['', [Validators.maxLength(10)]],
    city: ['', [Validators.maxLength(50)]],
    password: ['', [Validators.required, Validators.minLength(8)]],
    password2: ['', [Validators.required, Validators.minLength(8)]],
  });

  protected submit(): void {
    this.error.set(null);
    this.success.set(null);

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const v = this.form.getRawValue();
    if (v.password !== v.password2) {
      this.error.set('De wachtwoorden komen niet overeen.');
      return;
    }

    this.saving.set(true);
    this.api
      .register({
        firstname: v.firstname.trim(),
        name: v.name.trim(),
        username: v.username.trim(),
        email: v.email.trim(),
        phone: v.phone.trim(),
        street: v.street.trim(),
        number: v.number.trim(),
        bus: v.bus.trim(),
        postcode: v.postcode.trim(),
        city: v.city.trim(),
        password: v.password,
        password2: v.password2,
      })
      .pipe(finalize(() => this.saving.set(false)))
      .subscribe({
        next: (result) => {
          this.success.set(
            result.message ||
              'Registratie gelukt. Controleer je e-mail om je account te activeren, en meld je daarna aan.',
          );
          this.form.reset();
        },
        error: (err: unknown) => {
          this.error.set(this.toMessage(err, 'Registratie mislukt. Controleer je gegevens.'));
        },
      });
  }

  protected goLogin(): void {
    this.auth.goLogin();
  }

  private toMessage(err: unknown, fallback: string): string {
    const e = err as {
      status?: number;
      error?: { message?: string; messages?: string[] };
      message?: string;
    };
    if (e?.error?.message) return e.error.message;
    if (Array.isArray(e?.error?.messages) && e.error.messages.length) {
      return e.error.messages.join(' ');
    }
    return e?.message ?? fallback;
  }
}
