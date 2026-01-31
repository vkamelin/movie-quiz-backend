<?php
/** @var array $errors */
/** @var array $data */
/** @var string $csrfToken */
?>
<h1 class="mb-3">Загрузка файла</h1>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?>
            <div><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<form method="post" action="<?= url('/dashboard/files') ?>" enctype="multipart/form-data">
    <input type="hidden" name="<?= $_ENV['CSRF_TOKEN_NAME'] ?? '_csrf_token' ?>" value="<?= $csrfToken ?>">
    <div class="mb-3">
        <label for="fileType" class="form-label">Тип</label>
        <?php $curType = $data['type'] ?? 'photo'; ?>
        <select class="form-select" name="type" id="fileType">
            <?php foreach (['photo' => 'Фото', 'document' => 'Документ', 'audio' => 'Аудио', 'video' => 'Видео', 'voice' => 'Голос'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= $curType === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="fileInput" class="form-label">Файл</label>
        <input class="form-control" type="file" name="file" id="fileInput">
    </div>
    <button type="submit" class="btn btn-outline-primary">Загрузить</button>
</form>
