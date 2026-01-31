<?php

$menu = [
    [
        'url'   => '/dashboard',
        'title' => 'Главная',
        'icon'  => 'bi bi-speedometer2',
    ],
    [
        'url'      => '/dashboard/updates',
        'title'    => 'Telegram',
        'icon'     => 'bi bi-telegram',
        'children' => [
            [
                'url'   => '/dashboard/updates',
                'title' => 'Входящие',
                'icon'  => 'bi bi-inbox',
            ],
            [
                'url'   => '/dashboard/messages',
                'title' => 'Исходящие',
                'icon'  => 'bi bi-send',
            ],
            [
                'url'   => '/dashboard/referrals',
                'title' => 'Рефералы',
                'icon'  => 'bi bi-link-45deg',
            ],
            [
                'url'   => '/dashboard/messages/create',
                'title' => 'Отправить сообщение',
                'icon'  => 'bi bi-envelope',
            ],
            [
                'url'   => '/dashboard/scheduled',
                'title' => 'Расписание сообщений',
                'icon'  => 'bi bi-clock',
            ],
        ],
    ],
    [
        'url'      => '/dashboard/tg-users',
        'title'    => 'Пользователи',
        'icon'     => 'bi bi-people',
        'children' => [
            [
                'url'   => '/dashboard/tg-users',
                'title' => 'Пользователи',
                'icon'  => 'bi bi-people',
            ],
            [
                'url'   => '/dashboard/tg-groups',
                'title' => 'Группы',
                'icon'  => 'bi bi-people',
            ],
            [
                'url'   => '/dashboard/join-requests',
                'title' => 'Запросы на вступление',
                'icon'  => 'bi bi-person-plus',
            ],
            [
                'url'   => '/dashboard/chat-members',
                'title' => 'Подписчики',
                'icon'  => 'bi bi-people',
            ],
            [
                'url'   => '/dashboard/utm',
                'title' => 'UTM',
                'icon'  => 'bi bi-graph-up',
            ]
        ],
    ],
    [
        'url'      => '/dashboard/pre-checkout',
        'title'    => 'Платежи',
        'icon'     => 'bi bi-receipt',
        'children' => [
            [
                'url'   => '/dashboard/pre-checkout',
                'title' => 'Пред. заказ',
                'icon'  => 'bi bi-receipt',
            ],
            [
                'url'   => '/dashboard/shipping',
                'title' => 'Доставка',
                'icon'  => 'bi bi-truck',
            ],
            [
                'url'   => '/dashboard/invoices/create',
                'title' => 'Счета',
                'icon'  => 'bi bi-file-earmark-text',
            ],
        ],
    ],
    [
        'url'      => '/dashboard/sessions',
        'title'    => 'Администрирование',
        'icon'     => 'bi bi-gear',
        'children' => [
            [
                'url'   => '/dashboard/sessions',
                'title' => 'Сессии',
                'icon'  => 'bi bi-clock-history',
            ],
            [
                'url'   => '/dashboard/users',
                'title' => 'Администраторы',
                'icon'  => 'bi bi-person-gear',
            ],
            [
                'url'   => '/dashboard/tokens',
                'title' => 'Токены',
                'icon'  => 'bi bi-key',
            ],
            [
                'url'   => '/dashboard/system',
                'title' => 'Система',
                'icon'  => 'bi bi-gear',
            ],
            [
                'url'   => '/dashboard/logs',
                'title' => 'Логи',
                'icon'  => 'bi bi-journal-text',
            ],
        ],
    ],
];

if (is_dir(__DIR__ . '/dashboard/metrics') || file_exists(__DIR__ . '/dashboard/metrics.php')) {
    foreach ($menu as &$item) {
        if (($item['title'] ?? null) === 'Администрирование') {
            $item['children'][] = [
                'url'   => '/dashboard/metrics',
                'title' => 'Метрики',
                'icon'  => 'bi bi-graph-up',
            ];
            break;
        }
    }
    unset($item);
}

return $menu;
