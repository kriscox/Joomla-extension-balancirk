import { ChangeDetectionStrategy, Component } from '@angular/core';
import { DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-accounting-home',
  standalone: true,
  imports: [DatePipe, RouterLink],
  templateUrl: './accounting-home.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AccountingHomeComponent {
  protected readonly now = new Date();
}
