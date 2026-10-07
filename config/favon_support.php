<?php

return [
    'route_contexts' => [
        'dashboard' => ['context' => 'place-browse', 'module' => 'browse'],
        'places.show' => ['context' => 'place-profile', 'module' => 'place-profile'],
        'places.suggest.create' => ['context' => 'place-create-step-1', 'module' => 'place-create'],
        'places.suggest.store' => ['context' => 'place-create-step-1', 'module' => 'place-create'],
        'places.drafts.edit' => ['context' => 'place-create-step-2', 'module' => 'place-create'],
        'places.drafts.update' => ['context' => 'place-create-step-2', 'module' => 'place-create'],
        'places.drafts.features.edit' => ['context' => 'place-create-step-3', 'module' => 'place-create'],
        'places.drafts.features.update' => ['context' => 'place-create-step-3', 'module' => 'place-create'],
        'places.drafts.review' => ['context' => 'place-create-step-4', 'module' => 'place-create'],
        'notifications.index' => ['context' => 'notifications', 'module' => 'notifications'],
        'notifications.show' => ['context' => 'notifications', 'module' => 'notifications'],
        'profile.edit' => ['context' => 'account-profile', 'module' => 'account'],
        'security.edit' => ['context' => 'account-security', 'module' => 'account'],
        'appearance.edit' => ['context' => 'account-appearance', 'module' => 'account'],
        'notifications.settings' => ['context' => 'notification-settings', 'module' => 'account'],
        'data-export.show' => ['context' => 'data-export', 'module' => 'account'],
        'account-deletion.show' => ['context' => 'account-deletion', 'module' => 'account'],
        'support.my.index' => ['context' => 'support-tickets', 'module' => 'support'],
        'support.my.show' => ['context' => 'support-tickets', 'module' => 'support'],
    ],
];
