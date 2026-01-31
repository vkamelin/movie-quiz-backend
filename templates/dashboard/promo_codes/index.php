<?php
/** @var array $items @var int $total @var int $page @var int $limit @var string|null $status @var int|null $batch_id @var string $q @var array $batches @var string $csrfToken */
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 m-0">Промокоды</h2>
    <div>
        <a class="btn btn-sm btn-outline-primary" href="<?= url('/dashboard/promo-codes/upload') ?>"><i class="bi bi-upload"></i> Загрузить CSV</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('/dashboard/promo-codes/batches') ?>">Батчи</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('/dashboard/promo-codes/issues') ?>">Отчёт</a>
    </div>
    </div>

<form class="card card-body mb-3" method="get">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label">Статус</label>
            <select name="status" class="form-select">
                <option value="">— любой —</option>
                <?php foreach (['available','issued','redeemed','expired','disabled'] as $st): ?>
                    <option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Батч</label>
            <select name="batch_id" class="form-select">
                <option value="">— любой —</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= (int)$b['id'] ?>" <?= ($batch_id === (int)$b['id']) ? 'selected' : '' ?>>#<?= (int)$b['id'] ?> — <?= htmlspecialchars($b['filename'], ENT_QUOTES) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Поиск</label>
            <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES) ?>" placeholder="code...">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label">На странице</label>
            <input type="number" min="20" max="50" class="form-control" name="limit" value="<?= (int)$limit ?>">
        </div>
        <div class="col-6 col-md-1">
            <button class="btn btn-outline-primary w-100" type="submit">Фильтр</button>
        </div>
    </div>
    </form>

<div class="table-responsive">
    <div class="table-responsive">
    <table class="table table-sm table-striped align-middle">
        <thead>
        <tr>
            <th>ID</th>
            <th>Code</th>
            <th>Status</th>
            <th>Batch</th>
            <th>Expires</th>
            <th>Issued at</th>
            <th class="text-end">Действия</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $row): ?>
            <tr>
                <td>#<?= (int)$row['id'] ?></td>
                <td><code><?= htmlspecialchars($row['code'], ENT_QUOTES) ?></code></td>
                <td><?= htmlspecialchars($row['status'], ENT_QUOTES) ?></td>
                <td>#<?= (int)$row['batch_id'] ?></td>
                <td><?= htmlspecialchars((string)$row['expires_at'], ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars((string)$row['issued_at'], ENT_QUOTES) ?></td>
                <td class="text-end">
                    <?php if ($row['status'] === 'available'): ?>
                        <form class="d-inline-flex gap-2" method="post" action="<?= url('/dashboard/promo-codes/' . (int)$row['id'] . '/issue') ?>">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                            <input type="number" required class="form-control form-control-sm" name="telegram_user_id" placeholder="telegram_user_id">
                            <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-check2"></i> Выдать</button>
                        </form>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<?php
$pages = (int)ceil($total / max(1, $limit));
if ($pages < 1) { $pages = 1; }
?>
<nav>
    <ul class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <?php $qs = http_build_query(array_merge($_GET, ['page' => $i])); ?>
            <li class="page-item <?= $i === (int)$page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= htmlspecialchars($qs, ENT_QUOTES) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
