<?php
/** @var array $item */
/** @var string $file */
?>

<h1>Лог: <?= htmlspecialchars($file) ?> (строка <?= (int)($item['line_no'] ?? 0) ?>)</h1>

<div class="mb-3">
  <a href="<?= url('/dashboard/logs') . '?file=' . rawurlencode((string)$file) ?>" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left"></i> К списку логов
  </a>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-secondary small">Время</div>
        <div class="fw-bold"><?= htmlspecialchars((string)($item['datetime'] ?? '')) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-secondary small">Уровень</div>
        <?php
          $lvl = strtoupper((string)($item['level_name'] ?? ''));
          $map = [
            'DEBUG' => 'bg-secondary',
            'INFO' => 'bg-primary',
            'NOTICE' => 'bg-info text-dark',
            'WARNING' => 'bg-warning text-dark',
            'ERROR' => 'bg-danger',
            'CRITICAL' => 'bg-danger',
            'ALERT' => 'bg-danger',
            'EMERGENCY' => 'bg-danger',
          ];
          $cls = $map[$lvl] ?? 'bg-light text-dark';
        ?>
        <span class="badge <?= $cls ?>"><?= htmlspecialchars($lvl) ?></span>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-secondary small">Канал</div>
        <div class="fw-bold"><?= htmlspecialchars((string)($item['channel'] ?? '')) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-secondary small">ID запроса</div>
        <div class="fw-bold"><?= htmlspecialchars((string)($item['request_id'] ?? '')) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header">Сообщение</div>
  <div class="card-body">
    <?= nl2br(htmlspecialchars((string)($item['message'] ?? ''))) ?>
  </div>
  <?php if (!empty($item['context_exception_class']) || !empty($item['context_exception_message'])): ?>
    <div class="card-footer text-danger">
      <div><strong><?= htmlspecialchars((string)($item['context_exception_class'] ?? '')) ?></strong></div>
      <div><?= nl2br(htmlspecialchars((string)($item['context_exception_message'] ?? ''))) ?></div>
    </div>
  <?php endif; ?>
  </div>

<div class="card">
  <div class="card-header">Исходные данные</div>
  <div class="card-body">
    <pre class="mb-0"><?php
      $raw = (string)($item['raw'] ?? '');
      $decoded = null;
      if ($raw !== '' && $raw[0] === '{') {
          $decoded = json_decode($raw, true);
      }
      if (is_array($decoded)) {
          echo htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES);
      } else {
          echo htmlspecialchars($raw, ENT_QUOTES);
      }
    ?></pre>
  </div>
  <div class="card-footer text-muted small">
    Файл: <code><?= htmlspecialchars($file) ?></code>, строка: <code><?= (int)($item['line_no'] ?? 0) ?></code>
  </div>
</div>
