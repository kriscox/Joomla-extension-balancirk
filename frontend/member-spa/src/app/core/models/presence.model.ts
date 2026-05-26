export interface LessonPresenceEntry {
  student: number;
  date: string;
}

export interface LessonPresenceSummary {
  lessonId: number;
  date: string;
  entries: LessonPresenceEntry[];
}
