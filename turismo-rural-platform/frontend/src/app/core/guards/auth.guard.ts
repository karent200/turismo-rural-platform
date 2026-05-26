import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { map } from 'rxjs';
import { AuthService } from '../services/auth.service';

export const authGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (auth.user()) {
    return true;
  }

  return auth.checkSession().pipe(
    map((user) => {
      if (user) {
        return true;
      }
      return router.createUrlTree(['/login']);
    }),
  );
};
