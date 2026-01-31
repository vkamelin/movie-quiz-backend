<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css">

<!-- Buttons CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.3/css/buttons.bootstrap5.min.css">

<h1>Запросы на вступление</h1>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="status" class="form-select">
            <option value="">статус</option>
            <option value="pending" <?= isset($_GET['status']) && $_GET['status'] === 'pending' ? 'selected' : '' ?>>
                ожидает
            </option>
            <option value="approved" <?= isset($_GET['status']) && $_GET['status'] === 'approved' ? 'selected' : '' ?>>
                одобрен
            </option>
            <option value="declined" <?= isset($_GET['status']) && $_GET['status'] === 'declined' ? 'selected' : '' ?>>
                отклонен
            </option>
        </select>
    </div>
    <div class="col-auto">
        <input type="text" name="chat_id" value="<?= htmlspecialchars($_GET['chat_id'] ?? '') ?>" class="form-control" placeholder="chat_id">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-primary">Фильтр</button>
    </div>
</form>

<div class="table-responsive">
<table id="joinRequestsTable" class="table table-center table-striped table-hover">
    <thead>
    <tr>
        <th>ID чата</th>
        <th>ID пользователя</th>
        <th>Логин</th>
        <th>Био</th>
        <th>Пригласительная ссылка</th>
        <th>Дата запроса</th>
        <th>Статус</th>
        <th>Дата решения</th>
        <th>Решен</th>
        <th class="text-end">Действия</th>
    </tr>
    </thead>
    <tbody></tbody>
    <tfoot>
    <tr>
        <th>ID чата</th>
        <th>ID пользователя</th>
        <th>Логин</th>
        <th>Био</th>
        <th>Ссылка</th>
        <th>Дата запроса</th>
        <th>Статус</th>
        <th>Дата решения</th>
        <th>Решен</th>
        <th class="text-end">Действия</th>
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
<script src="<?= url('/assets/js/datatable.join-requests.js') ?>"></script>
