<?php
/**
 * @var array $user
 * @var string $csrfToken Токен CSRF
 * @var array $errors
 */
$isNew = empty($user['id']);
?>

<h1 class="mb-3"><?= $isNew ? 'Создание пользователя' : 'Редактирование пользователя' ?></h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <?= implode('<br>', $errors) ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= $isNew ? url('/dashboard/users') : url('/dashboard/users/' . $user['id']) ?>">
    <input type="hidden" name="<?= $_ENV['CSRF_TOKEN_NAME'] ?? '_csrf_token' ?>" value="<?= $csrfToken ?>">

    <div class="mb-3">
        <label for="email" class="form-label">Почта</label>
        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="telegram_user_id" class="form-label">ID пользователя Telegram</label>
        <input type="text" class="form-control" id="telegram_user_id" name="telegram_user_id" value="<?= htmlspecialchars($user['telegram_user_id'] ?? '') ?>">
    </div>
    <button type="submit" class="btn btn-outline-success"><?= $isNew ? 'Создать' : 'Обновить' ?></button>
</form>
