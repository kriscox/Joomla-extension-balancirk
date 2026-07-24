export interface LessonSummary {
  id: number;
  name: string;
  year?: string | number;
  start?: string;
  end?: string;
  startdate?: string;
  enddate?: string;
  max_students?: number;
  enrolled?: number;
  waiting?: number;
  numberOfStudents?: number;
  numberOnWaitingList?: number;
  lesdays?: number;
  type?: string;
  fee?: number;
  min_age?: number;
  max_age?: number;
  state?: number;
}

export interface LessonDetail extends LessonSummary {
  description?: string;
  start_registration?: string;
  end_registration?: string;
}
