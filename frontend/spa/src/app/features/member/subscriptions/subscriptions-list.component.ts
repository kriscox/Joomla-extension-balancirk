import { ChangeDetectionStrategy, Component, OnInit, computed, inject, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { finalize } from 'rxjs';
import { SubscriptionApiService } from '../../../core/services/subscription-api.service';
import { StudentApiService } from '../../../core/services/student-api.service';
import { AuthService } from '../../../core/auth/auth.service';
import { SubscriptionSummary } from '../../../core/models/subscription.model';
import { StudentSummary } from '../../../core/models/student.model';

@Component({
  selector: 'app-subscriptions-list',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './subscriptions-list.component.html',
  styleUrl: './subscriptions-list.component.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SubscriptionsListComponent implements OnInit {
  private readonly auth = inject(AuthService);

  protected readonly loading = signal(true);
  protected readonly deletingId = signal<number | null>(null);
  protected readonly error = signal<string | null>(null);
  protected readonly notice = signal<string | null>(null);
  protected readonly subscriptions = signal<SubscriptionSummary[]>([]);
  protected readonly students = signal<StudentSummary[]>([]);
  protected readonly selectedYear = signal('');
  protected readonly selectedStudentId = signal<number>(0);
  protected readonly isStaff = this.auth.isTeacher();

  protected readonly years = computed(() => {
    const all = [...new Set(this.subscriptions().map((s) => String(s.year ?? '')).filter(Boolean))];
    return all.sort((a, b) => b.localeCompare(a));
  });

  protected readonly filtered = computed(() => {
    const ownIds = new Set(this.students().map((s) => s.id));
    let list = this.subscriptions();

    // Extra safety: never show other parents' children in the member portal.
    if (ownIds.size > 0) {
      list = list.filter((s) => !s.studentid || ownIds.has(Number(s.studentid)));
    }

    const y = this.selectedYear();
    const sid = this.selectedStudentId();
    if (y) list = list.filter((s) => String(s.year) === y);
    if (sid) list = list.filter((s) => Number(s.studentid) === sid);
    return list;
  });

  constructor(
    private readonly api: SubscriptionApiService,
    private readonly studentApi: StudentApiService,
    private readonly route: ActivatedRoute,
  ) {}

  ngOnInit(): void {
    const studentParam = Number(this.route.snapshot.queryParamMap.get('student') || 0);
    if (studentParam > 0) {
      this.selectedStudentId.set(studentParam);
    }

    const noticeParam = this.route.snapshot.queryParamMap.get('notice');
    if (noticeParam === 'waiting') {
      this.notice.set(
        'Inschrijving op de wachtlijst geplaatst. Je ontvangt een bevestiging per e-mail.',
      );
    } else if (noticeParam === 'enrolled') {
      this.notice.set('Inschrijving gelukt. Je ontvangt een bevestiging per e-mail.');
    }

    this.studentApi.getMyStudents().subscribe({
      next: (s) => this.students.set(s),
    });
    this.load();
  }

  protected setYear(event: Event): void {
    this.selectedYear.set((event.target as HTMLSelectElement).value);
  }

  protected setStudent(event: Event): void {
    this.selectedStudentId.set(Number((event.target as HTMLSelectElement).value));
  }

  protected deleteSubscription(sub: SubscriptionSummary): void {
    const child = `${sub.firstname ?? ''} ${sub.name ?? ''}`.trim() || 'deze leerling';
    const lesson = sub.lesson || 'deze les';
    if (!confirm(`Ben je zeker dat je ${child} wilt uitschrijven uit “${lesson}”?`)) {
      return;
    }

    this.error.set(null);
    this.notice.set(null);
    this.deletingId.set(sub.id);

    this.api
      .deleteSubscription(sub.id)
      .pipe(finalize(() => this.deletingId.set(null)))
      .subscribe({
        next: () => {
          this.subscriptions.update((list) => list.filter((s) => s.id !== sub.id));
          this.notice.set(`${child} is uitgeschreven uit “${lesson}”.`);
        },
        error: (err: unknown) => {
          this.error.set(this.toMessage(err, 'Uitschrijven mislukt.'));
        },
      });
  }

  protected stateLabel(value: number): string {
    return value === 0 ? 'Ingeschreven' : 'Wachtlijst';
  }

  private load(): void {
    this.api.getMySubscriptions().subscribe({
      next: (subs) => {
        this.subscriptions.set(subs);
        const currentYear = String(new Date().getFullYear());
        const allYears = [
          ...new Set(subs.map((s) => String(s.year ?? '')).filter(Boolean)),
        ].sort((a, b) => b.localeCompare(a));
        this.selectedYear.set(allYears.includes(currentYear) ? currentYear : (allYears[0] ?? ''));
        this.loading.set(false);
      },
      error: (err: unknown) => {
        this.error.set(this.toMessage(err));
        this.loading.set(false);
      },
    });
  }

  private toMessage(err: unknown, fallback = 'Er is een fout opgetreden.'): string {
    const e = err as { status?: number; error?: { message?: string }; message?: string };
    if (e?.status === 401) {
      return 'Authenticatie mislukt. Log in op de website en vernieuw de pagina.';
    }
    return e?.error?.message ?? e?.message ?? fallback;
  }
}
