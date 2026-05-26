import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly http = inject(HttpClient);
  private readonly base = environment.apiUrl.replace(/\/$/, '');

  get<T>(path: string, params?: Record<string, string>): Observable<T> {
    const httpParams = new URLSearchParams();
    if (params) {
      for (const [key, value] of Object.entries(params)) {
        if (value !== '') httpParams.set(key, value);
      }
    }
    const query = httpParams.toString();
    const url = `${this.base}/${path}${query ? '?' + query : ''}`;
    return this.http.get<T>(url);
  }

  post<T>(path: string, body?: Record<string, unknown>): Observable<T> {
    return this.http.post<T>(`${this.base}/${path}`, body ?? {});
  }

  put<T>(path: string, body?: Record<string, unknown>): Observable<T> {
    return this.http.put<T>(`${this.base}/${path}`, body ?? {});
  }

  patch<T>(path: string, body?: Record<string, unknown>): Observable<T> {
    return this.http.patch<T>(`${this.base}/${path}`, body ?? {});
  }

  delete<T>(path: string): Observable<T> {
    return this.http.delete<T>(`${this.base}/${path}`);
  }
}
