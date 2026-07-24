import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { LessonApiService } from '../../../core/services/lesson-api.service';
import { LessonSummary } from '../../../core/models/lesson.model';

@Component({
  selector: 'app-teacher-home',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './teacher-home.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TeacherHomeComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  protected readonly lessonCount = signal(0);

  constructor(private readonly lessonsApi: LessonApiService) {}

  ngOnInit(): void {
    this.lessonsApi.getLessons().subscribe({
      next: (lessons) => {
        this.lessonCount.set(lessons.length);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Lessen konden niet geladen worden.');
        this.loading.set(false);
      },
    });
  }
}
