<?php

declare(strict_types=1);

namespace AaiEduHr\HeartPhrameModuleNotification\Tests;

use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationPreferenceService;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\Migration\ReversibleMigrationInterface;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NotificationPreferenceService::class)]
final class NotificationPreferenceServiceTest extends TestCase
{
    private NotificationPreferenceService $preferences;

    /**
     * HR: Priprema praznu SQLite bazu s početnom Notification migracijom.
     * EN: Prepares an empty SQLite database with the initial Notification migration.
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

        $this->preferences = new NotificationPreferenceService($database);
    }

    /**
     * HR: Potvrđuje siguran zadani odabir te uključivanje i ponovno
     *     isključivanje e-mail kopija za istog korisnika.
     * EN: Confirms the safe default and enabling then disabling e-mail copies
     *     for the same user.
     */
    public function testEmailPreferenceDefaultsToDisabledAndCanBeChanged(): void
    {
        $this->assertTrue($this->preferences->tableReady());
        $this->assertFalse($this->preferences->emailEnabled(42));

        $enabled = $this->preferences->saveEmailEnabled(42, true);
        $this->assertTrue($enabled['email_enabled']);
        $this->assertTrue($this->preferences->emailEnabled(42));

        $disabled = $this->preferences->saveEmailEnabled(42, false);
        $this->assertFalse($disabled['email_enabled']);
        $this->assertFalse($this->preferences->emailEnabled(42));
    }

    /**
     * HR: Odbija nevažeći identifikator korisnika umjesto tihog spremanja
     *     postavke koja se nikada ne može primijeniti.
     * EN: Rejects an invalid user identifier instead of silently storing a
     *     preference that can never be applied.
     */
    public function testInvalidUserIdentifierIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->preferences->saveEmailEnabled(0, true);
    }
}
