# Upute za Notification modul

## Odgovornosti

`NotificationService` je javni poslovni API. Upravlja spremanjem, brojem
nepročitanih, paginacijom, stanjem čitanja i deduplikacijom po korisniku.
Pozivatelji ne trebaju poznavati shemu baze.

`NotificationNavigationProvider` Auth navigaciji izlaže samo nepročitani broj
i putanju inboxa. Badge je tako mala opcionalna integracija, a Auth nije vezan
uz internu bazu obavijesti.

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

## Opcionalni e-mail

Kada je module-email instaliran i uključene su kopije obavijesti, prva in-app
poruka ulazi i u SMTP outbox. Svaka greška pomoćnog mosta je izolirana:
nedostupan mail poslužitelj ne smije spriječiti inbox poruku ni poslovni
workflow koji ju je stvorio.

## Podaci i privatnost

Spremajte samo metapodatke potrebne za prikaz ili usmjeravanje obavijesti.
`data_json` je prikladan za ID-eve i brojeve verzija, ali ne za lozinke, tokene,
cijele dokumente ili druge tajne.
