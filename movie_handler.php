<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "mdk_project";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'addMovie':
            if (!isset($_POST['title'], $_POST['genre'], $_POST['release_year'], $_POST['image_url'])) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit();
            }

            $title = $conn->real_escape_string($_POST['title']);
            $genre = $conn->real_escape_string($_POST['genre']);
            $release_year = (int)$_POST['release_year'];
            $image_url = $conn->real_escape_string($_POST['image_url']);

            $sql = "INSERT INTO movies (title, genre, release_year, image_url) 
                    VALUES ('$title', '$genre', $release_year, '$image_url')";

            if ($conn->query($sql)) {
                echo json_encode(['success' => true, 'message' => 'Movie added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
            }
            break;

        case 'deleteMovie':
            if (!isset($_POST['id'])) {
                echo json_encode(['success' => false, 'message' => 'Missing movie ID']);
                exit();
            }

            $id = (int)$_POST['id'];
            $sql = "DELETE FROM movies WHERE id = $id";

            if ($conn->query($sql)) {
                echo json_encode(['success' => true, 'message' => 'Movie deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
            }
            break;

        case 'getMovies':
            $sql = "SELECT * FROM movies ORDER BY created_at DESC";
            $result = $conn->query($sql);
            
            $movies = [];
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $movies[] = $row;
                }
            }
            
            echo json_encode(['success' => true, 'movies' => $movies]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
