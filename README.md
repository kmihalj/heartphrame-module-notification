# HeartPhrame Notification Module

[Hrvatska verzija](README_hr.md)

The Notification module provides a reusable, persistent in-app inbox. It adds
an unread badge beside the authenticated user's name and a dedicated
notification screen.

## Dependencies

Required, in enable order:

1. `aaieduhr/heartphrame-framework` (`dev-main`)
2. `aaieduhr/heartphrame-module-orm` (`dev-main`)
3. `aaieduhr/heartphrame-module-auth` (`dev-main`)
4. `aaieduhr/heartphrame-module-notification` (`dev-main`)

Optional integrations:

- `aaieduhr/heartphrame-module-email` queues user-approved e-mail copies.
- `aaieduhr/heartphrame-module-api` exposes only the API-key owner's inbox and
  read state; it never permits arbitrary message creation.

```bash
composer require aaieduhr/heartphrame-module-notification:dev-main
vendor/bin/hph notification:install-migration
vendor/bin/hph orm-migrate:up
```

Croatian documentation: [README_hr.md](README_hr.md)

## Features

- per-user persistent inbox with unread state
- unread count in the top-right Auth user menu
- dedicated `/notifications` screen with pagination
- mark one notification read by opening it, or mark all read
- remove one read notification or clear all read notifications
- source module, source reference, structured JSON metadata, and safe local link
- per-user deduplication key for repeatable background or workflow events
- optional e-mail copies through `heartphrame-module-email`
- personal account setting for opting into e-mail copies
- optional owner-only HTTP API contributed through `config/api.php`
- portable ORM schema for SQLite, PostgreSQL, and MySQL/MariaDB
- no sample notifications in the initial migration

## Requirements

- PHP 8.2 or newer
- `aaieduhr/heartphrame-framework`
- `aaieduhr/heartphrame-module-auth`
- `aaieduhr/heartphrame-module-orm`

The E-mail module is optional. The inbox continues to work if it is absent or
SMTP delivery fails.

The API module is optional. Notification remains independently installable and
only advertises its scopes; HTTP adapters are registered by API when both
modules are enabled.

## Installation

```bash
composer require aaieduhr/heartphrame-module-notification
vendor/bin/hph notification:install-migration
vendor/bin/hph orm-migrate:up
```

Enable Notification after Auth and before modules that emit notifications:

```php
'aaieduhr/heartphrame-module-notification',
```

Detailed integration notes are in [docs/index_en.md](docs/index_en.md).

## Licence

This work is published under the
[European Union Public License (EUPL) v1.2](LICENSE).

## Dependency policy

The Framework and internal HeartPhrame modules are required from the moving
`dev-main` branch. This module does not commit `composer.lock`; CI resolves
the latest development heads and runs the complete `composer on-commit` suite.

## Performance characteristics

Inbox pagination remains two SELECT statements at any volume: one count and
one bounded page. The composite inbox index supports state filtering and
ordering without introducing a stale cross-request unread-count cache.
