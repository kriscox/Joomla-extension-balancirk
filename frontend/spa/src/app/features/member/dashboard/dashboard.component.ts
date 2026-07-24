import { ChangeDetectionStrategy, Component, OnInit, computed, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { catchError, forkJoin, of } from 'rxjs';
import { MemberApiService } from '../../../core/services/member-api.service';
import { StudentApiService } from '../../../core/services/student-api.service';
import { SubscriptionApiService } from '../../../core/services/subscription-api.service';
import { AuthService } from '../../../core/auth/auth.service';
import { MemberProfile } from '../../../core/models/member.model';
import { StudentSummary } from '../../../core/models/student.model';
import { SubscriptionSummary } from '../../../core/models/subscription.model';

@Component({
  selector: 'app-member-dashboard',
  standalone: true,
  imports: [DatePipe, RouterLink],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class MemberDashboardComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  protected readonly member = signal<MemberProfile | null>(null);
  protected readonly students = signal<StudentSummary[]>([]);
  protected readonly subscriptions = signal<SubscriptionSummary[]>([]);
  protected readonly selectedYear = signal('');
  protected readonly isStaff = inject(AuthService).isTeacher();

  protected readonly years = computed(() => {
    const all = [...new Set(this.subscriptions().map((s) => String(s.year ?? '')).filter(Boolean))];
    return all.sort((a, b) => b.localeCompare(a));
  });

  protected readonly filteredSubscriptions = computed(() => {
    const year = this.selectedYear();
    if (!year) {
      return this.subscriptions();
    }
    return this.subscriptions().filter((s) => String(s.year) === year);
  });

  constructor(
    private readonly memberApi: MemberApiService,
    private readonly studentApi: StudentApiService,
    private readonly subscriptionApi: SubscriptionApiService,
  ) {}

  ngOnInit(): void {
    forkJoin({
      member: this.memberApi.getCurrentMember(),
      students: this.studentApi.getMyStudents().pipe(catchError(() => of([]))),
      subscriptions: this.subscriptionApi.getMySubscriptions().pipe(catchError(() => of([]))),
    }).subscribe({
      next: ({ member, students, subscriptions }) => {
        this.member.set(member);
        this.students.set(students);
        this.subscriptions.set(subscriptions);
        const currentYear = String(new Date().getFullYear());
        const years = [...new Set(subscriptions.map((s) => String(s.year ?? '')).filter(Boolean))].sort((a, b) =>
          b.localeCompare(a),
        );
        this.selectedYear.set(years.includes(currentYear) ? currentYear : (years[0] ?? ''));
        this.loading.set(false);
      },
      error: (err: unknown) => {
        this.error.set(this.toMessage(err));
        this.loading.set(false);
      },
    });
  }

  protected setYear(event: Event): void {
    this.selectedYear.set((event.target as HTMLSelectElement).value);
  }

  protected canEditStudent(student: StudentSummary): boolean {
    return Number(student.isPrimary) === 1;
  }

  protected stateLabel(value: number): string {
    return value === 0 ? 'Ingeschreven' : 'Wachtlijst';
  }

  private toMessage(err: unknown): string {
    const e = err as { status?: number; error?: { message?: string }; message?: string };
    if (e?.status === 401) {
      return 'Authenticatie mislukt. Log in op de website en vernieuw de pagina.';
    }
    return e?.error?.message ?? e?.message ?? 'Gegevens konden niet geladen worden.';
  }
}
