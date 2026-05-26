import { Injectable, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { Observable, tap } from 'rxjs';
import { User, UserRole } from '../../models/user.model';
import { ApiService } from './api.service';

interface LoginResponse {
  access: string;
  refresh: string;
  user: User;
}

interface RegisterResponse {
  id?: number;
  email?: string;
  role?: string;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly api = inject(ApiService);
  private readonly router = inject(Router);

  readonly user = signal<User | null>(this.loadStoredUser());

  checkSession(): Observable<User> {
    return this.api.get<User>('auth/me/').pipe(
      tap({
        next: (u) => this.setUser(u),
        error: () => this.clearUser(),
      }),
    );
  }

  login(email: string, password: string): Observable<LoginResponse> {
    return this.api.post<LoginResponse>('auth/login/', { email, password }).pipe(
      tap((res) => {
        localStorage.setItem('access_token', res.access);
        localStorage.setItem('refresh_token', res.refresh);
        this.setUser(res.user);
      }),
    );
  }

  register(
    name: string,
    email: string,
    password: string,
    role: 'turista' | 'prestador',
  ): Observable<RegisterResponse> {
    return this.api.post<RegisterResponse>('auth/register/', {
      username: name,
      email,
      password,
      role,
    });
  }

  logout(): Observable<void> {
    this.clearUser();
    return new Observable((sub) => {
      localStorage.removeItem('access_token');
      localStorage.removeItem('refresh_token');
      sub.next();
      sub.complete();
    });
  }

  redirectByRole(role: UserRole): void {
    const routes: Record<UserRole, string> = {
      turista: '/turista',
      prestador: '/prestador',
      admin: '/admin',
    };
    void this.router.navigateByUrl(routes[role] ?? '/login');
  }

  private setUser(user: User): void {
    this.user.set(user);
    localStorage.setItem('turismo_user', JSON.stringify(user));
  }

  private clearUser(): void {
    this.user.set(null);
    localStorage.removeItem('turismo_user');
  }

  private loadStoredUser(): User | null {
    try {
      const raw = localStorage.getItem('turismo_user');
      return raw ? (JSON.parse(raw) as User) : null;
    } catch {
      return null;
    }
  }
}
