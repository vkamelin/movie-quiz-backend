<?php
/** @var string $csrfToken CSRF-токен */
/** @var array $groups Группы */
?>

<h1>Группы пользователей Telegram</h1>

<form method="post" class="mb-3" action="<?= url('/dashboard/tg-groups') ?>">
    <input type="hidden" name="<?= $_ENV['CSRF_TOKEN_NAME'] ?? '_csrf_token' ?>" value="<?= $csrfToken ?>">
    <div class="input-group">
        <input type="text" name="name" class="form-control" placeholder="Имя группы" required>
        <button type="submit" class="btn btn-outline-success">Создать</button>
    </div>
</form>

<div class="table-responsive">
<table class="table table-striped">
    <thead>
    <tr>
        <th>ID</th>
        <th>Название</th>
        <th>Пользователи</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($groups as $g): ?>
        <tr>
            <td><?= $g['id'] ?></td>
            <td><?= htmlspecialchars($g['name']) ?></td>
            <td><?= $g['members'] ?></td>
            <td>
                <a class="btn btn-sm btn-outline-secondary" href="<?= url('/dashboard/tg-groups/' . $g['id']) ?>">
                    Открыть
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
