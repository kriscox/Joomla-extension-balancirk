import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { LessonApiService } from '../../../core/services/lesson-api.service';
import { LessonDetail } from '../../../core/models/lesson.model';
import { formatLesdays } from '../../../core/utils/lesson-days';

@Component({
  selector: 'app-teacher-lesson-detail',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './teacher-lesson-detail.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TeacherLessonDetailComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  protected readonly lesson = signal<LessonDetail | null>(null);
  protected readonly lesdaysLabel = signal('—');

  constructor(
    private readonly api: LessonApiService,
    private readonly route: ActivatedRoute,
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id') || 0);
    if (!id) {
      this.error.set('Ongeldige les.');
      this.loading.set(false);
      return;
    }

    this.api.getLesson(id).subscribe({
      next: (lesson) => {
        this.lesson.set(lesson);
        this.lesdaysLabel.set(formatLesdays(lesson.lesdays));
        this.loading.set(false);
      },
      error: (err: unknown) => {
        const e = err as { error?: { message?: string }; message?: string };
        this.error.set(e?.error?.message ?? e?.message ?? 'Les kon niet geladen worden.');
        this.loading.set(false);
      },
    });
  }
}
