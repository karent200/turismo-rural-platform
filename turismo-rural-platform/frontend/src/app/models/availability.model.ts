export interface AvailabilitySlot {
  id: number;
  service_id: number;
  service_name?: string;
  date: string;
  slots_available: number;
}
