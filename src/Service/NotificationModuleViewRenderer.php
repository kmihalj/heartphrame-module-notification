<?php

declare(strict_types=1);

namespace AaiEduHr\HeartPhrameModuleNotification\Service;

use AaiEduHr\HeartPhrameModuleNotification\ModuleNotification;
use HeartPhrame\Config\ConfigInterface;
use HeartPhrame\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

/**
 * HR: Renderira Notification prikaze uz podršku za aplikacijske overridee.
 * EN: Renders Notification views with host-application override support.
 */
final readonly class NotificationModuleViewRenderer
{
    /**
     * HR: Prima framework response factory i aplikacijsku konfiguraciju viewova.
     * EN: Receives the framework response factory and application view configuration.
     */
    public function __construct(
        private ResponseFactory $responseFactory,
        private ConfigInterface $config,
    ) {
    }

    /**
     * HR: Renderira puni prikaz iz overridea ili iz samog modula.
     * EN: Renders a full view from an override or from the module itself.
     *
     * @param array<string, mixed> $data
     */
    public function render(
        string $view,
        array $data = [],
        null|true|string $layout = true,
        int $status = 200,
    ): ResponseInterface {
        $override = $this->findOverrideView($view);
        if ($override !== null) {
            return $this->responseFactory->view($override, $data, $layout, $status);
        }

        return $this->responseFactory->viewForModule(
            ModuleNotification::PACKAGE_NAME,
            $view,
            $data,
            $layout,
            $status,
        );
    }

    /**
     * HR: Traži kratku i punu aplikacijsku override putanju.
     * EN: Searches the short and fully qualified application override paths.
     */
    private function findOverrideView(string $view): ?string
    {
        $viewsRoot = rtrim($this->config->getAsString('app.views.path') ?? '', '/');
        if ($viewsRoot === '') {
            return null;
        }

        foreach (
            [
                'modules/heartphrame-module-notification/' . $view,
                'modules/aaieduhr/heartphrame-module-notification/' . $view,
            ] as $candidate
        ) {
            if (is_file($viewsRoot . '/' . $candidate . '.php')) {
                return $candidate;
            }
        }

        return null;
    }
}
