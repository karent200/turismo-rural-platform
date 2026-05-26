# Backend Django REST - Turismo Rural

Adaptacion del backend original (PHP) a Django REST Framework con JWT, manteniendo la base conceptual:

- Usuarios con roles: `admin`, `prestador`, `turista`
- Servicios turisticos
- Reservas

## 1) Instalacion

```bash
cd backend-django
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
```

## 2) Configuracion

```bash
copy .env.example .env
```

Ajusta variables de DB en `.env` para usar tu PostgreSQL actual.

## 3) Migraciones y superusuario

```bash
python manage.py makemigrations
python manage.py migrate
python manage.py createsuperuser
```

## 4) Correr API

```bash
python manage.py runserver
```

Base URL: `http://127.0.0.1:8000`

## 5) Docker (API + PostgreSQL en 1 comando)

```bash
cd backend-django
docker compose up --build
```

URLs:
- API Django: `http://127.0.0.1:8001`
- Admin Django: `http://127.0.0.1:8001/admin`

Notas:
- El contenedor `api` espera a PostgreSQL y ejecuta migraciones automaticamente.
- La DB de Docker usa el puerto local `5433` para evitar choque con otros PostgreSQL.
- Si quieres resetear DB de Docker: `docker compose down -v`

---

## Endpoints principales (Postman)

### Auth
- `POST /api/auth/register/`
- `POST /api/auth/login/` (JWT access + refresh)
- `POST /api/auth/refresh/`
- `GET /api/auth/me/` (Bearer token)

### Servicios
- `GET /api/services/`
- `GET /api/services/?type=alojamiento`
- `POST /api/services/` (solo `prestador` o `admin`)
- `GET /api/services/my/` (solo prestador/admin)

### Reservas
- `GET /api/reservations/`
  - turista: solo sus reservas
  - prestador: reservas de sus servicios
  - admin: todas
- `POST /api/reservations/` (solo turista)
- `PATCH /api/reservations/{id}/status/` (prestador dueño o admin)

---

## Ejemplos Postman

### 1. Registrar usuario turista
`POST /api/auth/register/`

```json
{
  "username": "turista1",
  "first_name": "Turista",
  "last_name": "Demo",
  "email": "turista1@test.com",
  "role": "turista",
  "password": "password123"
}
```

### 2. Login JWT
`POST /api/auth/login/`

```json
{
  "username": "turista1",
  "password": "password123"
}
```

Guarda el `access` y envialo como:
`Authorization: Bearer <token>`

### 3. Crear servicio (prestador)
`POST /api/services/`

```json
{
  "name": "Glamping Rural",
  "type": "alojamiento",
  "description": "Alojamiento en naturaleza con vista al rio.",
  "capacity": 4,
  "price": "150.00",
  "location": "Nariño Rural"
}
```

### 4. Crear reserva (turista)
`POST /api/reservations/`

```json
{
  "service": 1,
  "reservation_date": "2026-05-10"
}
```

---

## Integracion Angular (rapida)

En Angular, define `environment.apiUrl = 'http://127.0.0.1:8000/api'`

Rutas sugeridas:
- login: `POST ${apiUrl}/auth/login/`
- register: `POST ${apiUrl}/auth/register/`
- services: `GET/POST ${apiUrl}/services/`
- reservations: `GET/POST ${apiUrl}/reservations/`

Usa un `HttpInterceptor` para incluir el token Bearer automaticamente.
