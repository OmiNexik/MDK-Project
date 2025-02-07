<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "mdk_project";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

$sql = "SELECT * FROM movies ORDER BY created_at DESC";
$result = $conn->query($sql);

$movies = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $movies[] = [
            'title' => $row['title'],
            'genre' => $row['genre'],
            'release_year' => $row['release_year'],
            'image_url' => $row['image_url']
        ];
    }
}

echo json_encode(['success' => true, 'movies' => $movies]);

$conn->close();
?>
