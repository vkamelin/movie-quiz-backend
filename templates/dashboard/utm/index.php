<?php
/**
 * @var array $utms
 * @var string $from
 * @var string $to
 */
?>

<h1>UTM</h1>

<form class="row" method="post">
    <input type="hidden" name="<?= $_ENV['CSRF_TOKEN_NAME'] ?? '_csrf_token' ?>" value="<?= $csrfToken ?>">
    
    <div class="col-md-3 d-flex justify-content-start align-items-center">
        <label for="from" class="col-sm-2 control-label">От</label>
        <input type="datetime-local" class="form-control" id="from" name="from" value="<?= $from ?>">
    </div>
    <div class="col-md-3 d-flex justify-content-start align-items-center">
        <label for="to" class="col-sm-2 control-label">До</label>
        <input type="datetime-local" class="form-control" id="to" name="to" value="<?= $to ?>">
    </div>
    <div class="col-md-3 d-flex justify-content-start align-items-center">
        <button type="submit" class="btn btn-outline-secondary">Показать</button>
    </div>
</form>

<div class="table-responsive">
<table class="table table-center table-striped table-hover">
    <thead>
    <tr>
        <th>Метки</th>
        <th>Кол-во</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($utms as $utm): ?>
    <tr>
        <td><?= $utm['utm'] ?></td>
        <td><?= $utm['total'] ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<div class="mt-3">
    <strong>Итого:</strong> <?= (int)($grandTotal ?? 0) ?>
    <small class="text-muted">(в минимальных единицах валюты)</small>
    
</div>
