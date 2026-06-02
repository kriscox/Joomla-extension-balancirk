import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { AuthService } from '../auth/auth.service';
import { JoomlaResponse } from './json-api.utils';

export interface MemberRelation {
  member_id: number;
  member_name: string;
  member_firstname: string;
  student_id: number;
  student_name: string;
  student_firstname: string;
  primary: number;
}

@Injectable({ providedIn: 'root' })
export class AccountingApiService {
  private readonly base: string;

  constructor(private readonly http: HttpClient, auth: AuthService) {
    this.base = auth.apiBase;
  }

  /** GET /v1/subscriptions/accounting-export — download CSV for invoicing. */
  getAccountingExport(): Observable<Blob> {
    return this.http.get(`${this.base}/subscriptions/accounting-export`, {
      responseType: 'blob',
    });
  }

  /** GET /v1/members/relations — member–student relations overview. */
  getMemberRelations(): Observable<MemberRelation[]> {
    return this.http
      .get<JoomlaResponse<MemberRelation[]>>(`${this.base}/members/relations`)
      .pipe(map(r => (Array.isArray(r.data) ? r.data : [])));
  }
}
