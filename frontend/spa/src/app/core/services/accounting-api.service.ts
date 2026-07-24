import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { AuthService } from '../auth/auth.service';
import { JoomlaResponse } from './json-api.utils';

export interface MemberRelation {
  id?: number;
  parent_id?: number;
  parent_name?: string;
  parent_firstname?: string;
  parent_email?: string;
  student_id?: number;
  student_name?: string;
  student_firstname?: string;
  is_primary?: number;
  member_id?: number;
  member_name?: string;
  member_firstname?: string;
  primary?: number;
}

@Injectable({ providedIn: 'root' })
export class AccountingApiService {
  private readonly base: string;

  constructor(private readonly http: HttpClient, auth: AuthService) {
    this.base = auth.apiBase;
  }

  getAccountingExport(year?: string, format: 'csv' | 'xls' | 'json' = 'csv'): Observable<Blob> {
    let params = new HttpParams().set('format', format);
    if (year) {
      params = params.set('year', year);
    }
    return this.http.get(`${this.base}/subscriptions/accounting-export`, {
      params,
      responseType: 'blob',
    });
  }

  getAccountingRows(year?: string): Observable<{ year: string | null; rows: Record<string, unknown>[] }> {
    let params = new HttpParams().set('format', 'json');
    if (year) {
      params = params.set('year', year);
    }
    return this.http
      .get<JoomlaResponse<{ year?: string | null; rows?: Record<string, unknown>[] }>>(
        `${this.base}/subscriptions/accounting-export`,
        { params },
      )
      .pipe(
        map((r) => ({
          year: r.data?.year ?? year ?? null,
          rows: Array.isArray(r.data?.rows) ? r.data.rows : [],
        })),
      );
  }

  getMemberRelations(): Observable<MemberRelation[]> {
    return this.http
      .get<JoomlaResponse<MemberRelation[]>>(`${this.base}/members/relations`)
      .pipe(map((r) => (Array.isArray(r.data) ? r.data : [])));
  }
}
