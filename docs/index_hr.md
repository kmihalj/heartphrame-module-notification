# Upute za Notification modul

## Odgovornosti

`NotificationService` je javni poslovni API. Upravlja spremanjem, brojem
nepročitanih, paginacijom, stanjem čitanja i deduplikacijom po korisniku.
Pozivatelji ne trebaju poznavati shemu baze.

`NotificationNavigationProvider` Auth navigaciji izlaže samo nepročitani broj
i putanju inboxa. Badge je tako mala opcionalna integracija, a Auth nije vezan
uz internu bazu obavijesti.

Inbox formatira vrijeme prema aktivnom jeziku aplikacije, a izvornu strojno
čitljivu vrijednost zadržava u HTML `datetime` atributu.

## Kreiranje obavijesti

```php
$notifications->notifyUser(
    $userId,
    'workspace.review_requested',
    'Stranica čeka pregled',
    'Stranica "Upute" poslana je na pregled.',
    '/workspace/tim/upute?draft=preview',
    'workspace',
    '42:hr',
    'workspace:review:42:hr:7',
    ['node_id' => 42, 'version_number' => 7],
    true,
);
```

Link mora biti lokalna apsolutna putanja. Inbox controller odbija vanjska i
protocol-relative preusmjeravanja. Kada za istog korisnika već postoji neprazan
dedup ključ, postojeći redak se osvježava i ponovno postaje nepročitan.

Za popis primatelja koristite `notifyUsers()`. Dupli i nevaljani ID-evi se
preskaču.

## Stanje čitanja i uklanjanje

Otvaranje obavijesti označava je pročitanom prije praćenja sigurne lokalne
poveznice. U inboxu se mogu označiti pročitanima i sve poruke odjednom. Korisnik
može trajno ukloniti pojedinu vlastitu pročitanu obavijest ili sve svoje
pročitane obavijesti; tim radnjama nije moguće ukloniti nepročitane ni tuđe
poruke.

## Opcionalni e-mail

Auth ekran korisničkog računa sadrži postavku **Šalji obavijesti aplikacije
e-mailom**. Postavka je zadano isključena. Kada je module-email instaliran i
primatelj uključi kopije, in-app poruka ulazi i u SMTP outbox. Svaka greška
pomoćnog mosta je izolirana: nedostupan mail poslužitelj ne smije spriječiti
inbox poruku ni poslovni workflow koji ju je stvorio.

## Opcionalni HTTP API

`config/api.php` oglašava `notifications:read` i `notifications:write` bez
uvoza API klasa. Kada je uključen i API modul, njegove rute rade isključivo nad
inboxom vlasnika ključa. Namjerno nema endpointa za proizvoljno stvaranje
obavijesti; poruke stvaraju domenski workflowi.

## Podaci i privatnost

Spremajte samo metapodatke potrebne za prikaz ili usmjeravanje obavijesti.
`data_json` je prikladan za ID-eve i brojeve verzija, ali ne za lozinke, tokene,
cijele dokumente ili druge tajne.

## Veliki inboxi

Paginacija inboxa izvodi jedan `COUNT` i jedan ograničeni upit stranice bez
obzira na broj spremljenih obavijesti. Početna migracija sadrži kompozitni indeks
`user_id`, `read_at`, `created_at`, a regresijski test provjerava ugovor od dva
upita na 250 nepročitanih obavijesti. Cache broja nepročitanih ne dijeli se među
zahtjevima pa su nove i pročitane poruke odmah vidljive.
