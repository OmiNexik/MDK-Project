<?php
session_start();
require_once 'mail_config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: index.html');
    exit();
}

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "mdk_project";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Подключение не удалось: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (!isset($_POST['action'])) {
        echo json_encode(['success' => false, 'message' => 'Не указано действие']);
        exit();
    }
    
    if ($_POST['action'] === 'getUsers') {
        $sql = "SELECT id, name, email, is_admin FROM users";
        $result = $conn->query($sql);
        
        $users = array();
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $users[] = array(
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'is_admin' => (bool)$row['is_admin']
                );
            }
        }
        
        echo json_encode(['users' => $users]);
        exit();
    }
    
    if ($_POST['action'] === 'updateUser') {
        if (!isset($_POST['id']) || !isset($_POST['name']) || !isset($_POST['email']) || !isset($_POST['is_admin'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
        
        $id = $conn->real_escape_string($_POST['id']);
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $is_admin = (int)$_POST['is_admin'];
        
        $sql = "UPDATE users SET name = '$name', email = '$email', is_admin = $is_admin WHERE id = $id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        exit();
    }

    if ($_POST['action'] === 'getMovies') {
        error_log('Getting movies list');
        
        $sql = "SELECT * FROM movies ORDER BY id DESC";
        $result = $conn->query($sql);
        
        if ($result === false) {
            error_log('SQL Error: ' . $conn->error);
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $conn->error
            ]);
            exit();
        }
        
        $movies = array();
        while($row = $result->fetch_assoc()) {
            $movies[] = array(
                'id' => $row['id'],
                'title' => $row['title'],
                'genre' => $row['genre'],
                'release_year' => $row['release_year'],
                'image_url' => $row['image_url']
            );
        }
        
        error_log('Found ' . count($movies) . ' movies');
        error_log('Movies data: ' . print_r($movies, true));
        
        echo json_encode([
            'success' => true,
            'movies' => $movies
        ]);
        exit();
    }

    if ($_POST['action'] === 'getMovie') {
        if (!isset($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID не указан']);
            exit();
        }
        
        $id = $conn->real_escape_string($_POST['id']);
        $sql = "SELECT * FROM movies WHERE id = $id";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $movie = $result->fetch_assoc();
            echo json_encode([
                'success' => true,
                'movie' => [
                    'id' => $movie['id'],
                    'title' => $movie['title'],
                    'genre' => $movie['genre'],
                    'release_year' => $movie['release_year'],
                    'image_url' => $movie['image_url']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Фильм не найден']);
        }
        exit();
    }
        if (!isset($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing movie ID']);
            exit();
        }
        
        $id = $conn->real_escape_string($_POST['id']);
        $sql = "SELECT * FROM movies WHERE id = $id";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $movie = $result->fetch_assoc();
            echo json_encode(['success' => true, 'movie' => $movie]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Movie not found']);
        }
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'updateMovie') {
        error_log('Updating movie. POST data: ' . print_r($_POST, true));
        
        if (empty($_POST['id']) || empty($_POST['title']) || empty($_POST['genre']) || 
            empty($_POST['release_year'])) {
            echo json_encode([
                'success' => false, 
                'message' => 'Пожалуйста, заполните все обязательные поля (Название, Жанр, Год)'
            ]);
            exit();
        }
        
        $id = (int)$_POST['id'];
        $title = $conn->real_escape_string($_POST['title']);
        $genre = $conn->real_escape_string($_POST['genre']);
        $release_year = (int)$_POST['release_year'];
        
        error_log("Processing update for movie ID: $id");
        error_log("New values - Title: $title, Genre: $genre, Year: $release_year");
        
        // Обработка изображения при загрузке файла
        $image_url_update = '';
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            error_log('Processing new image upload');
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
            
            if (!in_array($file_extension, $allowed_extensions)) {
                echo json_encode(['success' => false, 'message' => 'Неверный формат файла. Разрешены только: ' . implode(', ', $allowed_extensions)]);
                exit();
            }
            
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_path)) {
                $image_url_update = ", image_url = '$upload_path'";
                error_log("New image path: $upload_path");
            } else {
                echo json_encode(['success' => false, 'message' => 'Ошибка при загрузке файла']);
                exit();
            }
        }
        
        // Обновляем данные фильма
        $sql = "UPDATE movies SET 
                title = '$title', 
                genre = '$genre', 
                release_year = $release_year
                $image_url_update 
                WHERE id = $id";
        
        error_log("Executing SQL: $sql");
        
        if ($conn->query($sql)) {
            error_log("Update successful for movie ID: $id");
            echo json_encode(['success' => true, 'message' => 'Фильм успешно обновлен']);
        } else {
            error_log("Update failed. MySQL Error: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении фильма: ' . $conn->error]);
        }
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'addMovie') {
        if (empty($_POST['title']) || empty($_POST['genre']) || empty($_POST['release_year'])) {
            echo json_encode(['success' => false, 'message' => 'Пожалуйста, заполните все обязательные поля (Название, Жанр, Год)']);
            exit();
        }
        
        $title = $conn->real_escape_string($_POST['title']);
        $genre = $conn->real_escape_string($_POST['genre']);
        $release_year = (int)$_POST['release_year'];
        
        // Handle image file upload
        $image_url = 'images/default-movie.jpg';
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
            
            if (!in_array($file_extension, $allowed_extensions)) {
                echo json_encode(['success' => false, 'message' => 'Неверный формат файла. Разрешены только: ' . implode(', ', $allowed_extensions)]);
                exit();
            }
            
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_path)) {
                $image_url = $upload_path;
            } else {
                echo json_encode(['success' => false, 'message' => 'Ошибка при загрузке файла']);
                exit();
            }
        }
        
        $sql = "INSERT INTO movies (title, genre, release_year, image_url) 
                VALUES ('$title', '$genre', $release_year, '$image_url')";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        exit();
    }

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineFlow - Панель администратора</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Metal+Mania:wght@400&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="css/media.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .image-input-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .image-input-container span {
            text-align: center;
            font-weight: bold;
            color: #666;
        }
        
        .image-input-container input[type="url"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .image-input-container input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="header__admin">
            <div class="logotype__admin"><span class="logotype">CineFlow</span></div>
            <div class="admin-title">Панель администратора</div>
            <div class="admin-nav">
                <button class="admin-btn" onclick="window.location.href='index.html'">На главную</button>
                <button class="admin-btn" onclick="handleLogout()">Выход</button>
            </div>
        </div>

        <div class="admin-content">
            <div class="sidebar">
                <button class="sidebar-btn active" data-section="users">Пользователи</button>
                <button class="sidebar-btn" data-section="movies">Фильмы</button>
                <button class="sidebar-btn" data-section="shows">ТВ шоу</button>
                <button class="sidebar-btn" data-section="categories">Категории</button>
            </div>

            <div class="main-content">
                <div class="section" id="users-section">
                    <h2>Управление пользователями</h2>
                    <div class="table-container">
                        <table id="users-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Имя</th>
                                    <th>Email</th>
                                    <th>Админ</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="section" id="movies-section">
                    <h2>Управление фильмами</h2>
                    <div class="add-movie-form">
                        <h3>Добавить новый фильм</h3>
                        <form id="add-movie-form" onsubmit="return false;" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="movie-title">Название фильма:</label>
                                <input type="text" id="movie-title" name="title">
                            </div>
                            <div class="form-group">
                                <label for="movie-genre">Жанр:</label>
                                <input type="text" id="movie-genre" name="genre">
                            </div>
                            <div class="form-group">
                                <label for="movie-year">Год выпуска:</label>
                                <input type="number" id="movie-year" name="release_year" min="1900" max="2099">
                            </div>
                            <div class="form-group">
                                <label>Изображение:</label>
                                <div class="image-input-container">
                                    <input type="file" id="movie-image-file" name="image_file" accept="image/*">
                                </div>
                            </div>
                            <input type="hidden" id="movie-id" name="id">
                            <button type="button" class="admin-btn" onclick="handleMovieSubmit()">Добавить фильм</button>
                        </form>
                    </div>
                    <div class="table-container">
                        <table id="movies-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Жанр</th>
                                    <th>Год</th>
                                    <th>Изображение</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="section hidden" id="shows-section">
                    <h2>Управление ТВ шоу</h2>
                </div>

                <div class="section hidden" id="categories-section">
                    <h2>Управление категориями</h2>
                </div>
            </div>
        </div>
    </div>

    <script src="admin.js"></script>
    <script>
        function handleMovieSubmit() {
            const form = document.getElementById('add-movie-form');
            const title = document.getElementById('movie-title').value.trim();
            const genre = document.getElementById('movie-genre').value.trim();
            const year = document.getElementById('movie-year').value.trim();
            const fileInput = document.getElementById('movie-image-file');
            
            // Проверка обязательных полей
            if (!title || !genre || !year) {
                alert('Пожалуйста, заполните все обязательные поля (Название, Жанр, Год)');
                return;
            }
            
            console.log('Form data before submission:');
            console.log('Title:', title);
            console.log('Genre:', genre);
            console.log('Year:', year);
            console.log('Edit mode:', form.dataset.mode);
            console.log('Movie ID:', form.dataset.movieId);
            
            const formData = new FormData(form);
            const isEdit = form.dataset.mode === 'edit';
            formData.append('action', isEdit ? 'updateMovie' : 'addMovie');
            if (isEdit) {
                formData.append('id', form.dataset.movieId);
            }
            
            console.log('FormData entries:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(isEdit ? 'Фильм успешно обновлен' : 'Фильм успешно добавлен');
                    form.reset();
                    form.dataset.mode = 'add';
                    form.dataset.movieId = '';
                    document.querySelector('#add-movie-form button.admin-btn').textContent = 'Добавить фильм';
                    loadMovies();
                } else {
                    alert('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при отправке формы');
            });
        }

        // Функция редактирования фильма
        function editMovie(id) {
            console.log('Editing movie:', id);
            const formData = new FormData();
            formData.append('action', 'getMovie');
            formData.append('id', id);
            
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const movie = data.movie;
                    document.getElementById('movie-title').value = movie.title;
                    document.getElementById('movie-genre').value = movie.genre;
                    document.getElementById('movie-year').value = movie.release_year;
                    
                    const form = document.getElementById('add-movie-form');
                    form.dataset.mode = 'edit';
                    form.dataset.movieId = movie.id;
                    document.querySelector('#add-movie-form button.admin-btn').textContent = 'Сохранить изменения';
                } else {
                    alert('Ошибка: ' + (data.message || 'Не удалось загрузить данные фильма'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при загрузке данных фильма');
            });
        }

        // Функция загрузки фильмов
        function loadMovies() {
            console.log('Loading movies...'); // Отладка
            
            const formData = new FormData();
            formData.append('action', 'getMovies');
            
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response received:', response); // Отладка
                return response.json();
            })
            .then(result => {
                console.log('Parsed result:', result); // Отладка
                
                if (!result.success) {
                    throw new Error(result.message || 'Ошибка загрузки фильмов');
                }

                const tableBody = document.querySelector('#movies-table tbody');
                if (!tableBody) {
                    console.error('Table body not found!'); // Отладка
                    return;
                }
                
                console.log('Clearing table...'); // Отладка
                tableBody.innerHTML = '';
                
                if (!result.movies || result.movies.length === 0) {
                    console.log('No movies found'); // Отладка
                    const row = document.createElement('tr');
                    row.innerHTML = '<td colspan="6" style="text-align: center;">Фильмы не найдены</td>';
                    tableBody.appendChild(row);
                    return;
                }
                
                console.log(`Adding ${result.movies.length} movies to table`); // Отладка
                result.movies.forEach(movie => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${movie.id}</td>
                        <td>${movie.title}</td>
                        <td>${movie.genre}</td>
                        <td>${movie.release_year}</td>
                        <td><img src="${movie.image_url}" alt="${movie.title}" style="max-width: 100px;"></td>
                        <td>
                            <button onclick="editMovie(${movie.id})" class="admin-btn edit-btn">Редактировать</button>
                            <button onclick="deleteMovie(${movie.id})" class="admin-btn delete-btn">Удалить</button>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
                console.log('Table updated successfully'); // Отладка
            })
            .catch(error => {
                console.error('Error loading movies:', error);
                const tableBody = document.querySelector('#movies-table tbody');
                if (tableBody) {
                    tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center;">Ошибка загрузки фильмов: ${error.message}</td></tr>`;
                }
            });
        }

        // Загружаем фильмы при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing...'); // Отладка
            
            // Показываем раздел фильмов при загрузке
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => section.classList.add('hidden'));
            document.getElementById('movies-section').classList.remove('hidden');
            
            // Загружаем фильмы
            loadMovies();
        });

        // Загрузка фильмов при открытии раздела
        document.querySelector('[data-section="movies"]').addEventListener('click', function() {
            loadMovies();
        });

        // Загрузка фильмов при загрузке страницы, если открыт раздел фильмов
        if (document.querySelector('#movies-section').style.display !== 'none') {
            loadMovies();
        }
    </script>
</body>
</html>
