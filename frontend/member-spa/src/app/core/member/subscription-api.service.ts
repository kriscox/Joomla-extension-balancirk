import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { AuthService } from '../auth/auth.service';
import { OpenLesson, SubscriptionSummary } from './models/subscription.model';
import { JsonApiResponse, JoomlaResponse, extractList } from './json-api.utils';

interface OpenLessonsPayload {
  lessons?: OpenLesson[];
  message?: string;
  hasOpenLessons?: boolean;
}

@Injectable({ providedIn: 'root' })
export class SubscriptionApiService {
  private readonly base: string;

  constructor(private readonly http: HttpClient, auth: AuthService) {
    this.base = auth.apiBase;
  }

  getMySubscriptions(): Observable<SubscriptionSummary[]> {
    return this.http
      .get<JsonApiResponse<SubscriptionSummary>>(`${this.base}/subscriptions`)
      .pipe(
        map(r =>
          extractList(r).map(s => ({
            ...s,
            id: Number(s.id ?? 0),
            lesson: s.lesson ?? '',
            year: s.year ?? '',
            subscribed: Number(s.subscribed ?? 0),
          })),
        ),
      );
  }

  getOpenLessonsForStudent(studentId: number): Observable<OpenLessonsPayload> {
    return this.http
      .get<JoomlaResponse<OpenLessonsPayload>>(
        `${this.base}/subscriptions/open-lessons/${studentId}`,
      )
      .pipe(
        map(r => {
          const payload = r.data ?? (r as unknown as OpenLessonsPayload);
          return {
            lessons: Array.isArray(payload.lessons) ? payload.lessons : [],
            message: payload.message ?? '',
            hasOpenLessons: payload.hasOpenLessons ?? false,
          };
        }),
      );
  }

  createSubscription(studentId: number, lessonId: number): Observable<SubscriptionSummary> {
    return this.http
      .post<JsonApiResponse<SubscriptionSummary>>(
        `${this.base}/subscriptions`,
        { data: { type: 'subscriptions', attributes: { student: studentId, lesson: lessonId } } },
      )
      .pipe(
        map(r => {
          const raw = r.data;
          if (raw && !Array.isArray(raw)) {
            const attrs = (raw.attributes ?? {}) as Record<string, unknown>;
            return { id: Number(raw.id ?? 0), ...attrs } as SubscriptionSummary;
          }
          return { id: 0, lesson: '', year: '', subscribed: 0 };
        }),
      );
  }

  deleteSubscription(id: number): Observable<void> {
    return this.http.delete<void>(`${this.base}/subscription/${id}`);
  }
}
