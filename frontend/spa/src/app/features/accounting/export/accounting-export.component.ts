import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-accounting-export',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './accounting-export.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AccountingExportComponent {}
