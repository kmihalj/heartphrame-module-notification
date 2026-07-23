# HeartPhrame Notification modul

Notification modul daje aplikaciji zajednički trajni inbox. Uz ime prijavljenog
korisnika prikazuje broj nepročitanih poruka i dodaje zaseban ekran obavijesti.

English documentation: [README.md](README.md)

## Mogućnosti

- trajni inbox i stanje pročitanosti za svakog korisnika
- broj nepročitanih uz ime korisnika u gornjem desnom Auth meniju
- zaseban paginirani ekran `/notifications`
- označavanje jedne poruke čitanjem ili svih jednim klikom
- izvorni modul, referenca, strukturirani JSON podaci i siguran lokalni link
- korisnički dedup ključ za ponovljive workflow i pozadinske događaje
- opcionalne e-mail kopije kroz `heartphrame-module-email`
- prijenosna ORM shema za SQLite, PostgreSQL i MySQL/MariaDB
- početna migracija bez probnih obavijesti

## Preduvjeti

- PHP 8.2 ili noviji
- `aaieduhr/heartphrame-framework`
- `aaieduhr/heartphrame-module-auth`
- `aaieduhr/heartphrame-module-orm`

E-mail modul je opcionalan. Inbox nastavlja raditi kada ga nema ili kada SMTP
slanje ne uspije.

## Instalacija

```bash
composer require aaieduhr/heartphrame-module-notification
vendor/bin/hph notification:install-migration
vendor/bin/hph orm-migrate:up
```

Notification uključite nakon Autha, a prije modula koji stvaraju obavijesti:

```php
'aaieduhr/heartphrame-module-notification',
```

Detaljna integracija opisana je u [docs/index_hr.md](docs/index_hr.md).

## Licenca

Modul je objavljen pod
[European Union Public License (EUPL) v1.2](LICENSE).
