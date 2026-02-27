# Architecture Decisions

## Scope and intent

The implementation is intentionally minimal and focused on the assignment requirements:
- calculate portfolio value snapshot,
- persist historical values,
- expose history via API for charting.

## Key decisions

### 1) Service split by responsibility

- `BinancePriceService`: isolates external HTTP calls to Binance (`avgPrice`) and handles integration errors/logging.
- `PortfolioValuationService`: contains valuation business logic and iterates configured holdings dynamically.

This keeps business logic independent from transport details and makes unit testing straightforward.

### 2) Command as scheduled entrypoint

- `app:portfolio:snapshot` is the single use-case entrypoint for hourly calculation and persistence.
- It is designed to be triggered by cron (`0 * * * *`).

This keeps scheduling concerns outside application logic and matches Symfony operational patterns.

### 3) Persistence model

- Entity: `PortfolioValuationSnapshot`
- Table: `portfolio_valuation_snapshots`
- Fields:
  - `id` (PK),
  - `calculated_at` (`datetime_immutable`),
  - `amount_usdt` (`decimal(20,2)`).

`decimal` is used for money storage reliability in DB (avoids binary floating-point storage artifacts).

### 4) API query model

- Endpoint: `GET /api/portfolio/history`
- Supports:
  - `hours` lookback window, or
  - explicit `from`/`to` range.
- Returns chronological order (ASC) for direct chart consumption.
- Returns clear `400` responses for invalid or conflicting parameters.

This gives flexibility for common frontend chart scenarios while keeping API behavior explicit.

### 5) External integration resilience and observability

- Monolog is used in price fetching, valuation, and snapshot persistence flow.
- Errors from Binance integration are logged with context.
- Binance price requests use lightweight retry/backoff:
  - up to 2 attempts per asset,
  - 250ms delay before the second attempt,
  - retry is triggered on transport exceptions and on invalid/non-200 `avgPrice` responses.
  - if all attempts fail, the service throws a `RuntimeException` with symbol context.

This improves debugging and production support without adding heavy infrastructure.

## Trade-offs and future improvements

- Portfolio holdings are currently static in configuration (as required by assignment input).  
  Future: move to DB or external config source.
- Price requests are made sequentially for clarity.  
  Future: fetch in parallel for lower latency.
- Basic retry/backoff is implemented, but there is no circuit-breaker/fallback price source yet.  
  Future: add circuit-breaker behavior and optional secondary data source.
- API is small and hand-built (no API Platform).  
  This keeps dependencies minimal for assignment scope.

## Operational TODOs

- @todo Add health/readiness endpoint for deployment probes and dependency visibility.  
  Proposed shape: lightweight `/health/live` (process alive) and `/health/ready` (DB reachable, external dependency checks with timeout budget).  
  Rationale: lets orchestrators (Kubernetes, ECS, etc.) make safe restart/traffic decisions.

- @todo Add structured metrics for runtime observability (without changing core business flow).  
  Proposed minimum set: snapshot command success/failure counters, valuation duration histogram, Binance request latency and error counters.  
  Rationale: enables alerting and trend analysis, and reduces mean time to detect/resolve incidents.
