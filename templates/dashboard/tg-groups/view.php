<?php
/** @var array $group */
/** @var array $members */
/** @var string $csrfToken */
?>
<h1 class="mb-3">Группа <?= htmlspecialchars($group['name']) ?></h1>

<form method="post" class="mb-4" action="<?= url('/dashboard/tg-groups/' . $group['id']) ?>">
    <input type="hidden" name="<?= $_ENV['CSRF_TOKEN_NAME'] ?? '_csrf_token' ?>" value="<?= $csrfToken ?>">
    <div class="input-group">
        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($group['name']) ?>">
        <button type="submit" class="btn btn-outline-success">Сохранить</button>
    </div>

</form>

<h2 class="h5">Добавить пользователя</h2>
<div class="mb-4">
    <input type="text" class="form-control mb-2" id="userSearchInput" placeholder="Поиск по username или ID">
    <ul class="list-group" id="userSearchResults"></ul>
</div>

<h2 class="h5">Участники</h2>
<p id="noMembers" class="<?= empty($members) ? '' : 'd-none' ?>">Нет пользователей</p>
<div class="table-responsive <?= empty($members) ? 'd-none' : '' ?>">
<table class="table table-striped" id="membersTable">
    <thead>
    <tr>
        <th>ID</th>
        <th>ID Telegram</th>
        <th>Логин</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($members as $m): ?>
        <tr data-member-id="<?= $m['id'] ?>">
            <td><?= $m['id'] ?></td>
            <td><?= $m['user_id'] ?></td>
            <td><?= htmlspecialchars($m['username'] ?? '') ?></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-member-btn" data-user-id="<?= $m['id'] ?>">Удалить</button></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<script>
    window.tgUserSearchUrl = '<?= url('/dashboard/tg-users/search') ?>';
    window.tgGroupAddUrl = '<?= url('/dashboard/tg-groups/' . $group['id'] . '/add-user') ?>';
    window.tgGroupRemoveUrl = '<?= url('/dashboard/tg-groups/' . $group['id'] . '/remove-user') ?>';
</script>
<script src="<?= url('/assets/js/tg-groups-edit.js') ?>"></script>
