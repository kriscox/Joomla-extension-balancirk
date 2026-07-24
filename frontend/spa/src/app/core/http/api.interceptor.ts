import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { catchError, throwError } from 'rxjs';
import { AuthService } from '../auth/auth.service';

export const apiInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(AuthService);
  let headers = req.headers.set('Accept', 'application/json, application/vnd.api+json');

  if (auth.token) {
    headers = headers.set('Authorization', `Bearer ${auth.token}`);
  }

  const authed = req.clone({ headers, withCredentials: true });

  return next(authed).pipe(
    catchError(err => {
      if (err?.status === 401) {
        auth.handleUnauthorized();
      }
      return throwError(() => err);
    }),
  );
};
