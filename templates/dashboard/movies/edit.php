<?php
/**
 * @var array $movie
 * @var array $errors
 * @var array $genres
 * @var array $countries
 * @var string $csrfToken
 */
$isNew = empty($movie['id']);
?>

<h1 class="mb-3">Редактирование фильма: <?= htmlspecialchars($movie['title']) ?></h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <?= implode('<br>', $errors) ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="post" action="/dashboard/movies/<?= $movie['id'] ?>">
                    <input type="hidden" name="<?= $_ENV['CSRF_TOKEN_NAME'] ?? '_csrf_token' ?>" value="<?= $csrfToken ?>">
                    <input type="hidden" name="_method" value="PUT">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Название</label>
                        <input type="text" class="form-control" id="title" 
                               value="<?= htmlspecialchars($movie['title']) ?>" readonly>
                    </div>
                    
                    <?php if ($movie['title_original'] && $movie['title_original'] !== $movie['title']): ?>
                        <div class="mb-3">
                            <label for="title_original" class="form-label">Оригинальное название</label>
                            <input type="text" class="form-control" id="title_original" 
                                   value="<?= htmlspecialchars($movie['title_original']) ?>" readonly>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="year" class="form-label">Год</label>
                        <input type="text" class="form-control" id="year" 
                               value="<?= htmlspecialchars($movie['year'] ?: 'Не указан') ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Статус</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="1" <?= $movie['status'] ? 'selected' : '' ?>>Активен</option>
                            <option value="0" <?= !$movie['status'] ? 'selected' : '' ?>>Неактивен</option>
                        </select>
                        <div class="form-text">
                            Активные фильмы отображаются на сайте, неактивные - скрыты
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-success">
                            <i class="bi bi-check"></i> Сохранить
                        </button>
                        <a href="/dashboard/movies/<?= $movie['id'] ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-eye"></i> Просмотр
                        </a>
                        <a href="/dashboard/movies" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> К списку
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Информация о фильме</h6>
                
                <div class="mb-3">
                    <strong>Жанры:</strong><br>
                    <?php if (!empty($genres)): ?>
                        <?php foreach ($genres as $genre): ?>
                            <span class="badge bg-secondary me-1"><?= htmlspecialchars($genre) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-muted">Не указаны</span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <strong>Страны:</strong><br>
                    <?php if (!empty($countries)): ?>
                        <?php foreach ($countries as $country): ?>
                            <span class="badge bg-info me-1"><?= htmlspecialchars($country) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-muted">Не указаны</span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <strong>Рейтинг Кинопоиск:</strong><br>
                    <?php if ($movie['rating_kinopoisk']): ?>
                        <span class="text-warning">★ <?= htmlspecialchars($movie['rating_kinopoisk']) ?></span>
                    <?php else: ?>
                        <span class="text-muted">Не указан</span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <strong>Рейтинг IMDb:</strong><br>
                    <?php if ($movie['rating_imdb']): ?>
                        <span class="text-info">★ <?= htmlspecialchars($movie['rating_imdb']) ?></span>
                    <?php else: ?>
                        <span class="text-muted">Не указан</span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">
                        <strong>ID в базе:</strong> <?= $movie['id'] ?><br>
                        <?php if ($movie['kinopoisk_id']): ?>
                            <strong>ID Кинопоиск:</strong> <?= $movie['kinopoisk_id'] ?><br>
                        <?php endif; ?>
                        <strong>Создан:</strong> <?= date('d.m.Y H:i', strtotime($movie['created_at'])) ?><br>
                        <strong>Обновлен:</strong> <?= date('d.m.Y H:i', strtotime($movie['updated_at'])) ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        <strong>Примечание:</strong> Изменять можно только статус фильма. Название, год, жанры и страны изменить нельзя, 
        так как они загружаются из внешних источников и должны оставаться неизменными для корректной работы системы.
    </div>
</div>