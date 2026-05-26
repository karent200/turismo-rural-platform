# Plataforma Turismo Rural Nariño

Plataforma web para descubrir servicios turísticos rurales en Nariño: alojamiento, gastronomía, recreación y actividades.

## Requisitos

- Docker y Docker Compose, **o**
- PHP 8+ y MySQL 8 (modo local)

## Inicio rápido con Docker

```bash
cd turismo-rural-platform
cp .env.example .env
docker-compose up --build -d
```

Abre:

- **Angular (recomendado):** http://localhost:8080/app/ (tras `cd frontend && npm run build`)
- **HTML clásico:** http://localhost:8080/index.html

- phpMyAdmin: http://localhost:8081

## Frontend Angular

```bash
cd frontend
npm install
npm start          # desarrollo en http://localhost:4200 (proxy a API)
npm run build      # compila a public/app/ para Docker
```

Ver `frontend/README.md`.

## Inicio local (sin Docker)

```bash
cd turismo-rural-platform/public
php -S localhost:8000
```

Abre: http://localhost:8000/index.html  
(Necesitas MySQL configurado y el archivo `.env` en la raíz del proyecto.)

## Credenciales de prueba

Contraseña para todos: **`password`**

| Rol       | Email                 |
|-----------|------------------------|
| Admin     | admin@example.com      |
| Prestador | `prestador@test.com` (3 servicios + reservas demo) o `maria@glamping.co` |
| Turista   | turista@test.com o ana@turista.co       |

## Estructura principal

| Ruta | Descripción |
|------|-------------|
| `frontend/` | SPA Angular 19 (turista, prestador, admin) |
| `public/app/` | Build de Angular servido en `/app/` |
| `public/index.html` | Hub turista HTML clásico |
| `public/prestador.html` | Panel prestador: perfil, servicios, reservas, disponibilidad |
| `docker/migrations/002_prestador_panel.sql` | Migración si ya tenías la BD creada |
| `public/admin.html` | Panel admin (usuarios, stats, reservas) |
| `src/controllers/` | API PHP (auth, services, reservations) |
| `docker/init.sql` | Esquema y datos demo |

## Probar la base de datos

http://localhost:8080/test-db.php

## Notas

- La sesión PHP y `localStorage` deben coincidir: si recargas y fallan las reservas, cierra sesión e inicia de nuevo.
- Los prestadores se redirigen automáticamente a `prestador.html` al iniciar sesión.
- Los administradores van a `admin.html` (`admin@example.com` / `password`).
- Iconos: `public/vendor/fontawesome/` (local, no requiere internet).
- API usa `window.location.origin + "/src/"` (puertos 8080 u 8000).

### Actualizar BD existente (panel prestador)

Si Docker ya creó la base de datos antes, ejecuta en phpMyAdmin o:

```bash
docker exec -i turismo-mysql mysql -u turismo_user -p turismo_db < docker/migrations/002_prestador_panel.sql
```

O recrea todo: `docker-compose down -v` y `docker-compose up --build -d`.
