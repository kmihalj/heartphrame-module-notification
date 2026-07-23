<?php

declare(strict_types=1);

namespace AaiEduHr\HeartPhrameModuleNotification;

/**
 * HR: Sadrži stabilne identifikatore Notification modula koje dijele migracija
 *     i runtime servisi.
 * EN: Contains stable Notification module identifiers shared by the migration
 *     and runtime services.
 */
final class ModuleNotification
{
    public const PACKAGE_NAME = 'aaieduhr/heartphrame-module-notification';

    public const TABLE_NOTIFICATIONS = 'notifications';

    /**
     * HR: Sprječava instanciranje registra konstanti.
     * EN: Prevents instantiation of the constants registry.
     */
    private function __construct()
    {
    }
}
