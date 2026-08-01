<?php

declare(strict_types=1);

/**
 * HR: Notification modul oglašava samo scopeove vlastitog korisničkog inboxa.
 * EN: The Notification module advertises only scopes for its own user inbox.
 */
return [
    'module' => 'notification',
    'resources' => [
        'notifications' => [
            'label' => [
                'hr' => 'Obavijesti',
                'en' => 'Notifications',
            ],
            'description' => [
                'hr' => 'Čitanje i upravljanje obavijestima vlasnika API ključa.',
                'en' => 'Read and manage notifications owned by the API-key owner.',
            ],
            'scopes' => [
                'notifications:read' => [
                    'label' => ['hr' => 'Čitanje', 'en' => 'Read'],
                    'description' => [
                        'hr' => 'Dohvat vlastitog inboxa i pojedine obavijesti.',
                        'en' => 'Read the owner inbox and individual notifications.',
                    ],
                ],
                'notifications:write' => [
                    'label' => ['hr' => 'Upravljanje', 'en' => 'Manage'],
                    'description' => [
                        'hr' => 'Označavanje pročitanog i uklanjanje vlastitih pročitanih obavijesti.',
                        'en' => 'Change read state and remove owned read notifications.',
                    ],
                ],
            ],
        ],
    ],
];
