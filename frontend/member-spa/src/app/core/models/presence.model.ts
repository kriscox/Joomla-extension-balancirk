export interface LessonPresenceEntry {
  student: number;
  date: string;
  present?: boolean;
}

export interface LessonPresenceSummary {
  lessonId: number;
  date: string;
  entries: LessonPresenceEntry[];
}

export interface TeacherEntry {
  member: number;
  date: string;
}
