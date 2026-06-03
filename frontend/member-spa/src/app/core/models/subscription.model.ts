export interface SubscriptionSummary {
  id: number;
  studentid?: number;
  name?: string;
  firstname?: string;
  lessonid?: number;
  lesson: string;
  year: string | number;
  /** 0 = enrolled, 1 = waiting list. */
  subscribed: number;
  start?: string;
  end?: string;
}

/** One lesson option returned by GET /v1/subscriptions/open-lessons/:studentId. */
export interface OpenLesson {
  value: number;
  text: string;
}
