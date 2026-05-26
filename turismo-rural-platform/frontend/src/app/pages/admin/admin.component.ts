import { SlicePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { ApiService } from '../../core/services/api.service';
import { AuthService } from '../../core/services/auth.service';
import { Review } from '../../models/review.model';
import { Reservation } from '../../models/reservation.model';
import { User } from '../../models/user.model';

interface AdminStats {
  users: number;
  services: number;
  reservations: number;
  pending: number;
}

@Component({
  selector: 'app-admin',
  standalone: true,
  imports: [SlicePipe],
  templateUrl: './admin.component.html',
})
export class AdminComponent implements OnInit {
  private readonly api = inject(ApiService);
  readonly auth = inject(AuthService);

  stats = signal<AdminStats | null>(null);
  users = signal<User[]>([]);
  reservations = signal<Reservation[]>([]);
  reviews = signal<Review[]>([]);
  tab = signal<'stats' | 'users' | 'reservations' | 'reviews'>('stats');

  ngOnInit(): void {
    this.loadStats();
    this.loadUsers();
    this.loadReservations();
  }

  setTab(t: 'stats' | 'users' | 'reservations' | 'reviews'): void {
    this.tab.set(t);
    if (t === 'reviews') this.loadReviews();
  }

  loadStats(): void {
    this.api.get<AdminStats>('admin/stats/').subscribe({
      next: (s) => this.stats.set(s),
    });
  }

  loadUsers(): void {
    this.api.get<User[]>('admin/users/').subscribe({
      next: (u) => this.users.set(Array.isArray(u) ? u : []),
    });
  }

  loadReservations(): void {
    this.api.get<Reservation[]>('admin/reservations/').subscribe({
      next: (r) => this.reservations.set(Array.isArray(r) ? r : []),
    });
  }

  loadReviews(): void {
    this.api.get<Review[]>('reviews/all/').subscribe({
      next: (r) => this.reviews.set(Array.isArray(r) ? r : []),
      error: () => this.reviews.set([]),
    });
  }

  deleteReview(id: number): void {
    if (!confirm('Eliminar esta reseña?')) return;
    this.api.delete(`reviews/${id}/`).subscribe({
      next: () => this.loadReviews(),
    });
  }

  starsArray(rating: number): number[] {
    return [1, 2, 3, 4, 5].map(i => (i <= Math.round(rating) ? 1 : 0));
  }

  logout(): void {
    this.auth.logout().subscribe(() => {
      window.location.href = '/app/login';
    });
  }
}
