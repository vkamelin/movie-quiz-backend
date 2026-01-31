<?php
/**
 * Centered layout
 *
 * @author Vitaliy Kamelin <v.kamelin@gmail.com>
 *
 * @var string $content Контент страницы
 * @var string $title Заголовок страницы
 * @var string $csrfToken Токен CSRF
 * @var string $currentPath Текущий путь (без параметров запроса)
 * @var array $menu Меню
 * @var array $submenu Подменю
 */
?>
<!doctype html>
<html lang="ru" data-bs-theme="auto">
<head>
    <title><?= $title ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="<?= url('/assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= url('/assets/fonts/bootstrap-icons/bootstrap-icons.min.css'); ?>" rel="stylesheet">
    <link href="<?= url('/assets/css/login.css') ?>" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center py-4 bg-body-tertiary">

<button id="theme-toggle" class="btn btn-link position-absolute top-0 end-0 m-3">
    <i id="theme-icon" class="bi"></i>
</button>

<?= $content ?>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('/assets/js/bootstrap-init.js') ?>"></script>

</body>
</html>
