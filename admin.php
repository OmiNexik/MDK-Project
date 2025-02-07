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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
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
                        <form id="add-movie-form">
                            <div class="form-group">
                                <label for="movie-title">Название фильма:</label>
                                <input type="text" id="movie-title" required>
                            </div>
                            <div class="form-group">
                                <label for="movie-genre">Жанр:</label>
                                <input type="text" id="movie-genre" required>
                            </div>
                            <div class="form-group">
                                <label for="movie-year">Год выпуска:</label>
                                <input type="number" id="movie-year" min="1900" max="2099" required>
                            </div>
                            <div class="form-group">
                                <label for="movie-image">URL изображения:</label>
                                <input type="url" id="movie-image" required>
                            </div>
                            <button type="submit" class="admin-btn">Добавить фильм</button>
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
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="section hidden" id="movies-section">
                    <h2>Управление фильмами</h2>
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
</body>
</html>
