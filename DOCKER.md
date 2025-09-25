## Docker architecture overview

This application runs a Laravel-based web app and background worker with Postgres, Redis, RabbitMQ, MinIO, and an Nginx reverse proxy. Everything is orchestrated via `docker-compose.yml` on a single user-defined bridge network `jeromes`.

### Services and containers

- **nginx** (reverse proxy)
  - Image: `nginx:1.18`
  - Ports: `8081 -> 80`
  - Volumes:
    - `./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf`
    - `./web:/var/www/html` (serves Laravel `public/`)
  - Network: `jeromes`
  - Depends on: `web`
  - Purpose: Public HTTP entrypoint that forwards PHP requests to `web:9000` (php-fpm).

- **web** (Laravel app, php-fpm)
  - Build: `docker/web/Dockerfile` (FROM `php:8.2-fpm`)
  - Volumes:
    - `./web:/var/www/html`
    - `./docker/web/php.ini:/usr/local/etc/php/php.ini`
  - Network: `jeromes`
  - Depends on: `postgres`
  - Entrypoint: `docker/web/entrypoint.sh`
    - Installs Composer deps, generates app key, runs `php-fpm`.
  - Purpose: Runs Laravel application (FPM). Nginx connects to it over FastCGI.

- **postgres** (database)
  - Image: `postgres:15`
  - Ports: `5433 -> 5432`
  - Env: `POSTGRES_DB`, `POSTGRES_PASSWORD`, `POSTGRES_USER`
  - Volume: named volume `postgres_data:/var/lib/postgresql/data`
  - Network: `jeromes`

- **pgadmin** (DB UI)
  - Image: `dpage/pgadmin4:latest`
  - Ports: `8082 -> 80`
  - Env: `PGADMIN_DEFAULT_EMAIL`, `PGADMIN_DEFAULT_PASSWORD`
  - Volume: named volume `pgadmin_data:/var/lib/pgadmin`
  - Network: `jeromes`
  - Depends on: `postgres`

- **redis** (cache/queue driver)
  - Image: `redis:7.2`
  - Ports: `6380 -> 6379`
  - Volume: named volume `redis_data:/data`
  - Network: `jeromes`

- **rabbitmq** (AMQP broker with management UI)
  - Image: `rabbitmq:3-management`
  - Ports:
    - `5673 -> 5672` (AMQP)
    - `15673 -> 15672` (Management UI)
  - Env: `RABBITMQ_DEFAULT_USER`, `RABBITMQ_DEFAULT_PASS`
  - Network: `jeromes`

- **minio** (S3-compatible object storage)
  - Image: `minio/minio:latest`
  - Ports:
    - `9002 -> 9000` (S3 API)
    - `9003 -> 9001` (Console)
  - Env: `MINIO_ROOT_USER`, `MINIO_ROOT_PASSWORD`
  - Command: `server /data --console-address ":9001"`
  - Volume: named volume `minio_data:/data`
  - Network: `jeromes`

- **worker** (background jobs, php-fpm runtime)
  - Build: `docker/worker/Dockerfile` (FROM `php:8.2-fpm`)
  - Ports: `8085 -> 80` (not typically needed for FPM-only, but exposed)
  - Volumes:
    - `./worker:/var/www/html`
    - `./docker/worker/php.ini:/usr/local/etc/php/php.ini`
  - Network: `jeromes`
  - Depends on: `postgres`, `redis`, `rabbitmq`, `minio`
  - Entrypoint: `docker/worker/entrypoint.sh`
    - Installs Composer deps, generates app key, starts `php-fpm` (no migrations).
  - Purpose: Intended to run queue/async tasks; connect to queues/cache/db.

### Networking

- User-defined bridge network: `jeromes` (default driver `bridge`).
- Service-to-service DNS: containers resolve each other by service name (`web`, `postgres`, `redis`, `rabbitmq`, `minio`).
- External port mapping:
  - App via Nginx: `http://localhost:8081`
  - PgAdmin: `http://localhost:8082`
  - Redis: `localhost:6380`
  - Postgres: `localhost:5433`
  - RabbitMQ UI: `http://localhost:15673`, AMQP at `localhost:5673`
  - MinIO S3 API: `http://localhost:9002`, Console: `http://localhost:9003`

### Request flow

1. Client -> `nginx:80` (host `8081`) using `default.conf`.
2. Nginx serves static assets from `./web/public` and forwards PHP to `web:9000` (FastCGI).
3. Laravel (`web`) connects to:
   - `postgres:5432` for DB
   - `redis:6379` for cache/queues
   - `rabbitmq:5672` for AMQP messaging (if configured)
   - `minio:9000` for object storage (S3-compatible)
4. `worker` uses the same backends for async/background processing.

### Volumes

- Named volumes (persist across container recreations):
  - `postgres_data`, `pgadmin_data`, `redis_data`, `minio_data`.
- Bind mounts for application code and configs:
  - `./web` → `web` and `nginx`
  - `./worker` → `worker`
  - `./docker/*/php.ini` → respective PHP configs
  - `./docker/nginx/default.conf` → Nginx site config

### Dockerfiles and entrypoints

- `docker/web/Dockerfile` and `docker/worker/Dockerfile`:
  - Base: `php:8.2-fpm` with extensions: `pdo`, `pdo_pgsql`, `pgsql`, `mbstring`, `xml`, `gd`, `bcmath`, and PECL `redis`.
  - Composer installed from `composer:2.8` image.
- Entrypoints:
  - `docker/web/entrypoint.sh`: creates required Laravel dirs, sets permissions, runs `composer install`, `php artisan key:generate`, `php artisan migrate:fresh --seed`, then starts `php-fpm`.
  - `docker/worker/entrypoint.sh`: similar setup without running migrations; starts `php-fpm`. For queues you may instead run a supervisor or `php artisan queue:work`.

### Environment configuration

Set the following in a `.env` or exported env when running compose:

- Database: `POSTGRES_DB`, `POSTGRES_USER`, `POSTGRES_PASSWORD`
- PgAdmin: `PGADMIN_DEFAULT_EMAIL`, `PGADMIN_DEFAULT_PASSWORD`
- RabbitMQ: `RABBITMQ_DEFAULT_USER`, `RABBITMQ_DEFAULT_PASS`
- MinIO: `MINIO_ROOT_USER`, `MINIO_ROOT_PASSWORD`

Laravel application `.env` inside the `web`/`worker` code should point to service names on the `jeromes` network, for example:

```
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=${POSTGRES_DB}
DB_USERNAME=${POSTGRES_USER}
DB_PASSWORD=${POSTGRES_PASSWORD}

REDIS_HOST=redis
REDIS_PORT=6379

RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=${MINIO_ROOT_USER}
AWS_SECRET_ACCESS_KEY=${MINIO_ROOT_PASSWORD}
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=local
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

### How to run

```
docker compose up --build -d
```

Then open:

- App: `http://localhost:8081`
- PgAdmin: `http://localhost:8082`
- RabbitMQ UI: `http://localhost:15673`
- MinIO Console: `http://localhost:9003`

To stop:

```
docker compose down
```



