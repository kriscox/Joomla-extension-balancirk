import { ChangeDetectionStrategy, Component, OnInit, computed, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { LessonApiService } from '../../../core/services/lesson-api.service';
import { LessonSummary } from '../../../core/models/lesson.model';

@Component({
  selector: 'app-teacher-lessons',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './teacher-lessons.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TeacherLessonsComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  protected readonly lessons = signal<LessonSummary[]>([]);
  protected readonly selectedYear = signal('');

  protected readonly years = computed(() => {
    const all = [...new Set(this.lessons().map((l) => String(l.year ?? '')).filter(Boolean))];
    return all.sort((a, b) => b.localeCompare(a));
  });

  protected readonly filtered = computed(() => {
    const year = this.selectedYear();
    if (!year) return this.lessons();
    return this.lessons().filter((l) => String(l.year) === year);
  });

  constructor(private readonly api: LessonApiService) {}

  ngOnInit(): void {
    this.api.getLessons().subscribe({
      next: (lessons) => {
        this.lessons.set(lessons);
        const currentYear = String(new Date().getFullYear());
        const years = [...new Set(lessons.map((l) => String(l.year ?? '')).filter(Boolean))].sort((a, b) =>
          b.localeCompare(a),
        );
        this.selectedYear.set(years.includes(currentYear) ? currentYear : (years[0] ?? ''));
        this.loading.set(false);
      },
      error: (err: unknown) => {
        const e = err as { error?: { message?: string }; message?: string };
        this.error.set(e?.error?.message ?? e?.message ?? 'Lessen konden niet geladen worden.');
        this.loading.set(false);
      },
    });
  }

  protected setYear(event: Event): void {
    this.selectedYear.set((event.target as HTMLSelectElement).value);
  }
}
