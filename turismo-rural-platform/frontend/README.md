# Frontend Angular — Turismo Rural

SPA en **Angular 19** que consume la API PHP en `/src/controllers/`.

## Desarrollo (recomendado)

1. Levanta Docker (API en puerto **8080**):

```bash
cd ..
docker-compose up -d
```

2. En esta carpeta:

```bash
npm install
npm start
```

3. Abre: http://localhost:4200

El proxy (`proxy.conf.json`) reenvía `/src` al backend PHP.

## Producción (servido por Apache)

```bash
npm run build
```

Genera los archivos en `../public/app/`. Luego:

http://localhost:8080/app/

## Rutas

| Ruta | Rol |
|------|-----|
| `/app/login` | Login y registro |
| `/app/turista` | Hub turista |
| `/app/prestador` | Panel prestador |
| `/app/admin` | Panel admin |

## Estructura

- `src/app/core/` — API, auth, guards
- `src/app/pages/` — Pantallas por rol
- Estilos compartidos: `../public/css/style.css`

La versión HTML clásica sigue en `../public/index.html`.
