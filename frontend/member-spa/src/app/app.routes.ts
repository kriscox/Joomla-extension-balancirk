import { Routes } from '@angular/router';
import { authGuard } from './core/auth/auth.guard';
import { requireRole } from './core/auth/role.guard';

export const routes: Routes = [
  {
    path: '',
    pathMatch: 'full',
    redirectTo: 'member',
  },

  // ── Member section — all authenticated users ──────────────────────────────
  {
    path: 'member',
    canActivate: [authGuard],
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'dashboard' },
      {
        path: 'dashboard',
        loadComponent: () =>
          import('./features/member/dashboard/dashboard.component').then(m => m.MemberDashboardComponent),
      },
      {
        path: 'profile',
        loadComponent: () =>
          import('./features/member/profile/profile-form.component').then(m => m.ProfileFormComponent),
      },
      {
        path: 'students',
        loadComponent: () =>
          import('./features/member/students/students-list.component').then(m => m.StudentsListComponent),
      },
      {
        path: 'students/new',
        loadComponent: () =>
          import('./features/member/students/student-form.component').then(m => m.StudentFormComponent),
      },
      {
        path: 'students/:id/edit',
        loadComponent: () =>
          import('./features/member/students/student-form.component').then(m => m.StudentFormComponent),
      },
      {
        path: 'students/:id',
        loadComponent: () =>
          import('./features/member/students/student-detail.component').then(m => m.StudentDetailComponent),
      },
      {
        path: 'subscriptions',
        loadComponent: () =>
          import('./features/member/subscriptions/subscriptions-list.component').then(m => m.SubscriptionsListComponent),
      },
      {
        path: 'subscriptions/new',
        loadComponent: () =>
          import('./features/member/subscriptions/subscription-form.component').then(m => m.SubscriptionFormComponent),
      },
    ],
  },

  // ── Teacher section — teachers, accountants and admins ───────────────────
  {
    path: 'teacher',
    canActivate: [authGuard, requireRole('teacher')],
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'home' },
      {
        path: 'home',
        loadComponent: () =>
          import('./features/teacher/teacher-home/teacher-home.component').then(m => m.TeacherHomeComponent),
      },
      {
        path: 'lessons',
        loadComponent: () =>
          import('./features/teacher/lessons/teacher-lessons.component').then(m => m.TeacherLessonsComponent),
      },
      {
        path: 'attendance',
        loadComponent: () =>
          import('./features/teacher/attendance/attendance.component').then(m => m.AttendanceComponent),
      },
    ],
  },

  // ── Accounting section — accountants and admins ──────────────────────────
  {
    path: 'accounting',
    canActivate: [authGuard, requireRole('accountant')],
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'home' },
      {
        path: 'home',
        loadComponent: () =>
          import('./features/accounting/accounting-home/accounting-home.component').then(m => m.AccountingHomeComponent),
      },
      {
        path: 'export',
        loadComponent: () =>
          import('./features/accounting/export/accounting-export.component').then(m => m.AccountingExportComponent),
      },
    ],
  },

  // ── Admin section — administrators only ──────────────────────────────────
  {
    path: 'admin',
    canActivate: [authGuard, requireRole('admin')],
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'home' },
      {
        path: 'home',
        loadComponent: () =>
          import('./features/admin/admin-home/admin-home.component').then(m => m.AdminHomeComponent),
      },
      {
        path: 'members',
        loadComponent: () =>
          import('./features/admin/members/admin-members.component').then(m => m.AdminMembersComponent),
      },
      {
        path: 'students',
        loadComponent: () =>
          import('./features/admin/students/admin-students.component').then(m => m.AdminStudentsComponent),
      },
      {
        path: 'lessons',
        loadComponent: () =>
          import('./features/admin/lessons/admin-lessons.component').then(m => m.AdminLessonsComponent),
      },
      {
        path: 'subscriptions',
        loadComponent: () =>
          import('./features/admin/subscriptions/admin-subscriptions.component').then(m => m.AdminSubscriptionsComponent),
      },
    ],
  },

  { path: '**', redirectTo: 'member' },
];
