<?php

declare(strict_types=1);

namespace AaiEduHr\HeartPhrameModuleNotification\Service;

use Psr\Container\ContainerInterface;
use Throwable;

use function is_object;
use function method_exists;

/**
 * HR: Opcionalno prosljeđuje kopiju obavijesti module-email redu. Notification
 *     modul ostaje funkcionalan kada e-mail modul nije instaliran ili SMTP ne radi.
 * EN: Optionally forwards a notification copy to the module-email queue. The
 *     Notification module remains functional when e-mail is absent or SMTP fails.
 */
final readonly class NotificationEmailBridge
{
    private const EMAIL_SERVICE = 'AaiEduHr\\HeartPhrameModuleEmail\\Service\\EmailService';

    /**
     * HR: Prima zajednički container bez čvrste Composer ovisnosti o e-mail modulu.
     * EN: Receives the shared container without a hard Composer dependency on the e-mail module.
     */
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * HR: Pokušava staviti e-mail u outbox; svaku grešku namjerno izolira od
     *     primarne in-app obavijesti.
     * EN: Attempts to queue an e-mail and deliberately isolates every failure
     *     from the primary in-app notification.
     */
    public function queueForUser(
        int $userId,
        string $subject,
        string $message,
        string $linkUrl,
        string $dedupKey,
    ): void {
        if ($userId <= 0 || !class_exists(self::EMAIL_SERVICE)) {
            return;
        }

        try {
            $service = $this->container->get(self::EMAIL_SERVICE);
            if (!is_object($service) || !method_exists($service, 'queueForUser')) {
                return;
            }

            $service->queueForUser(
                $userId,
                $subject,
                $message,
                null,
                $dedupKey !== '' ? 'notification:' . $dedupKey : null,
                $linkUrl,
            );
        } catch (Throwable) {
            // HR: Inbox je primaran; opcionalni kanal ne smije prekinuti pozivatelja.
            // EN: The inbox is primary; an optional channel must not break the caller.
        }
    }
}
