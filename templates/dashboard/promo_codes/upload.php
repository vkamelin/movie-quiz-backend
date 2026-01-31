<?php /** @var string $csrfToken */ ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 m-0">Загрузка промокодов (CSV)</h2>
    <div>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('/dashboard/promo-codes') ?>">К списку</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" action="<?= url('/dashboard/promo-codes/upload') ?>">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
            <div class="mb-3">
                <label class="form-label">CSV-файл</label>
                <input type="file" class="form-control" name="file" accept=".csv,text/csv,text/plain,application/vnd.ms-excel" required>
                <div class="form-text">Первая строка — заголовок. Колонки: <code>code</code> (обязательно), <code>expires_at</code> (опц.), <code>meta</code> (опц.). Макс. 5MB.</div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-upload"></i> Импортировать</button>
                <a class="btn btn-outline-secondary" href="<?= url('/dashboard/promo-codes') ?>">Отмена</a>
            </div>
        </form>
    </div>
</div>
