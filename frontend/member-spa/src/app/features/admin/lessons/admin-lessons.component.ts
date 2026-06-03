import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-admin-lessons',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './admin-lessons.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminLessonsComponent {}
