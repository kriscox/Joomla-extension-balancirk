import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-accounting-home',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './accounting-home.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AccountingHomeComponent {}
