import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../core/auth/auth.service';

@Component({
  selector: 'app-member-messages',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './messages.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class MemberMessagesComponent {
  private readonly auth = inject(AuthService);

  protected readonly newsletterUrl = this.auth.newsletterUrl();
}
