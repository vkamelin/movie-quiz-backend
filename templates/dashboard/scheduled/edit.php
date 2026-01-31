<?php
/** @var array $item */
/** @var array $errors */
/** @var string $csrfToken */
?>
<h1 class="mb-3">Редактировать сообщение</h1>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?>
            <div><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('/dashboard/scheduled/' . urlencode((string)($item['id'] ?? '')) . '/update') ?>">
    <input type="hidden" name="<?= $_ENV['CSRF_TOKEN_NAME'] ?? '_csrf_token' ?>" value="<?= $csrfToken ?>">

    <div class="mb-3">
        <b>ID</b> #<?= htmlspecialchars((string)($item['id'] ?? '')) ?>
    </div>
    <div class="mb-3">
        <label class="form-label">Приоритет</label>
        <input type="number" class="form-control" name="priority" value="<?= htmlspecialchars((string)($item['priority'] ?? 2)) ?>" min="0" max="2">
    </div>
    <?php
    $sendAfterRaw = (string)($item['send_after'] ?? '');
    $sendAfterVal = '';
    if ($sendAfterRaw !== '') {
        // Поддерживаем как "YYYY-MM-DD HH:MM:SS", так и "YYYY-MM-DDTHH:MM[:SS]"
        $normalized = str_replace('T', ' ', substr($sendAfterRaw, 0, 19));
        $ts = strtotime($normalized);
        if ($ts !== false) {
            $sendAfterVal = date('Y-m-d\TH:i', $ts);
        }
    }
    ?>
    <div class="mb-3">
        <label class="form-label">Время отправки</label>
        <input type="datetime-local" class="form-control" name="send_after" value="<?= htmlspecialchars($sendAfterVal) ?>">
    </div>
    <button type="submit" class="btn btn-outline-success">Сохранить</button>
    <a href="<?= url('/dashboard/scheduled') ?>" class="btn btn-outline-secondary ms-2">Отменить</a>
</form>
