import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { StudentApiService } from '../../../core/services/student-api.service';
import { StudentSummary } from '../../../core/models/student.model';

@Component({
  selector: 'app-admin-students',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './admin-students.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminStudentsComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  protected readonly students = signal<StudentSummary[]>([]);

  constructor(private readonly api: StudentApiService) {}

  ngOnInit(): void {
    this.api.getStudents().subscribe({
      next: (students) => {
        this.students.set(students);
        this.loading.set(false);
      },
      error: (err: unknown) => {
        const e = err as { error?: { message?: string }; message?: string };
        this.error.set(e?.error?.message ?? e?.message ?? 'Leerlingen konden niet geladen worden.');
        this.loading.set(false);
      },
    });
  }
}
