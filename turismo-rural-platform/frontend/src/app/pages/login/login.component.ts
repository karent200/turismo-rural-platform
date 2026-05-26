import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './login.component.html',
})
export class LoginComponent {
  private readonly auth = inject(AuthService);
  tab: 'login' | 'register' = 'login';
  error = signal('');

  loginEmail = '';
  loginPassword = '';
  regName = '';
  regEmail = '';
  regPassword = '';
  regRole: 'turista' | 'prestador' = 'turista';

  ngOnInit(): void {
    if (this.auth.user()) {
      this.auth.redirectByRole(this.auth.user()!.role);
    }
  }

  setTab(t: 'login' | 'register'): void {
    this.tab = t;
    this.error.set('');
  }

  onLogin(): void {
    this.error.set('');
    if (!this.validEmail(this.loginEmail)) {
      this.error.set('Ingresa un email válido');
      return;
    }
    this.auth.login(this.loginEmail.trim(), this.loginPassword).subscribe({
      next: (res) => {
        if (res.user) {
          this.auth.redirectByRole(res.user.role);
        }
      },
      error: () => this.error.set('Credenciales incorrectas o error de conexión'),
    });
  }

  onRegister(): void {
    this.error.set('');
    if (this.regName.trim().length < 2) {
      this.error.set('El nombre debe tener al menos 2 caracteres');
      return;
    }
    if (!this.validEmail(this.regEmail)) {
      this.error.set('Ingresa un email válido');
      return;
    }
    if (this.regPassword.length < 6) {
      this.error.set('La contraseña debe tener al menos 6 caracteres');
      return;
    }
    this.auth
      .register(
        this.regName.trim(),
        this.regEmail.trim().toLowerCase(),
        this.regPassword,
        this.regRole,
      )
      .subscribe({
        next: (res) => {
          if (res.email) {
            this.setTab('login');
            this.loginEmail = this.regEmail.trim().toLowerCase();
            this.error.set('');
            alert('Registro exitoso. Inicia sesión.');
          } else {
            this.error.set('Error al registrar');
          }
        },
        error: () => this.error.set('Error de conexión'),
      });
  }

  private validEmail(email: string): boolean {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }
}
