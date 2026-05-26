export interface Review {
  id: number;
  service: number;
  service_name?: string;
  tourist: number;
  tourist_name?: string;
  reservation: number;
  rating: number;
  comment?: string;
  created_at?: string;
}
