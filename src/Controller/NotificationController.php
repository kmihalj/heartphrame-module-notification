<?php

declare(strict_types=1);

namespace AaiEduHr\HeartPhrameModuleNotification\Controller;

use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationModuleViewRenderer;
use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationService;
use HeartPhrame\Alert\Alert;
use HeartPhrame\Alert\AlertHandler;
use HeartPhrame\Authn\AuthnHandlerInterface;
use HeartPhrame\CodeBook\AlertLevelEnum;
use HeartPhrame\Http\ResponseFactory;
use HeartPhrame\Routing\UrlGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function is_array;
use function is_numeric;
use function is_scalar;
use function max;
use function rawurlencode;
use function rtrim;
use function str_starts_with;
use function trim;

/**
 * HR: Poslužuje korisnički inbox i akcije označavanja poruka pročitanima.
 * EN: Serves the user inbox and actions for marking notifications as read.
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
        }

        return $this->viewRenderer->render('notification/index', [
            'title' => __('Obavijesti'),
            'migrationMissing' => false,
            'inbox' => $inbox,
            'unreadCount' => $this->notifications->unreadCount($userId),
            'markAllPath' => $this->pathFor('notification.read-all', '/notifications/read-all'),
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
