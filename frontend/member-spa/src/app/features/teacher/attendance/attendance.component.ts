import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-attendance',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './attendance.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AttendanceComponent {}
