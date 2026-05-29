import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { finalize, switchMap } from 'rxjs';
import { StudentApiService } from '../../core/member/student-api.service';
import { SubscriptionApiService } from '../../core/member/subscription-api.service';
import { StudentSummary } from '../../core/member/models/student.model';
import { OpenLesson } from '../../core/member/models/subscription.model';

@Component({
  selector: 'app-subscription-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './subscription-form.component.html',
  styleUrl: './subscription-form.component.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SubscriptionFormComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly loadingLessons = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly lessonsMessage = signal('');
  protected readonly hasOpenLessons = signal(false);
  protected readonly students = signal<StudentSummary[]>([]);
  protected readonly lessons = signal<OpenLesson[]>([]);

  protected readonly form = this.fb.nonNullable.group({
    student: ['', [Validators.required]],
    lesson:  ['', [Validators.required]],
  });

  constructor(
    private readonly studentApi: StudentApiService,
    private readonly subscriptionApi: SubscriptionApiService,
    private readonly fb: FormBuilder,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.studentApi.getMyStudents().subscribe({
      next: students => {
        this.students.set(students);
        this.loading.set(false);
      },
      error: (err: unknown) => {
        this.error.set(this.toMessage(err));
        this.loading.set(false);
      },
    });
  }

  protected onStudentChange(event: Event): void {
    const studentId = Number((event.target as HTMLSelectElement).value);
    this.form.controls.lesson.setValue('');
    this.lessons.set([]);
    this.lessonsMessage.set('');

    if (!studentId) {
      return;
    }

    this.loadingLessons.set(true);
    this.subscriptionApi.getOpenLessonsForStudent(studentId)
      .pipe(finalize(() => this.loadingLessons.set(false)))
      .subscribe({
        next: payload => {
          this.hasOpenLessons.set(payload.hasOpenLessons ?? false);
          this.lessons.set(payload.lessons ?? []);
          this.lessonsMessage.set(payload.message ?? '');
        },
        error: (err: unknown) => {
          this.error.set(this.toMessage(err));
        },
      });
  }

  protected submit(): void {
    this.error.set(null);

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const v = this.form.getRawValue();
    const studentId = Number(v.student);
    const lessonId = Number(v.lesson);

    this.saving.set(true);
    this.subscriptionApi.createSubscription(studentId, lessonId)
      .pipe(finalize(() => this.saving.set(false)))
      .subscribe({
        next: () => {
          this.router.navigate(['/subscriptions']);
        },
        error: (err: unknown) => {
          this.error.set(this.toMessage(err, 'Inschrijven mislukt. Controleer of de leerling al is ingeschreven of niet in de juiste leeftijdscategorie valt.'));
        },
      });
  }

  private toMessage(err: unknown, fallback = 'Er is een fout opgetreden.'): string {
    const e = err as { status?: number; error?: { message?: string }; message?: string };
    if (e?.status === 401) return 'Authenticatie mislukt. Log in op de website en vernieuw de pagina.';
    return e?.error?.message ?? e?.message ?? fallback;
  }
}
