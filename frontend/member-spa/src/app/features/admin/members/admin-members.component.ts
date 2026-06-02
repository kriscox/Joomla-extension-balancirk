import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-admin-members',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './admin-members.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminMembersComponent {}
