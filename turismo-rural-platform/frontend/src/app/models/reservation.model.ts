export interface Reservation {
  id: number;
  tourist?: number;
  tourist_name?: string;
  tourist_email?: string;
  service?: number;
  service_name?: string;
  service_type?: string;
  service_price?: number;
  reservation_date: string;
  personas?: number;
  telefono?: string;
  status: string;
  created_at?: string;
  has_review?: boolean;
}
