import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';
import { roleGuard } from './core/guards/role.guard';

export const routes: Routes = [
  { path: '', redirectTo: 'login', pathMatch: 'full' },
  {
    path: 'login',
    loadComponent: () =>
      import('./pages/login/login.component').then((m) => m.LoginComponent),
  },
  {
    path: 'turista',
    canActivate: [authGuard, roleGuard('turista')],
    loadComponent: () =>
      import('./pages/turista/turista-hub.component').then((m) => m.TuristaHubComponent),
  },
  {
    path: 'prestador',
    canActivate: [authGuard, roleGuard('prestador')],
    loadComponent: () =>
      import('./pages/prestador/prestador.component').then((m) => m.PrestadorComponent),
  },
  {
    path: 'admin',
    canActivate: [authGuard, roleGuard('admin')],
    loadComponent: () =>
      import('./pages/admin/admin.component').then((m) => m.AdminComponent),
  },
  { path: '**', redirectTo: 'login' },
];
