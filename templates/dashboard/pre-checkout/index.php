<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css">

<!-- Buttons CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.3/css/buttons.bootstrap5.min.css">

<h1>Предварительный заказ</h1>

<div class="table-responsive">
<table id="preCheckoutTable" class="table table-center table-striped table-hover">
    <thead>
    <tr>
        <th>ID</th>
        <th>От ID пользователя</th>
        <th>Валюта</th>
        <th>Сумма</th>
        <th>Опция доставки ID</th>
        <th>Принят</th>
    </tr>
    </thead>
    <tbody></tbody>
    <tfoot>
    <tr>
        <th>ID</th>
        <th>От ID пользователя</th>
        <th>Валюта</th>
        <th>Сумма</th>
        <th>Опция доставки ID</th>
        <th>Принят</th>
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
<script src="<?= url('/assets/js/datatable.pre-checkout.js') ?>"></script>
