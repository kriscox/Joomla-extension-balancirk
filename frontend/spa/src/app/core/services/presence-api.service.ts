import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { AuthService } from '../auth/auth.service';
import { LessonPresenceSummary } from '../models/presence.model';
import { JoomlaResponse } from './json-api.utils';

@Injectable({ providedIn: 'root' })
export class PresenceApiService {
  private readonly base: string;

  constructor(private readonly http: HttpClient, auth: AuthService) {
    this.base = auth.apiBase;
  }

  getPresence(lessonId: number, date?: string): Observable<LessonPresenceSummary> {
    const url = date
      ? `${this.base}/presence/${lessonId}/${date}`
      : `${this.base}/presence/${lessonId}`;
    return this.http.get<JoomlaResponse<LessonPresenceSummary>>(url).pipe(
      map((r) => {
        const data = r.data ?? (r as unknown as LessonPresenceSummary);
        return {
          lesson: Number(data.lesson ?? lessonId),
          date: String(data.date ?? date ?? ''),
          students: Array.isArray(data.students) ? data.students.map(Number) : [],
          roster: Array.isArray(data.roster)
            ? data.roster.map((row) => ({
                id: Number(row.id ?? 0),
                firstname: String(row.firstname ?? ''),
                name: String(row.name ?? ''),
                present: Boolean(row.present),
              }))
            : [],
        };
      }),
    );
  }

  setPresence(lessonId: number, date: string, studentIds: number[]): Observable<LessonPresenceSummary> {
    return this.http
      .post<JoomlaResponse<LessonPresenceSummary>>(`${this.base}/presence/${lessonId}`, {
        lesson: lessonId,
        date,
        students: studentIds,
      })
      .pipe(
        map((r) => {
          const data = r.data ?? (r as unknown as LessonPresenceSummary);
          return {
            lesson: Number(data.lesson ?? lessonId),
            date: String(data.date ?? date),
            students: Array.isArray(data.students) ? data.students.map(Number) : studentIds,
            roster: Array.isArray(data.roster) ? data.roster : [],
          };
        }),
      );
  }
}
