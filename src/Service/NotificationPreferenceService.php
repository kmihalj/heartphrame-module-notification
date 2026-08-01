<?php

declare(strict_types=1);

namespace AaiEduHr\HeartPhrameModuleNotification\Service;

use AaiEduHr\HeartPhrameModuleNotification\ModuleNotification;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;

use function date;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_scalar;
use function strtolower;
use function trim;

/**
 * HR: Upravlja osobnim postavkama kanala obavijesti bez izlaganja tablice
 *     drugim modulima. E-mail kopije su po zadanom isključene.
 * EN: Manages personal notification-channel preferences without exposing the
 *     table to other modules. E-mail copies are disabled by default.
 */
final readonly class NotificationPreferenceService
{
    /**
     * HR: Prima ORM bazu koju koristi za prijenosne upite.
     * EN: Receives the ORM database used for portable queries.
     */
    public function __construct(private Database $database)
    {
    }

    /**
     * HR: Provjerava postoji li tablica osobnih postavki.
     * EN: Checks whether the personal-preferences table exists.
     */
    public function tableReady(): bool
    {
        return $this->database->schema()->hasTable(ModuleNotification::TABLE_USER_PREFERENCES);
    }

    /**
     * HR: Vraća želi li korisnik e-mail kopije obavijesti. Nepostojeća postavka
     *     i neprimijenjena migracija namjerno znače `false`.
     * EN: Returns whether the user wants e-mail notification copies. A missing
     *     preference or migration deliberately means `false`.
     */
    public function emailEnabled(int $userId): bool
    {
        if ($userId <= 0 || !$this->tableReady()) {
            return false;
        }

        $row = $this->database->table(ModuleNotification::TABLE_USER_PREFERENCES)
            ->select(['email_enabled'])
            ->where('user_id', '=', $userId)
            ->first();

        return is_array($row) && $this->boolValue($row['email_enabled'] ?? false);
    }

    /**
     * HR: Sprema korisnikov odabir prijenosnim ORM upsertom.
     * EN: Stores the user's choice with a portable ORM upsert.
     *
     * @return array{user_id:int,email_enabled:bool,updated_at:string}
     */
    public function saveEmailEnabled(int $userId, bool $enabled): array
    {
        if ($userId <= 0 || !$this->tableReady()) {
            throw new \RuntimeException(__('Postavke obavijesti trenutačno nisu dostupne.'));
        }

        $now = date('Y-m-d H:i:s');
        $this->database->table(ModuleNotification::TABLE_USER_PREFERENCES)->upsert(
            [[
                'user_id' => $userId,
                'email_enabled' => $enabled,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['user_id'],
            ['email_enabled', 'updated_at'],
        );

        return [
            'user_id' => $userId,
            'email_enabled' => $enabled,
            'updated_at' => $now,
        ];
    }

    /**
     * HR: Normalizira boolean vrijednosti koje različite baze vraćaju kao
     *     bool, broj ili tekst.
     * EN: Normalizes boolean values returned by different databases as bool,
     *     number, or text.
     */
    private function boolValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value !== 0;
        }

        if (!is_scalar($value)) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }
}
