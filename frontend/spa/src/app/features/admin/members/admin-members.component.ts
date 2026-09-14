import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MemberApiService } from '../../../core/services/member-api.service';
import { MemberProfile } from '../../../core/models/member.model';

@Component({
  selector: 'app-admin-members',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './admin-members.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminMembersComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  protected readonly members = signal<MemberProfile[]>([]);

  constructor(private readonly api: MemberApiService) {}

  ngOnInit(): void {
    this.api.getMembers().subscribe({
      next: (members) => {
        this.members.set(members);
        this.loading.set(false);
      },
      error: (err: unknown) => {
        const e = err as { error?: { message?: string }; message?: string };
        this.error.set(e?.error?.message ?? e?.message ?? 'Leden konden niet geladen worden.');
        this.loading.set(false);
      },
    });
  }
}
