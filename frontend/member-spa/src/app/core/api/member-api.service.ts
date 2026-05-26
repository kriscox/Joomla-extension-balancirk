import { HttpClient, HttpHeaders, HttpResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { MemberProfile, MemberProfileUpdate } from '../models/member.model';
import { ParentStudentRelation } from '../models/relation.model';
import { SubscriptionSummary } from '../models/subscription.model';
import { StudentSummary } from '../models/student.model';

interface JsonApiResponse<T> {
  data?: {
    id?: string;
    attributes?: T;
  } | Array<{
    id?: string;
    attributes?: T;
  }>;
}

interface JoomlaJsonResponse<T> {
  success?: boolean;
  data?: T;
  message?: string;
}

@Injectable({ providedIn: 'root' })
export class MemberApiService {
  private readonly apiBase = this.resolveApiBase();

  constructor(private readonly http: HttpClient) {}

  getCurrentMember(): Observable<MemberProfile> {
    return this.http
      .get<JsonApiResponse<MemberProfile>>(`${this.apiBase}/members/me`, this.requestOptions())
      .pipe(map((response) => this.fromItem(this.getSingleItem(response))));
  }

  updateCurrentMember(payload: MemberProfileUpdate): Observable<MemberProfile> {
    return this.http
      .put<JsonApiResponse<MemberProfile>>(
        `${this.apiBase}/members/me`,
        {
          data: {
            type: 'members',
            attributes: payload
          }
        },
        this.requestOptions()
      )
      .pipe(map((response) => this.fromItem(this.getSingleItem(response))));
  }

  getMyStudents(): Observable<StudentSummary[]> {
    return this.http
      .get<JoomlaJsonResponse<StudentSummary[]> | JsonApiResponse<StudentSummary>>(
        `${this.apiBase}/members/me/students`,
        this.requestOptions()
      )
      .pipe(
        map((response) => {
          if (this.isJoomlaResponse(response)) {
            return Array.isArray(response.data)
              ? response.data.map((student) => this.normalizeStudent(student))
              : [];
          }

          const items = Array.isArray(response.data) ? response.data : [];

          return items.map((item) => this.fromItem<StudentSummary>(item));
        })
      );
  }

  getMySubscriptions(): Observable<SubscriptionSummary[]> {
    return this.http
      .get<JsonApiResponse<SubscriptionSummary>>(`${this.apiBase}/subscriptions`, this.requestOptions())
      .pipe(
        map((response) => {
          const items = Array.isArray(response.data) ? response.data : [];

          return items.map((item) => this.normalizeSubscription(this.fromItem<SubscriptionSummary>(item)));
        })
      );
  }

  getParentStudentRelations(): Observable<ParentStudentRelation[]> {
    return this.http
      .get<JoomlaJsonResponse<ParentStudentRelation[]>>(`${this.apiBase}/members/relations`, this.requestOptions())
      .pipe(
        map((response) =>
          Array.isArray(response.data) ? response.data.map((row) => this.normalizeRelation(row)) : []
        )
      );
  }

  downloadAccountingExport(format: 'csv' | 'xls', year?: string): Observable<{ blob: Blob; filename: string }> {
    const params: Record<string, string> = { format };
    const normalizedYear = year?.trim() ?? '';

    if (normalizedYear !== '') {
      params['year'] = normalizedYear;
    }

    return this.http
      .get(`${this.apiBase}/subscriptions/accounting-export`, {
        ...this.requestOptions('text/csv, application/vnd.ms-excel, application/json'),
        observe: 'response',
        responseType: 'blob',
        params
      })
      .pipe(
        map((response: HttpResponse<Blob>) => {
          const disposition = response.headers.get('content-disposition') ?? '';

          return {
            blob: response.body ?? new Blob(),
            filename: this.extractFilename(disposition, `subscriptions.${format}`)
          };
        })
      );
  }

  private requestOptions(accept = 'application/json, application/vnd.api+json') {
    const token = localStorage.getItem('balancirk_api_token') ?? this.readJoomlaToken();
    let headers = new HttpHeaders({
      Accept: accept
    });

    if (token) {
      headers = headers.set('Authorization', `Bearer ${token}`);
    }

    return {
      withCredentials: true,
      headers
    };
  }

  private resolveApiBase(): string {
    const joomla = (globalThis as { Joomla?: { getOptions?: (name: string) => unknown } }).Joomla;
    const options = joomla?.getOptions?.('balancirk-member-spa') as { apiBase?: unknown } | undefined;
    const configured = typeof options?.apiBase === 'string' ? options.apiBase : '/api/index.php/v1';

    return configured.replace(/\/+$/, '');
  }

  private readJoomlaToken(): string | null {
    const joomla = (globalThis as { Joomla?: { getOptions?: (name: string) => unknown } }).Joomla;
    const options = joomla?.getOptions?.('balancirk-member-spa') as { token?: unknown } | undefined;

    return typeof options?.token === 'string' && options.token.trim() !== '' ? options.token.trim() : null;
  }

  private getSingleItem<T>(response: JsonApiResponse<T>) {
    if (Array.isArray(response.data)) {
      throw new Error('Expected single JSON:API item.');
    }

    if (!response.data) {
      throw new Error('Missing JSON:API payload.');
    }

    return response.data;
  }

  private fromItem<T>(item: { id?: string; attributes?: T }): T & { id: number } {
    const attributes = (item.attributes ?? {}) as Record<string, unknown>;

    return {
      id: Number(item.id ?? attributes['id'] ?? 0),
      ...(attributes as object)
    } as T & { id: number };
  }

  private isJoomlaResponse(
    value: JoomlaJsonResponse<StudentSummary[]> | JsonApiResponse<StudentSummary>
  ): value is JoomlaJsonResponse<StudentSummary[]> {
    return 'success' in value || ('data' in value && Array.isArray((value as JoomlaJsonResponse<StudentSummary[]>).data));
  }

  private normalizeStudent(student: StudentSummary): StudentSummary {
    return {
      ...student,
      id: Number(student.id ?? 0),
      firstname: student.firstname ?? '',
      name: student.name ?? '',
      birthdate: student.birthdate ?? '',
      mutuality: student.mutuality ?? '',
      uitpas: student.uitpas ?? ''
    };
  }

  private normalizeSubscription(subscription: SubscriptionSummary): SubscriptionSummary {
    return {
      ...subscription,
      id: Number(subscription.id ?? 0),
      lesson: subscription.lesson ?? '',
      year: subscription.year ?? '',
      subscribed: Number(subscription.subscribed ?? 0),
      start: subscription.start ?? '',
      end: subscription.end ?? ''
    };
  }

  private normalizeRelation(relation: ParentStudentRelation): ParentStudentRelation {
    const row = relation as unknown as Record<string, unknown>;

    return {
      id: Number(row['id'] ?? 0),
      parentId: Number(row['parentId'] ?? row['parent_id'] ?? 0),
      parentFirstname: String(row['parentFirstname'] ?? row['parent_firstname'] ?? ''),
      parentName: String(row['parentName'] ?? row['parent_name'] ?? ''),
      parentEmail: String(row['parentEmail'] ?? row['parent_email'] ?? ''),
      studentId: Number(row['studentId'] ?? row['student_id'] ?? 0),
      studentFirstname: String(row['studentFirstname'] ?? row['student_firstname'] ?? ''),
      studentName: String(row['studentName'] ?? row['student_name'] ?? ''),
      isPrimary: Number(row['isPrimary'] ?? row['is_primary'] ?? 0),
    };
  }

  private extractFilename(contentDisposition: string, fallback: string): string {
    const utf8Match = /filename\*=UTF-8''([^;]+)/i.exec(contentDisposition);

    if (utf8Match && utf8Match[1]) {
      return decodeURIComponent(utf8Match[1]);
    }

    const quotedMatch = /filename=\"?([^\";]+)\"?/i.exec(contentDisposition);

    if (quotedMatch && quotedMatch[1]) {
      return quotedMatch[1];
    }

    return fallback;
  }
}
