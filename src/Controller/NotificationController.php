<?php

declare(strict_types=1);

namespace AaiEduHr\HeartPhrameModuleNotification\Controller;

use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationModuleViewRenderer;
use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationPreferenceService;
use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationService;
use HeartPhrame\Alert\Alert;
use HeartPhrame\Alert\AlertHandler;
use HeartPhrame\Authn\AuthnHandlerInterface;
use HeartPhrame\CodeBook\AlertLevelEnum;
use HeartPhrame\Http\ResponseFactory;
use HeartPhrame\Routing\UrlGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function in_array;
use function is_array;
use function is_numeric;
use function is_scalar;
use function max;
use function rawurlencode;
use function rtrim;
use function str_starts_with;
use function strtolower;
use function trim;

/**
 * HR: Poslužuje korisnički inbox te akcije čitanja i uklanjanja poruka.
 * EN: Serves the user inbox and actions for reading and removing notifications.
 */
final readonly class NotificationController
{
    /**
     * HR: Prima framework HTTP servise, auth kontekst i poslovni servis obavijesti.
     * EN: Receives framework HTTP services, auth context, and the notification business service.
     */
    public function __construct(
        private ResponseFactory $responseFactory,
        private NotificationModuleViewRenderer $viewRenderer,
        private NotificationService $notifications,
        private AuthnHandlerInterface $authnHandler,
        private UrlGenerator $urlGenerator,
        private AlertHandler $alertHandler,
        private NotificationPreferenceService $preferences,
    ) {
    }

    /**
     * HR: Prikazuje paginirani inbox prijavljenog korisnika.
     * EN: Displays the authenticated user's paginated inbox.
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->notifications->tablesReady()) {
            return $this->viewRenderer->render('notification/index', [
                'title' => __('Obavijesti'),
                'migrationMissing' => true,
                'inbox' => ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'page_size' => 30],
                'unreadCount' => 0,
                'markAllPath' => $this->pathFor('notification.read-all', '/notifications/read-all'),
                'deleteAllReadPath' => $this->pathFor(
                    'notification.delete-read',
                    '/notifications/delete-read',
                ),
                'indexPath' => $this->pathFor('notification.index', '/notifications'),
            ], true, 503);
        }

        $query = $request->getQueryParams();
        $pageValue = $query['page'] ?? 1;
        $page = is_numeric($pageValue) ? max(1, (int)$pageValue) : 1;
        $userId = $this->currentUserId();
        $inbox = $this->notifications->inbox($userId, $page);
        foreach ($inbox['items'] as $index => $notification) {
            $uuid = is_scalar($notification['uuid'] ?? null)
            ? trim((string)$notification['uuid'])
            : '';
            $inbox['items'][$index]['open_url'] = $this->pathFor(
                'notification.open',
                '/notifications/open/{uuid}',
                ['uuid' => $uuid],
            );
            $inbox['items'][$index]['delete_url'] = $this->pathFor(
                'notification.delete',
                '/notifications/delete/{uuid}',
                ['uuid' => $uuid],
            );
        }

        return $this->viewRenderer->render('notification/index', [
            'title' => __('Obavijesti'),
            'migrationMissing' => false,
            'inbox' => $inbox,
            'unreadCount' => $this->notifications->unreadCount($userId),
            'markAllPath' => $this->pathFor('notification.read-all', '/notifications/read-all'),
            'deleteAllReadPath' => $this->pathFor(
                'notification.delete-read',
                '/notifications/delete-read',
            ),
            'indexPath' => $this->pathFor('notification.index', '/notifications'),
        ]);
    }

    /**
     * HR: Označava poruku pročitanom i sigurno slijedi njezinu lokalnu poveznicu.
     * EN: Marks a notification as read and safely follows its local link.
     */
    public function open(string $uuid): ResponseInterface
    {
        $notification = $this->notifications->markRead($this->currentUserId(), $uuid);
        $fallback = $this->pathFor('notification.index', '/notifications');
        if (!is_array($notification)) {
            return $this->responseFactory->redirect($fallback);
        }

        $link = is_scalar($notification['link_url'] ?? null)
        ? trim((string)$notification['link_url'])
        : '';

        return $this->responseFactory->redirect($this->safeLocalLink($link, $fallback));
    }

    /**
     * HR: Označava cijeli korisnikov inbox pročitanim.
     * EN: Marks the user's complete inbox as read.
     */
    public function markAllRead(): ResponseInterface
    {
        $this->notifications->markAllRead($this->currentUserId());
        $this->alertHandler->add(new Alert(
            __('Sve obavijesti označene su pročitanima.'),
            AlertLevelEnum::Success,
        ));

        return $this->responseFactory->redirect(
            $this->pathFor('notification.index', '/notifications'),
        );
    }

    /**
     * HR: Uklanja jednu pročitanu obavijest ako pripada prijavljenom korisniku.
     * EN: Removes one read notification when it belongs to the authenticated user.
     */
    public function deleteRead(string $uuid): ResponseInterface
    {
        $deleted = $this->notifications->deleteRead($this->currentUserId(), $uuid);
        $this->alertHandler->add(new Alert(
            $deleted
                ? __('Pročitana obavijest je uklonjena.')
                : __('Ukloniti se može samo postojeća pročitana obavijest.'),
            $deleted ? AlertLevelEnum::Success : AlertLevelEnum::Warning,
        ));

        return $this->responseFactory->redirect(
            $this->pathFor('notification.index', '/notifications'),
        );
    }

    /**
     * HR: Uklanja sve pročitane obavijesti prijavljenog korisnika.
     * EN: Removes all read notifications owned by the authenticated user.
     */
    public function deleteAllRead(): ResponseInterface
    {
        $deleted = $this->notifications->deleteAllRead($this->currentUserId());
        $this->alertHandler->add(new Alert(
            $deleted > 0
                ? sprintf(__('Uklonjene pročitane obavijesti: %d'), $deleted)
                : __('Nema pročitanih obavijesti za uklanjanje.'),
            $deleted > 0 ? AlertLevelEnum::Success : AlertLevelEnum::Info,
        ));

        return $this->responseFactory->redirect(
            $this->pathFor('notification.index', '/notifications'),
        );
    }

    /**
     * HR: Sprema osobnu privolu za e-mail kopije obavijesti i vraća korisnika
     *     na postojeći ekran profila.
     * EN: Stores the personal opt-in for e-mail notification copies and returns
     *     the user to the existing profile screen.
     */
    public function savePreferences(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $emailValue = is_array($body) ? ($body['email_enabled'] ?? null) : null;
        $emailEnabled = is_scalar($emailValue)
        && in_array(
            strtolower(trim((string)$emailValue)),
            ['1', 'true', 'yes', 'on'],
            true,
        );

        $this->preferences->saveEmailEnabled($this->currentUserId(), $emailEnabled);
        $this->alertHandler->add(new Alert(
            __('Postavke obavijesti su spremljene.'),
            AlertLevelEnum::Success,
        ));

        return $this->responseFactory->redirect(
            $this->pathFor('auth.account.profile', '/auth/account/profile'),
        );
    }

    /**
     * HR: Vraća ID prijavljenog korisnika iz auth session payloada.
     * EN: Returns the authenticated user's ID from the auth session payload.
     */
    private function currentUserId(): int
    {
        $user = $this->authnHandler->userData();
        $id = is_array($user) ? $user['id'] ?? 0 : 0;

        return is_numeric($id) ? (int)$id : 0;
    }

    /**
     * HR: Generira named rutu ili fallback putanju.
     * EN: Generates a named route or a fallback path.
     *
     * @param array<string, scalar> $params
     */
    private function pathFor(string $routeName, string $fallback, array $params = []): string
    {
        if ($this->urlGenerator->namedRouteExists($routeName)) {
            return $this->urlGenerator->getPathFor($routeName, $params);
        }

        foreach ($params as $key => $value) {
            $fallback = str_replace('{' . $key . '}', rawurlencode((string)$value), $fallback);
        }

        return rtrim($this->urlGenerator->getBasePath(), '/') . $fallback;
    }

    /**
     * HR: Dopušta samo lokalnu apsolutnu putanju i time sprječava open redirect.
     * EN: Allows only a local absolute path and thereby prevents open redirects.
     */
    private function safeLocalLink(string $link, string $fallback): string
    {
        return $link !== '' && str_starts_with($link, '/') && !str_starts_with($link, '//')
        ? $link
        : $fallback;
    }
}
