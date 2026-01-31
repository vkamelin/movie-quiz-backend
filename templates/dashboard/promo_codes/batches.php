<?php /** @var array $items */ ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 m-0">Батчи промокодов</h2>
    <div>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('/dashboard/promo-codes') ?>">К промокодам</a>
    </div>
</div>

<div class="table-responsive">
    <div class="table-responsive">
    <table class="table table-sm table-striped align-middle">
        <thead>
        <tr>
            <th>ID</th>
            <th>Filename</th>
            <th>Created by</th>
            <th>Created at</th>
            <th>Total</th>
            <th>Available</th>
            <th>Issued</th>
            <th>Expired</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $b): ?>
            <tr>
                <td>#<?= (int)$b['id'] ?></td>
                <td><?= htmlspecialchars($b['filename'], ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars((string)($b['created_by'] ?? ''), ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars((string)$b['created_at'], ENT_QUOTES) ?></td>
                <td><?= (int)$b['total'] ?></td>
                <td><?= (int)($b['available'] ?? 0) ?></td>
                <td><?= (int)($b['issued'] ?? 0) ?></td>
                <td><?= (int)($b['expired'] ?? 0) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    </div>
