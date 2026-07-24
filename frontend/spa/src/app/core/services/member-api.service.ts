import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { AuthService } from '../auth/auth.service';
import { MemberProfile, MemberProfileUpdate, MemberRegistration } from '../models/member.model';
import { JsonApiItem, JsonApiResponse, JoomlaResponse, extractItem, extractList } from './json-api.utils';

@Injectable({ providedIn: 'root' })
export class MemberApiService {
  private readonly base: string;

  constructor(private readonly http: HttpClient, auth: AuthService) {
    this.base = auth.apiBase;
  }

  getCurrentMember(): Observable<MemberProfile> {
    return this.http
      .get<JsonApiResponse<MemberProfile>>(`${this.base}/members/me`)
      .pipe(map((r) => extractItem(r.data as JsonApiItem<MemberProfile>)));
  }

  getMembers(): Observable<MemberProfile[]> {
    return this.http
      .get<JsonApiResponse<MemberProfile>>(`${this.base}/members`)
      .pipe(map((r) => extractList(r)));
  }

  updateCurrentMember(payload: MemberProfileUpdate): Observable<MemberProfile> {
    return this.http
      .put<JsonApiResponse<MemberProfile>>(`${this.base}/members/me`, {
        data: { type: 'members', attributes: payload },
      })
      .pipe(map((r) => extractItem(r.data as JsonApiItem<MemberProfile>)));
  }

  register(payload: MemberRegistration): Observable<{ registered: boolean; message?: string }> {
    return this.http
      .post<JoomlaResponse<{ registered?: boolean; message?: string }>>(
        `${this.base}/members/register`,
        payload,
      )
      .pipe(
        map((response) => {
          if (response?.success === false) {
            throw new Error(response.message || 'Registratie mislukt. Controleer je gegevens.');
          }
          const data = response?.data;
          return {
            registered: Boolean(data?.registered),
            message: data?.message ?? response.message ?? '',
          };
        }),
      );
  }
}
