export interface Service {
  id: number;
  provider?: number;
  provider_name?: string;
  name: string;
  type: string;
  description?: string;
  capacity?: number;
  price?: number;
  location?: string;
  created_at?: string;
  avg_rating?: number | null;
  total_reviews?: number;
}
