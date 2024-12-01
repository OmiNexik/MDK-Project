<?php
session_start();
require_once 'mail_config.php';

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "mdk_project";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Подключение не удалось: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'register') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if ($name && $email && $password) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
                $message = 'Пожалуйста, введите корректный email адрес.';
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $message = 'Пользователь с таким Email уже зарегистрирован.';
                    $messageType = 'error';
                    unset($_SESSION['pending_email']); 
                } else {
                    $verification_code = sprintf("%06d", mt_rand(0, 999999));
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, verification_code, is_verified) VALUES (?, ?, ?, ?, 0)");
                    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $verification_code);
                    
                    if ($stmt->execute()) {
                        if(sendVerificationCode($email, $verification_code)) {
                            $_SESSION['pending_email'] = $email;
                            $message = 'Код подтверждения отправлен на ваш email.';
                            $messageType = 'success';
                        } else {
                            $message = 'Ошибка при отправке кода подтверждения.';
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'Произошла ошибка при регистрации.';
                        $messageType = 'error';
                    }
                }
                $stmt->close();
            }
        } else {
            $message = 'Пожалуйста, заполните все поля.';
            $messageType = 'error';
        }
    } elseif ($action === 'verify_code') {
        $email = $_SESSION['pending_email'] ?? '';
        $code = trim($_POST['verification_code']);
        
        if ($email && $code) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND verification_code = ?");
            $stmt->bind_param("ss", $email, $code);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = NULL WHERE email = ?");
                $stmt->bind_param("s", $email);
                if ($stmt->execute()) {
                    unset($_SESSION['pending_email']);
                    $message = 'Email успешно подтвержден! Теперь вы можете войти.';
                    $messageType = 'success';
                }
            } else {
                $message = 'Неверный код подтверждения.';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }

    if ($action === 'login') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if ($email && $password) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
                $message = 'Пожалуйста, введите корректный email адрес.';
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                if ($user && password_verify($password, $user['password'])) {
                    if (!$user['is_verified']) {
                        $message = 'Пожалуйста, подтвердите ваш email перед входом.';
                        $messageType = 'error';
                    } else {
                        $_SESSION['user'] = $user['name'];
                        echo "<script>
                            localStorage.setItem('username', '" . addslashes($user['name']) . "');
                            window.location.href = 'index.html';
                        </script>";
                        exit;
                    }
                } else {
                    $message = 'Неверный Email или пароль.';
                    $messageType = 'error';
                }
                $stmt->close();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/error.css">
    <title>Авторизация и регистрация</title>
</head>
<body>
<div class="form-structor">
    <?php if (isset($_SESSION['pending_email']) && $messageType !== 'error'): ?>
    <div class="signup">
        <h2 class="form-title">Подтверждение Email</h2>
        <form method="POST">
            <input type="hidden" name="action" value="verify_code">
            <div class="form-holder">
                <p style="text-align: center; color: #000;">Мы отправили код подтверждения на ваш email:<br><?php echo htmlspecialchars($_SESSION['pending_email']); ?></p>
                <input type="text" name="verification_code" class="input" placeholder="Введите код подтверждения" required maxlength="6" pattern="[0-9]{6}">
            </div>
            <button type="submit" class="submit-btn">Подтвердить</button>
        </form>
    </div>
    <?php else: ?>
    <div class="signup">
        <h2 class="form-title" id="signup">Регистрация</h2>
        <form method="POST">
            <input type="hidden" name="action" value="register">
            <div class="form-holder">
                <input type="text" name="name" class="input" placeholder="Имя" required maxlength="7">
                <input type="email" name="email" class="input" placeholder="Email" required>
                <input type="password" name="password" class="input" placeholder="Пароль" required>
            </div>
            <button type="submit" class="submit-btn">Зарегистрироваться</button>
        </form>
    </div>

    <div class="login slide-up">
        <div class="center">
            <h2 class="form-title" id="login">Вход</h2>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-holder">
                    <input type="email" name="email" class="input" placeholder="Email" required>
                    <input type="password" name="password" class="input" placeholder="Пароль" required>
                </div>
                <button type="submit" class="submit-btn">Войти</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (isset($message) && isset($messageType)): ?>
    <div id="toast" class="toast <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<script>
    <?php if (isset($message) && isset($messageType)): ?>
    document.getElementById("toast").classList.add("show");
    setTimeout(function() {
        document.getElementById("toast").classList.remove("show");
    }, 3000);
    <?php endif; ?>

    const urlParams = new URLSearchParams(window.location.search);
    const username = urlParams.get('username');
    if (username) {
        localStorage.setItem('username', username);
        window.location.href = 'index.html';
    }
</script>
<script src="js/main.js"></script>
</body>
</html>