import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-admin-students',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './admin-students.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminStudentsComponent {}
