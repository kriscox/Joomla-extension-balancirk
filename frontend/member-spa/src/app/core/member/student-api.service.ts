import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { AuthService } from '../auth/auth.service';
import { Student, StudentSummary, StudentWrite } from './models/student.model';
import {
  JsonApiResponse,
  JoomlaResponse,
  extractItem,
  extractList,
  isJoomlaResponse,
} from './json-api.utils';

@Injectable({ providedIn: 'root' })
export class StudentApiService {
  private readonly base: string;

  constructor(private readonly http: HttpClient, auth: AuthService) {
    this.base = auth.apiBase;
  }

  /**
   * Returns the students linked to the current user.
   * The endpoint responds with a plain Joomla JsonResponse, not JSON:API.
   */
  getMyStudents(): Observable<StudentSummary[]> {
    return this.http
      .get<JoomlaResponse<StudentSummary[]> | JsonApiResponse<StudentSummary>>(
        `${this.base}/members/me/students`,
      )
      .pipe(
        map(response => {
          if (isJoomlaResponse<StudentSummary[]>(response) && Array.isArray(response.data)) {
            return response.data.map(s => this.normalizeStudentSummary(s));
          }
          return extractList(response as JsonApiResponse<StudentSummary>).map(s =>
            this.normalizeStudentSummary(s),
          );
        }),
      );
  }

  getStudent(id: number): Observable<Student> {
    return this.http
      .get<JsonApiResponse<Student>>(`${this.base}/students/${id}`)
      .pipe(map(r => extractItem(r.data as import('./json-api.utils').JsonApiItem<Student>)));
  }

  createStudent(data: StudentWrite): Observable<Student> {
    return this.http
      .post<JsonApiResponse<Student>>(
        `${this.base}/students`,
        { data: { type: 'students', attributes: data } },
      )
      .pipe(map(r => extractItem(r.data as import('./json-api.utils').JsonApiItem<Student>)));
  }

  updateStudent(id: number, data: Partial<StudentWrite>): Observable<Student> {
    return this.http
      .patch<JsonApiResponse<Student>>(
        `${this.base}/students/${id}`,
        { data: { type: 'students', id: String(id), attributes: data } },
      )
      .pipe(map(r => extractItem(r.data as import('./json-api.utils').JsonApiItem<Student>)));
  }

  private normalizeStudentSummary(s: StudentSummary): StudentSummary {
    return {
      ...s,
      id: Number(s.id ?? 0),
      firstname: s.firstname ?? '',
      name: s.name ?? '',
      birthdate: s.birthdate ?? '',
      mutuality: s.mutuality ?? '',
      uitpas: s.uitpas ?? '',
    };
  }
}
