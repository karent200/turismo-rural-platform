# Guía de Estudio — Turismo Rural Platform

## Cómo usar esta guía
Cada tema tiene:
1. **Qué es** — explicación simple
2. **Dónde está en el código** — archivo:línea
3. **Ejercicio** — algo práctico que hacer para aprenderlo

---

## Nivel 1: Fundamentos (imprescindible)

### 1. PHP básico orientado a web

| Concepto | Código |
|----------|--------|
| `$_POST`, `$_GET` | `src/controllers/auth.php:39-41` |
| `$_SESSION` | `src/session.php:20-34` |
| `json_encode()` | `src/controllers/auth.php:53-63` |
| `header('Content-Type: application/json')` | `src/controllers/auth.php:7` |
| `require_once` | `src/controllers/auth.php:1-5` |

**Ejercicio**: Crea un nuevo endpoint `src/controllers/hola.php` que reciba `$_GET['nombre']` y devuelva `{"mensaje": "Hola, {nombre}"}`.

---

### 2. HTML + CSS + JavaScript básico

| Concepto | Código |
|----------|--------|
| `document.getElementById()` | `public/js/app.js:102-109` |
| `addEventListener()` | `public/js/app.js:172-174` |
| `innerHTML` | `public/js/app.js:133-146` |
| `fetch()` | `public/js/app.js:40-54` |
| `async/await` | `public/js/app.js:18-55` |
| Flexbox/Grid | `public/index.html` (estilos inline) |
| `backdrop-filter: blur()` | `public/prestador.html:54-56` |

**Ejercicio**: En `public/js/app.js`, agrega un console.log que muestre "Servicios cargados" después de `loadServices()`.

---

### 3. Base de datos MySQL

| Concepto | Código |
|----------|--------|
| CREATE TABLE | `docker/init.sql` |
| INSERT | `src/models/Service.php:13-17` |
| SELECT con JOIN | `src/models/Service.php:19-29` |
| WHERE dinámico | `src/models/Service.php:33-48` |
| `GROUP BY` | `src/models/Service.php:28` |
| `LEFT JOIN` | `src/models/Service.php:23-29` |

**Ejercicio**: Conéctate a MySQL (`docker exec -it turismo-mysql mysql -u turismo -p`) y ejecuta `SELECT * FROM services;` y `SELECT * FROM reviews;`. Escribe una consulta que muestre el nombre del servicio y su promedio de calificación.

---

## Nivel 2: Autenticación y Seguridad

### 4. Sesiones PHP (session.php)

**Qué es**: PHP guarda datos del usuario en el servidor y le da al navegador una cookie con el ID de sesión.

**Archivo clave**: `src/session.php` (completo, solo 84 líneas)

**Flujo completo**:
```
1. initSecureSession()
   ├── session_set_cookie_params(httponly, samesite)  ← cookie segura
   ├── session_start()                                 ← inicia/recupera sesión
   ├── idle timeout check                              ← 30 min sin actividad = logout
   └── update _last_activity

2. auth.php → login
   ├── password_verify($password, $user['password'])   ← verifica hash
   ├── session_regenerate_id(true)                     ← evita session fixation
   └── $_SESSION['user_id'] = ...                      ← guarda datos

3. auth.php → logout
   ├── $_SESSION = []                                  ← limpia datos
   ├── setcookie(session_name(), '', time()-42000)     ← elimina cookie
   └── session_destroy()                               ← destruye archivo
```

**Ejercicio**: Comenta la línea `session_regenerate_id(true)` en `auth.php` y prueba iniciar sesión. ¿Puedes ver el riesgo de seguridad? (investiga "session fixation attack").

---

### 5. CSRF (Cross-Site Request Forgery)

**Qué es**: Un ataque donde un sitio malicioso hace que el navegador de un usuario autenticado envíe requests a tu app.

**Cómo lo resuelves**:
```
Servidor:  $_SESSION['_csrf_token'] = bin2hex(random_bytes(32))
Frontend:  GET /auth.php → recibe csrf_token → lo guarda en memoria
Frontend:  POST /api.php → &_csrf={token} en cada request
Servidor:  validateCsrfToken($_POST['_csrf']) → compara con hash_equals()
```

**Archivos**: `src/session.php:37-49`, `public/js/app.js:36`

**Ejercicio**: Abre la consola del navegador en la app, escribe `csrfToken` y ve su valor. Luego intercepta un POST en la pestaña Network y busca el `_csrf` en el body.

---

### 6. JWT (JSON Web Token) en Django/Angular

**Qué es**: Un token que contiene datos del usuario firmados con una clave secreta. No necesita almacenarse en el servidor.

**Flujo Angular + Django**:
```
1. Login → POST /api/auth/login/ (email + password)
2. Django devuelve { access, refresh }
3. Angular guarda en localStorage
4. auth.interceptor.ts agrega Authorization: Bearer {access}
5. Django valida la firma del token en cada request
6. Cuando expira, POST /api/auth/refresh/ con refresh token
```

**Archivos**:
- `frontend/src/app/core/interceptors/auth.interceptor.ts`
- `frontend/src/app/core/services/auth.service.ts`
- `backend-django/turismo_api/urls.py` (login y refresh endpoints)
- `backend-django/core/serializers.py:89-104` (EmailTokenObtainPairSerializer)

**Ejercicio**: Ve a la consola del navegador cuando estés en la app Angular y escribe `localStorage.getItem('access_token')`. Copia el token y pégalo en https://jwt.io — ahí puedes ver qué datos contiene.

---

### 7. Password Hashing

**PHP**:
```php
// registrar — se guarda el hash, no la contraseña
password_hash($password, PASSWORD_DEFAULT)

// login — se verifica contra el hash
password_verify($password, $user['password'])
```

**Archivos**: `src/models/User.php` (métodos `create` y `findByEmail`)

**Ejercicio**: Busca en MySQL la tabla `users` y ejecuta `SELECT password FROM users LIMIT 1;`. Verás un string largo como `$2y$10$...` — ese es el hash bcrypt, no la contraseña original.

---

## Nivel 3: Frontend Dinámico

### 8. Event Delegation con data-pag

**Problema**: Cuando generas HTML con `innerHTML`, los `onclick` se pierden.

**Solución**: Un solo listener en el contenedor PADRE captura eventos de hijos (gracias al **event bubbling**).

```javascript
// MAL — se pierde al regenerar HTML:
element.innerHTML = '<button onclick="fn()">Click</button>'

// BIEN — event delegation:
contenedor.addEventListener('click', function(e) {
    const btn = e.target.closest('[data-pag]');
    if (!btn) return; // no era un botón de paginación
    // procesar...
})
```

**Diagrama del event bubbling**:
```
Usuario hace clic en <button data-pag="2">
  ↓
button recibe el evento click
  ↓
El evento "burbujea" hacia arriba:
  button → div#pagination → body → document
  ↓
El listener en div#pagination lo captura
  ↓
e.target = el button original
e.target.closest('[data-pag]') → encuentra el button
```

**Archivos**: `public/index.html:1307-1325`, `public/prestador.html:850-865`, `public/admin.html:620-635`

**Ejercicio**: Cambia el listener a `document.addEventListener('click', ...)` en vez de `#pagination.addEventListener('click', ...)`. ¿Funciona igual? ¿Por qué?

---

### 9. Paginación Client-Side

**Qué es**: Partir un array grande en páginas más chicas sin consultar al servidor.

```javascript
const pageSize = 6;
let currentPage = 1;

function renderPagina() {
    const start = (currentPage - 1) * pageSize;
    const end = start + pageSize;
    const pageData = allData.slice(start, end);  // ← la magia
    // dibujar pageData...
    
    const totalPages = Math.ceil(allData.length / pageSize);
    // dibujar botones Anterior / 1 2 3 ... / Siguiente
}
```

**Fórmulas clave**:
- `pageSize` = cuántos items por página
- `start = (currentPage - 1) * pageSize`
- `end = start + pageSize`
- `totalPages = Math.ceil(total / pageSize)`

**Archivos**: `public/index.html` (buscar `pageSize` y `currentPage`), `public/prestador.html`, `public/admin.html`

**Ejercicio**: Cambia `pageSize` de 6 a 3 en `index.html`. ¿Cómo cambia el número de páginas?

---

## Nivel 4: SQL Avanzado

### 10. LEFT JOIN con Subquery para Promedios

**Qué hace**: En UNA sola consulta, trae todos los servicios con su calificación promedio y conteo de reseñas.

```sql
SELECT s.*,
       COALESCE(rv.avg_rating, 0) as avg_rating,
       COALESCE(rv.total_reviews, 0) as total_reviews
FROM services s
LEFT JOIN (
    SELECT service_id,
           ROUND(AVG(rating), 1) as avg_rating,
           COUNT(*) as total_reviews
    FROM reviews
    GROUP BY service_id
) rv ON s.id = rv.service_id
```

**Paso a paso**:
1. `SELECT FROM reviews GROUP BY service_id` → calcula promedios
2. `LEFT JOIN (...) rv ON s.id = rv.service_id` → junta con services
3. `COALESCE(..., 0)` → si no hay reseñas, muestra 0 en vez de NULL

**Archivo**: `src/models/Service.php:19-29`

**Ejercicio**: Conéctate a MySQL y ejecuta la subquery sola: `SELECT service_id, ROUND(AVG(rating), 1), COUNT(*) FROM reviews GROUP BY service_id;`. Luego ejecuta la consulta completa. ¿Cuál es la diferencia?

---

### 11. LEFT JOIN en Django (SerializerMethodField + Avg)

En Django no escribes SQL directamente, usas el ORM:

```python
class ServiceSerializer(serializers.ModelSerializer):
    avg_rating = serializers.SerializerMethodField()

    def get_avg_rating(self, obj):
        agg = obj.reviews.aggregate(avg=Avg("rating"))
        #            ↑ related_name del ForeignKey en Review
        return round(agg["avg"], 1) if agg["avg"] is not None else None
```

**Archivo**: `backend-django/core/serializers.py:75-80`

**Ejercicio**: Agrega un campo `rating_count` al serializer que devuelva `obj.reviews.count()`.

---

## Nivel 5: Arquitectura

### 12. Modelo Colaborativo

**Regla**: Cualquier prestador ve y gestiona TODOS los servicios y reservas.

```php
// reservations.php — NO hay WHERE provider_id
$sql = "SELECT r.*, s.name as service_name FROM reservations r
        JOIN services s ON r.service_id = s.id";
// Todos ven todo
```

**¿Por qué?** Es una plataforma de turismo rural comunitario. Los prestadores colaboran. Si quisieras que cada quien vea solo lo suyo, agregarías:
```php
WHERE s.provider_id = {$_SESSION['user_id']}
```

**Archivos**: `src/controllers/reservations.php`, `src/controllers/services.php`

**Ejercicio**: Modifica `services.php` para que los prestadores solo vean sus propios servicios (agrega `WHERE provider_id = ?`). ¿Qué pasa cuando un admin entra?

---

### 13. OneToOneField Review-Reservation

**Por qué**: Una reserva solo puede tener UNA reseña. No puedes calificar dos veces la misma estadía.

```python
# models.py
class Review(models.Model):
    reservation = models.OneToOneField(
        Reservation, on_delete=models.CASCADE,
        related_name="review"
    )
```

**Validación en la vista**:
```python
if hasattr(reservation, "review") and reservation.review is not None:
    return Response({"detail": "Ya has calificado esta reserva."}, status=400)
```

**Detección en el frontend**:
```python
# Serializer
def get_has_review(self, obj):
    return hasattr(obj, "review") and obj.review is not None
```

```html
<!-- Angular template -->
@if (r.status === 'completada' && !r.has_review) {
  <button (click)="openRating(r)">Calificar</button>
}
```

**Archivos**: `backend-django/core/models.py` (Review), `backend-django/core/views.py` (ReviewCreateView), `backend-django/core/serializers.py:105-106`

**Ejercicio**: Intenta crear dos reseñas para la misma reserva via curl:
```bash
curl -X POST http://localhost:8001/api/reviews/ -H "Authorization: Bearer {token}" -d "service=1&reservation=1&rating=5"
# El segundo intento debe fallar con "Ya has calificado esta reserva"
```

---

### 14. Sesiones PHP vs JWT Angular — Cuándo usar cada uno

| Situación | Sesiones | JWT |
|-----------|----------|-----|
| App tradicional PHP + HTML | ✅ | ❌ |
| SPA (Angular/React) | ❌ (requiere cookies) | ✅ |
| API para mobile | ❌ | ✅ |
| Microservicios | ❌ (sesión en un servidor) | ✅ |
| Alta seguridad (banca) | ✅ (más control) | ❌ (token expuesto) |
| Escalabilidad horizontal | ❌ (sesión pegada a 1 servidor) | ✅ (cualquier servidor) |

**Tu proyecto usa ambos**: PHP con sesiones para la app tradicional (`/index.html`), y JWT para la API Angular (`/app/`).

---

## Nivel 6: DevOps y Docker

### 15. Docker Compose

**Archivo**: `docker-compose.yml`

```yaml
services:
  php:
    image: php:8.2-apache
    ports: ["8080:80"]
    volumes: ["./public:/var/www/html"]
    depends_on: [mysql]

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: turismo_rural

  django_api:
    build: ./backend-django
    ports: ["8001:8001"]
    depends_on: [django_db]

  django_db:
    image: postgres:15
```

**Comandos esenciales**:
```bash
docker compose up -d              # iniciar todo
docker compose down               # detener todo
docker compose logs php           # ver logs de PHP
docker exec -it turismo-mysql mysql -u turismo -p  # entrar a MySQL
docker exec turismo_django_api python manage.py migrate  # migraciones Django
```

**Ejercicio**: Detén el contenedor de Django (`docker stop turismo_django_api`) e intenta acceder a la app Angular. ¿Qué falla? ¿Por qué?

---

### 16. docker cp para actualizar archivos

Cuando editas código local, lo copias al contenedor:
```bash
docker cp models.py turismo_django_api:/app/core/models.py
docker exec turismo_django_api python manage.py migrate
```

**Archivo**: `backend-django/Dockerfile` (configuración del contenedor)

---

## Plan de Estudio Sugerido

| Semana | Temas | Archivos a leer |
|--------|-------|-----------------|
| 1 | PHP básico, HTML, CSS, JS | `auth.php`, `app.js` |
| 2 | Sesiones, CSRF, password_hash | `session.php`, `auth.php` |
| 3 | SQL, JOINs, subqueries | `Service.php`, `init.sql` |
| 4 | Event delegation, paginación | `index.html` (JS al final) |
| 5 | Django REST Framework | `serializers.py`, `views.py` |
| 6 | Angular components, servicios | `turista-hub.component.ts` |
| 7 | JWT, interceptors, guards | `auth.interceptor.ts`, `role.guard.ts` |
| 8 | Docker, deploy | `docker-compose.yml` |

## Ejercicio Final Integrador

Implementa una nueva funcionalidad: **"Servicios favoritos"**

1. **BD**: Crea tabla `favorites(user_id, service_id)`
2. **PHP**: Endpoint `services.php?action=favorite` para agregar/quitar favorito
3. **JS**: Botón ♥ en cada tarjeta de servicio
4. **Django**: Modelo `Favorite` y endpoint `POST /api/favorites/`
5. **Angular**: Botón ♥ en el listado de servicios, pestaña "Mis favoritos"

Esto toca todos los temas de la guía.
