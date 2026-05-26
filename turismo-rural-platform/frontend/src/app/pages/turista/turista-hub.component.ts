import { CurrencyPipe, SlicePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/services/api.service';
import { AuthService } from '../../core/services/auth.service';
import { Review } from '../../models/review.model';
import { Reservation } from '../../models/reservation.model';
import { Service } from '../../models/service.model';

type WindowId = 'services' | 'map' | 'reservations' | 'reservar' | 'confirmacion' | 'perfil' | 'calificar' | null;

@Component({
  selector: 'app-turista-hub',
  standalone: true,
  imports: [FormsModule, CurrencyPipe, SlicePipe],
  templateUrl: './turista-hub.component.html',
})
export class TuristaHubComponent implements OnInit {
  private readonly api = inject(ApiService);
  readonly auth = inject(AuthService);

  activeWindow = signal<WindowId>(null);
  services = signal<Service[]>([]);
  reservations = signal<Reservation[]>([]);
  loadingServices = signal(false);
  loadingReservations = signal(false);

  typeFilter = '';
  locationFilter = '';
  locations = signal<{location: string}[]>([]);
  selectedProvider: Service | null = null;

  selectedService: Service | null = null;
  reservaFecha = '';
  reservaPersonas = 1;
  reservaTelefono = '';
  reservaError = '';
  confirmText = '';

  minDate = new Date().toISOString().split('T')[0];

  ratingServiceId = 0;
  ratingReservationId = 0;
  ratingServiceName = '';
  ratingValue = 0;
  ratingComment = '';
  ratingError = '';
  ratingHover = 0;

  ngOnInit(): void {
    this.loadServices();
    this.loadLocations();
  }

  onLocationInput(): void {
    if (this.locationFilter.trim().length > 2) this.loadServices();
  }

  loadLocations(): void {
    this.api.get<{location: string}[]>('services/locations/').subscribe({
      next: (list) => this.locations.set(Array.isArray(list) ? list : []),
    });
  }

  verProveedor(s: Service): void {
    this.selectedProvider = s;
    this.openWindow('perfil');
    this.loadServices();
  }

  getProviderServiceNames(providerName: string | undefined): string {
    if (!providerName) return '';
    return this.services()
      .filter(x => x.provider_name === providerName)
      .map(x => x.name)
      .join(', ');
  }

  openWindow(id: WindowId): void {
    this.activeWindow.set(id);
    if (id === 'services') this.loadServices();
    if (id === 'reservations') this.loadReservations();
  }

  closeWindow(): void {
    this.activeWindow.set(null);
  }

  loadServices(): void {
    this.loadingServices.set(true);
    const params: Record<string, string> = {};
    if (this.typeFilter) params['type'] = this.typeFilter;
    if (this.locationFilter.trim()) params['location'] = this.locationFilter.trim();

    this.api.get<Service[]>('services/', params).subscribe({
      next: (list) => {
        this.services.set(Array.isArray(list) ? list : []);
        this.loadingServices.set(false);
      },
      error: () => {
        this.services.set([]);
        this.loadingServices.set(false);
      },
    });
  }

  loadReservations(): void {
    this.loadingReservations.set(true);
    this.api.get<Reservation[]>('reservations/').subscribe({
      next: (list) => {
        this.reservations.set(Array.isArray(list) ? list : []);
        this.loadingReservations.set(false);
      },
      error: () => {
        this.reservations.set([]);
        this.loadingReservations.set(false);
      },
    });
  }

  startReserva(s: Service): void {
    this.selectedService = s;
    this.reservaFecha = '';
    this.reservaPersonas = 1;
    this.reservaTelefono = '';
    this.reservaError = '';
    this.openWindow('reservar');
  }

  confirmarReserva(): void {
    const s = this.selectedService;
    if (!s) return;

    if (!this.reservaFecha) {
      this.reservaError = 'Selecciona una fecha';
      return;
    }
    if (this.reservaFecha < this.minDate) {
      this.reservaError = 'No puedes reservar en fechas pasadas';
      return;
    }
    const cap = s.capacity ?? 1;
    if (this.reservaPersonas < 1 || this.reservaPersonas > cap) {
      this.reservaError = `Máximo ${cap} personas`;
      return;
    }
    if (this.reservaTelefono.length < 7) {
      this.reservaError = 'Ingresa un teléfono válido';
      return;
    }

    this.api
      .post<Reservation>('reservations/', {
        service: s.id,
        reservation_date: this.reservaFecha,
        personas: this.reservaPersonas,
        telefono: this.reservaTelefono,
      })
      .subscribe({
        next: () => {
          this.confirmText = `${s.name} — ${this.reservaFecha} — ${this.reservaPersonas} pers.`;
          this.activeWindow.set('confirmacion');
        },
        error: () => (this.reservaError = 'Error al crear la reserva'),
      });
  }

  cancelarReserva(id: number): void {
    if (!confirm('¿Cancelar esta reserva?')) return;
    this.api.patch<Reservation>(`reservations/${id}/status/`, { status: 'cancelada' }).subscribe({
      next: () => this.loadReservations(),
    });
  }

  openRating(res: Reservation): void {
    this.ratingServiceId = res.service ?? 0;
    this.ratingReservationId = res.id;
    this.ratingServiceName = res.service_name ?? '';
    this.ratingValue = 0;
    this.ratingComment = '';
    this.ratingError = '';
    this.ratingHover = 0;
    this.openWindow('calificar');
  }

  submitRating(): void {
    if (this.ratingValue < 1 || this.ratingValue > 5) {
      this.ratingError = 'Selecciona una calificacion';
      return;
    }
    this.api
      .post<Review>('reviews/', {
        service: this.ratingServiceId,
        reservation: this.ratingReservationId,
        rating: this.ratingValue,
        comment: this.ratingComment,
      })
      .subscribe({
        next: () => {
          this.closeWindow();
          this.loadReservations();
          this.loadServices();
        },
        error: (err) => {
          this.ratingError = err.error?.detail || 'Error al enviar calificacion';
        },
      });
  }

  starsArray(rating: number | null | undefined): number[] {
    const r = rating ?? 0;
    return [1, 2, 3, 4, 5].map(i => (i <= Math.round(r) ? 1 : 0));
  }

  logout(): void {
    this.auth.logout().subscribe(() => {
      window.location.href = '/app/login';
    });
  }

  typeIcon(type: string): string {
    const map: Record<string, string> = {
      alojamiento: 'fa-campground',
      recreacion: 'fa-person-hiking',
      gastronomia: 'fa-utensils',
      actividades: 'fa-map-signs',
    };
    return map[type] ?? 'fa-star';
  }

  statusClass(status: string): string {
    if (status === 'confirmada') return 'status-confirmada';
    if (status === 'cancelada') return 'status-cancelada';
    return 'status-pendiente';
  }
}
