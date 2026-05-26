export interface ParentStudentRelation {
  id: number;
  parentId: number;
  parentFirstname: string;
  parentName: string;
  parentEmail: string;
  studentId: number;
  studentFirstname: string;
  studentName: string;
  isPrimary: number;
}
