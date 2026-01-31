<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css">

<!-- Buttons CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.3/css/buttons.bootstrap5.min.css">

<h1>Пользователи</h1>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="is_premium" class="form-select">
            <option value="">премиум</option>
            <option value="1" <?= isset($_GET['is_premium']) && $_GET['is_premium'] === '1' ? 'selected' : '' ?>>
                ДА
            </option>
            <option value="0" <?= isset($_GET['is_premium']) && $_GET['is_premium'] === '0' ? 'selected' : '' ?>>
                НЕТ
            </option>
        </select>
    </div>
    <div class="col-auto">
        <select name="is_user_banned" class="form-select">
            <option value="">забанен</option>
            <option value="1" <?= isset($_GET['is_user_banned']) && $_GET['is_user_banned'] === '1' ? 'selected' : '' ?>>
                ДА
            </option>
            <option value="0" <?= isset($_GET['is_user_banned']) && $_GET['is_user_banned'] === '0' ? 'selected' : '' ?>>
                НЕТ
            </option>
        </select>
    </div>
    <div class="col-auto">
        <select name="is_bot_banned" class="form-select">
            <option value="">бот забанен</option>
            <option value="1" <?= isset($_GET['is_bot_banned']) && $_GET['is_bot_banned'] === '1' ? 'selected' : '' ?>>
                ДА
            </option>
            <option value="0" <?= isset($_GET['is_bot_banned']) && $_GET['is_bot_banned'] === '0' ? 'selected' : '' ?>>
                НЕТ
            </option>
        </select>
    </div>
    <div class="col-auto">
        <select name="is_subscribed" class="form-select">
            <option value="">подписан</option>
            <option value="1" <?= isset($_GET['is_subscribed']) && $_GET['is_subscribed'] === '1' ? 'selected' : '' ?>>
                ДА
            </option>
            <option value="0" <?= isset($_GET['is_subscribed']) && $_GET['is_subscribed'] === '0' ? 'selected' : '' ?>>
                НЕТ
            </option>
        </select>
    </div>
    <div class="col-auto">
        <input type="text" name="language_code" value="<?= htmlspecialchars($_GET['language_code'] ?? '') ?>"
               class="form-control" placeholder="язык">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-success">Фильтр</button>
    </div>
</form>

<div class="table-responsive">
<table id="tgUsersTable" class="table table-center table-striped table-hover">
    <thead>
    <tr>
        <th>ID пользователя</th>
        <th>Логин</th>
        <th>Язык</th>
        <th>Премиум</th>
        <th>Подписан</th>
        <th>Забанен</th>
        <th>Бот забанен</th>
        <th class="text-end">Действия</th>
    </tr>
    </thead>
    <tbody></tbody>
    <tfoot>
    <tr>
        <th>ID пользователя</th>
        <th>Логин</th>
        <th>Язык</th>
        <th>Премиум</th>
        <th>Подписан</th>
        <th>Забанен</th>
        <th>Бот забанен</th>
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
<script src="<?= url('/assets/js/datatable.tg-users.js') ?>"></script>
