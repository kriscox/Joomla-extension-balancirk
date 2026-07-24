import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { finalize } from 'rxjs';
import { LessonApiService } from '../../../core/services/lesson-api.service';
import { PresenceApiService } from '../../../core/services/presence-api.service';
import { LessonSummary } from '../../../core/models/lesson.model';
import { PresenceRosterStudent } from '../../../core/models/presence.model';

@Component({
  selector: 'app-attendance',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './attendance.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AttendanceComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly loadingRoster = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly notice = signal<string | null>(null);
  protected readonly lessons = signal<LessonSummary[]>([]);
  protected readonly roster = signal<PresenceRosterStudent[]>([]);
  protected readonly selectedLessonId = signal(0);
  protected readonly selectedDate = signal(new Date().toISOString().slice(0, 10));

  constructor(
    private readonly lessonsApi: LessonApiService,
    private readonly presenceApi: PresenceApiService,
    private readonly route: ActivatedRoute,
  ) {}

  ngOnInit(): void {
    const presetLesson = Number(this.route.snapshot.queryParamMap.get('lesson') || 0);
    const presetDate = this.route.snapshot.queryParamMap.get('date');
    if (presetDate) {
      this.selectedDate.set(presetDate);
    }

    this.lessonsApi.getLessons().subscribe({
      next: (lessons) => {
        this.lessons.set(lessons);
        this.loading.set(false);
        const lessonId = presetLesson > 0 ? presetLesson : (lessons[0]?.id ?? 0);
        if (lessonId > 0) {
          this.selectedLessonId.set(lessonId);
          this.loadRoster();
        }
      },
      error: (err: unknown) => {
        const e = err as { error?: { message?: string }; message?: string };
        this.error.set(e?.error?.message ?? e?.message ?? 'Lessen konden niet geladen worden.');
        this.loading.set(false);
      },
    });
  }

  protected onLessonChange(event: Event): void {
    this.selectedLessonId.set(Number((event.target as HTMLSelectElement).value));
    this.loadRoster();
  }

  protected onDateChange(event: Event): void {
    this.selectedDate.set((event.target as HTMLInputElement).value);
    this.loadRoster();
  }

  protected togglePresent(studentId: number): void {
    this.roster.update((rows) =>
      rows.map((row) => (row.id === studentId ? { ...row, present: !row.present } : row)),
    );
  }

  protected save(): void {
    const lessonId = this.selectedLessonId();
    const date = this.selectedDate();
    if (!lessonId || !date) {
      return;
    }

    const presentIds = this.roster().filter((r) => r.present).map((r) => r.id);
    this.saving.set(true);
    this.error.set(null);
    this.notice.set(null);

    this.presenceApi
      .setPresence(lessonId, date, presentIds)
      .pipe(finalize(() => this.saving.set(false)))
      .subscribe({
        next: () => {
          this.notice.set('Aanwezigheid opgeslagen.');
        },
        error: (err: unknown) => {
          const e = err as { error?: { message?: string }; message?: string };
          this.error.set(e?.error?.message ?? e?.message ?? 'Opslaan mislukt.');
        },
      });
  }

  private loadRoster(): void {
    const lessonId = this.selectedLessonId();
    const date = this.selectedDate();
    if (!lessonId || !date) {
      this.roster.set([]);
      return;
    }

    this.loadingRoster.set(true);
    this.error.set(null);
    this.presenceApi
      .getPresence(lessonId, date)
      .pipe(finalize(() => this.loadingRoster.set(false)))
      .subscribe({
        next: (payload) => {
          this.roster.set(payload.roster ?? []);
        },
        error: (err: unknown) => {
          const e = err as { error?: { message?: string }; message?: string };
          this.error.set(e?.error?.message ?? e?.message ?? 'Aanwezigheid kon niet geladen worden.');
          this.roster.set([]);
        },
      });
  }
}
