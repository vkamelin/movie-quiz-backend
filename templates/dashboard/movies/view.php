<?php
/**
 * @var array $movie
 * @var array $genres
 * @var array $countries
 */
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <img src="<?= htmlspecialchars($movie['poster_url'] ?: $movie['poster_url_preview'] ?: '/assets/images/no-poster.png') ?>" 
                 class="card-img-top" alt="<?= htmlspecialchars($movie['title']) ?>"
                 onerror="this.src='/assets/images/no-poster.png'">
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h1 class="card-title"><?= htmlspecialchars($movie['title']) ?></h1>
                
                <?php if ($movie['title_original'] && $movie['title_original'] !== $movie['title']): ?>
                    <h5 class="text-muted"><?= htmlspecialchars($movie['title_original']) ?></h5>
                <?php endif; ?>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Год:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($movie['year'] ?: 'Не указан') ?></dd>
                            
                            <dt class="col-sm-4">Статус:</dt>
                            <dd class="col-sm-8">
                                <span class="badge <?= $movie['status'] ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $movie['status'] ? 'Активен' : 'Неактивен' ?>
                                </span>
                            </dd>
                            
                            <?php if ($movie['rating_kinopoisk']): ?>
                                <dt class="col-sm-4">Кинопоиск:</dt>
                                <dd class="col-sm-8">
                                    <span class="text-warning">★ <?= htmlspecialchars($movie['rating_kinopoisk']) ?></span>
                                </dd>
                            <?php endif; ?>
                            
                            <?php if ($movie['rating_imdb']): ?>
                                <dt class="col-sm-4">IMDb:</dt>
                                <dd class="col-sm-8">
                                    <span class="text-info">★ <?= htmlspecialchars($movie['rating_imdb']) ?></span>
                                </dd>
                            <?php endif; ?>
                            
                            <?php if (!empty($countries)): ?>
                                <dt class="col-sm-4">Страны:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars(implode(', ', $countries)) ?></dd>
                            <?php endif; ?>
                            
                            <?php if (!empty($genres)): ?>
                                <dt class="col-sm-4">Жанры:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars(implode(', ', $genres)) ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
                
                <?php if ($movie['description']): ?>
                    <div class="mt-4">
                        <h5>Описание</h5>
                        <p class="card-text"><?= nl2br(htmlspecialchars($movie['description'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="/dashboard/movies/<?= $movie['id'] ?>/edit" class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Изменить статус
                    </a>
                    <a href="/dashboard/movies" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> К списку фильмов
                    </a>
                </div>
                
                <div class="mt-3">
                    <small class="text-muted">
                        ID в базе: <?= $movie['id'] ?>
                        <?php if ($movie['kinopoisk_id']): ?>
                            | ID Кинопоиск: <?= $movie['kinopoisk_id'] ?>
                        <?php endif; ?>
                        | Создан: <?= date('d.m.Y H:i', strtotime($movie['created_at'])) ?>
                        | Обновлен: <?= date('d.m.Y H:i', strtotime($movie['updated_at'])) ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>