# Testing Guide

This guide provides a console-only flow to verify the application end-to-end.

Run commands from project root:

```bash
cd /path/to/project
```

## 1) Start dependencies

```bash
docker compose up -d database
symfony server:start -d --no-tls
```

Check status (optional):

```bash
docker compose ps
symfony server:status
```

## 2) Prepare database schema

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
```

## 3) Generate snapshot data

```bash
php bin/console app:portfolio:snapshot
```

Run additional times for easier history testing.

## 4) Test history API endpoint

Last 24 hours:

```bash
curl -s "http://127.0.0.1:8000/api/portfolio/history?hours=24"
```

Explicit date range (edit from and to params for necessary):

```bash
curl -s "http://127.0.0.1:8000/api/portfolio/history?from=2026-02-26T00:00:00Z&to=2026-02-27T00:00:00Z"
```

Validation tests:

Mixed parameters (`hours` with `from`):

```bash
curl -s -i "http://127.0.0.1:8000/api/portfolio/history?hours=24&from=2026-02-26T00:00:00Z"
```

Invalid hours:

```bash
curl -s -i "http://127.0.0.1:8000/api/portfolio/history?hours=-1"
```

Invalid date:

```bash
curl -s -i "http://127.0.0.1:8000/api/portfolio/history?from=not-a-date"
```

## 5) Test Swagger/OpenAPI docs

Swagger UI:

```bash
open "http://127.0.0.1:8000/api/doc"
```

OpenAPI JSON:

```bash
curl -s "http://127.0.0.1:8000/api/doc.json"
```

Pretty-print JSON (optional):

```bash
curl -s "http://127.0.0.1:8000/api/doc.json" | python3 -m json.tool | head -n 40
```

## 6) Run unit tests

```bash
php bin/phpunit
```

## 7) Stop services

```bash
symfony server:stop
docker compose down
```
