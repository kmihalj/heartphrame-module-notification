<?php

declare(strict_types=1);

namespace AaiEduHr\HeartPhrameModuleNotification\Service;

use AaiEduHr\HeartPhrameModuleNotification\ModuleNotification;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use JsonException;
use RuntimeException;

use function date;
use function is_array;
use function is_numeric;
use function is_scalar;
use function json_decode;
use function json_encode;
use function max;
use function random_bytes;
use function sprintf;
use function trim;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * HR: Jedinstveni poslovni API za stvaranje, čitanje i označavanje korisničkih
 *     obavijesti. Drugi moduli ne trebaju poznavati strukturu tablice.
 * EN: The single business API for creating, reading, and marking user
 *     notifications. Other modules do not need to know the table structure.
 */
final readonly class NotificationService
{
    /**
     * HR: Prima ORM bazu i opcionalni most prema e-mail kanalu.
     * EN: Receives the ORM database and the optional bridge to the e-mail channel.
     */
    public function __construct(
        private Database $database,
        private NotificationEmailBridge $emailBridge,
    ) {
    }

    /**
     * HR: Provjerava je li početna migracija modula primijenjena.
     * EN: Checks whether the module's initial migration has been applied.
     */
    public function tablesReady(): bool
    {
        return $this->database->schema()->hasTable(ModuleNotification::TABLE_NOTIFICATIONS);
    }

    /**
     * HR: Sprema jednu obavijest i opcionalno stavlja e-mail kopiju u outbox.
     *     Isti neprazni dedup ključ za istog korisnika osvježava postojeći redak.
     * EN: Stores one notification and optionally queues an e-mail copy. The same
     *     non-empty dedup key for one user refreshes the existing row.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function notifyUser(
        int $userId,
        string $notificationKey,
        string $title,
        string $message,
        string $linkUrl = '',
        string $sourceModule = '',
        string $sourceReference = '',
        string $dedupKey = '',
        array $data = [],
        bool $sendEmail = true,
    ): array {
        $this->assertTablesReady();
        if ($userId <= 0) {
            throw new RuntimeException(__('Korisnik obavijesti nije valjan.'));
        }

        $notificationKey = $this->required($notificationKey, __('Vrsta obavijesti je obavezna.'));
        $title = $this->required($title, __('Naslov obavijesti je obavezan.'));
        $message = $this->required($message, __('Tekst obavijesti je obavezan.'));
        $sourceModule = trim($sourceModule) !== '' ? trim($sourceModule) : 'application';
        $sourceReference = trim($sourceReference);
        $dedupKey = trim($dedupKey);
        $now = date('Y-m-d H:i:s');
        $existing = $dedupKey !== '' ? $this->findByDedupKey($userId, $dedupKey) : null;
        $values = [
            'notification_key' => $notificationKey,
            'title' => $title,
            'message' => $message,
            'link_url' => trim($linkUrl) !== '' ? trim($linkUrl) : null,
            'source_module' => $sourceModule,
            'source_reference' => $sourceReference !== '' ? $sourceReference : null,
            'dedup_key' => $dedupKey !== '' ? $dedupKey : null,
            'data_json' => $data !== [] ? $this->encodeData($data) : null,
            'read_at' => null,
            'updated_at' => $now,
        ];

        if (is_array($existing)) {
            $this->database->table(ModuleNotification::TABLE_NOTIFICATIONS)
                ->where('id', '=', $this->intValue($existing['id'] ?? 0))
                ->update($values);
            $notification = $this->findById($this->intValue($existing['id'] ?? 0));
        } else {
            $this->database->table(ModuleNotification::TABLE_NOTIFICATIONS)->insert([
                'uuid' => $this->uuid(),
                'user_id' => $userId,
                'created_at' => $now,
                ...$values,
            ]);
            $notification = $this->findById((int)$this->database->lastInsertId());

            if ($sendEmail) {
                $this->emailBridge->queueForUser(
                    $userId,
                    $title,
                    $message,
                    trim($linkUrl),
                    $dedupKey,
                );
            }
        }

        if (!is_array($notification)) {
            throw new RuntimeException(__('Spremljenu obavijest nije moguće učitati.'));
        }

        return $this->normalizeRow($notification);
    }

    /**
     * HR: Šalje istu obavijest većem broju korisnika bez dupliranja ID-eva.
     * EN: Sends the same notification to multiple users without duplicate IDs.
     *
     * @param list<int> $userIds
     * @param array<string, mixed> $data
     * @return list<array<string, mixed>>
     */
    public function notifyUsers(
        array $userIds,
        string $notificationKey,
        string $title,
        string $message,
        string $linkUrl = '',
        string $sourceModule = '',
        string $sourceReference = '',
        string $dedupKey = '',
        array $data = [],
        bool $sendEmail = true,
    ): array {
        $notifications = [];
        $seen = [];
        foreach ($userIds as $userId) {
            $userId = (int)$userId;
            if ($userId <= 0) {
                continue;
            }

            if (isset($seen[$userId])) {
                continue;
            }

            $seen[$userId] = true;
            $notifications[] = $this->notifyUser(
                $userId,
                $notificationKey,
                $title,
                $message,
                $linkUrl,
                $sourceModule,
                $sourceReference,
                $dedupKey,
                $data,
                $sendEmail,
            );
        }

        return $notifications;
    }

    /**
     * HR: Vraća broj nepročitanih poruka za mali badge u navigaciji.
     * EN: Returns the unread count for the compact navigation badge.
     */
    public function unreadCount(int $userId): int
    {
        if ($userId <= 0 || !$this->tablesReady()) {
            return 0;
        }

        $row = $this->database->table(ModuleNotification::TABLE_NOTIFICATIONS)
            ->select(['COUNT(*) AS aggregate'])
            ->where('user_id', '=', $userId)
            ->whereNull('read_at')
            ->first();

        return $this->intValue(is_array($row) ? $row['aggregate'] ?? 0 : 0);
    }

    /**
     * HR: Vraća jednu stranicu inboxa i ukupan broj rezultata.
     * EN: Returns one inbox page and the total result count.
     *
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, page_size: int}
     */
    public function inbox(int $userId, int $page = 1, int $pageSize = 30): array
    {
        $this->assertTablesReady();
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        $countRow = $this->database->table(ModuleNotification::TABLE_NOTIFICATIONS)
            ->select(['COUNT(*) AS aggregate'])
            ->where('user_id', '=', $userId)
            ->first();
        $total = $this->intValue(is_array($countRow) ? $countRow['aggregate'] ?? 0 : 0);
        $pages = max(1, (int)ceil($total / $pageSize));
        $page = min($page, $pages);
        $rows = $this->database->table(ModuleNotification::TABLE_NOTIFICATIONS)
            ->where('user_id', '=', $userId)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($pageSize)
            ->offset(($page - 1) * $pageSize)
            ->get();
        $items = [];
        foreach ($rows as $row) {
            $row = $this->row($row);
            if ($row !== null) {
                $items[] = $this->normalizeRow($row);
            }
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'page_size' => $pageSize,
        ];
    }

    /**
     * HR: Označava jednu korisnikovu poruku pročitanom i vraća je.
     * EN: Marks one notification owned by the user as read and returns it.
     *
     * @return array<string, mixed>|null
     */
    public function markRead(int $userId, string $uuid): ?array
    {
        $notification = $this->findForUserByUuid($userId, $uuid);
        if (!is_array($notification)) {
            return null;
        }

        if (($notification['read_at'] ?? null) === null) {
            $now = date('Y-m-d H:i:s');
            $this->database->table(ModuleNotification::TABLE_NOTIFICATIONS)
                ->where('id', '=', $this->intValue($notification['id'] ?? 0))
                ->update(['read_at' => $now, 'updated_at' => $now]);
            $notification['read_at'] = $now;
        }

        return $this->normalizeRow($notification);
    }

    /**
     * HR: Označava sve nepročitane poruke jednog korisnika pročitanima.
     * EN: Marks all unread notifications for one user as read.
     */
    public function markAllRead(int $userId): int
    {
        $this->assertTablesReady();
        $now = date('Y-m-d H:i:s');

        return $this->database->table(ModuleNotification::TABLE_NOTIFICATIONS)
            ->where('user_id', '=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => $now, 'updated_at' => $now]);
    }

    /**
     * HR: Učitava jednu poruku po korisniku i javnom UUID-u.
     * EN: Loads one notification by owner and public UUID.
     *
     * @return array<string, mixed>|null
     */
    public function findForUserByUuid(int $userId, string $uuid): ?array
    {
        if ($userId <= 0 || trim($uuid) === '' || !$this->tablesReady()) {
            return null;
        }

        $row = $this->database->table(ModuleNotification::TABLE_NOTIFICATIONS)
            ->where('user_id', '=', $userId)
            ->where('uuid', '=', trim($uuid))
            ->first();

        $row = $this->row($row);

        return $row !== null ? $this->normalizeRow($row) : null;
    }

    /**
     * HR: Učitava redak po internom ID-u nakon spremanja.
     * EN: Loads a row by its internal ID after persistence.
     *
     * @return array<string, mixed>|null
     */
    private function findById(int $id): ?array
    {
        $row = $this->database->table(ModuleNotification::TABLE_NOTIFICATIONS)
            ->where('id', '=', $id)
            ->first();

        return $this->row($row);
    }

    /**
     * HR: Traži postojeću obavijest po korisničkom dedup ključu.
     * EN: Finds an existing notification by its per-user deduplication key.
     *
     * @return array<string, mixed>|null
     */
    private function findByDedupKey(int $userId, string $dedupKey): ?array
    {
        $row = $this->database->table(ModuleNotification::TABLE_NOTIFICATIONS)
            ->where('user_id', '=', $userId)
            ->where('dedup_key', '=', $dedupKey)
            ->first();

        return $this->row($row);
    }

    /**
     * HR: Pretvara generički ORM rezultat u redak sa string ključevima.
     * EN: Converts a generic ORM result into a string-keyed row.
     *
     * @return array<string, mixed>|null
     */
    private function row(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $row = [];
        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $row[$key] = $item;
            }
        }

        return $row;
    }

    /**
     * HR: Dekodira strukturirane podatke i normalizira tipove DB retka za view.
     * EN: Decodes structured data and normalizes database-row types for the view.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $row['id'] = $this->intValue($row['id'] ?? 0);
        $row['user_id'] = $this->intValue($row['user_id'] ?? 0);
        $row['is_read'] = is_scalar($row['read_at'] ?? null) && trim((string)$row['read_at']) !== '';
        $row['data'] = [];

        $json = is_scalar($row['data_json'] ?? null) ? trim((string)$row['data_json']) : '';
        if ($json !== '') {
            try {
                $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                $row['data'] = is_array($decoded) ? $decoded : [];
            } catch (JsonException) {
                $row['data'] = [];
            }
        }

        return $row;
    }

    /**
     * HR: Kodira strukturirane podatke bez gubitka Unicode znakova.
     * EN: Encodes structured data without losing Unicode characters.
     *
     * @param array<string, mixed> $data
     */
    private function encodeData(array $data): string
    {
        try {
            return json_encode(
                $data,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );
        } catch (JsonException $jsonException) {
            throw new RuntimeException(__('Podaci obavijesti nisu valjan JSON.'), 0, $jsonException);
        }
    }

    /**
     * HR: Zaustavlja rad s jasnom porukom kada migracija nedostaje.
     * EN: Stops with a clear message when the migration is missing.
     */
    private function assertTablesReady(): void
    {
        if (!$this->tablesReady()) {
            throw new RuntimeException(__('Notification migracija nije primijenjena.'));
        }
    }

    /**
     * HR: Validira obavezni tekst i uklanja rubne razmake.
     * EN: Validates required text and removes surrounding whitespace.
     */
    private function required(string $value, string $message): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new RuntimeException($message);
        }

        return $value;
    }

    /**
     * HR: Pretvara miješanu DB vrijednost u cijeli broj.
     * EN: Converts a mixed database value to an integer.
     */
    private function intValue(mixed $value): int
    {
        return is_numeric($value) ? (int)$value : 0;
    }

    /**
     * HR: Generira kriptografski nasumičan UUID v4 bez vanjske biblioteke.
     * EN: Generates a cryptographically random UUID v4 without an external library.
     */
    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}
