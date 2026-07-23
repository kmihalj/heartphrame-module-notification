<?php

declare(strict_types=1);

namespace AaiEduHr\HeartPhrameModuleNotification\Tests;

use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationEmailBridge;
use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationService;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\Migration\ReversibleMigrationInterface;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

#[CoversClass(NotificationService::class)]
#[UsesClass(NotificationEmailBridge::class)]
final class NotificationServiceTest extends TestCase
{
    private NotificationService $notifications;

    /**
     * HR: Priprema praznu prijenosnu inbox tablicu i most bez E-mail modula.
     * EN: Prepares an empty portable inbox table and a bridge without the E-mail module.
     */
    protected function setUp(): void
    {
        $helper = new Helper();
        $config = new Config($helper, [
            'database' => [
                'connections' => [
                    'default' => [
                        'driver' => 'sqlite',
                        'database' => ':memory:',
                    ],
                ],
            ],
        ]);
        $database = new Database($config, $helper);
        $migration = require dirname(__DIR__) . '/resources/migrations/initial_notification_schema.php';
        $this->assertInstanceOf(ReversibleMigrationInterface::class, $migration);
        $migration->up($database);
        $this->notifications = new NotificationService(
            $database,
            new NotificationEmailBridge($this->emptyContainer()),
        );
    }

    /**
     * HR: Provjerava inbox, deduplikaciju, ponovno otvaranje osvježene poruke
     *     i vlasništvo nad označavanjem pročitanog.
     * EN: Verifies the inbox, deduplication, reopening a refreshed message, and
     *     ownership enforcement when marking an item as read.
     */
    public function testInboxDeduplicatesAndTracksReadStatePerUser(): void
    {
        $first = $this->notifications->notifyUser(
            7,
            'workspace.review',
            'Pregled',
            'Prva poruka',
            '/workspace/demo',
            'workspace',
            '42:hr',
            'workspace:42:hr',
            ['version' => 1],
        );
        $this->assertSame(1, $this->notifications->unreadCount(7));

        $read = $this->notifications->markRead(7, (string)($first['uuid'] ?? ''));
        $this->assertIsArray($read);
        $this->assertTrue((bool)($read['is_read'] ?? false));
        $this->assertSame(0, $this->notifications->unreadCount(7));
        $this->assertNull($this->notifications->markRead(8, (string)($first['uuid'] ?? '')));

        $refreshed = $this->notifications->notifyUser(
            7,
            'workspace.review',
            'Ponovni pregled',
            'Nova verzija poruke',
            '/workspace/demo?draft=preview',
            'workspace',
            '42:hr',
            'workspace:42:hr',
            ['version' => 2],
        );
        $this->assertSame($first['id'] ?? null, $refreshed['id'] ?? null);
        $this->assertFalse((bool)($refreshed['is_read'] ?? true));
        $this->assertSame(1, $this->notifications->unreadCount(7));
        $this->assertCount(1, $this->notifications->inbox(7)['items']);

        $this->notifications->notifyUsers(
            [7, 7, 8],
            'workspace.published',
            'Objavljeno',
            'Stranica je objavljena.',
        );
        $this->assertSame(2, $this->notifications->unreadCount(7));
        $this->assertSame(1, $this->notifications->unreadCount(8));
        $this->assertSame(2, $this->notifications->markAllRead(7));
        $this->assertSame(0, $this->notifications->unreadCount(7));
    }

    /**
     * HR: Vraća minimalni PSR container koji nema opcionalne servise.
     * EN: Returns a minimal PSR container with no optional services.
     */
    private function emptyContainer(): ContainerInterface
    {
        return new class implements ContainerInterface {
            /**
             * HR: Odbija dohvat servisa jer ih test namjerno ne registrira.
             * EN: Rejects service resolution because the test intentionally registers none.
             */
            public function get(string $id): never
            {
                throw new RuntimeException('Service is not registered: ' . $id);
            }

            /**
             * HR: Potvrđuje da prazni container nema traženi servis.
             * EN: Confirms that the empty container does not contain the requested service.
             */
            public function has(string $id): bool
            {
                return false;
            }
        };
    }
}
