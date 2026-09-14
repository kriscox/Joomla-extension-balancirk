import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { catchError, forkJoin, of } from 'rxjs';
import { MemberApiService } from '../../../core/services/member-api.service';
import { StudentApiService } from '../../../core/services/student-api.service';
import { SubscriptionApiService } from '../../../core/services/subscription-api.service';
import { MemberProfile } from '../../../core/models/member.model';

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
  protected readonly studentCount = signal(0);
  protected readonly subscriptionCount = signal(0);
  protected readonly now = new Date();

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
        this.studentCount.set(students.length);
        this.subscriptionCount.set(subscriptions.length);
        this.loading.set(false);
      },
      error: (err: unknown) => {
        this.error.set(this.toMessage(err));
        this.loading.set(false);
      },
    });
  }

  private toMessage(err: unknown): string {
    const e = err as { status?: number; error?: { message?: string }; message?: string };
    if (e?.status === 401) return 'Authenticatie mislukt. Log in op de website en vernieuw de pagina.';
    return e?.error?.message ?? e?.message ?? 'Gegevens konden niet geladen worden.';
  }
}
