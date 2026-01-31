<?php
/**
 * View: Просмотр одного обновления Telegram
 *
 * @var array $row      Метаданные (id, update_id, user_id, message_id, type, sent_at, created_at)
 * @var string $rawPretty Форматированный JSON
 * @var array|null $json  Декодированный JSON
 * @var array $summary    Краткое описание ['type' => string, 'info' => array]
 */
?>

<div class="container-fluid">
    <div class="d-flex align-items-center mb-3">
        <h1 class="h4 mb-0">Обновление #<?= (int)$row['id'] ?></h1>
        <span class="badge text-bg-secondary ms-3"><?= htmlspecialchars((string)$row['type']) ?></span>
        <?php if (!empty($row['update_id'])): ?>
            <span class="badge text-bg-light ms-2">Update ID: <?= (int)$row['update_id'] ?></span>
        <?php endif; ?>
        <?php if (!empty($row['message_id'])): ?>
            <span class="badge text-bg-light ms-2">Message ID: <?= (int)$row['message_id'] ?></span>
        <?php endif; ?>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-braces"></i> Исходные данные (JSON)
                </div>
                <div class="card-body">
                    <pre class="mb-0" style="white-space: pre-wrap; word-wrap: break-word;">
<?= htmlspecialchars($rawPretty, ENT_QUOTES | ENT_SUBSTITUTE) ?>
                    </pre>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-list-check"></i> Разбор
                </div>
                <div class="card-body">
                    <?php if (!empty($summary['info'])): ?>
                        <dl class="row mb-0">
                            <?php foreach ($summary['info'] as $label => $value): ?>
                                <dt class="col-sm-4 text-muted"><?= htmlspecialchars((string)$label) ?></dt>
                                <dd class="col-sm-8">
                                    <?php if (is_array($value)): ?>
                                        <code><?= htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_SUBSTITUTE) ?></code>
                                    <?php else: ?>
                                        <?= nl2br(htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE)) ?>
                                    <?php endif; ?>
                                </dd>
                            <?php endforeach; ?>
                        </dl>
                    <?php else: ?>
                        <div class="text-muted">Не удалось определить тип события или данные отсутствуют.</div>
                    <?php endif; ?>
                </div>
                <div class="card-footer small text-muted">
                    Создано: <?= htmlspecialchars((string)$row['created_at']) ?>
                    <?php if (!empty($row['sent_at'])): ?>
                        · Отправлено: <?= htmlspecialchars((string)$row['sent_at']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($replyTarget) && is_array($replyTarget)): ?>
    <div class="row g-3 mt-2" id="reply">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-reply"></i> Ответить на сообщение
                    <span class="text-muted small">(чат: <?= (int)$replyTarget['chat_id'] ?>, сообщение: <?= (int)$replyTarget['message_id'] ?>)</span>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= url('/dashboard/updates/' . (int)$row['id'] . '/reply') ?>">
                        <input type="hidden" name="<?= $_ENV['CSRF_TOKEN_NAME'] ?? '_csrf_token' ?>" value="<?= $csrfToken ?>">
                        <div class="mb-3">
                            <label for="reply-text" class="form-label">Текст ответа</label>
                            <textarea id="reply-text" name="text" class="form-control" rows="3" required placeholder="Введите текст..."></textarea>
                            <div class="form-text">Отправляется как ответ (reply_to_message_id).</div>
                        </div>
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-send"></i> Отправить ответ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
