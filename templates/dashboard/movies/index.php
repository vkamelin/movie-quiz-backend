<h1 class="mb-4">Фильмы</h1>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="moviesSearch" class="form-control" placeholder="Поиск по названию, году...">
        </div>
    </div>
    <div class="col-md-2">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-journal-bookmark"></i></span>
            <input type="number" id="pageInput" class="form-control" placeholder="Страница" min="1" value="1">
            <button type="button" id="goToPage" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </div>
    <div class="col-md-2">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-film"></i></span>
            <input type="number" id="movieIdInput" class="form-control" placeholder="ID фильма">
            <button type="button" id="goToMovie" class="btn btn-outline-primary">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>
    <div class="col-md-6">
        <div class="d-flex justify-content-end gap-2">
            <button type="button" id="bulkToggleStatus" class="btn btn-outline-primary" style="display: none;">
                <i class="bi bi-toggles"></i> Переключить статус
            </button>
            <button type="button" id="bulkActivate" class="btn btn-outline-success" style="display: none;">
                <i class="bi bi-check-circle"></i> Активировать
            </button>
            <button type="button" id="bulkDeactivate" class="btn btn-outline-danger" style="display: none;">
                <i class="bi bi-x-circle"></i> Деактивировать
            </button>
        </div>
    </div>
</div>

<div id="moviesGrid" class="row g-4">
    <!-- Карточки фильмов будут загружаться здесь -->
</div>

<div id="loadingSpinner" class="text-center py-5" style="display: none;">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Загрузка...</span>
    </div>
</div>

<div id="emptyState" class="text-center py-5" style="display: none;">
    <i class="bi bi-film" style="font-size: 4rem; color: #ccc;"></i>
    <h4 class="mt-3 text-muted">Фильмы не найдены</h4>
    <p class="text-muted">Попробуйте изменить параметры поиска</p>
</div>

<!-- Пагинация -->
<div id="paginationContainer" class="d-flex justify-content-center mt-5" style="display: none;">
    <nav aria-label="Навигация по страницам">
        <ul class="pagination" id="pagination">
            <!-- Пагинация будет добавлена здесь -->
        </ul>
    </nav>
</div>

<!-- Информация о странице -->
<div id="pageInfo" class="text-center text-muted mt-3" style="display: none;">
    <small id="pageStats" class="fw-medium"></small>
</div>

<script src="<?= url('/assets/js/movies.js') ?>"></script>

<style>
/* Основные стили карточек */
.movie-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
    height: 100%;
}

/* Постер фильма */
.movie-poster {
    aspect-ratio: 2/3;
    width: 100%;
    object-fit: cover;
    border-radius: 8px 8px 0 0;
    background: #f8f9fa;
}

/* Контейнер для постеров */
.card-img-top {
    aspect-ratio: 2/3;
    width: 100%;
    object-fit: cover;
}

.movie-poster.no-image {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    aspect-ratio: 2/3;
    background: #6c757d;
    color: white;
    font-size: 1rem;
    border-radius: 8px 8px 0 0;
    text-align: center;
    width: 100%;
}

.movie-poster.no-image i {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

/* Тело карточки */
.card-body {
    padding: 1rem;
    display: flex;
    flex-direction: column;
}

.card-title {
    font-size: 1rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.5rem;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-text {
    color: #6c757d;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

/* Чекбокс выбора */
.movie-checkbox {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 10;
    width: 20px;
    height: 20px;
}

/* Статус фильма */
.status-toggle {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 10;
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid #dee2e6;
    border-radius: 15px;
    padding: 4px 8px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

/* Выделенная карточка */
.card-selected {
    border-color: #007bff !important;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Неактивные фильмы */
.inactive-movie {
    opacity: 0.7;
    background: #f8f9fa;
}

.inactive-movie::before {
    background: #6c757d;
}

.inactive-movie .card-title {
    color: #495057;
}

.inactive-movie .movie-poster {
    filter: grayscale(50%);
}

/* Разделитель */
.inactive-divider {
    display: flex;
    align-items: center;
    width: 100%;
    margin: 2rem 0;
    padding: 0 1rem;
}

.divider-line {
    flex: 1;
    height: 1px;
    background: #dee2e6;
}

.divider-text {
    padding: 0 1rem;
    color: #dc3545;
    font-weight: 600;
    font-size: 0.9rem;
    white-space: nowrap;
    background: white;
    border: 1px solid #dc3545;
    border-radius: 15px;
    padding: 0.5rem 1rem;
}

.divider-text i {
    margin-right: 0.5rem;
}

/* Кнопки массовых операций */
#bulkToggleStatus,
#bulkActivate,
#bulkDeactivate {
    font-weight: 500;
    border-radius: 6px;
}

/* Пагинация */
.pagination {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 0.5rem;
}

.pagination .page-item {
    margin: 0 1px;
}

.pagination .page-link {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 0.5rem 0.75rem;
    font-weight: 500;
    color: #495057;
    background: white;
}

.pagination .page-link:hover {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

.pagination .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

.pagination .page-item.disabled .page-link {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Кнопки действий в карточках */
.btn-group .btn {
    border-radius: 4px;
    font-weight: 500;
    font-size: 0.8rem;
    padding: 0.4rem 0.6rem;
}

/* Карточка фильма */
.movie-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.movie-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.movie-card.loading {
    opacity: 0.7;
    pointer-events: none;
}

/* Заглушка для постеров до загрузки */
.movie-poster-placeholder {
    aspect-ratio: 2/3;
    width: 100%;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
    border-radius: 8px 8px 0 0;
}

@keyframes loading {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}
    
/* Информация о странице */
#pageInfo {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 0.75rem;
    border: 1px solid #dee2e6;
}

/* Адаптивность */
@media (max-width: 768px) {
    .card-body {
        padding: 0.75rem;
    }
    
    .movie-checkbox {
        top: 8px;
        left: 8px;
        width: 18px;
        height: 18px;
    }
    
    .status-toggle {
        top: 8px;
        right: 8px;
        font-size: 0.65rem;
        padding: 3px 6px;
    }
    
    .pagination .page-link {
        padding: 0.4rem 0.6rem;
    }
    
    .inactive-divider {
        margin: 1.5rem 0;
    }
    
    .divider-text {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
    }
}

@media (max-width: 576px) {
    .card-title {
        font-size: 0.9rem;
    }
    
    .btn-group .btn {
        font-size: 0.75rem;
        padding: 0.3rem 0.5rem;
    }
    
    /* Быстрая навигация */
    #pageInput, #movieIdInput {
        font-size: 0.9rem;
    }
    
    #goToPage, #goToMovie {
        border-radius: 0 4px 4px 0;
    }
    
    #goToPage:hover, #goToMovie:hover {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }
}
</style>