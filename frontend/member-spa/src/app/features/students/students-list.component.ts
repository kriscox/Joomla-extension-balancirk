import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { StudentApiService } from '../../core/member/student-api.service';
import { StudentSummary } from '../../core/member/models/student.model';

@Component({
  selector: 'app-students-list',
  standalone: true,
  imports: [DatePipe, RouterLink],
  templateUrl: './students-list.component.html',
  styleUrl: './students-list.component.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class StudentsListComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  protected readonly students = signal<StudentSummary[]>([]);

  constructor(private readonly api: StudentApiService) {}

  ngOnInit(): void {
    this.api.getMyStudents().subscribe({
      next: s => {
        this.students.set(s);
        this.loading.set(false);
      },
      error: (err: unknown) => {
        this.error.set(this.toMessage(err));
        this.loading.set(false);
      },
    });
  }

  private toMessage(err: unknown): string {
    const e = err as { status?: number; error?: { message?: string }; message?: string };
    if (e?.status === 401) return 'Authenticatie mislukt. Log in op de website en vernieuw de pagina.';
    return e?.error?.message ?? e?.message ?? 'Leerlingen konden niet geladen worden.';
  }
}
