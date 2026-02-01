let currentPage = 0;
let totalPages = 0;
let totalRecords = 0;
let recordsPerPage = 100;
let isLoading = false;
let searchTimeout;
let selectedMovies = new Set();

function loadMovies(page = 0, search = '') {
    console.log('Загружаем фильмы:', { page, search });
    
    if (isLoading) {
        console.log('Уже загружается, пропускаем');
        return;
    }
    
    // Убираем защиту от повторной загрузки для первой загрузки
    if (page === currentPage && !search && currentPage !== 0) {
        console.log('Страница уже загружена, пропускаем');
        return;
    }
    
    isLoading = true;
    
    // Находим элементы DOM
    const spinner = document.getElementById('loadingSpinner');
    const grid = document.getElementById('moviesGrid');
    const emptyState = document.getElementById('emptyState');
    const paginationContainer = document.getElementById('paginationContainer');
    const pageInfo = document.getElementById('pageInfo');
    
    // Проверяем наличие критичных элементов
    if (!grid) {
        console.error('Элемент #moviesGrid не найден!');
        isLoading = false;
        return;
    }
    
    // Всегда очищаем контент при загрузке новой страницы
    grid.innerHTML = '';
    if (emptyState) emptyState.style.display = 'none';
    if (paginationContainer) paginationContainer.style.display = 'none';
    if (pageInfo) pageInfo.style.display = 'none';
    
    // Сбрасываем выделения при смене страницы
    selectedMovies.clear();
    updateBulkButtons();
    
    // Показываем спиннер
    if (spinner) spinner.style.display = 'block';
    
    const formData = new FormData();
    formData.append('start', page * recordsPerPage);
    formData.append('length', recordsPerPage);
    formData.append('draw', Date.now());
    if (search) {
        formData.append('search[value]', search);
    }
    
    // Добавляем CSRF токен если доступен
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('_csrf_token', csrfToken);
    }
    
    fetch('/dashboard/movies/data', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Получены данные:', data);
        
        if (spinner) spinner.style.display = 'none';
        isLoading = false;
        
        // Обновляем переменные
        currentPage = page;
        totalRecords = data.recordsTotal;
        totalPages = Math.ceil(data.recordsTotal / recordsPerPage);
        
        console.log('Статистика:', { currentPage, totalRecords, totalPages, dataLength: data.data.length });
        
        // Отображаем данные если они есть
        if (data.data.length > 0) {
            console.log('Отображаем', data.data.length, 'фильмов');
            
            // Добавляем разделитель между активными и неактивными фильмами
            if (page === 0) {
                addInactiveDivider(data.data);
            }
            
            // Добавляем карточки фильмов
            data.data.forEach((movie, index) => {
                setTimeout(() => {
                    const card = createMovieCard(movie);
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    grid.appendChild(card);
                    
                    // Анимация появления
                    requestAnimationFrame(() => {
                        card.style.transition = 'all 0.4s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    });
                }, index * 100);
            });
            
            // Показываем пагинацию если есть данные и больше одной страницы
            if (data.recordsTotal > recordsPerPage) {
                setTimeout(() => {
                    updatePagination(page, totalPages, data.recordsTotal);
                    updatePageInfo(page, totalPages, data.recordsTotal);
                }, data.data.length * 100 + 200);
            }
        } else {
            console.log('Нет данных для отображения');
            
            // Показываем пустое состояние только если это первая страница и нет поиска
            if (page === 0 && !search) {
                if (emptyState) emptyState.style.display = 'block';
            } else if (page > 0) {
                // Если это не первая страница и данных нет, показываем предупреждение
                showAlert(`Страница ${page + 1} не содержит данных. Показана последняя доступная страница.`, 'warning');
                const lastPage = Math.max(0, totalPages - 1);
                if (lastPage !== page && lastPage >= 0) {
                    loadMovies(lastPage, search);
                    return;
                }
            }
        }
        
        // Обновляем поле ввода страницы
        updatePageInput(page);
    })
    .catch(error => {
        spinner.style.display = 'none';
        isLoading = false;
        console.error('Ошибка загрузки фильмов:', error);
        showAlert('Ошибка загрузки фильмов', 'danger');
    });
}

function addInactiveDivider(movies) {
    const grid = document.getElementById('moviesGrid');
    const hasActive = movies.some(movie => movie.status == 1);
    const hasInactive = movies.some(movie => movie.status == 0);
    
    if (hasActive && hasInactive) {
        const dividerCol = document.createElement('div');
        dividerCol.className = 'col-12';
        dividerCol.style.opacity = '0';
        dividerCol.style.transform = 'translateY(20px)';
        dividerCol.innerHTML = `
            <div class="inactive-divider">
                <div class="divider-line"></div>
                <div class="divider-text">
                    <i class="bi bi-eye-slash"></i> Неактивные фильмы
                </div>
                <div class="divider-line"></div>
            </div>
        `;
        grid.appendChild(dividerCol);
        
        // Анимация появления разделителя
        setTimeout(() => {
            dividerCol.style.transition = 'all 0.6s ease';
            dividerCol.style.opacity = '1';
            dividerCol.style.transform = 'translateY(0)';
        }, 100);
    }
}

function updatePagination(currentPage, totalPages, totalRecords) {
    const pagination = document.getElementById('pagination');
    const paginationContainer = document.getElementById('paginationContainer');
    
    pagination.innerHTML = '';
    
    // Кнопка "Предыдущая"
    const prevItem = document.createElement('li');
    prevItem.className = `page-item ${currentPage === 0 ? 'disabled' : ''}`;
    prevItem.innerHTML = `
        <a class="page-link" href="#" data-page="${currentPage - 1}" ${currentPage === 0 ? 'tabindex="-1" aria-disabled="true"' : ''}>
            <i class="bi bi-chevron-left"></i>
        </a>
    `;
    pagination.appendChild(prevItem);
    
    // Номера страниц
    const startPage = Math.max(0, currentPage - 2);
    const endPage = Math.min(totalPages - 1, startPage + 4);
    
    // Первая страница
    if (startPage > 0) {
        const firstItem = createPageItem(0, currentPage);
        pagination.appendChild(firstItem);
        
        if (startPage > 1) {
            const ellipsis = document.createElement('li');
            ellipsis.className = 'page-item disabled';
            ellipsis.innerHTML = '<span class="page-link">...</span>';
            pagination.appendChild(ellipsis);
        }
    }
    
    // Страницы вокруг текущей
    for (let i = startPage; i <= endPage; i++) {
        const pageItem = createPageItem(i, currentPage);
        pagination.appendChild(pageItem);
    }
    
    // Последняя страница
    if (endPage < totalPages - 1) {
        if (endPage < totalPages - 2) {
            const ellipsis = document.createElement('li');
            ellipsis.className = 'page-item disabled';
            ellipsis.innerHTML = '<span class="page-link">...</span>';
            pagination.appendChild(ellipsis);
        }
        
        const lastItem = createPageItem(totalPages - 1, currentPage);
        pagination.appendChild(lastItem);
    }
    
    // Кнопка "Следующая"
    const nextItem = document.createElement('li');
    nextItem.className = `page-item ${currentPage >= totalPages - 1 ? 'disabled' : ''}`;
    nextItem.innerHTML = `
        <a class="page-link" href="#" data-page="${currentPage + 1}" ${currentPage >= totalPages - 1 ? 'tabindex="-1" aria-disabled="true"' : ''}>
            <i class="bi bi-chevron-right"></i>
        </a>
    `;
    pagination.appendChild(nextItem);
    
    paginationContainer.style.display = 'flex';
    
    // Добавляем обработчики событий сразу к каждому элементу
    pagination.querySelectorAll('.page-link[data-page]').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const page = parseInt(link.dataset.page);
            if (page >= 0 && page < totalPages && page !== currentPage) {
                loadMovies(page, document.getElementById('moviesSearch').value.trim());
            }
        });
    });
}

function createPageItem(page, currentPage) {
    const item = document.createElement('li');
    item.className = `page-item ${page === currentPage ? 'active' : ''}`;
    item.innerHTML = `
        <a class="page-link" href="#" data-page="${page}">${page + 1}</a>
    `;
    return item;
}

function updatePageInfo(currentPage, totalPages, totalRecords) {
    const pageInfo = document.getElementById('pageInfo');
    const pageStats = document.getElementById('pageStats');
    
    const start = currentPage * recordsPerPage + 1;
    const end = Math.min((currentPage + 1) * recordsPerPage, totalRecords);
    
    pageStats.textContent = `Страница ${currentPage + 1} из ${totalPages} • Показано ${start}-${end} из ${totalRecords} фильмов`;
    pageInfo.style.display = 'block';
    
    // Анимация появления
    pageInfo.style.opacity = '0';
    pageInfo.style.transform = 'translateY(10px)';
    setTimeout(() => {
        pageInfo.style.transition = 'all 0.3s ease';
        pageInfo.style.opacity = '1';
        pageInfo.style.transform = 'translateY(0)';
    }, 100);
}
    
function createMovieCard(movie) {
    const col = document.createElement('div');
    col.className = 'col-xl-3 col-lg-4 col-md-6';
    
    const posterUrl = movie.poster_url_preview || movie.poster_url || '/assets/images/no-poster.webp';
    const statusClass = movie.status ? 'bg-success' : 'bg-danger';
    const statusText = movie.status ? 'Активен' : 'Неактивен';
    const newStatus = movie.status ? 0 : 1;
    const newStatusText = movie.status ? 'Деактивировать' : 'Активировать';
    const ratingText = movie.rating_kinopoisk ? `КП: ${movie.rating_kinopoisk}` : '';
    const imdbRatingText = movie.rating_imdb ? `IMDb: ${movie.rating_imdb}` : '';
    
    // Добавляем класс для неактивных фильмов
    const cardClass = movie.status ? 'movie-card' : 'movie-card inactive-movie';
    
    col.innerHTML = `
        <div class="movie-card ${cardClass}" data-movie-id="${movie.id}" data-status="${movie.status}">
            <div class="position-relative">
                <input type="checkbox" class="form-check-input movie-checkbox" 
                       id="movie_${movie.id}" data-movie-id="${movie.id}">
                <img src="${posterUrl}" class="movie-poster" alt="${movie.title}" 
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="movie-poster no-image" style="display: none;">
                    <i class="bi bi-image"></i>
                    <span>Нет постера</span>
                </div>
                <span class="badge ${statusClass} position-absolute status-toggle">${statusText}</span>
            </div>
            <div class="card-body d-flex flex-column">
                <h6 class="card-title">${movie.title}</h6>
                ${movie.title_original && movie.title_original !== movie.title ? 
                    `<p class="card-text text-muted small">${movie.title_original}</p>` : ''}
                ${movie.year ? `<p class="card-text"><small class="text-muted">${movie.year}</small></p>` : ''}
                <div class="mt-auto">
                    ${ratingText || imdbRatingText ? 
                        `<div class="d-flex justify-content-between align-items-center mb-2">
                            ${ratingText ? `<small class="text-warning">★ ${ratingText}</small>` : ''}
                            ${imdbRatingText ? `<small class="text-info">★ ${imdbRatingText}</small>` : ''}
                        </div>` : ''}
                    ${movie.countries ? `<p class="card-text"><small class="text-muted">${movie.countries}</small></p>` : ''}
                    ${movie.genres ? `<p class="card-text"><small class="text-muted">${movie.genres}</small></p>` : ''}
                    <div class="btn-group w-100 mt-2" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm toggle-status" 
                                data-movie-id="${movie.id}" data-current-status="${movie.status}">
                            <i class="bi bi-toggles"></i> ${newStatusText}
                        </button>
                        <a href="/dashboard/movies/${movie.id}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-eye"></i> Подробнее
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Добавляем обработчики событий
    const checkbox = col.querySelector('.movie-checkbox');
    const toggleBtn = col.querySelector('.toggle-status');
    
    checkbox.addEventListener('change', function() {
        const movieId = parseInt(this.dataset.movieId);
        const card = col.querySelector('.movie-card');
        if (this.checked) {
            selectedMovies.add(movieId);
            card.classList.add('card-selected');
        } else {
            selectedMovies.delete(movieId);
            card.classList.remove('card-selected');
        }
        updateBulkButtons();
    });
    
    toggleBtn.addEventListener('click', function() {
        const movieId = parseInt(this.dataset.movieId);
        const currentStatus = parseInt(this.dataset.currentStatus);
        toggleMovieStatus(movieId, currentStatus, this);
    });
    
    return col;
}

function toggleMovieStatus(movieId, currentStatus, buttonElement) {
    const newStatus = currentStatus ? 0 : 1;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (!csrfToken) {
        showAlert('CSRF токен не найден', 'danger');
        return;
    }
    
    const originalText = buttonElement.innerHTML;
    buttonElement.disabled = true;
    buttonElement.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    
    const card = buttonElement.closest('.movie-card');
    card.classList.add('loading');
    
    const formData = new FormData();
    formData.append('_csrf_token', csrfToken);
    formData.append('status', newStatus);
    
    fetch(`/dashboard/movies/${movieId}`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            // Обновляем UI
            const statusBadge = card.querySelector('.status-toggle');
            
            if (newStatus) {
                statusBadge.className = 'badge bg-success position-absolute status-toggle';
                statusBadge.textContent = 'Активен';
                buttonElement.dataset.currentStatus = '1';
                buttonElement.innerHTML = '<i class="bi bi-toggles"></i> Деактивировать';
                card.classList.remove('inactive-movie');
            } else {
                statusBadge.className = 'badge bg-danger position-absolute status-toggle';
                statusBadge.textContent = 'Неактивен';
                buttonElement.dataset.currentStatus = '0';
                buttonElement.innerHTML = '<i class="bi bi-toggles"></i> Активировать';
                card.classList.add('inactive-movie');
            }
            
            // Обновляем атрибут data-status
            card.setAttribute('data-status', newStatus);
            
            // Убираем класс загрузки с анимацией
            setTimeout(() => {
                card.classList.remove('loading');
            }, 300);
            
            showAlert('Статус фильма обновлен', 'success');
        } else {
            throw new Error('Ошибка обновления статуса');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        showAlert('Ошибка обновления статуса', 'danger');
        buttonElement.innerHTML = originalText;
        card.classList.remove('loading');
    })
    .finally(() => {
        buttonElement.disabled = false;
    });
}

function updateBulkButtons() {
    const bulkToggle = document.getElementById('bulkToggleStatus');
    const bulkActivate = document.getElementById('bulkActivate');
    const bulkDeactivate = document.getElementById('bulkDeactivate');
    const count = selectedMovies.size;
    
    if (count > 0) {
        bulkToggle.style.display = 'inline-block';
        bulkActivate.style.display = 'inline-block';
        bulkDeactivate.style.display = 'inline-block';
        bulkToggle.textContent = `Переключить статус (${count})`;
        bulkActivate.textContent = `Активировать (${count})`;
        bulkDeactivate.textContent = `Деактивировать (${count})`;
    } else {
        bulkToggle.style.display = 'none';
        bulkActivate.style.display = 'none';
        bulkDeactivate.style.display = 'none';
    }
}

function bulkUpdateStatus(action) {
    if (selectedMovies.size === 0) {
        showAlert('Выберите фильмы для массового обновления', 'warning');
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        showAlert('CSRF токен не найден', 'danger');
        return;
    }
    
    const movieIds = Array.from(selectedMovies);
    const formData = new FormData();
    formData.append('_csrf_token', csrfToken);
    formData.append('action', action);
    formData.append('movie_ids', JSON.stringify(movieIds));
    
    let buttonId = '';
    switch (action) {
        case 'toggle':
            buttonId = 'bulkToggleStatus';
            break;
        case 'activate':
            buttonId = 'bulkActivate';
            break;
        case 'deactivate':
            buttonId = 'bulkDeactivate';
            break;
    }
    
    const button = document.getElementById(buttonId);
    const originalText = button.textContent;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Обработка...';
    
    // Добавляем эффект загрузки ко всем выбранным карточкам
    movieIds.forEach(movieId => {
        const card = document.querySelector(`[data-movie-id="${movieId}"] .movie-card`);
        if (card) {
            card.classList.add('loading');
        }
    });
    
    fetch('/dashboard/movies/bulk-status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Обновляем статусы в UI
            movieIds.forEach(movieId => {
                const card = document.querySelector(`[data-movie-id="${movieId}"]`);
                if (card) {
                    const cardElement = card.querySelector('.movie-card');
                    const statusBadge = cardElement.querySelector('.status-toggle');
                    const toggleBtn = cardElement.querySelector('.toggle-status');
                    
                    if (action === 'activate') {
                        statusBadge.className = 'badge bg-success position-absolute status-toggle';
                        statusBadge.textContent = 'Активен';
                        toggleBtn.dataset.currentStatus = '1';
                        toggleBtn.innerHTML = '<i class="bi bi-toggles"></i> Деактивировать';
                        cardElement.classList.remove('inactive-movie');
                    } else if (action === 'deactivate') {
                        statusBadge.className = 'badge bg-danger position-absolute status-toggle';
                        statusBadge.textContent = 'Неактивен';
                        toggleBtn.dataset.currentStatus = '0';
                        toggleBtn.innerHTML = '<i class="bi bi-toggles"></i> Активировать';
                        cardElement.classList.add('inactive-movie');
                    } else if (action === 'toggle') {
                        const currentStatus = parseInt(toggleBtn.dataset.currentStatus);
                        const newStatus = currentStatus ? 0 : 1;
                        if (newStatus) {
                            statusBadge.className = 'badge bg-success position-absolute status-toggle';
                            statusBadge.textContent = 'Активен';
                            toggleBtn.dataset.currentStatus = '1';
                            toggleBtn.innerHTML = '<i class="bi bi-toggles"></i> Деактивировать';
                            cardElement.classList.remove('inactive-movie');
                        } else {
                            statusBadge.className = 'badge bg-danger position-absolute status-toggle';
                            statusBadge.textContent = 'Неактивен';
                            toggleBtn.dataset.currentStatus = '0';
                            toggleBtn.innerHTML = '<i class="bi bi-toggles"></i> Активировать';
                            cardElement.classList.add('inactive-movie');
                        }
                    }
                    
                    // Обновляем атрибут data-status
                    cardElement.setAttribute('data-status', newStatus);
                    
                    // Убираем эффект загрузки
                    setTimeout(() => {
                        cardElement.classList.remove('loading');
                    }, 300);
                }
            });
            
            // Снимаем выделение
            selectedMovies.clear();
            updateBulkButtons();
            document.querySelectorAll('.movie-checkbox:checked').forEach(checkbox => {
                checkbox.checked = false;
                checkbox.closest('.movie-card').classList.remove('card-selected');
            });
            
            showAlert(`Массовое обновление завершено. Обработано: ${data.processed}`, 'success');
        } else {
            showAlert('Ошибка массового обновления: ' + (data.message || 'Неизвестная ошибка'), 'danger');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        showAlert('Ошибка массового обновления', 'danger');
    })
    .finally(() => {
        button.disabled = false;
        button.textContent = originalText;
        
        // Убираем эффект загрузки со всех карточек
        movieIds.forEach(movieId => {
            const card = document.querySelector(`[data-movie-id="${movieId}"] .movie-card`);
            if (card) {
                card.classList.remove('loading');
            }
        });
    });
}

function showAlert(message, type) {
    // Создаем алерт
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = `
        top: 20px; 
        right: 20px; 
        z-index: 9999; 
        min-width: 300px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Анимация появления
    setTimeout(() => {
        alertDiv.style.transform = 'translateX(0)';
    }, 100);
    
    // Автоматически скрываем через 5 секунд
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.style.transform = 'translateX(100%)';
            setTimeout(() => {
                alertDiv.remove();
            }, 300);
        }
    }, 5000);
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Обработчик поиска
    const searchInput = document.getElementById('moviesSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const search = this.value.trim();
            
            searchTimeout = setTimeout(() => {
                // При поиске всегда начинаем с первой страницы
                loadMovies(0, search);
            }, 500);
        });
    }
    
    // Обработчик перехода на страницу
    const pageInput = document.getElementById('pageInput');
    const goToPageBtn = document.getElementById('goToPage');
    
    if (pageInput && goToPageBtn) {
        // Переход по кнопке
        goToPageBtn.addEventListener('click', function() {
            const targetPage = parseInt(pageInput.value) - 1; // -1 так как страницы начинаются с 0
            
            if (isNaN(targetPage) || targetPage < 0) {
                showAlert('Введите корректный номер страницы (начиная с 1)', 'warning');
                pageInput.value = currentPage + 1;
                return;
            }
            
            if (targetPage === currentPage) {
                showAlert('Вы уже находитесь на этой странице', 'info');
                return;
            }
            
            // Загружаем нужную страницу без жесткой валидации по totalPages
            loadMovies(targetPage, document.getElementById('moviesSearch').value.trim());
        });
            
        // Переход по Enter
        pageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                goToPageBtn.click();
            }
        });
        
        // Валидация ввода
        pageInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            if (isNaN(value) || value < 1) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '';
            }
        });
    }
    
    // Обработчик перехода к фильму по ID
    const movieIdInput = document.getElementById('movieIdInput');
    const goToMovieBtn = document.getElementById('goToMovie');
    
    if (movieIdInput && goToMovieBtn) {
        // Переход по кнопке
        goToMovieBtn.addEventListener('click', function() {
            const movieId = parseInt(movieIdInput.value);
            
            if (movieId > 0) {
                // Сначала ищем фильм на текущей странице
                const movieCard = document.querySelector(`[data-movie-id="${movieId}"]`);
                if (movieCard) {
                    // Фильм найден на текущей странице - прокручиваем к нему
                    movieCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Выделяем карточку на 2 секунды
                    const card = movieCard.querySelector('.movie-card');
                    card.style.borderColor = '#007bff';
                    card.style.boxShadow = '0 0 0 0.3rem rgba(0, 123, 255, 0.25)';
                    setTimeout(() => {
                        card.style.borderColor = '';
                        card.style.boxShadow = '';
                    }, 2000);
                } else {
                    // Фильм не найден - ищем его на сервере
                    searchMovieById(movieId);
                }
            } else {
                showAlert('Введите корректный ID фильма', 'warning');
            }
        });
        
        // Переход по Enter
        movieIdInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                goToMovieBtn.click();
            }
        });
    }
    
    // Обработчики массовых действий
    document.getElementById('bulkToggleStatus').addEventListener('click', () => bulkUpdateStatus('toggle'));
    document.getElementById('bulkActivate').addEventListener('click', () => bulkUpdateStatus('activate'));
    document.getElementById('bulkDeactivate').addEventListener('click', () => bulkUpdateStatus('deactivate'));
    
    // Загрузка первых фильмов при загрузке страницы
    loadMovies();
});

// Функция обновления поля ввода страницы
function updatePageInput(pageNumber) {
    const pageInput = document.getElementById('pageInput');
    if (pageInput) {
        pageInput.value = pageNumber + 1; // +1 так как пользователи видят с 1
    }
}

// Функция поиска фильма по ID
function searchMovieById(movieId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        showAlert('CSRF токен не найден', 'danger');
        return;
    }
    
    const formData = new FormData();
    formData.append('_csrf_token', csrfToken);
    formData.append('movie_id', movieId);
    
    // Показываем индикатор загрузки
    const goToMovieBtn = document.getElementById('goToMovie');
    const originalText = goToMovieBtn.innerHTML;
    goToMovieBtn.disabled = true;
    goToMovieBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    
    fetch('/dashboard/movies/search-by-id', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.found) {
            // Переходим на нужную страницу
            const targetPage = Math.floor(data.position / recordsPerPage);
            loadMovies(targetPage, document.getElementById('moviesSearch').value.trim());
            
            // После загрузки страницы прокрутим к фильму
            setTimeout(() => {
                const movieCard = document.querySelector(`[data-movie-id="${movieId}"]`);
                if (movieCard) {
                    movieCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const card = movieCard.querySelector('.movie-card');
                    card.style.borderColor = '#007bff';
                    card.style.boxShadow = '0 0 0 0.3rem rgba(0, 123, 255, 0.25)';
                    setTimeout(() => {
                        card.style.borderColor = '';
                        card.style.boxShadow = '';
                    }, 2000);
                }
            }, 800); // Увеличиваем время ожидания для загрузки страницы
            
            showAlert(`Фильм найден на странице ${targetPage + 1}`, 'success');
        } else {
            showAlert('Фильм с указанным ID не найден', 'warning');
        }
    })
    .catch(error => {
        console.error('Ошибка поиска:', error);
        showAlert('Ошибка при поиске фильма', 'danger');
    })
    .finally(() => {
        goToMovieBtn.disabled = false;
        goToMovieBtn.innerHTML = originalText;
    });
}