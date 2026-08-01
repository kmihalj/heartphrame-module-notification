<?php

declare(strict_types=1);

namespace AaiEduHr\HeartPhrameModuleNotification\Account;

use AaiEduHr\HeartPhrameModuleAuth\Account\AuthAccountSectionProviderInterface;
use AaiEduHr\HeartPhrameModuleNotification\ModuleNotification;
use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationPreferenceService;
use HeartPhrame\Routing\UrlGenerator;

/**
 * HR: Dodaje osobne postavke Notification modula u proširivi Auth profil.
 * EN: Adds Notification personal settings to the extensible Auth profile.
 */
final readonly class NotificationAccountSectionProvider implements AuthAccountSectionProviderInterface
{
    /**
     * HR: Prima servis postavki i generator ruta bez ovisnosti o glavnoj aplikaciji.
     * EN: Receives the preference service and route generator without host-app coupling.
     */
    public function __construct(
        private NotificationPreferenceService $preferences,
        private UrlGenerator $urlGenerator,
    ) {
    }

    /**
     * HR: Vraća opis Notification partiala za jednog korisnika.
     * EN: Returns the Notification partial descriptor for one user.
     *
     * @return array{key:string,package:string,partial:string,data:array<string,mixed>}|null
     */
    public function sectionForUser(int $userId): ?array
    {
        if ($userId <= 0 || !$this->preferences->tableReady()) {
            return null;
        }

        $savePath = $this->urlGenerator->namedRouteExists('notification.preferences.save')
        ? $this->urlGenerator->getPathFor('notification.preferences.save')
        : rtrim($this->urlGenerator->getBasePath(), '/') . '/notifications/preferences';

        return [
            'key' => 'notification-preferences',
            'package' => ModuleNotification::PACKAGE_NAME,
            'partial' => 'notification/account_settings',
            'data' => [
                'emailEnabled' => $this->preferences->emailEnabled($userId),
                'savePath' => $savePath,
            ],
        ];
    }
}
