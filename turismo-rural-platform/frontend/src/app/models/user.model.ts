export type UserRole = 'turista' | 'prestador' | 'admin';

export interface User {
  id: number;
  username: string;
  first_name?: string;
  last_name?: string;
  email: string;
  role: UserRole;
  business_name?: string;
  municipio?: string;
  descripcion?: string;
  telefono_contacto?: string;
}

export interface AuthLoginResponse {
  access: string;
  refresh: string;
  user: User;
}
