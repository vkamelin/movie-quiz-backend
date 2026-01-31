<?php
/**
 * Dashboard view
 *
 * @var int        $pendingMessages
 * @var int        $processingMessages
 * @var int        $scheduledMessages
 * @var int        $lastHourSuccess
 * @var int        $lastHourFailed
 * @var float      $lastHourSuccessShare
 * @var float      $lastHourFailedShare
 * @var int        $distinctUsers24h
 * @var int        $activeSessions24h
 * @var array      $lastErrors
 * @var array      $chartLabels
 * @var array      $chartSuccess
 * @var array      $chartFailed
 * @var array{db: bool, redis: bool, worker: bool, status: string} $health
 * @var array|null $queueSizes
 * @var int|null   $sendSpeed
 */
?>

<div class="row g-3">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0 kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-secondary-emphasis small text-uppercase">Ожидают</div>
                    <div class="display-6 fw-semibold mb-0"><?= $pendingMessages ?></div>
                </div>
                <i class="bi bi-hourglass-split kpi-icon text-primary"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0 kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-secondary-emphasis small text-uppercase">Обрабатываются</div>
                    <div class="display-6 fw-semibold mb-0"><?= $processingMessages ?></div>
                </div>
                <i class="bi bi-gear kpi-icon text-info"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0 kpi-card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-secondary-emphasis small text-uppercase">К отправке</div>
                    <div class="display-6 fw-semibold mb-0"><?= $scheduledMessages ?></div>
                </div>
                <i class="bi bi-send kpi-icon text-success"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-secondary-emphasis small text-uppercase">Состояние</div>
                    <i class="bi bi-heart-pulse text-danger"></i>
                </div>
                <div class="mt-2 d-flex flex-wrap gap-2">
                    <?= $health['db'] ? '<span class="badge text-bg-success">БД OK</span>' : '<span class="badge text-bg-danger">БД СБОЙ</span>' ?>
                    <?= $health['redis'] ? '<span class="badge text-bg-success">Redis OK</span>' : '<span class="badge text-bg-danger">Redis СБОЙ</span>' ?>
                    <?= $health['worker'] ? '<span class="badge text-bg-success">Воркер OK</span>' : '<span class="badge text-bg-danger">Воркер СБОЙ</span>' ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-secondary-emphasis small text-uppercase">За 1 час</div>
                    <i class="bi bi-graph-up text-success"></i>
                </div>
                <div class="mt-1">
                    <div class="h5 mb-0">Успешные / Неудачные</div>
                    <div class="fs-4 fw-semibold">
                        <span class="text-success"><?= $lastHourSuccess ?></span>
                        /
                        <span class="text-danger"><?= $lastHourFailed ?></span>
                    </div>
                    <div class="text-secondary small">
                        <?= $lastHourSuccessShare ?>% / <?= $lastHourFailedShare ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-secondary-emphasis small text-uppercase">Уникальные пользователи 24ч</div>
                    <div class="display-6 fw-semibold mb-0"><?= $distinctUsers24h ?></div>
                </div>
                <i class="bi bi-people text-primary"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-secondary-emphasis small text-uppercase">Активные сессии 24ч</div>
                    <div class="display-6 fw-semibold mb-0"><?= $activeSessions24h ?></div>
                </div>
                <i class="bi bi-person-check text-warning"></i>
            </div>
        </div>
    </div>
    <?php if (!empty($queueSizes)): ?>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-secondary-emphasis small text-uppercase">Очереди</div>
                        <i class="bi bi-collection text-secondary"></i>
                    </div>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <span class="badge text-bg-primary">p2: <?= $queueSizes['p2'] ?></span>
                        <span class="badge text-bg-info">p1: <?= $queueSizes['p1'] ?></span>
                        <span class="badge text-bg-secondary">p0: <?= $queueSizes['p0'] ?></span>
                    </div>
                    <div class="text-secondary small mt-2">DLQ: <?= $queueSizes['dlq'] ?>; RPS: <?= $sendSpeed ?></div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-activity text-primary"></i>
                    <span class="fw-semibold">Динамика отправок по времени</span>
                </div>
            </div>
            <div class="card-body">
                <canvas id="statusChart"
                        data-labels='<?= htmlspecialchars(json_encode($chartLabels), ENT_QUOTES) ?>'
                        data-success='<?= htmlspecialchars(json_encode($chartSuccess), ENT_QUOTES) ?>'
                        data-failed='<?= htmlspecialchars(json_encode($chartFailed), ENT_QUOTES) ?>'></canvas>
                <div class="text-secondary small mt-2">Зелёный — успешные, красный — ошибки</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-exclamation-triangle text-danger"></i>
            <h4 class="mb-0">Последние ошибки</h4>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                <tr>
                    <th class="text-secondary">ID</th>
                    <th class="text-secondary">Пользователь</th>
                    <th class="text-secondary">Код</th>
                    <th class="text-secondary">Ошибка</th>
                    <th class="text-secondary">Время</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($lastErrors as $err): ?>
                    <tr>
                        <td class="text-body-secondary small"><?= htmlspecialchars((string)$err['id']) ?></td>
                        <td class="text-body-secondary small"><?= htmlspecialchars((string)$err['user_id']) ?></td>
                        <td><span class="badge text-bg-danger-subtle border border-danger-subtle"><?= htmlspecialchars((string)$err['code']) ?></span></td>
                        <td class="text-break"><?= htmlspecialchars((string)$err['error']) ?></td>
                        <td class="text-body-secondary small"><?= htmlspecialchars((string)$err['processed_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= url('/assets/js/dashboard-chart.js') ?>"></script>

