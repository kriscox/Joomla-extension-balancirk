import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { finalize, switchMap, of } from 'rxjs';
import { StudentApiService } from '../../core/member/student-api.service';
import { SettingsApiService } from '../../core/member/settings-api.service';
import { StudentWrite } from '../../core/member/models/student.model';

@Component({
  selector: 'app-student-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './student-form.component.html',
  styleUrl: './student-form.component.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class StudentFormComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly mutualityOptions = signal<string[]>([]);
  protected readonly editId = signal<number | null>(null);

  protected readonly form = this.fb.nonNullable.group({
    firstname:   ['', [Validators.required, Validators.maxLength(255)]],
    name:        ['', [Validators.required, Validators.maxLength(255)]],
    birthdate:   ['', [Validators.required]],
    street:      ['', [Validators.maxLength(255)]],
    number:      ['', [Validators.maxLength(10)]],
    bus:         ['', [Validators.maxLength(10)]],
    postcode:    ['', [Validators.maxLength(10)]],
    city:        ['', [Validators.maxLength(50)]],
    phone:       ['', [Validators.maxLength(15)]],
    email:       ['', [Validators.email, Validators.maxLength(100)]],
    mutuality:   [''],
    uitpas:      ['', [Validators.maxLength(13)]],
    allow_photo: [false],
  });

  constructor(
    private readonly api: StudentApiService,
    private readonly settingsApi: SettingsApiService,
    private readonly fb: FormBuilder,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    const studentId = id ? Number(id) : null;
    this.editId.set(studentId);

    this.settingsApi.getPublicSettings().pipe(
      switchMap(settings => {
        this.mutualityOptions.set(settings.mutuality_list);
        if (studentId) {
          return this.api.getStudent(studentId);
        }
        return of(null);
      }),
    ).subscribe({
      next: student => {
        if (student) {
          this.form.patchValue({
            firstname:   student.firstname ?? '',
            name:        student.name ?? '',
            birthdate:   student.birthdate ?? '',
            street:      student.street ?? '',
            number:      student.number ?? '',
            bus:         student.bus ?? '',
            postcode:    student.postcode ?? '',
            city:        student.city ?? '',
            phone:       student.phone ?? '',
            email:       student.email ?? '',
            mutuality:   student.mutuality ?? '',
            uitpas:      student.uitpas ?? '',
            allow_photo: !!student.allow_photo,
          });
        }
        this.loading.set(false);
      },
      error: (err: unknown) => {
        this.error.set(this.toMessage(err));
        this.loading.set(false);
      },
    });
  }

  protected save(): void {
    this.error.set(null);

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const v = this.form.getRawValue();
    const payload: StudentWrite = {
      firstname:   v.firstname.trim(),
      name:        v.name.trim(),
      birthdate:   v.birthdate,
      street:      v.street.trim(),
      number:      v.number.trim(),
      bus:         v.bus.trim(),
      postcode:    v.postcode.trim(),
      city:        v.city.trim(),
      phone:       v.phone.trim(),
      email:       v.email.trim(),
      mutuality:   v.mutuality,
      uitpas:      v.uitpas.trim(),
      allow_photo: v.allow_photo,
      state:       'published',
    };

    this.saving.set(true);
    const id = this.editId();
    const request$ = id
      ? this.api.updateStudent(id, payload)
      : this.api.createStudent(payload);

    request$.pipe(finalize(() => this.saving.set(false))).subscribe({
      next: () => {
        this.router.navigate(['/students']);
      },
      error: (err: unknown) => {
        this.error.set(this.toMessage(err, 'Opslaan mislukt. Controleer de gegevens.'));
      },
    });
  }

  private toMessage(err: unknown, fallback = 'Er is een fout opgetreden.'): string {
    const e = err as { status?: number; error?: { message?: string }; message?: string };
    if (e?.status === 401) return 'Authenticatie mislukt. Log in op de website en vernieuw de pagina.';
    return e?.error?.message ?? e?.message ?? fallback;
  }
}
