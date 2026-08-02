# HeartPhrame Notification modul

[English version](README.md)

Notification modul daje aplikaciji zajednički trajni inbox. Uz ime prijavljenog
korisnika prikazuje broj nepročitanih poruka i dodaje zaseban ekran obavijesti.

## Ovisnosti

Obavezno, redoslijedom uključivanja:

1. `aaieduhr/heartphrame-framework` (`dev-main`)
2. `aaieduhr/heartphrame-module-orm` (`dev-main`)
3. `aaieduhr/heartphrame-module-auth` (`dev-main`)
4. `aaieduhr/heartphrame-module-notification` (`dev-main`)

Opcionalne integracije:

- `aaieduhr/heartphrame-module-email` stavlja korisnički odobrene e-mail kopije
  u red slanja.
- `aaieduhr/heartphrame-module-api` izlaže samo inbox i stanje pročitanosti
  vlasnika API ključa; ne dopušta stvaranje proizvoljnih poruka.

```bash
composer require aaieduhr/heartphrame-module-notification:dev-main
vendor/bin/hph notification:install-migration
vendor/bin/hph orm-migrate:up
```

English documentation: [README.md](README.md)

## Mogućnosti

- trajni inbox i stanje pročitanosti za svakog korisnika
- broj nepročitanih uz ime korisnika u gornjem desnom Auth meniju
- zaseban paginirani ekran `/notifications`
- označavanje jedne poruke čitanjem ili svih jednim klikom
- uklanjanje pojedine pročitane poruke ili svih pročitanih poruka
- izvorni modul, referenca, strukturirani JSON podaci i siguran lokalni link
- korisnički dedup ključ za ponovljive workflow i pozadinske događaje
- opcionalne e-mail kopije kroz `heartphrame-module-email`
- osobna postavka korisnika za uključivanje e-mail kopija
- opcionalni owner-only HTTP API oglašen kroz `config/api.php`
- prijenosna ORM shema za SQLite, PostgreSQL i MySQL/MariaDB
- početna migracija bez probnih obavijesti

## Preduvjeti

- PHP 8.2 ili noviji
- `aaieduhr/heartphrame-framework`
- `aaieduhr/heartphrame-module-auth`
- `aaieduhr/heartphrame-module-orm`

E-mail modul je opcionalan. Inbox nastavlja raditi kada ga nema ili kada SMTP
slanje ne uspije.

API modul je opcionalan. Notification se može instalirati samostalno i samo
oglašava svoje scopeove; HTTP adaptere registrira API kada su oba modula
uključena.

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

## Politika ovisnosti

Framework i interni HeartPhrame moduli zahtijevaju se s pomične grane
`dev-main`. Ovaj modul ne sprema `composer.lock`; CI dohvaća najnovija
razvojna stanja i pokreće cijeli skup provjera `composer on-commit`.

## Svojstva performansi

Paginacija inboxa ostaje na dva SELECT upita neovisno o količini: jedan za broj
i jedan za ograničenu stranicu. Kompozitni indeks podržava filtriranje stanja i
redoslijed bez zastarjelog cachea broja nepročitanih među zahtjevima.
