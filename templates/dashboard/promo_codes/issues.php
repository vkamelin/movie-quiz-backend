<?php /** @var array $items @var string $from @var string $to @var int|null $telegram_user_id @var int $page @var int $limit @var int $total */ ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 m-0">Отчёт по выдачам</h2>
    <div>
        <?php $qs = http_build_query($_GET); ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('/dashboard/promo-codes') ?>">К промокодам</a>
        <a class="btn btn-sm btn-outline-primary" href="<?= url('/dashboard/promo-codes/issues/export') . '?' . htmlspecialchars($qs, ENT_QUOTES) ?>"><i class="bi bi-download"></i> Экспорт CSV</a>
    </div>
</div>

<form class="card card-body mb-3" method="get">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label">c даты</label>
            <input type="datetime-local" class="form-control" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES) ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">по дату</label>
            <input type="datetime-local" class="form-control" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES) ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">telegram_user_id</label>
            <input type="number" class="form-control" name="telegram_user_id" value="<?= htmlspecialchars((string)($telegram_user_id ?? ''), ENT_QUOTES) ?>">
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
            <th>Issued at</th>
            <th>Code</th>
            <th>telegram_user_id</th>
            <th>issued_by</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $r): ?>
            <tr>
                <td><?= htmlspecialchars((string)$r['issued_at'], ENT_QUOTES) ?></td>
                <td><code><?= htmlspecialchars($r['code'], ENT_QUOTES) ?></code></td>
                <td><?= (int)$r['telegram_user_id'] ?></td>
                <td><?= (int)$r['issued_by'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    </div>

<?php $pages = (int)ceil($total / max(1, $limit)); if ($pages < 1) { $pages = 1; } ?>
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
