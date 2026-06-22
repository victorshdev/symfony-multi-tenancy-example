# Multi-tenancy with Doctrine SQL Filters

A minimal Symfony 7 app that scopes every Doctrine query to the current
organization automatically, using a Doctrine SQL Filter - no repeated
`WHERE organization_id = ...` in every repository method, no accidental
cross-tenant data leaks.

Companion code for the article
[*How to add multi-tenancy to Symfony with Doctrine SQL Filters*](https://victorsh.dev/blog/row-level-multitenancy-doctrine-sql-filters).

## How it works

Three pieces wire together:

| File | Role |
| --- | --- |
| `Domain/Shared/src/Attribute/ContextAware.php` | Marks an entity as tenant-scoped and names the column to filter on. |
| `src/Adapter/Doctrine/Filter/AccessContextFilter.php` | Reads the attribute via reflection and returns the SQL fragment Doctrine appends to every query for that entity. |
| `src/EventListener/RequestListener.php` | On each `private_area_*` request, enables the filter and feeds it the logged-in user's `org_id`. |

The filter is registered **disabled by default** in
`config/packages/doctrine.yaml` (`context_filter`) and only switched on per
request by the listener.

Two scoping strategies are demonstrated:

- **`Document`** - has `organization_id` directly → simple `d.organization_id = :org_id`.
- **`Employee`** - only has `team_id` → scoped through an `EXISTS` subquery against the `team` table.

## Requirements

- PHP 8.4 with `pdo_pgsql`, `xml`, `mbstring`, `intl`, `zip` extensions
- Docker (for PostgreSQL)
- Symfony CLI (optional - the `Makefile` falls back to plain `php bin/console` and PHP's built-in server)

## Setup

```bash
make setup   # deps + Docker (Postgres) + schema + demo data
make serve   # start the local web server
```

Then open <https://localhost:8000/dashboard>.

Run `make help` to see all targets (`make reset` rebuilds the DB and reloads
fixtures from scratch). Prefer the raw commands? They're in the `Makefile`.

> **Without the Symfony CLI:** the Symfony CLI auto-wires the database
> host/port from Docker. On plain `php bin/console`, point `DATABASE_URL` in
> `.env` at the port Docker mapped - check it with `docker compose port database 5432`.

## Test users

The fixtures create two organizations, each with its own team, documents,
employees and one login user:

| Organization | Email | Password |
| --- | --- | --- |
| Org A | `user@org-a.test` | `password` |
| Org B | `user@org-b.test` | `password` |

## Verifying the filter

1. Open `/dashboard` - you'll be redirected to the login form.
2. Log in as **`user@org-a.test`** → the page shows **only Org A's** documents
   and employees.
3. Log out, log in as **`user@org-b.test`** → the lists swap completely to Org B.

Neither user can ever see the other's rows, and the controller never adds a
single `WHERE` clause itself - it just calls `findAll()`.

### Negative check

To prove the scoping is the filter (and not something else), temporarily stop
the listener from enabling it (comment out the body of `initializeContext()` in
`src/EventListener/RequestListener.php`). The dashboard will then show **all**
rows from both tenants.

## Caveats

(Covered in detail in [the article](https://victorsh.dev/blog/row-level-multitenancy-doctrine-sql-filters).)

The filter only applies where the ORM generates the SQL. It does **not** cover:

- eager-loaded associations (`fetch: EAGER`),
- raw SQL / DBAL / native queries,
- code running outside the request (CLI, Messenger, cron).

It also guards **reads** only - writes, validation and uniqueness checks still
need their own tenant scoping.
