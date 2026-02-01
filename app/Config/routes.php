<?php

declare(strict_types=1);

use App\Controllers\Api\TgAuthController;
use App\Controllers\Api\VkAuthController;
use App\Controllers\Dashboard\AuthController;
use App\Controllers\Dashboard\ChatJoinRequestsController;
use App\Controllers\Dashboard\ChatMembersController;
use App\Controllers\Dashboard\FilesController;
use App\Controllers\Dashboard\HomeController;
use App\Controllers\Dashboard\InvoicesController;
use App\Controllers\Dashboard\LogsController;
use App\Controllers\Dashboard\MessagesController;
use App\Controllers\Dashboard\PanelUsersController;
use App\Controllers\Dashboard\PreCheckoutController;
use App\Controllers\Dashboard\PromoCodesController;
use App\Controllers\Dashboard\ReferralsController;
use App\Controllers\Dashboard\ScheduledController;
use App\Controllers\Dashboard\SessionsController;
use App\Controllers\Dashboard\ShippingQueriesController;
use App\Controllers\Dashboard\SystemController;
use App\Controllers\Dashboard\TokensController;
use App\Controllers\Dashboard\TgGroupsController;
use App\Controllers\Dashboard\TgUsersController;
use App\Controllers\Dashboard\UpdatesController;
use App\Controllers\Dashboard\UtmController;

return [
    'dashboard' => [
        'middleware' => [
            \App\Middleware\SessionMiddleware::class,
            \App\Middleware\CsrfMiddleware::class,
            \App\Middleware\AuthMiddleware::class,
        ],
        'routes' => [
            ['GET', '/login', [AuthController::class, 'showLogin']],
            ['POST', '/login', [AuthController::class, 'login']],
            ['POST', '/logout', [AuthController::class, 'logout']],

            '' => [
                ['GET', '', [HomeController::class, 'index']],
                ['GET', '/messages', [MessagesController::class, 'index']],
                ['POST', '/messages/data', [MessagesController::class, 'data']],
                ['GET', '/messages/create', [MessagesController::class, 'create']],
                ['POST', '/messages/send', [MessagesController::class, 'send']],
                ['POST', '/messages/{id}/resend', [MessagesController::class, 'resend']],
                ['GET', '/messages/{id}/response', [MessagesController::class, 'download']],
                ['GET', '/files', [FilesController::class, 'index']],
                ['POST', '/files/data', [FilesController::class, 'data']],
                ['GET', '/files/create', [FilesController::class, 'create']],
                ['POST', '/files', [FilesController::class, 'store']],
                ['GET', '/files/{id}', [FilesController::class, 'show']],
                ['GET', '/pre-checkout', [PreCheckoutController::class, 'index']],
                ['POST', '/pre-checkout/data', [PreCheckoutController::class, 'data']],
                ['GET', '/shipping', [ShippingQueriesController::class, 'index']],
                ['POST', '/shipping/data', [ShippingQueriesController::class, 'data']],
                ['GET', '/invoices/create', [InvoicesController::class, 'create']],
                ['POST', '/invoices', [InvoicesController::class, 'store']],
                ['GET', '/updates', [UpdatesController::class, 'index']],
                ['POST', '/updates/data', [UpdatesController::class, 'data']],
                ['GET', '/updates/{id}', [UpdatesController::class, 'show']],
                ['POST', '/updates/{id}/reply', [UpdatesController::class, 'reply']],
                ['GET', '/sessions', [SessionsController::class, 'index']],
                ['POST', '/sessions/data', [SessionsController::class, 'data']],
                ['GET', '/tokens', [TokensController::class, 'index']],
                ['POST', '/tokens/data', [TokensController::class, 'data']],
                ['POST', '/tokens/{id}/revoke', [TokensController::class, 'revoke']],
                ['GET', '/tg-users', [TgUsersController::class, 'index']],
                ['POST', '/tg-users/data', [TgUsersController::class, 'data']],
                ['POST', '/tg-users/search', [TgUsersController::class, 'search']],
                ['GET', '/tg-users/{id}', [TgUsersController::class, 'view']],
                ['GET', '/tg-users/{id}/chat', [TgUsersController::class, 'chat']],
                ['GET', '/tg-groups', [TgGroupsController::class, 'index']],
                ['POST', '/tg-groups', [TgGroupsController::class, 'store']],
                ['MAP', ['GET', 'POST'], '/tg-groups/{id}', [TgGroupsController::class, 'view']],
                ['POST', '/tg-groups/{id}/add-user', [TgGroupsController::class, 'addUser']],
                ['POST', '/tg-groups/{id}/remove-user', [TgGroupsController::class, 'removeUser']],
                ['GET', '/join-requests', [ChatJoinRequestsController::class, 'index']],
                ['POST', '/join-requests/data', [ChatJoinRequestsController::class, 'data']],
                ['GET', '/join-requests/{chat_id}/{user_id}', [ChatJoinRequestsController::class, 'view']],
                ['POST', '/join-requests/{chat_id}/{user_id}/approve', [ChatJoinRequestsController::class, 'approve']],
                ['POST', '/join-requests/{chat_id}/{user_id}/decline', [ChatJoinRequestsController::class, 'decline']],
                ['GET', '/chat-members', [ChatMembersController::class, 'index']],
                ['POST', '/chat-members/data', [ChatMembersController::class, 'data']],
                ['GET', '/users', [PanelUsersController::class, 'index']],
                ['POST', '/users/data', [PanelUsersController::class, 'data']],
                ['GET', '/users/create', [PanelUsersController::class, 'create']],
                ['POST', '/users', [PanelUsersController::class, 'store']],
                ['GET', '/users/{id}/edit', [PanelUsersController::class, 'edit']],
                ['POST', '/users/{id}', [PanelUsersController::class, 'update']],
                ['GET', '/scheduled', [ScheduledController::class, 'index']],
                ['POST', '/scheduled/data', [ScheduledController::class, 'data']],
                ['GET', '/scheduled/{id:\d+}', [ScheduledController::class, 'show']],
                ['POST', '/scheduled/{id:\d+}/messages', [ScheduledController::class, 'messages']],
                ['GET', '/scheduled/{id}/edit', [ScheduledController::class, 'edit']],
                ['POST', '/scheduled/{id}/update', [ScheduledController::class, 'update']],
                ['POST', '/scheduled/{id}/cancel', [ScheduledController::class, 'cancel']],
                ['POST', '/scheduled/{id}/send-now', [ScheduledController::class, 'sendNow']],
                ['POST', '/scheduled/{id}/delete', [ScheduledController::class, 'delete']],
                ['GET', '/system', [SystemController::class, 'index']],
                ['GET', '/logs', [LogsController::class, 'index']],
                ['POST', '/logs/files', [LogsController::class, 'files']],
                ['POST', '/logs/data', [LogsController::class, 'data']],
                ['GET', '/logs/view', [LogsController::class, 'show']],
                ['GET', '/utm', [UtmController::class, 'index']],
                ['POST', '/utm', [UtmController::class, 'index']],
                ['GET', '/promo-codes', [PromoCodesController::class, 'index']],
                ['GET', '/promo-codes/upload', [PromoCodesController::class, 'upload']],
                ['POST', '/promo-codes/upload', [PromoCodesController::class, 'uploadHandle']],
                ['POST', '/promo-codes/{id}/issue', [PromoCodesController::class, 'issue']],
                ['GET', '/promo-codes/issues', [PromoCodesController::class, 'issues']],
                ['GET', '/promo-codes/batches', [PromoCodesController::class, 'batches']],
                ['GET', '/promo-codes/issues/export', [PromoCodesController::class, 'exportIssuesCsv']],
                ['GET', '/referrals', [ReferralsController::class, 'index']],
                ['POST', '/referrals/data', [ReferralsController::class, 'data']],
                ['POST', '/referrals/grouped', [ReferralsController::class, 'grouped']],
            ],
        ],
    ],

    'api' => [
        'routes' => [
            'vk' => [
                'middleware' => [
                    \App\Middleware\JwtMiddleware::class,
                    \App\Middleware\RateLimitMiddleware::class,
                ],
                'routes' => [
                    ['POST', '/auth', VkAuthController::class],
                ],
            ],
            'tg' => [
                'middleware' => [
                    \App\Middleware\TelegramInitDataMiddleware::class,
                ],
                'routes' => [
                    ['POST', '/auth', TgAuthController::class],
                    'protected' => [
                        'middleware' => [
                            \App\Middleware\JwtMiddleware::class,
                            \App\Middleware\RateLimitMiddleware::class,
                        ],
                        'routes' => [

                        ],
                    ],
                ],
            ],
        ],
    ],
];