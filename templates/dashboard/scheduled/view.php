<?php
/**
 * Детали рассылки/отложенного сообщения
 * @var array $item
 */
?>

<h1>Рассылка #<?= (int)$item['id'] ?></h1>

<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="fw-bold text-secondary">Выбрано получателей</div>
        <div class="display-6"><?= (int)($item['selected_count'] ?? 0) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="fw-bold text-success">Успешно</div>
        <div class="display-6"><?= (int)($item['success_count'] ?? 0) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="fw-bold text-danger">Ошибки</div>
        <div class="display-6"><?= (int)($item['failed_count'] ?? 0) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="fw-bold text-secondary">Статус</div>
        <div class="h4 mb-0"><span class="badge bg-secondary text-uppercase"><?= htmlspecialchars((string)$item['status']) ?></span></div>
        <div class="small text-muted mt-1">Начало: <?= htmlspecialchars((string)($item['started_at'] ?? '—')) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body">
    <div class="fw-bold mb-2">Содержимое сообщения</div>
    <?php 
      $pl = $payload ?? []; 
      $m = (string)($item['method'] ?? ''); 
      // Helper: map local absolute paths to public URLs if possible
      $toPublicUrl = static function (string $val): ?string {
          if ($val === '') { return null; }
          if (preg_match('~^https?://~i', $val)) { return $val; }
          $publicBase = \App\Helpers\Path::base('public');
          $storageMsgBase = \App\Helpers\Path::base('storage/messages');
          // If the file resides under public/, build URL relative to it
          if (strpos($val, $publicBase) === 0) {
              $rel = ltrim(str_replace(['\\\\','\\'], '/', substr($val, strlen($publicBase))), '/');
              return url('/' . $rel);
          }
          // If the file is in storage/messages, try to expose as /storage/messages/<basename>
          if (strpos($val, $storageMsgBase) === 0) {
              $rel = 'storage/messages/' . basename($val);
              return url('/' . $rel);
          }
          return null;
      };
      $isImageUrl = static function (string $url): bool {
          $q = parse_url($url, PHP_URL_PATH) ?: $url;
          $ext = strtolower(pathinfo((string)$q, PATHINFO_EXTENSION));
          return in_array($ext, ['jpg','jpeg','png','gif','webp'], true);
      };
    ?>
    <div class="mb-2"><span class="text-muted">Метод:</span> <code><?= htmlspecialchars($m) ?></code></div>

    <?php if (isset($pl['text']) && $pl['text'] !== ''): ?>
      <div class="mb-2"><span class="text-muted">Текст:</span></div>
      <div class="border rounded p-2 bg-body-secondary text-body" style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars((string)$pl['text'])) ?></div>
    <?php endif; ?>

    <?php if (isset($pl['caption']) && $pl['caption'] !== ''): ?>
      <div class="mt-3 mb-1"><span class="text-muted">Подпись:</span></div>
      <div class="border rounded p-2 bg-body-secondary text-body" style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars((string)$pl['caption'])) ?></div>
    <?php endif; ?>

    <?php
      $fileKeys = ['photo' => 'Фото', 'video' => 'Видео', 'document' => 'Документ', 'audio' => 'Аудио', 'animation' => 'Анимация', 'voice' => 'Голос', 'video_note' => 'Видеосообщение', 'sticker' => 'Стикер'];
      foreach ($fileKeys as $k => $label):
        if (!empty($pl[$k])): $val = (string)$pl[$k]; $href = $toPublicUrl($val) ?? (preg_match('~^https?://~i',$val) ? $val : null);
    ?>
      <div class="mt-2">
        <div><span class="text-muted"><?= $label ?>:</span>
        <?php if ($href !== null): ?>
          <a href="<?= htmlspecialchars($href) ?>" target="_blank" rel="noreferrer noopener"><?= htmlspecialchars($href) ?></a>
        <?php else: ?>
          <code><?= htmlspecialchars($val) ?></code>
        <?php endif; ?></div>
        <?php if ($href !== null && $isImageUrl($href)): ?>
          <div class="mt-2">
            <img src="<?= htmlspecialchars($href) ?>" alt="preview" style="max-width: 320px; max-height: 320px; object-fit: contain; border: 1px solid #ddd; border-radius: 4px; padding: 2px;">
          </div>
        <?php endif; ?>
      </div>
    <?php endif; endforeach; ?>

    <?php if (!empty($pl['media']) && is_array($pl['media'])): ?>
      <div class="mt-3 mb-1"><span class="text-muted">Медиагруппа:</span></div>
      <ul class="mb-0">
        <?php foreach ($pl['media'] as $idx => $itemMedia): $itemMedia = is_array($itemMedia) ? $itemMedia : []; ?>
          <li>
            <span class="text-muted">Тип:</span> <code><?= htmlspecialchars((string)($itemMedia['type'] ?? '')) ?></code>
            <?php if (!empty($itemMedia['media'])): $mv = (string)$itemMedia['media']; ?>
              — <?php if (preg_match('~^https?://~', $mv)): ?>
                <a href="<?= htmlspecialchars($mv) ?>" target="_blank" rel="noreferrer noopener"><?= htmlspecialchars($mv) ?></a>
              <?php else: ?>
                <code><?= htmlspecialchars($mv) ?></code>
              <?php endif; ?>
            <?php endif; ?>
            <?php if (!empty($itemMedia['caption'])): ?>
              <div class="small text-muted">Подпись:</div>
              <div class="border rounded p-2 mb-2 bg-body-secondary text-body" style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars((string)$itemMedia['caption'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($itemMedia['media'])): $href = $toPublicUrl((string)$itemMedia['media']) ?? (preg_match('~^https?://~i',(string)$itemMedia['media']) ? (string)$itemMedia['media'] : null); if ($href && $isImageUrl($href)): ?>
              <div class="mt-2">
                <img src="<?= htmlspecialchars($href) ?>" alt="preview" style="max-width: 320px; max-height: 320px; object-fit: contain; border: 1px solid #ddd; border-radius: 4px; padding: 2px;">
              </div>
            <?php endif; endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php
      // Generic options to display if present
      $optKeys = ['parse_mode' => 'Parse mode', 'has_spoiler' => 'Содержит спойлер', 'width' => 'Ширина', 'height' => 'Высота', 'duration' => 'Длительность', 'length' => 'Длина', 'title' => 'Заголовок', 'performer' => 'Исполнитель'];
      $shown = [];
      foreach ($optKeys as $ok => $label) {
          if (isset($pl[$ok]) && $pl[$ok] !== '' && $pl[$ok] !== null) {
              $shown[$label] = is_bool($pl[$ok]) ? ($pl[$ok] ? 'yes' : 'no') : (string)$pl[$ok];
          }
      }
    ?>
    <?php if (!empty($shown)): ?>
      <div class="mt-3">
        <?php foreach ($shown as $label => $val): ?>
          <div><span class="text-muted"><?= htmlspecialchars($label) ?>:</span> <code><?= htmlspecialchars($val) ?></code></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="card-footer text-muted small">
    Параметры и вложения отображаются по данным, сохранённым при создании рассылки.
  </div>
  
</div>

<?php
  $selected = (int)($item['selected_count'] ?? 0);
  $succ = (int)($item['success_count'] ?? 0);
  $fail = (int)($item['failed_count'] ?? 0);
  $done = max(0, min(100, $selected > 0 ? (int)round((($succ + $fail) / $selected) * 100) : 0));
?>

<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="fw-bold">Прогресс отправки</div>
      <div class="text-muted small"><?= $succ + $fail ?>/<?= $selected ?> (<?= $done ?>%)</div>
    </div>
    <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $done ?>">
      <div class="progress-bar bg-success" style="width: <?= $selected > 0 ? (100 * $succ / $selected) : 0 ?>%"></div>
      <div class="progress-bar bg-danger" style="width: <?= $selected > 0 ? (100 * $fail / $selected) : 0 ?>%"></div>
    </div>
  </div>
  <div class="card-footer text-muted small">
    Метод: <code><?= htmlspecialchars((string)$item['method']) ?></code>
    · Тип: <code><?= htmlspecialchars((string)$item['type']) ?></code>
    · Приоритет: <code><?= (int)$item['priority'] ?></code>
    · Отправлять после: <code><?= htmlspecialchars((string)$item['send_after']) ?></code>
  </div>
</div>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css">

<div class="table-responsive">
<table id="scheduledMessagesTable" class="table table-center table-striped table-hover" data-scheduled-id="<?= (int)$item['id'] ?>">
  <thead>
    <tr>
      <th>ID</th>
      <th>ID пользователя</th>
      <th>Метод</th>
      <th>Статус</th>
      <th>Ошибка</th>
      <th>Код</th>
      <th>ID сообщения</th>
      <th>Обработано</th>
    </tr>
  </thead>
  <tbody></tbody>
  <tfoot>
    <tr>
      <th>ID</th>
      <th>ID пользователя</th>
      <th>Метод</th>
      <th>Статус</th>
      <th>Ошибка</th>
      <th>Код</th>
      <th>ID сообщения</th>
      <th>Обработано</th>
    </tr>
  </tfoot>
</table>
</div>

<!-- jQuery и DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap5.min.js"></script>

<script src="<?= url('/assets/js/datatable.common.js') ?>"></script>
<script src="<?= url('/assets/js/datatable.scheduled.view.js') ?>"></script>
