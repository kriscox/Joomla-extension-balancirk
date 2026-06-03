import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService, UserRole, ROLE_LEVEL } from './auth.service';

/**
 * Route guard factory that requires the logged-in user to have at least
 * the given role level before accessing the route.
 *
 * Role hierarchy (ascending): member < teacher < accountant < admin
 *
 * Usage in routes:
 *   canActivate: [authGuard, requireRole('teacher')]
 */
export function requireRole(minRole: UserRole): CanActivateFn {
  return () => {
    const auth = inject(AuthService);
    const router = inject(Router);

    if (ROLE_LEVEL[auth.highestRole] >= ROLE_LEVEL[minRole]) {
      return true;
    }

    return router.createUrlTree(['/member']);
  };
}
