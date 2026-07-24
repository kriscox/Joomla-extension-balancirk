import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { AuthService } from '../auth/auth.service';
import { JoomlaResponse } from './json-api.utils';

export interface PublicSettings {
  mutuality_list: string[];
  mutuality_options: string;
}

@Injectable({ providedIn: 'root' })
export class SettingsApiService {
  private readonly base: string;

  constructor(private readonly http: HttpClient, auth: AuthService) {
    this.base = auth.apiBase;
  }

  getPublicSettings(): Observable<PublicSettings> {
    return this.http
      .get<JoomlaResponse<PublicSettings>>(`${this.base}/settings/public`)
      .pipe(
        map(r => {
          const d = r.data ?? (r as unknown as PublicSettings);
          return {
            mutuality_list: Array.isArray(d.mutuality_list) ? d.mutuality_list : [],
            mutuality_options: d.mutuality_options ?? '',
          };
        }),
      );
  }
}
