import { Routes } from '@angular/router';
import { authGuard } from './core/auth/auth.guard';

export const routes: Routes = [
  {
    path: '',
    pathMatch: 'full',
    redirectTo: 'dashboard',
  },
  {
    path: 'dashboard',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./features/dashboard/dashboard.component').then(m => m.DashboardComponent),
  },
  {
    path: 'profile',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./features/profile/profile-form.component').then(m => m.ProfileFormComponent),
  },
  {
    path: 'students',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./features/students/students-list.component').then(m => m.StudentsListComponent),
  },
  {
    path: 'students/new',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./features/students/student-form.component').then(m => m.StudentFormComponent),
  },
  {
    path: 'students/:id/edit',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./features/students/student-form.component').then(m => m.StudentFormComponent),
  },
  {
    path: 'students/:id',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./features/students/student-detail.component').then(m => m.StudentDetailComponent),
  },
  {
    path: 'subscriptions',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./features/subscriptions/subscriptions-list.component').then(m => m.SubscriptionsListComponent),
  },
  {
    path: 'subscriptions/new',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./features/subscriptions/subscription-form.component').then(m => m.SubscriptionFormComponent),
  },
  {
    path: '**',
    redirectTo: 'dashboard',
  },
];
