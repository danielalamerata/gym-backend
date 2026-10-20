# FitMotion — Backend

API REST en Laravel para un sistema de gestión de gimnasios. Maneja toda la lógica de negocio: socios, planes, pagos, rutinas y progreso. El frontend en React consume esta API.

## Stack

- **PHP 8.2 + Laravel 11** — backend y API REST
- **PostgreSQL 16** — base de datos
- **Laravel Sanctum** — autenticación por tokens
- **Docker** — para correr PostgreSQL localmente

## Cómo levantar el proyecto

```bash
git clone https://github.com/danielalamerata/gym-backend.git
cd gym-backend

composer install

cp .env.example .env
php artisan key:generate
```

Configurar la base de datos en `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gym_fitmotion
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password

SANCTUM_STATEFUL_DOMAINS=localhost:5173
FRONTEND_URL=http://localhost:5173
```

```bash
php artisan migrate
php artisan serve
```

La API corre en `http://localhost:8000`.

## Pagos automáticos

El comando `pagos:generar` crea el pago pendiente del mes para cada socio activo. Saltea a los que ya tienen el pago de ese mes, así que se puede correr varias veces sin duplicar nada.

```bash
php artisan pagos:generar               # mes actual
php artisan pagos:generar --mes=2026-11 # un mes puntual (AAAA-MM)
```

La forma de cobrar se elige en el `.env` (por ahora la única política es `mensual`: mes completo al precio actual del plan, con vencimiento el día configurado por el admin):

```env
COBROS_POLITICA=mensual
```

El comando está programado para correr todos los días a las 00:05 (hora de Argentina). Así, un socio que se da de alta a mitad de mes recibe esa misma noche el pago del mes completo.

Para que la programación funcione, el scheduler de Laravel tiene que estar corriendo:

```bash
# en local
php artisan schedule:work
```

```bash
# en producción, una línea en el crontab del servidor
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

## Endpoints principales

| Método | Endpoint | Descripción | Auth |
|---|---|---|---|
| POST | `/api/login` | Login | No |
| POST | `/api/logout` | Logout | Sí |
| GET | `/api/socios` | Listar socios | Sí (admin) |
| POST | `/api/socios` | Crear socio | Sí (admin) |
| GET | `/api/planes` | Listar planes | Sí |
| GET | `/api/pagos/pendientes` | Pagos del mes | Sí (admin) |
| GET | `/api/dashboard/admin` | Resumen del día | Sí (admin) |
| GET | `/api/dashboard/cliente` | Dashboard socio | Sí (cliente) |

## Estructura

```
app/
├── Http/
│   ├── Controllers/   — lógica de cada endpoint
│   └── Middleware/    — validación de roles
├── Models/            — modelos Eloquent
└── Console/Commands/  — tareas automáticas (pagos mensuales)
database/
└── migrations/        — estructura de la base de datos
routes/
└── api.php            — definición de rutas
```

## Algunas decisiones que tomé

**Single-tenant** — cada gimnasio tiene su propia instalación. Es más simple de mantener y permite personalizar por cliente sin afectar a otros. Arranqué así porque estoy en etapa de MVP y no tenía sentido agregar complejidad de multitenancy todavía.

**PostgreSQL en vez de SQLite** — SQLite está bien para desarrollo pero tiene problemas de concurrencia cuando varios usuarios acceden al mismo tiempo. PostgreSQL lo manejo con Docker localmente y es el mismo motor que voy a usar en producción.

**Sanctum para auth** — manejo sesiones con tokens Bearer. El frontend guarda el token y lo manda en cada request. Simple y funciona bien para este caso.

---

Desarrollado por Daniela Lamerata · [Ver frontend](https://github.com/danielalamerata/gym-frontend.git)