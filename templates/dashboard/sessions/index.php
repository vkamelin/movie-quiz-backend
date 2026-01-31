<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css">

<!-- Buttons CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.3/css/buttons.bootstrap5.min.css">

<h1>Сессии</h1>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <input type="text" name="state" value="<?= htmlspecialchars($_GET['state'] ?? '') ?>" class="form-control" placeholder="состояние">
    </div>
    <div class="col-auto">
        <input type="date" name="updated_from" value="<?= htmlspecialchars($_GET['updated_from'] ?? '') ?>" class="form-control" placeholder="дата от">
    </div>
    <div class="col-auto">
        <input type="date" name="updated_to" value="<?= htmlspecialchars($_GET['updated_to'] ?? '') ?>" class="form-control" placeholder="дата до">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-primary">Фильтр</button>
    </div>
    <?php // сохраняем совместимость: если в URL всё ещё приходит period, разложим его на два скрытых поля для ссылки/перезагрузки ?>
    <?php if (!empty($_GET['period']) && (empty($_GET['updated_from']) && empty($_GET['updated_to']))) : ?>
        <?php [$pf, $pt] = array_pad(explode(',', (string)$_GET['period']), 2, ''); ?>
        <input type="hidden" name="updated_from" value="<?= htmlspecialchars($pf) ?>">
        <input type="hidden" name="updated_to" value="<?= htmlspecialchars($pt) ?>">
    <?php endif; ?>
</form>

<div class="table-responsive">
<table id="sessionsTable" class="table table-center table-striped table-hover">
    <thead>
    <tr>
        <th>ID пользователя</th>
        <th>Состояние</th>
        <th>Создана</th>
        <th>Обновлена</th>
    </tr>
    </thead>
    <tbody></tbody>
    <tfoot>
    <tr>
        <th>ID пользователя</th>
        <th>Состояние</th>
        <th>Создана</th>
        <th>Обновлена</th>
    </tr>
    </tfoot>
</table>
</div>

<!-- jQuery и DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap5.min.js"></script>

<!-- Buttons core и HTML5-экспорт -->
<script src="https://cdn.datatables.net/buttons/2.3.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.3/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.3/js/buttons.bootstrap5.min.js"></script>

<script src="<?= url('/assets/js/datatable.common.js') ?>"></script>
<script src="<?= url('/assets/js/datatable.sessions.js') ?>"></script>
