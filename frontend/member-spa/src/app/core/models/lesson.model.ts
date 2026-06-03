export interface LessonSummary {
  id: number;
  name: string;
  year: string;
  startdate: string;
  enddate: string;
  max_students: number;
  enrolled?: number;
  waiting?: number;
  lesdays?: number;
  type?: string;
}

export interface LessonDetail extends LessonSummary {
  fee?: number;
  min_age?: number;
  max_age?: number;
  description?: string;
}
