import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
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

  protected readonly isAdmin = this.auth.isAdmin();
  protected readonly isAccountant = this.auth.isAccountant();
  protected readonly isTeacher = this.auth.isTeacher();
}
