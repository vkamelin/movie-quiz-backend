<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css">

<h1>Логи</h1>

<div class="row g-2 mb-2">
  <div class="col-md-4">
    <label class="form-label">Файл</label>
    <select id="logFile" class="form-select"></select>
  </div>
  <div class="col-md-2">
    <label class="form-label">Уровень</label>
    <select id="logLevel" class="form-select">
      <option value="">Все</option>
      <option>ERROR</option>
      <option>WARNING</option>
      <option>INFO</option>
      <option>DEBUG</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Поиск</label>
    <input id="logSearch" type="text" class="form-control" placeholder="Сообщение / исключение / канал">
  </div>
  <div class="col-md-2 d-flex align-items-end">
    <button id="logReload" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-repeat"></i> Обновить</button>
  </div>
  <div class="col-12 small text-muted">
    Показ последних 50 000 строк выбранного файла. Поиск и сортировка выполняются на сервере.
  </div>
</div>

<div class="table-responsive">
<table id="logsTable" class="table table-center table-striped table-hover">
  <thead>
    <tr>
      <th>Строка</th>
      <th>Время</th>
      <th>Уровень</th>
      <th>Канал</th>
      <th>Сообщение</th>
      <th>Исключение</th>
      <th>RID</th>
      <th class="text-end">Действия</th>
    </tr>
  </thead>
  <tbody></tbody>
  <tfoot>
    <tr>
      <th>Строка</th>
      <th>Время</th>
      <th>Уровень</th>
      <th>Канал</th>
      <th>Сообщение</th>
      <th>Исключение</th>
      <th>RID</th>
      <th class="text-end">Действия</th>
    </tr>
  </tfoot>
</table>
</div>

<!-- jQuery и DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap5.min.js"></script>

<!-- Buttons (экспорт) -->
<script src="https://cdn.datatables.net/buttons/2.3.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.3/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.3/js/buttons.bootstrap5.min.js"></script>

<script src="<?= url('/assets/js/datatable.common.js') ?>"></script>
<script src="<?= url('/assets/js/datatable.logs.js') ?>"></script>
