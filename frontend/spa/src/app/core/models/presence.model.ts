export interface PresenceRosterStudent {
  id: number;
  firstname: string;
  name: string;
  present: boolean;
}

export interface LessonPresenceSummary {
  lesson: number;
  date: string;
  students: number[];
  roster: PresenceRosterStudent[];
}

export interface TeacherEntry {
  member: number;
  date: string;
}
