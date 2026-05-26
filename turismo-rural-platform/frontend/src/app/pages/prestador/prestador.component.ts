import { CurrencyPipe, SlicePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/services/api.service';
import { AuthService } from '../../core/services/auth.service';
import { User } from '../../models/user.model';
import { Reservation } from '../../models/reservation.model';
import { Review } from '../../models/review.model';
import { AvailabilitySlot } from '../../models/availability.model';
import { Service } from '../../models/service.model';

interface ServiceStats {
  id: number;
  name: string;
  avg_rating: number | null;
  total_reviews: number;
}

@Component({
  selector: 'app-prestador',
  standalone: true,
  imports: [FormsModule, CurrencyPipe, SlicePipe],
  templateUrl: './prestador.component.html',
})
export class PrestadorComponent implements OnInit {
  private readonly api = inject(ApiService);
  readonly auth = inject(AuthService);

  tab = signal<'perfil' | 'servicios' | 'reservas' | 'disponibilidad' | 'resenas'>('perfil');
  profile = signal<User | null>(null);
  services = signal<Service[]>([]);
  pending = signal<Reservation[]>([]);
  availability = signal<AvailabilitySlot[]>([]);
  reviews = signal<Review[]>([]);
  message = signal('');

  availServiceId = 0;
  availDate = '';
  availSlots = 5;
  minDate = new Date().toISOString().split('T')[0];

  businessName = '';
  municipio = '';
  descripcion = '';
  telefono = '';

  serviceForm = {
    id: 0,
    name: '',
    type: 'alojamiento',
    description: '',
    capacity: 1,
    price: 0,
    location: '',
  };

  ngOnInit(): void {
    this.loadProfile();
    this.loadServices();
    this.loadPending();
  }

  setTab(t: 'perfil' | 'servicios' | 'reservas' | 'disponibilidad' | 'resenas'): void {
    this.tab.set(t);
    if (t === 'disponibilidad') {
      this.syncAvailServiceSelect();
      this.loadAvailability();
    }
    if (t === 'resenas') {
      this.loadReviews();
    }
  }

  loadProfile(): void {
    this.api.get<User>('provider/profile/').subscribe({
      next: (p) => {
        this.profile.set(p ?? null);
        this.businessName = p?.business_name ?? '';
        this.municipio = p?.municipio ?? '';
        this.descripcion = p?.descripcion ?? '';
        this.telefono = p?.telefono_contacto ?? '';
      },
    });
  }

  saveProfile(): void {
    this.api
      .put('provider/profile/', {
        business_name: this.businessName,
        municipio: this.municipio,
        descripcion: this.descripcion,
        telefono_contacto: this.telefono,
      })
      .subscribe({
        next: () => {
          this.message.set('Perfil guardado');
          this.loadProfile();
        },
        error: () => this.message.set('Error al guardar'),
      });
  }

  loadServices(): void {
    this.api.get<Service[]>('services/my/').subscribe({
      next: (list) => {
        this.services.set(Array.isArray(list) ? list : []);
        this.syncAvailServiceSelect();
      },
    });
  }

  loadAvailability(): void {
    this.api.get<AvailabilitySlot[]>('availability/').subscribe({
      next: (rows) => this.availability.set(Array.isArray(rows) ? rows : []),
      error: () => this.availability.set([]),
    });
  }

  saveAvailability(): void {
    if (!this.services().length) {
      this.message.set('Crea un servicio primero');
      return;
    }
    if (!this.availServiceId || !this.availDate) {
      this.message.set('Selecciona servicio y fecha');
      return;
    }
    this.api
      .post<{ success?: boolean; error?: string }>('availability/', {
        service: this.availServiceId,
        date: this.availDate,
        slots_available: this.availSlots,
      })
      .subscribe({
        next: () => {
          this.availDate = '';
          this.message.set('Disponibilidad guardada');
          this.loadAvailability();
        },
        error: () => this.message.set('Error al guardar disponibilidad'),
      });
  }

  deleteAvailability(id: number): void {
    if (!confirm('¿Eliminar esta fecha de disponibilidad?')) return;
    this.api.delete(`availability/${id}/`).subscribe({
      next: () => this.loadAvailability(),
    });
  }

  private syncAvailServiceSelect(): void {
    const list = this.services();
    if (list.length && !list.some((s) => s.id === this.availServiceId)) {
      this.availServiceId = list[0].id;
    }
  }

  loadPending(): void {
    this.api.get<Reservation[]>('reservations/', { status: 'pendiente' }).subscribe({
      next: (list) => this.pending.set(Array.isArray(list) ? list : []),
    });
  }

  loadReviews(): void {
    this.api.get<Review[]>('reviews/provider/').subscribe({
      next: (list) => this.reviews.set(Array.isArray(list) ? list : []),
      error: () => this.reviews.set([]),
    });
  }

  getServiceStats(): ServiceStats[] {
    const svcs = this.services();
    const revs = this.reviews();
    return svcs.map((s) => {
      const sRev = revs.filter((r) => r.service === s.id);
      const avg = sRev.length ? sRev.reduce((a, r) => a + r.rating, 0) / sRev.length : 0;
      return { id: s.id, name: s.name, avg_rating: avg ? Math.round(avg * 10) / 10 : null, total_reviews: sRev.length };
    });
  }

  starsArray(rating: number | null | undefined): number[] {
    const r = rating ?? 0;
    return [1, 2, 3, 4, 5].map(i => (i <= Math.round(r) ? 1 : 0));
  }

  saveService(): void {
    const f = this.serviceForm;
    const body: Record<string, unknown> = {
      name: f.name,
      type: f.type,
      description: f.description,
      capacity: f.capacity,
      price: f.price,
      location: f.location,
    };

    if (f.id) {
      this.api.put(`services/${f.id}/`, body).subscribe({
        next: () => {
          this.resetServiceForm();
          this.loadServices();
          this.message.set('Servicio actualizado');
        },
        error: () => this.message.set('Error al actualizar servicio'),
      });
    } else {
      this.api.post('services/', body).subscribe({
        next: () => {
          this.resetServiceForm();
          this.loadServices();
          this.message.set('Servicio creado');
        },
        error: () => this.message.set('Error al crear servicio'),
      });
    }
  }

  editService(s: Service): void {
    this.serviceForm = {
      id: s.id,
      name: s.name,
      type: s.type,
      description: s.description ?? '',
      capacity: s.capacity ?? 1,
      price: s.price ?? 0,
      location: s.location ?? '',
    };
  }

  deleteService(id: number): void {
    if (!confirm('¿Eliminar servicio?')) return;
    this.api.delete(`services/${id}/`).subscribe({
      next: () => this.loadServices(),
    });
  }

  updateStatus(id: number, status: string): void {
    this.api.patch(`reservations/${id}/status/`, { status }).subscribe({
      next: () => this.loadPending(),
    });
  }

  logout(): void {
    this.auth.logout().subscribe(() => {
      window.location.href = '/app/login';
    });
  }

  private resetServiceForm(): void {
    this.serviceForm = {
      id: 0,
      name: '',
      type: 'alojamiento',
      description: '',
      capacity: 1,
      price: 0,
      location: '',
    };
  }
}
