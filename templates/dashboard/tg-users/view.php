<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="mb-0">Пользователь <?= htmlspecialchars($user['username'] ?? $user['user_id']) ?></h1>
    <div>
        <a href="/dashboard/tg-users/<?= urlencode((string)($user['id'] ?? '')) ?>/chat" class="btn btn-outline-primary">
            <i class="bi bi-chat-dots"></i> Чат
        </a>
    </div>
 </div>

<div class="card mb-4">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">ID пользователя</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string)$user['user_id']) ?></dd>
            <dt class="col-sm-3">Логин</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string)$user['username']) ?></dd>
            <dt class="col-sm-3">Имя</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string)$user['first_name']) ?></dd>
            <dt class="col-sm-3">Фамилия</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string)$user['last_name']) ?></dd>
            <dt class="col-sm-3">Язык</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string)$user['language_code']) ?></dd>
            <dt class="col-sm-3">Премиум</dt>
            <dd class="col-sm-9"><?= (int)$user['is_premium'] == 0 ? 'Нет' : 'Да' ?></dd>
            <dt class="col-sm-3">Забанен</dt>
            <dd class="col-sm-9"><?= (int)$user['is_user_banned'] == 0 ? 'Нет' : 'Да' ?></dd>
            <dt class="col-sm-3">Бот забанен</dt>
            <dd class="col-sm-9"><?= (int)$user['is_bot_banned'] == 0 ? 'Нет' : 'Да' ?></dd>
            <dt class="col-sm-3">Подписан</dt>
            <dd class="col-sm-9"><?= (int)$user['is_subscribed'] == 0 ? 'Нет' : 'Да' ?></dd>
            <dt class="col-sm-3">Реферальный код</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string)$user['referral_code']) ?></dd>
            <dt class="col-sm-3">Реферальная ссылка</dt>
            <dd class="col-sm-9">
                <?php $bot = $_ENV['BOT_NAME'] ?? ''; $code = (string)($user['referral_code'] ?? ''); $link = $bot ? ('https://t.me/' . $bot . '?start=' . urlencode('code___' . $code)) : ''; ?>
                <?php if ($link): ?>
                    <a href="<?= htmlspecialchars($link) ?>" target="_blank"><?= htmlspecialchars($link) ?></a>
                <?php else: ?>
                    —
                <?php endif; ?>
            </dd>
            <dt class="col-sm-3">UTM</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string)$user['utm']) ?></dd>
        </dl>
    </div>
</div>

<h2>Последние сообщения</h2>
<div class="table-responsive mb-4">
<table class="table table-center table-striped table-hover mb-0">
    <thead>
    <tr>
        <th>ID</th>
        <th>Метод</th>
        <th>Тип</th>
        <th>Статус</th>
        <th>Обработано</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($messages as $msg): ?>
        <tr>
            <td><?= htmlspecialchars((string)$msg['id']) ?></td>
            <td><?= htmlspecialchars((string)$msg['method']) ?></td>
            <td><?= htmlspecialchars((string)$msg['type']) ?></td>
            <td><?= htmlspecialchars((string)$msg['status']) ?></td>
            <td><?= htmlspecialchars((string)$msg['processed_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h2>Последние обновления</h2>
<div class="table-responsive">
<table class="table table-center table-striped table-hover mb-0">
    <thead>
    <tr>
        <th>ID</th>
        <th>ID обновления</th>
        <th>Тип</th>
        <th>Создано</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($updates as $upd): ?>
        <tr>
            <td><?= htmlspecialchars((string)$upd['id']) ?></td>
            <td><?= htmlspecialchars((string)$upd['update_id']) ?></td>
            <td><?= htmlspecialchars((string)$upd['type']) ?></td>
            <td><?= htmlspecialchars((string)$upd['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<h2>Рефералы</h2>
<div class="table-responsive mb-4">
<table class="table table-center table-striped table-hover mb-0">
    <thead>
    <tr>
        <th>ID приглашённого</th>
        <th>Логин</th>
        <th>Имя</th>
        <th>Фамилия</th>
        <th>Создано</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach (($referrals ?? []) as $ref): ?>
        <tr>
            <td><?= htmlspecialchars((string)$ref['invitee_user_id']) ?></td>
            <td><?= htmlspecialchars((string)($ref['username'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($ref['first_name'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($ref['last_name'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)$ref['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
 </table>
 </div>
