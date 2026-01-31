<?php
/**
 * @author Vitaliy Kamelin <v.kamelin@gmail.com>
 *
 * @var string $csrfToken Токен CSRF
 * @var string $error
 */
?>

<main class="form-signin w-100">
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert">
        <?= $error ?>
    </div>
    <?php endif ?>
    
    <form method="POST" action="/dashboard/login">
        <input type="hidden" name="<?= $_ENV['CSRF_TOKEN_NAME'] ?? '_csrf_token' ?>" value="<?= $csrfToken ?>">
        <div class="form-floating">
            <input type="email" name="email" class="form-control" id="email" placeholder="name@example.com">
            <label for="email">Почта</label>
        </div>
        <div class="form-floating">
            <input type="password" name="password" class="form-control" id="password" placeholder="Пароль">
            <label for="password">Пароль</label>
        </div>
        
        <button typeof="submit" class="btn btn-outline-primary w-100 py-2" type="submit">Войти</button>
    </form>
</main>
