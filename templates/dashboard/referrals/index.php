<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css">

<!-- Buttons CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.3/css/buttons.bootstrap5.min.css">

<h1>Рефералы</h1>

<?php $m = $metrics ?? ['total'=>0,'inviters'=>0,'invitees'=>0,'avg_per_inviter'=>0]; ?>
<div class="row g-2 mb-3">
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Всего переходов</div>
        <div class="fs-4 fw-semibold"><?= (int)$m['total'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Активных пригласивших</div>
        <div class="fs-4 fw-semibold"><?= (int)$m['inviters'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Всего приглашённых</div>
        <div class="fs-4 fw-semibold"><?= (int)$m['invitees'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Сред. на пригласившего</div>
        <div class="fs-4 fw-semibold"><?= htmlspecialchars((string)$m['avg_per_inviter']) ?></div>
      </div>
    </div>
  </div>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <input type="text" name="inviter_user_id" value="<?= htmlspecialchars($_GET['inviter_user_id'] ?? '') ?>"
               class="form-control" placeholder="ID пригласившего">
    </div>
    <div class="col-auto">
        <input type="text" name="invitee_user_id" value="<?= htmlspecialchars($_GET['invitee_user_id'] ?? '') ?>"
               class="form-control" placeholder="ID приглашённого">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-success">Фильтр</button>
    </div>
    <div class="col-auto">
        <a href="/dashboard/referrals" class="btn btn-outline-secondary">Сбросить</a>
    </div>
    <div class="col-auto">
        <a href="/dashboard/tg-users" class="btn btn-outline-primary">Пользователи</a>
    </div>
    <div class="col-auto">
        <a href="/dashboard" class="btn btn-outline-secondary">Главная</a>
    </div>
  </form>

<h2 class="h5 mt-3">ТОП пригласивших</h2>
<div class="table-responsive">
<table id="referralsGroupedTable" class="table table-center table-striped table-hover mb-4">
  <thead>
  <tr>
    <th>Пригласивший</th>
    <th>Переходов</th>
    <th>Первый</th>
    <th>Последний</th>
    <th class="text-end">Действия</th>
  </tr>
  </thead>
  <tbody></tbody>
  <tfoot>
  <tr>
    <th>Пригласивший</th>
    <th>Переходов</th>
    <th>Первый</th>
    <th>Последний</th>
    <th class="text-end">Действия</th>
  </tr>
  </tfoot>
</table>
</div>

<h2 class="h5">Лог переходов</h2>
<div class="table-responsive">
<table id="referralsTable" class="table table-center table-striped table-hover">
    <thead>
    <tr>
        <th>ID</th>
        <th>Пригласивший</th>
        <th>Приглашённый</th>
        <th>Код</th>
        <th>Создано</th>
    </tr>
    </thead>
    <tbody></tbody>
    <tfoot>
    <tr>
        <th>ID</th>
        <th>Пригласивший</th>
        <th>Приглашённый</th>
        <th>Код</th>
        <th>Создано</th>
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
<script src="<?= url('/assets/js/datatable.referrals.js') ?>"></script>
<script src="<?= url('/assets/js/datatable.referrals.grouped.js') ?>"></script>
