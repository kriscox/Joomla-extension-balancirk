import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { finalize } from 'rxjs';
import { AccountingApiService } from '../../../core/services/accounting-api.service';

@Component({
  selector: 'app-accounting-export',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './accounting-export.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AccountingExportComponent implements OnInit {
  protected readonly loading = signal(true);
  protected readonly exporting = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly notice = signal<string | null>(null);
  protected readonly years = signal<string[]>([]);
  protected readonly selectedYear = signal('');
  protected readonly previewCount = signal(0);

  constructor(private readonly accountingApi: AccountingApiService) {}

  ngOnInit(): void {
    const currentYear = String(new Date().getFullYear());
    const yearOptions = [String(Number(currentYear) + 1), currentYear, String(Number(currentYear) - 1)];
    this.years.set(yearOptions);
    this.selectedYear.set(currentYear);
    this.loadPreview();
    this.loading.set(false);
  }

  protected setYear(event: Event): void {
    this.selectedYear.set((event.target as HTMLSelectElement).value);
    this.loadPreview();
  }

  protected export(format: 'csv' | 'xls'): void {
    this.exporting.set(true);
    this.error.set(null);
    this.notice.set(null);
    const year = this.selectedYear();

    this.accountingApi
      .getAccountingExport(year, format)
      .pipe(finalize(() => this.exporting.set(false)))
      .subscribe({
        next: (blob) => {
          const url = URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = `balancirk-accounting-${year || 'all'}.${format}`;
          a.click();
          URL.revokeObjectURL(url);
          this.notice.set(`Export (${format.toUpperCase()}) gedownload.`);
        },
        error: (err: unknown) => {
          const e = err as { error?: { message?: string }; message?: string };
          this.error.set(e?.error?.message ?? e?.message ?? 'Export mislukt.');
        },
      });
  }

  private loadPreview(): void {
    this.accountingApi.getAccountingRows(this.selectedYear()).subscribe({
      next: (payload) => this.previewCount.set(payload.rows.length),
      error: () => this.previewCount.set(0),
    });
  }
}
