import { ChangeDetectionStrategy, Component, OnInit, computed, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { finalize } from 'rxjs';
import { LessonApiService } from '../../../core/services/lesson-api.service';
import { PresenceApiService } from '../../../core/services/presence-api.service';
import { LessonSummary } from '../../../core/models/lesson.model';
import { PresenceRosterStudent } from '../../../core/models/presence.model';
import {
  formatLessonDateLabel,
  getLessonDates,
  pickDefaultLessonDate,
} from '../../../core/utils/lesson-days';

@Component({
  selector: 'app-attendance',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './attendance.component.html',
  styleUrl: './attendance.component.css',
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
  protected readonly selectedDate = signal('');
  protected readonly lessonDates = signal<string[]>([]);
  private dirty = false;

  protected readonly selectedLesson = computed(() =>
    this.lessons().find((l) => l.id === this.selectedLessonId()) ?? null,
  );

  protected readonly dateOptions = computed(() =>
    this.lessonDates().map((iso) => ({
      value: iso,
      label: formatLessonDateLabel(iso),
    })),
  );

  constructor(
    private readonly lessonsApi: LessonApiService,
    private readonly presenceApi: PresenceApiService,
    private readonly route: ActivatedRoute,
  ) {}

  ngOnInit(): void {
    const presetLesson = Number(this.route.snapshot.queryParamMap.get('lesson') || 0);
    const presetDate = this.route.snapshot.queryParamMap.get('date');

    this.lessonsApi.getLessons().subscribe({
      next: (lessons) => {
        this.lessons.set(lessons);
        this.loading.set(false);
        const lessonId = presetLesson > 0 ? presetLesson : (lessons[0]?.id ?? 0);
        if (lessonId > 0) {
          this.selectLesson(lessonId, presetDate);
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
    if (this.dirty && !window.confirm('Niet-opgeslagen aanwezigheid gaat verloren. Doorgaan?')) {
      (event.target as HTMLSelectElement).value = String(this.selectedLessonId());
      return;
    }
    this.selectLesson(Number((event.target as HTMLSelectElement).value));
  }

  protected onDateChange(event: Event): void {
    const next = (event.target as HTMLSelectElement).value;
    if (this.dirty && !window.confirm('Niet-opgeslagen aanwezigheid gaat verloren. Doorgaan?')) {
      (event.target as HTMLSelectElement).value = this.selectedDate();
      return;
    }
    this.selectedDate.set(next);
    this.dirty = false;
    this.notice.set(null);
    this.loadRoster();
  }

  protected togglePresent(studentId: number): void {
    this.dirty = true;
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
          this.dirty = false;
          this.notice.set('Aanwezigheid opgeslagen.');
        },
        error: (err: unknown) => {
          const e = err as { error?: { message?: string }; message?: string };
          this.error.set(e?.error?.message ?? e?.message ?? 'Opslaan mislukt.');
        },
      });
  }

  private selectLesson(lessonId: number, preferredDate?: string | null): void {
    this.selectedLessonId.set(lessonId);
    this.dirty = false;
    this.notice.set(null);

    const lesson = this.lessons().find((l) => l.id === lessonId);
    if (!lesson) {
      this.lessonDates.set([]);
      this.selectedDate.set('');
      this.roster.set([]);
      return;
    }

    // Prefer full lesson payload (lesdays/start/end) when list fields are incomplete.
    const applyDates = (source: LessonSummary): void => {
      const dates = getLessonDates(source.start, source.end, source.lesdays);
      this.lessonDates.set(dates);
      this.selectedDate.set(pickDefaultLessonDate(dates, preferredDate));
      this.loadRoster();
    };

    if (lesson.lesdays && lesson.start && lesson.end) {
      applyDates(lesson);
      return;
    }

    this.lessonsApi.getLesson(lessonId).subscribe({
      next: (detail) => {
        this.lessons.update((rows) =>
          rows.map((row) => (row.id === lessonId ? { ...row, ...detail } : row)),
        );
        applyDates(detail);
      },
      error: () => {
        applyDates(lesson);
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
          this.dirty = false;
        },
        error: (err: unknown) => {
          const e = err as { error?: { message?: string }; message?: string };
          this.error.set(e?.error?.message ?? e?.message ?? 'Aanwezigheid kon niet geladen worden.');
          this.roster.set([]);
        },
      });
  }
}
