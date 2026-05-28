/** Compact representation returned by GET /v1/members/me/students. */
export interface StudentSummary {
  id: number;
  firstname: string;
  name: string;
  birthdate?: string;
  mutuality?: string;
  uitpas?: string;
  /** 1 = current user is the primary parent of this student. */
  isPrimary?: number;
}

/** Full student record returned by GET /v1/students/:id. */
export interface Student extends StudentSummary {
  street?: string;
  number?: string;
  bus?: string;
  postcode?: string;
  city?: string;
  phone?: string;
  email?: string;
  allow_photo?: boolean;
  state?: string;
}

/** Payload for POST /v1/students and PATCH /v1/students/:id. */
export interface StudentWrite {
  firstname: string;
  name: string;
  birthdate: string;
  street?: string;
  number?: string;
  bus?: string;
  postcode?: string;
  city?: string;
  phone?: string;
  email?: string;
  mutuality?: string;
  uitpas?: string;
  allow_photo?: boolean;
  state?: string;
}
