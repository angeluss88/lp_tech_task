# Cryptocurrency Portfolio Valuation API (Symfony)

Backend service that calculates hourly portfolio value in USDT, stores historical snapshots, and exposes chart-ready history API.

## Prerequisites

- PHP 8.4+
- Composer 2+
- Docker + Docker Compose
- Symfony CLI (optional, recommended)

## Run locally

1. Install dependencies:

```bash
composer install
```

1. Start PostgreSQL container:

```bash
docker compose up -d database
```

1. Create database schema:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
```

1. (Optional) Start local Symfony server:

```bash
symfony server:start
```

## Run hourly snapshot manually

```bash
php bin/console app:portfolio:snapshot
```

Cron example:

```bash
0 * * * * php /path/to/project/bin/console app:portfolio:snapshot --env=prod
```

## API

### GET `/api/portfolio/history`

Returns historical portfolio valuation, sorted chronologically (ASC).

Supported query parameters:

- `hours` (example: `?hours=24`)
- `from` and `to` in ISO-8601 format (example: `?from=2026-02-26T10:00:00Z&to=2026-02-26T12:00:00Z`)

Do not combine `hours` with `from`/`to` in one request.

Example response:

```json
[
  { "time": "2026-02-26T10:00:00Z", "amount_usdt": 123456.78 },
  { "time": "2026-02-26T11:00:00Z", "amount_usdt": 123789.12 }
]
```

### Swagger / OpenAPI docs

- Swagger UI: `GET /api/doc`
- OpenAPI JSON: `GET /api/doc.json`

## Architectural decisions

- `BinancePriceService` encapsulates external HTTP calls and error handling for Binance avgPrice endpoint.
- `PortfolioValuationService` contains business logic for valuation formula and configured portfolio holdings.
- `PortfolioSnapshotCommand` is the scheduled entrypoint (`app:portfolio:snapshot`) that calculates and persists one snapshot per hour (UTC hour granularity).
- `PortfolioValuationSnapshot` Doctrine entity stores historical values in `portfolio_valuation_snapshots`.
- `PortfolioHistoryController` exposes chart-friendly API response and parameter validation.
- Monolog is used for operational visibility in price fetching, valuation, and snapshot persistence flow.

## Operational TODOs

- @todo Add health/readiness endpoint for deployment probes and upstream dependency visibility.
- @todo Add structured metrics (e.g. snapshot success/failure counters, Binance latency) for observability dashboards.

## Testing

Run tests:

```bash
php bin/phpunit
```

Included:

- Unit test for `PortfolioValuationService` total calculation logic.

