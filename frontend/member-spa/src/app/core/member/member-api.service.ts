import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { AuthService } from '../auth/auth.service';
import { MemberProfile, MemberProfileUpdate } from './models/member.model';
import { JsonApiItem, JsonApiResponse, extractItem } from './json-api.utils';

@Injectable({ providedIn: 'root' })
export class MemberApiService {
  private readonly base: string;

  constructor(private readonly http: HttpClient, auth: AuthService) {
    this.base = auth.apiBase;
  }

  getCurrentMember(): Observable<MemberProfile> {
    return this.http
      .get<JsonApiResponse<MemberProfile>>(`${this.base}/members/me`)
      .pipe(map(r => extractItem(r.data as JsonApiItem<MemberProfile>)));
  }

  updateCurrentMember(payload: MemberProfileUpdate): Observable<MemberProfile> {
    return this.http
      .put<JsonApiResponse<MemberProfile>>(
        `${this.base}/members/me`,
        { data: { type: 'members', attributes: payload } },
      )
      .pipe(map(r => extractItem(r.data as JsonApiItem<MemberProfile>)));
  }
}
