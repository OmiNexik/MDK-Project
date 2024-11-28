<?php
session_start();

if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [];
}

$users = &$_SESSION['users'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'register') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if ($name && $email && $password) {
            $emailExists = false;

            foreach ($users as $user) {
                if ($user['email'] === $email) {
                    $emailExists = true;
                    break;
                }
            }

            if ($emailExists) {
                $message = 'Пользователь с таким Email уже зарегистрирован.';
                $messageType = 'error';
            } else {
                $users[] = [
                    'name' => $name,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                ];
                $message = 'Регистрация успешна! Теперь вы можете войти.';
                $messageType = 'success';
            }
        } else {
            $message = 'Пожалуйста, заполните все поля.';
            $messageType = 'error';
        }
    }

    if ($action === 'login') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if ($email && $password) {
            $userFound = false;

            foreach ($users as $user) {
                if ($user['email'] === $email && password_verify($password, $user['password'])) {
                    $_SESSION['user'] = $user['name'];
                    $userFound = true;
                    header('Location: loginpage.html');
                    exit;
                }
            }

            if (!$userFound) {
                $message = 'Неверный Email или пароль.';
                $messageType = 'error';
            }
        } else {
            $message = 'Пожалуйста, заполните все поля.';
            $messageType = 'error';
        }
    }
}
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
    <div class="signup">
        <h2 class="form-title" id="signup">Регистрация</h2>
        <form method="POST">
            <input type="hidden" name="action" value="register">
            <div class="form-holder">
                <input type="text" name="name" class="input" placeholder="Имя" required>
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
</div>

<?php if (isset($message) && isset($messageType)): ?>
    <div id="toast" class="toast <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<script>
    <?php if (isset($message) && isset($messageType)): ?>
    var toast = document.getElementById('toast');
    toast.classList.add('show');
    setTimeout(function() {
        toast.classList.remove('show');
    }, 3000);
    <?php endif; ?>
</script>
<script src="js/main.js"></script>
</body>
</html>