<?php

declare(strict_types=1);

namespace AaiEduHr\HeartPhrameModuleNotification\Service;

use HeartPhrame\Authn\AuthnHandlerInterface;
use HeartPhrame\Routing\UrlGenerator;
use Throwable;

use function is_array;
use function is_numeric;
use function rtrim;

/**
 * HR: Daje Auth navigaciji samo broj nepročitanih poruka i putanju inboxa,
 *     bez izlaganja baze ili Notification internog modela.
 * EN: Gives Auth navigation only the unread count and inbox path without
 *     exposing the database or Notification internals.
 */
final readonly class NotificationNavigationProvider
{
    /**
     * HR: Prima servis obavijesti, auth kontekst i generator URL-ova.
     * EN: Receives the notification service, auth context, and URL generator.
     */
    public function __construct(
        private NotificationService $notifications,
        private AuthnHandlerInterface $authnHandler,
        private UrlGenerator $urlGenerator,
    ) {
    }

    /**
     * HR: Vraća nepročitani broj trenutnog korisnika; bootstrap ili DB greška
     *     ne smije srušiti glavni meni.
     * EN: Returns the current user's unread count; bootstrap or database errors
     *     must not break the main menu.
     */
    public function unreadCount(): int
    {
        try {
            return $this->notifications->unreadCount($this->currentUserId());
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * HR: Vraća named inbox putanju ili stabilni fallback.
     * EN: Returns the named inbox path or a stable fallback.
     */
    public function notificationsPath(): string
    {
        try {
            if ($this->urlGenerator->namedRouteExists('notification.index')) {
                return $this->urlGenerator->getPathFor('notification.index');
            }
        } catch (Throwable) {
        }

        return rtrim($this->urlGenerator->getBasePath(), '/') . '/notifications';
    }

    /**
     * HR: Čita numerički ID iz normaliziranog auth session payloada.
     * EN: Reads the numeric ID from the normalized auth session payload.
     */
    private function currentUserId(): int
    {
        $user = $this->authnHandler->userData();
        $id = is_array($user) ? $user['id'] ?? 0 : 0;

        return is_numeric($id) ? (int)$id : 0;
    }
}
