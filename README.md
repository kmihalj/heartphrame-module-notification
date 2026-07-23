# HeartPhrame Notification Module

The Notification module provides a reusable, persistent in-app inbox. It adds
an unread badge beside the authenticated user's name and a dedicated
notification screen.

Croatian documentation: [README_hr.md](README_hr.md)

## Features

- per-user persistent inbox with unread state
- unread count in the top-right Auth user menu
- dedicated `/notifications` screen with pagination
- mark one notification read by opening it, or mark all read
- source module, source reference, structured JSON metadata, and safe local link
- per-user deduplication key for repeatable background or workflow events
- optional e-mail copies through `heartphrame-module-email`
- portable ORM schema for SQLite, PostgreSQL, and MySQL/MariaDB
- no sample notifications in the initial migration

## Requirements

- PHP 8.2 or newer
- `aaieduhr/heartphrame-framework`
- `aaieduhr/heartphrame-module-auth`
- `aaieduhr/heartphrame-module-orm`

The E-mail module is optional. The inbox continues to work if it is absent or
SMTP delivery fails.

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
