<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleNotification\Controller\NotificationController;
use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationEmailBridge;
use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationModuleViewRenderer;
use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationNavigationProvider;
use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationService;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use HeartPhrame\Alert\AlertHandler;
use HeartPhrame\Authn\AuthnHandlerInterface;
use HeartPhrame\Config\ConfigInterface;
use HeartPhrame\Http\ResponseFactory;
use HeartPhrame\Routing\UrlGenerator;
use Psr\Container\ContainerInterface;

return [
    NotificationEmailBridge::class => static fn(ContainerInterface $container): NotificationEmailBridge =>
        new NotificationEmailBridge($container),

    NotificationService::class => static fn(ContainerInterface $container): NotificationService =>
        new NotificationService(
            $container->get(Database::class),
            $container->get(NotificationEmailBridge::class),
        ),

    NotificationNavigationProvider::class =>
        static fn(ContainerInterface $container): NotificationNavigationProvider =>
            new NotificationNavigationProvider(
                $container->get(NotificationService::class),
                $container->get(AuthnHandlerInterface::class),
                $container->get(UrlGenerator::class),
            ),

    NotificationModuleViewRenderer::class =>
        static fn(ContainerInterface $container): NotificationModuleViewRenderer =>
            new NotificationModuleViewRenderer(
                $container->get(ResponseFactory::class),
                $container->get(ConfigInterface::class),
            ),

    NotificationController::class => static fn(ContainerInterface $container): NotificationController =>
        new NotificationController(
            $container->get(ResponseFactory::class),
            $container->get(NotificationModuleViewRenderer::class),
            $container->get(NotificationService::class),
            $container->get(AuthnHandlerInterface::class),
            $container->get(UrlGenerator::class),
            $container->get(AlertHandler::class),
        ),
];
