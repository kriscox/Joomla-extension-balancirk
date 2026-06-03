import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { AuthService } from '../auth/auth.service';
import { LessonPresenceSummary, TeacherEntry } from '../models/presence.model';
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
    return this.http
      .get<JoomlaResponse<LessonPresenceSummary>>(url)
      .pipe(map(r => r.data ?? (r as unknown as LessonPresenceSummary)));
  }

  setPresence(lessonId: number, entries: { student: number; date: string; present: boolean }[]): Observable<void> {
    return this.http
      .post<void>(`${this.base}/presence/${lessonId}`, { entries });
  }

  getTeachers(lessonId: number, date?: string): Observable<TeacherEntry[]> {
    const url = date
      ? `${this.base}/teacher/${lessonId}/${date}`
      : `${this.base}/teacher/${lessonId}`;
    return this.http
      .get<JoomlaResponse<TeacherEntry[]>>(url)
      .pipe(map(r => (Array.isArray(r.data) ? r.data : [])));
  }

  setTeacher(lessonId: number, entries: TeacherEntry[]): Observable<void> {
    return this.http
      .post<void>(`${this.base}/teacher/${lessonId}`, { entries });
  }
}
