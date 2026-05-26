import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { User } from '../../models/user.model';
import { AuthService } from '../services/auth.service';

export const roleGuard: (role: string) => CanActivateFn = (expectedRole: string) => {
  return () => {
    const auth = inject(AuthService);
    const router = inject(Router);

    const check = (user: User | null): boolean => {
      if (user && user.role === expectedRole) {
        return true;
      }
      if (user) {
        auth.redirectByRole(user.role);
      } else {
        void router.navigateByUrl('/login');
      }
      return false;
    };

    const user = auth.user();
    if (user) {
      return check(user);
    }

    auth.checkSession().subscribe({
      next: (u) => {
        if (!check(u)) {
          void router.navigateByUrl('/login');
        }
      },
      error: () => void router.navigateByUrl('/login'),
    });
    return false;
  };
};
