<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleAuth\Middleware\RequireAuthenticatedUserMiddleware;
use AaiEduHr\HeartPhrameModuleAuth\ModuleAuth;
use AaiEduHr\HeartPhrameModuleNotification\Controller\NotificationController;
use AaiEduHr\HeartPhrameModuleNotification\ModuleNotification;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use HeartPhrame\Bridge\ComposerBridge;
use HeartPhrame\Command\CommandDefinition;
use HeartPhrame\Config\ConfigInterface;
use Psr\Container\ContainerInterface;

return new class extends \HeartPhrame\Module\AbstractModuleManifest {
    private const AUTH_MODULE_PACKAGE = 'aaieduhr/heartphrame-module-auth';

    private const ORM_MODULE_PACKAGE = 'aaieduhr/heartphrame-module-orm';

    /**
     * HR: Provjerava instalaciju i redoslijed obaveznih Auth i ORM modula.
     * EN: Verifies installation and ordering of the required Auth and ORM modules.
     */
    public function canLoad(ContainerInterface $container): bool
    {
        $composer = $container->get(ComposerBridge::class);
        if (!($composer instanceof ComposerBridge)) {
            throw new RuntimeException('Notification module requires ComposerBridge.');
        }

        if (!$composer->isInstalled(self::AUTH_MODULE_PACKAGE) || !class_exists(ModuleAuth::class)) {
            throw new RuntimeException('Notification module requires the installed auth module.');
        }

        if (!$composer->isInstalled(self::ORM_MODULE_PACKAGE) || !class_exists(Database::class)) {
            throw new RuntimeException('Notification module requires the installed ORM module.');
        }

        $config = $container->get(ConfigInterface::class);
        if (!($config instanceof ConfigInterface)) {
            throw new RuntimeException('Notification module requires ConfigInterface.');
        }

        $enabled = $config->getAsArrayWithValuesAsNonEmptyStrings('app.modules.enabled') ?? [];
        foreach ([self::AUTH_MODULE_PACKAGE, self::ORM_MODULE_PACKAGE] as $required) {
            if (!in_array($required, $enabled, true)) {
                throw new RuntimeException(
                    'Notification module requires enabled module "' . $required . '" before "'
                    . ModuleNotification::PACKAGE_NAME . '".',
                );
            }
        }

        return true;
    }

    /**
     * HR: Odgađa učitavanje do registracije obaveznih modula.
     * EN: Defers loading until required modules have been registered.
     */
    public function requiresDeferredLoading(): bool
    {
        return true;
    }

    /**
     * HR: Učitava servisne definicije modula.
     * EN: Loads the module service definitions.
     */
    public function getServices(): array
    {
        $services = require __DIR__ . '/config/services.php';
        if (!is_array($services)) {
            throw new RuntimeException('Notification config/services.php must return an array.');
        }

        return $services;
    }

    /**
     * HR: Registrira inbox i akcije čitanja samo za prijavljene korisnike.
     * EN: Registers inbox and read actions for authenticated users only.
     */
    public function getBaseRoutes(): array
    {
        $authenticated = [RequireAuthenticatedUserMiddleware::class];

        return [
            ['GET', '/notifications', NotificationController::class . '@index', 'notification.index', $authenticated],
            [
                'GET',
                '/notifications/open/{uuid}',
                NotificationController::class . '@open',
                'notification.open',
                $authenticated,
            ],
            [
                'POST',
                '/notifications/read-all',
                NotificationController::class . '@markAllRead',
                'notification.read-all',
                $authenticated,
            ],
        ];
    }

    /**
     * HR: Registrira helper za kopiranje početne migracije.
     * EN: Registers the helper for copying the initial migration.
     */
    public function getCommands(): array
    {
        return [
            new CommandDefinition(
                'notification',
                'Notification module helper command.',
                [\AaiEduHr\HeartPhrameModuleNotification\Command\HpNotificationCommand::class, 'run'],
            ),
            new CommandDefinition(
                'notification:install-migration',
                'Copy initial Notification migration into the host application.',
                [
                    \AaiEduHr\HeartPhrameModuleNotification\Command\HpNotificationCommand::class,
                    'installMigration',
                ],
            ),
        ];
    }

    /**
     * HR: Vraća direktorij viewova modula.
     * EN: Returns the module view directory.
     */
    public function getViewsPath(): string
    {
        return __DIR__ . '/views';
    }
};
