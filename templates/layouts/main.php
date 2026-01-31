<?php
/**
 * Main layout
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

use App\Helpers\Flash;

$messages = Flash::get();
?>

<!doctype html>
<html lang="ru" data-bs-theme="auto">
<head>
    <title><?= $title ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
    <link href="<?= url('/assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= url('/assets/fonts/bootstrap-icons/bootstrap-icons.min.css'); ?>" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Martian+Mono:wght@100..800&display=swap" rel="stylesheet">
    <link href="<?= url('/assets/css/styles.css') ?>" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body class="bg-body-tertiary">

<header class="mb-3">
    <nav class="navbar bg-body border-bottom" aria-label="Offcanvas navbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <!-- Левая половина шапки -->
                <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbarDark" aria-controls="offcanvasNavbarDark">
                    <i class="bi bi-list"></i>
                </button>
                <a class="navbar-brand mx-1" href="<?= url('/dashboard') ?>">Панель</a>
            </div>
            <div class="d-flex align-items-center">
                <!-- Правая половина шапки -->
                <button id="theme-toggle" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-sun"></i>
                </button>
                <a href="#" class="btn btn-outline-secondary">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="d-none d-lg-inline">Выйти</span>
                </a>
            </div>
            <div class="offcanvas offcanvas-start text-bg-dark d-lg-none" tabindex="-1" id="offcanvasNavbarDark"
                 aria-labelledby="offcanvasNavbarDarkLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="offcanvasNavbarDarkLabel">Панель</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                            aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="nav nav-pills flex-column mb-auto">
                        <?php foreach ($menu as $menuItem):?>
                        <li class="nav-item">
                            <?php $hasUrl = isset($menuItem['url']) && $menuItem['url'] !== null && $menuItem['url'] !== ''; ?>
                            <?php if ($hasUrl): ?>
                                <a href="<?= url($menuItem['url']) ?>" class="nav-link <?= $menuItem['class'] ?> text-white" aria-current="page">
                                    <i class="<?= $menuItem['icon'] ?>"></i>&nbsp;&nbsp;<?= $menuItem['title'] ?>
                                </a>
                            <?php else: ?>
                                <span class="nav-link <?= $menuItem['class'] ?> text-white">
                                    <i class="<?= $menuItem['icon'] ?>"></i>&nbsp;&nbsp;<?= $menuItem['title'] ?>
                                </span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach;?>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</header>

<main class="container-fluid">
    <div class="row">
        <div class="col-lg-4 col-xl-3 col-xxl-2">
            <nav class="sidebar d-none d-lg-block">
                <ul class="nav nav-pills flex-column mb-auto">
                    <?php foreach ($menu as $menuItem):?>
                    <li class="nav-item">
                        <?php $hasUrl = isset($menuItem['url']) && $menuItem['url'] !== null && $menuItem['url'] !== ''; ?>
                        <?php if ($hasUrl): ?>
                            <a href="<?= url($menuItem['url']) ?>" class="nav-link <?= $menuItem['class'] ?>" aria-current="page">
                                <i class="<?= $menuItem['icon'] ?>"></i>&nbsp;&nbsp;<?= $menuItem['title'] ?>
                            </a>
                        <?php else: ?>
                            <span class="nav-link <?= $menuItem['class'] ?>">
                                <i class="<?= $menuItem['icon'] ?>"></i>&nbsp;&nbsp;<?= $menuItem['title'] ?>
                            </span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach;?>
                </ul>
            </nav>
        </div>
        <div class="col-lg-8 col-xl-9 col-xxl-10 main-content">
            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show" role="alert">
                        <?php if ($message['type'] === 'success'): ?><i class="bi bi-check-circle"></i><?php endif; ?>
                        <?php if ($message['type'] === 'info'): ?><i class="bi bi-info-circle"></i><?php endif; ?>
                        <?php if ($message['type'] === 'error' || $message['type'] === 'warning'): ?><i class="bi bi-exclamation-triangle"></i><?php endif; ?>

                        <?= $message['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($submenu)) { require __DIR__ . '/../partials/submenu.php'; } ?>

            <?= $content ?>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('/assets/js/bootstrap-init.js') ?>"></script>

</body>
</html>
