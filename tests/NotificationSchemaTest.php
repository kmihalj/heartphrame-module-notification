<?php

declare(strict_types=1);

namespace AaiEduHr\HeartPhrameModuleNotification\Tests;

use AaiEduHr\HeartPhrameModuleNotification\ModuleNotification;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\Migration\ReversibleMigrationInterface;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class NotificationSchemaTest extends TestCase
{
    /**
     * HR: Provjerava da početna migracija stvara prazan i potpun korisnički inbox.
     * EN: Verifies that the initial migration creates an empty and complete user inbox.
     */
    public function testInitialMigrationCreatesEmptyPortableInbox(): void
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

        $this->assertTrue($database->schema()->hasColumns(ModuleNotification::TABLE_NOTIFICATIONS, [
            'uuid',
            'user_id',
            'notification_key',
            'title',
            'message',
            'link_url',
            'source_module',
            'source_reference',
            'dedup_key',
            'data_json',
            'read_at',
        ]));
        $this->assertSame([], $database->table(ModuleNotification::TABLE_NOTIFICATIONS)->get());
    }
}
