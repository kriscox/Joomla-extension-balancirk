import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { LessonApiService } from '../../../core/services/lesson-api.service';
import { LessonSummary } from '../../../core/models/lesson.model';

@Component({
  selector: 'app-admin-lessons',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './admin-lessons.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminLessonsComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  protected readonly lessons = signal<LessonSummary[]>([]);

  constructor(private readonly api: LessonApiService) {}

  ngOnInit(): void {
    this.api.getLessons().subscribe({
      next: (lessons) => {
        this.lessons.set(lessons);
        this.loading.set(false);
      },
      error: (err: unknown) => {
        const e = err as { error?: { message?: string }; message?: string };
        this.error.set(e?.error?.message ?? e?.message ?? 'Lessen konden niet geladen worden.');
        this.loading.set(false);
      },
    });
  }
}
