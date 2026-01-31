<?php
/** @var array $request */
/** @var string $csrfToken */
?>
<h1>Запрос на вступление <?= htmlspecialchars((string)$request['user_id']) ?></h1>

<div class="card mb-4">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">ID чата</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string)$request['chat_id']) ?></dd>
            <dt class="col-sm-3">ID пользователя</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string)$request['user_id']) ?></dd>
            <dt class="col-sm-3">Логин</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string)$request['username']) ?></dd>
            <dt class="col-sm-3">Имя</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string)$request['first_name']) ?></dd>
            <dt class="col-sm-3">Фамилия</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string)$request['last_name']) ?></dd>
            <dt class="col-sm-3">Био</dt>
            <dd class="col-sm-9"><pre class="mb-0"><?= htmlspecialchars((string)$request['bio']) ?></pre></dd>
            <dt class="col-sm-3">Ссылка</dt>
            <dd class="col-sm-9"><pre class="mb-0"><?= htmlspecialchars((string)$request['invite_link']) ?></pre></dd>
            <dt class="col-sm-3">Дата решения</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string)$request['requested_at']) ?></dd>
            <dt class="col-sm-3">Статус</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string)$request['status']) ?></dd>
            <dt class="col-sm-3">Дата решения</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string)$request['decided_at']) ?></dd>
            <dt class="col-sm-3">Решен</dt>
            <dd class="col-sm-9"><?= htmlspecialchars((string)$request['decided_by']) ?></dd>
        </dl>
    </div>
</div>

<form method="post" action="/dashboard/join-requests/<?= urlencode((string)$request['chat_id']) ?>/<?= urlencode((string)$request['user_id']) ?>/approve" class="d-inline">
    <input type="hidden" name="<?= env('CSRF_TOKEN_NAME', '_csrf_token') ?>" value="<?= $csrfToken ?>">
    <button type="submit" class="btn btn-outline-success">Одобрить</button>
</form>
<form method="post" action="/dashboard/join-requests/<?= urlencode((string)$request['chat_id']) ?>/<?= urlencode((string)$request['user_id']) ?>/decline" class="d-inline ms-2">
    <input type="hidden" name="<?= env('CSRF_TOKEN_NAME', '_csrf_token') ?>" value="<?= $csrfToken ?>">
    <button type="submit" class="btn btn-outline-danger">Отклонить</button>
</form>
