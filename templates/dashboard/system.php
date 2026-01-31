<?php
/**
 * System view
 *
 * @var array{db: bool, redis: bool, worker: bool, status: string} $health
 * @var array $env
 * @var array $workerCommands
 * @var array|null $queueSizes
 * @var int|null $sendSpeed
 */
?>

<div class="row">
    <div class="col-md-3">
        <div class="h-100 p-3 text-bg-dark rounded-3">
            <h4>Состояние</h4>
            <p class="lead">
                <?= $health['db'] ? 'БД ОК' : 'БД СБОЙ' ?>,
                <?= $health['redis'] ? 'Redis ОК' : 'Redis СБОЙ' ?>,
                <?= $health['worker'] ? 'Воркер ОК' : 'Воркер СБОЙ' ?>
            </p>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <h4>Env-переменные</h4>
        <div class="table-responsive">
        <table class="table table-dark table-striped">
            <tbody>
            <?php foreach ($env as $name => $value): ?>
                <tr>
                    <th><?= htmlspecialchars($name) ?></th>
                    <td><input type="text" readonly class="form-control-plaintext text-white" value="<?= htmlspecialchars((string)$value) ?>"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <div class="col-md-6">
        <h4>Конфигурация</h4>
        <div class="bg-dark text-white p-2 rounded-3" style="max-height: 400px; overflow:auto">
            <pre class="m-0"><code><?php
            $print = function($arr, $indent = 0) use (&$print) {
                foreach ($arr as $k => $v) {
                    echo str_repeat('  ', $indent) . htmlspecialchars((string)$k) . ': ';
                    if (is_array($v)) {
                        echo "\n";
                        $print($v, $indent + 1);
                    } else {
                        echo htmlspecialchars(var_export($v, true)) . "\n";
                    }
                }
            };
            $print($config);
            ?></code></pre>
        </div>
        <h4>Воркеры</h4>
        <h6>Проверка статуса</h6>
        <pre class="bg-dark text-white p-2 rounded-3"><code><?= implode("\n", $workerCommands['status']) ?></code></pre>
        <h6>Перезапуск</h6>
        <pre class="bg-dark text-white p-2 rounded-3"><code><?= implode("\n", $workerCommands['restart']) ?></code></pre>
    </div>
</div>

<?php if (!empty($queueSizes)): ?>
<div class="row mt-3">
    <div class="col-md-6">
        <h4>Очереди</h4>
        <p class="lead">p2: <?= $queueSizes['p2'] ?>, p1: <?= $queueSizes['p1'] ?>, p0: <?= $queueSizes['p0'] ?></p>
        <small>DLQ: <?= $queueSizes['dlq'] ?>; RPS: <?= $sendSpeed ?></small>
    </div>
</div>
<?php endif; ?>
