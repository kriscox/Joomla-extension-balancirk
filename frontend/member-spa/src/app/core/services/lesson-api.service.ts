import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { AuthService } from '../auth/auth.service';
import { LessonDetail, LessonSummary } from '../models/lesson.model';
import { JsonApiItem, JsonApiResponse, extractItem, extractList } from './json-api.utils';

@Injectable({ providedIn: 'root' })
export class LessonApiService {
  private readonly base: string;

  constructor(private readonly http: HttpClient, auth: AuthService) {
    this.base = auth.apiBase;
  }

  getLessons(): Observable<LessonSummary[]> {
    return this.http
      .get<JsonApiResponse<LessonSummary>>(`${this.base}/lessons`)
      .pipe(map(r => extractList(r)));
  }

  getLesson(id: number): Observable<LessonDetail> {
    return this.http
      .get<JsonApiResponse<LessonDetail>>(`${this.base}/lessons/${id}`)
      .pipe(map(r => extractItem(r.data as JsonApiItem<LessonDetail>)));
  }

  createLesson(data: Partial<LessonDetail>): Observable<LessonDetail> {
    return this.http
      .post<JsonApiResponse<LessonDetail>>(
        `${this.base}/lessons`,
        { data: { type: 'lessons', attributes: data } },
      )
      .pipe(map(r => extractItem(r.data as JsonApiItem<LessonDetail>)));
  }

  updateLesson(id: number, data: Partial<LessonDetail>): Observable<LessonDetail> {
    return this.http
      .patch<JsonApiResponse<LessonDetail>>(
        `${this.base}/lessons/${id}`,
        { data: { type: 'lessons', id: String(id), attributes: data } },
      )
      .pipe(map(r => extractItem(r.data as JsonApiItem<LessonDetail>)));
  }

  deleteLesson(id: number): Observable<void> {
    return this.http.delete<void>(`${this.base}/lessons/${id}`);
  }
}
