import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { SubscriptionApiService } from '../../../core/services/subscription-api.service';
import { SubscriptionSummary } from '../../../core/models/subscription.model';

@Component({
  selector: 'app-admin-subscriptions',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './admin-subscriptions.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminSubscriptionsComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  protected readonly subscriptions = signal<SubscriptionSummary[]>([]);

  constructor(private readonly api: SubscriptionApiService) {}

  ngOnInit(): void {
    this.api.getAllSubscriptions().subscribe({
      next: (subs) => {
        this.subscriptions.set(subs);
        this.loading.set(false);
      },
      error: (err: unknown) => {
        const e = err as { error?: { message?: string }; message?: string };
        this.error.set(e?.error?.message ?? e?.message ?? 'Inschrijvingen konden niet geladen worden.');
        this.loading.set(false);
      },
    });
  }

  protected stateLabel(value: number): string {
    return value === 0 ? 'Ingeschreven' : 'Wachtlijst';
  }
}
