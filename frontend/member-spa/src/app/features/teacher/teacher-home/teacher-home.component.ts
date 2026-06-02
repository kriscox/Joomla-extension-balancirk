import { ChangeDetectionStrategy, Component } from '@angular/core';
import { DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-teacher-home',
  standalone: true,
  imports: [DatePipe, RouterLink],
  templateUrl: './teacher-home.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TeacherHomeComponent {
  protected readonly now = new Date();
}
