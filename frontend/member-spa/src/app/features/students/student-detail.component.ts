import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { StudentApiService } from '../../core/member/student-api.service';
import { Student } from '../../core/member/models/student.model';

@Component({
  selector: 'app-student-detail',
  standalone: true,
  imports: [DatePipe, RouterLink],
  templateUrl: './student-detail.component.html',
  styleUrl: './student-detail.component.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class StudentDetailComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  protected readonly student = signal<Student | null>(null);

  constructor(
    private readonly api: StudentApiService,
    private readonly route: ActivatedRoute,
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    this.api.getStudent(id).subscribe({
      next: s => {
        this.student.set(s);
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
    return e?.error?.message ?? e?.message ?? 'Leerling kon niet geladen worden.';
  }
}
