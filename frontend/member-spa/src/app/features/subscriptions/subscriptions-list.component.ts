import { ChangeDetectionStrategy, Component, OnInit, computed, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { finalize } from 'rxjs';
import { SubscriptionApiService } from '../../core/member/subscription-api.service';
import { SubscriptionSummary } from '../../core/member/models/subscription.model';

@Component({
  selector: 'app-subscriptions-list',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './subscriptions-list.component.html',
  styleUrl: './subscriptions-list.component.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SubscriptionsListComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly deletingId = signal<number | null>(null);
  protected readonly error = signal<string | null>(null);
  protected readonly notice = signal<string | null>(null);
  protected readonly subscriptions = signal<SubscriptionSummary[]>([]);
  protected readonly selectedYear = signal('');

  protected readonly years = computed(() => {
    const all = [...new Set(this.subscriptions().map(s => String(s.year ?? '')).filter(Boolean))];
    return all.sort((a, b) => b.localeCompare(a));
  });

  protected readonly filtered = computed(() => {
    const y = this.selectedYear();
    return y ? this.subscriptions().filter(s => String(s.year) === y) : this.subscriptions();
  });

  constructor(private readonly api: SubscriptionApiService) {}

  ngOnInit(): void {
    this.load();
  }

  protected setYear(event: Event): void {
    this.selectedYear.set((event.target as HTMLSelectElement).value);
  }

  protected deleteSubscription(id: number): void {
    if (!confirm('Ben je zeker dat je deze inschrijving wil verwijderen?')) {
      return;
    }

    this.error.set(null);
    this.notice.set(null);
    this.deletingId.set(id);

    this.api.deleteSubscription(id)
      .pipe(finalize(() => this.deletingId.set(null)))
      .subscribe({
        next: () => {
          this.subscriptions.update(list => list.filter(s => s.id !== id));
          this.notice.set('Inschrijving verwijderd.');
        },
        error: (err: unknown) => {
          this.error.set(this.toMessage(err, 'Verwijderen mislukt.'));
        },
      });
  }

  protected stateLabel(value: number): string {
    return value === 0 ? 'Ingeschreven' : 'Wachtlijst';
  }

  private load(): void {
    this.api.getMySubscriptions().subscribe({
      next: subs => {
        this.subscriptions.set(subs);
        const currentYear = String(new Date().getFullYear());
        const allYears = [...new Set(subs.map(s => String(s.year ?? '')).filter(Boolean))].sort((a, b) => b.localeCompare(a));
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
    if (e?.status === 401) return 'Authenticatie mislukt. Log in op de website en vernieuw de pagina.';
    return e?.error?.message ?? e?.message ?? fallback;
  }
}
