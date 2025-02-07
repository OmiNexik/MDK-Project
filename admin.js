


let currentSection = 'users';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.sidebar-btn').forEach(btn => {
        btn.addEventListener('click', () => switchSection(btn.dataset.section));
    });

    loadUsers();
});

function switchSection(section) {
    // Скрываем все разделы
    document.querySelectorAll('.section').forEach(sec => {
        sec.style.display = 'none';
    });

    // Показываем выбранный раздел
    const selectedSection = document.getElementById(`${section}-section`);
    if (selectedSection) {
        selectedSection.style.display = 'block';
    }

    // Обновляем активную кнопку
    document.querySelectorAll('.sidebar-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.section === section) {
            btn.classList.add('active');
        }
    });

    currentSection = section;

    // Загружаем данные для раздела
    switch (section) {
        case 'users':
            loadUsers();
            break;
        case 'movies':
            loadMovies();
            break;
        case 'shows':
            break;
        case 'categories':
            break;
    }
}

function loadUsers() {
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=getUsers'
    })
    .then(response => response.json())
    .then(data => {
        const tbody = document.querySelector('#users-table tbody');
        tbody.innerHTML = '';
        
        data.users.forEach(user => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${user.id}</td>
                <td>${user.name}</td>
                <td>${user.email}</td>
                <td>${user.is_admin ? 'Да' : 'Нет'}</td>
                <td>
                    <button class="action-btn edit-btn" onclick="editUser(${user.id}, '${user.name}', '${user.email}', ${user.is_admin})">
                        Редактировать
                    </button>
                    <button class="action-btn delete-btn" onclick="deleteUser(${user.id})">
                        Удалить
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    })
    .catch(error => showNotification('Ошибка при загрузке пользователей', 'error'));
}

function editUser(id, name, email, isAdmin) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close-modal" onclick="this.parentElement.parentElement.remove()">&times;</span>
            <h2>Редактирование пользователя</h2>
            <form id="edit-user-form">
                <div class="form-group">
                    <label for="name">Имя:</label>
                    <input type="text" id="name" value="${name}" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" value="${email}" required>
                </div>
                <div class="form-group">
                    <label for="is_admin">Администратор:</label>
                    <select id="is_admin">
                        <option value="0" ${!isAdmin ? 'selected' : ''}>Нет</option>
                        <option value="1" ${isAdmin ? 'selected' : ''}>Да</option>
                    </select>
                </div>
                <button type="submit" class="admin-btn">Сохранить</button>
            </form>
        </div>
    `;
    document.body.appendChild(modal);
    modal.style.display = 'block';

    document.getElementById('edit-user-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('action', 'updateUser');
        formData.append('id', id);
        formData.append('name', document.getElementById('name').value);
        formData.append('email', document.getElementById('email').value);
        formData.append('is_admin', document.getElementById('is_admin').value);

        fetch('admin.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Пользователь успешно обновлен', 'success');
                modal.remove();
                loadUsers();
            } else {
                showNotification('Ошибка при обновлении пользователя', 'error');
            }
        })
        .catch(error => showNotification('Ошибка при обновлении пользователя', 'error'));
    });
}

function deleteUser(id) {
    if (confirm('Вы уверены, что хотите удалить этого пользователя?')) {
        fetch('admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=deleteUser&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Пользователь успешно удален', 'success');
                loadUsers();
            } else {
                showNotification('Ошибка при удалении пользователя', 'error');
            }
        })
        .catch(error => showNotification('Ошибка при удалении пользователя', 'error'));
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function handleLogout() {
    fetch('logout.php')
    .then(() => {
        window.location.href = 'index.php';
    })
    .catch(error => showNotification('Ошибка при выходе', 'error'));
}

// Функции для работы с фильмами
function loadMovies() {
    fetch('movie_handler.php?action=getMovies')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.querySelector('#movies-table tbody');
                tbody.innerHTML = '';
                
                data.movies.forEach(movie => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${movie.id}</td>
                        <td>${movie.title}</td>
                        <td>${movie.genre}</td>
                        <td>${movie.release_year}</td>
                        <td><img src="${movie.image_url}" alt="${movie.title}" style="width: 50px;"></td>
                        <td>
                            <button onclick="deleteMovie(${movie.id})" class="delete-btn">Удалить</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(error => showNotification('Ошибка при загрузке фильмов', 'error'));
}

// Обработка добавления нового фильма
document.addEventListener('DOMContentLoaded', () => {
    const addMovieForm = document.getElementById('add-movie-form');
    if (addMovieForm) {
        addMovieForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'addMovie');
            formData.append('title', document.getElementById('movie-title').value);
            formData.append('genre', document.getElementById('movie-genre').value);
            formData.append('release_year', document.getElementById('movie-year').value);
            formData.append('image_url', document.getElementById('movie-image').value);
            
            fetch('movie_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Фильм успешно добавлен!', 'success');
                    addMovieForm.reset();
                    loadMovies();
                } else {
                    showNotification('Ошибка при добавлении фильма: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Произошла ошибка при добавлении фильма', 'error');
            });
        });
    }
});

// Удаление фильма
function deleteMovie(movieId) {
    if (confirm('Вы уверены, что хотите удалить этот фильм?')) {
        const formData = new FormData();
        formData.append('action', 'deleteMovie');
        formData.append('id', movieId);
        
        fetch('movie_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Фильм успешно удален!', 'success');
                loadMovies();
            } else {
                showNotification('Ошибка при удалении фильма: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Произошла ошибка при удалении фильма', 'error');
        });
    }
}
