import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../../auth/auth.service';

@Component({
  selector: 'app-nav',
  standalone: true,
  imports: [RouterLink, RouterLinkActive],
  templateUrl: './nav.component.html',
  styleUrl: './nav.component.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class NavComponent {
  private readonly auth = inject(AuthService);

  protected readonly isGuest = this.auth.isGuest;
  protected readonly isAdmin = this.auth.isAdmin();
  protected readonly isAccountant = this.auth.isAccountant();
  protected readonly isTeacher = this.auth.isTeacher();
  protected readonly userName = this.auth.userName();
  protected readonly newsletterUrl = this.auth.newsletterUrl();
  protected readonly passwordResetUrl = this.auth.passwordResetUrl();
  protected readonly moreOpen = signal(false);

  protected toggleMore(): void {
    this.moreOpen.update((open) => !open);
  }

  protected closeMore(): void {
    this.moreOpen.set(false);
  }

  protected logout(): void {
    this.auth.goLogout();
  }
}
