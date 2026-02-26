# Cryptocurrency Portfolio Valuation API (Symfony)

## Prerequisites

- PHP 8.4+
- Composer 2+
- Docker + Docker Compose

## Run locally

1. Install dependencies:

```bash
composer install
```

2. Start PostgreSQL container:

```bash
docker compose up -d database
```

3. (Optional) Start local Symfony server:

```bash
symfony server:start
```

## Database configuration

Environment variables are defined in `.env`:
- `POSTGRES_DB=app`
- `POSTGRES_USER=app`
- `POSTGRES_PASSWORD=app`
- `POSTGRES_PORT=5432`

`DATABASE_URL` is configured to connect to the Dockerized PostgreSQL instance through `127.0.0.1:${POSTGRES_PORT}`.
