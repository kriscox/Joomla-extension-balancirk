import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-admin-subscriptions',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './admin-subscriptions.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminSubscriptionsComponent {}
