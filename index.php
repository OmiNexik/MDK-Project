<?php
session_start();
require_once 'mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "mdk_project";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Подключение не удалось: " . $conn->connect_error);
}

function sendResetCode($to, $code) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'kuleminson.vova@gmail.com';
        $mail->Password = 'piuu xtlh npka xstz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        
        $mail->Timeout = 60;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('kuleminson.vova@gmail.com', 'CineFlow');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Сброс пароля';
        $mail->Body = "
            <html>
            <head>
                <style>
                    .code {
                        font-size: 24px;
                        font-weight: bold;
                        color: #333;
                        padding: 10px;
                        background: #f5f5f5;
                        border-radius: 5px;
                    }
                </style>
            </head>
            <body>
                <h2>Сброс пароля в CineFlow</h2>
                <p>Ваш код для сброса пароля:</p>
                <div class='code'>$code</div>
                <p>Введите этот код на странице сброса пароля для установки нового пароля.</p>
                <p>Если вы не запрашивали сброс пароля, проигнорируйте это письмо.</p>
            </body>
            </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
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
                        $mail = new PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'kuleminson.vova@gmail.com';
                            $mail->Password = 'piuu xtlh npka xstz';
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                            $mail->Port = 465;
                            
                            $mail->Timeout = 60;
                            $mail->SMTPOptions = array(
                                'ssl' => array(
                                    'verify_peer' => false,
                                    'verify_peer_name' => false,
                                    'allow_self_signed' => true
                                )
                            );

                            $mail->setFrom('kuleminson.vova@gmail.com', 'CineFlow');
                            $mail->addAddress($email);

                            $mail->isHTML(true);
                            $mail->CharSet = 'UTF-8';
                            $mail->Subject = 'Подтверждение Email';
                            $mail->Body = "Ваш код подтверждения: " . $verification_code;

                            $mail->send();
                            $_SESSION['pending_email'] = $email;
                            $message = 'Код подтверждения отправлен на ваш email.';
                            $messageType = 'success';
                        } catch (Exception $e) {
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
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['name'];
                        $_SESSION['is_admin'] = $user['is_admin'];
                        echo "<script>
                            localStorage.setItem('username', '" . addslashes($user['name']) . "');
                            localStorage.setItem('isAdmin', '" . ($user['is_admin'] ? 'true' : 'false') . "');
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

    if ($action === 'forgot_password') {
        $email = trim($_POST['email']);
        
        if ($email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Пожалуйста, введите корректный email адрес.';
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    $message = 'Пользователь с таким email не найден.';
                    $messageType = 'error';
                } else {
                    $reset_code = sprintf("%06d", mt_rand(0, 999999));
                    
                    $stmt = $conn->prepare("UPDATE users SET verification_code = ? WHERE email = ?");
                    $stmt->bind_param("ss", $reset_code, $email);
                    $stmt->execute();
                    
                    if (sendResetCode($email, $reset_code)) {
                        $_SESSION['reset_email'] = $email;
                        $message = 'Код для сброса пароля отправлен на ваш email.';
                        $messageType = 'success';
                    } else {
                        $message = 'Ошибка при отправке кода. Попробуйте позже.';
                        $messageType = 'error';
                    }
                }
            }
        }
    }

    if ($action === 'reset_password') {
        $code = trim($_POST['code']);
        $new_password = trim($_POST['new_password']);
        $email = $_SESSION['reset_email'] ?? '';

        if ($code && $new_password && $email) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND verification_code = ?");
            $stmt->bind_param("ss", $email, $code);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $message = 'Неверный код подтверждения.';
                $messageType = 'error';
            } else {
                $hashedPassword = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET password = ?, verification_code = NULL WHERE email = ?");
                $stmt->bind_param("ss", $hashedPassword, $email);
                $stmt->execute();

                unset($_SESSION['reset_email']);
                $message = 'Пароль успешно изменен. Теперь вы можете войти.';
                $messageType = 'success';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Авторизация и регистрация</title>
    <style>
        /* Обновленные стили для секции сброса пароля */
        .forgot-password-section {
            margin-top: 15px;
            text-align: center;
            padding: 10px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .forgot-password-section h3,
        .reset-password-section h3 {
            color: rgba(0, 0, 0, 0.7);
            font-size: 15px;
            margin-bottom: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .forgot-password-section h3 i,
        .reset-password-section h3 i {
            color: #000;
            font-size: 14px;
        }

        .forgot-password-section form,
        .reset-password-section form {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-width: 280px;
            margin: 0 auto;
        }

        .input-group {
            position: relative;
            width: 100%;
        }

        .input-group i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #000;
            font-size: 14px;
        }

        .forgot-password-section input,
        .reset-password-section input {
            border: 1px solid rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.95);
            padding: 8px 8px 8px 32px;
            border-radius: 4px;
            width: 100%;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .forgot-password-section input:focus,
        .reset-password-section input:focus {
            border-color: #000;
            box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1);
            outline: none;
        }

        .forgot-password-section button,
        .reset-password-section button {
            background: linear-gradient(135deg, #2c2c2c, #000000);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .forgot-password-section button:hover,
        .reset-password-section button:hover {
            background: linear-gradient(135deg, #000000, #2c2c2c);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .forgot-password-section button:active,
        .reset-password-section button:active {
            transform: translateY(0);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            background: #000000;
        }

        .reset-password-section {
            margin-top: 15px;
            padding: 10px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
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
            
            <!-- Обновляем форму для сброса пароля -->
            <div class="forgot-password-section">
                <h3><i class="fas fa-key"></i>Забыли пароль?</h3>
                <form method="post" action="" id="forgotPasswordForm">
                    <input type="hidden" name="action" value="forgot_password">
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Введите ваш email" required>
                    </div>
                    <button type="submit"><i class="fas fa-paper-plane"></i>Отправить код</button>
                </form>
            </div>

            <div class="reset-password-section" id="resetPasswordSection" style="display: <?php echo isset($_SESSION['reset_email']) ? 'block' : 'none'; ?>;">
                <h3><i class="fas fa-lock"></i>Сброс пароля</h3>
                <form method="post" action="" id="resetPasswordForm">
                    <input type="hidden" name="action" value="reset_password">
                    <div class="input-group">
                        <i class="fas fa-key"></i>
                        <input type="text" name="code" placeholder="Введите код из email" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="new_password" placeholder="Новый пароль" required>
                    </div>
                    <button type="submit"><i class="fas fa-check"></i>Сменить пароль</button>
                </form>
            </div>
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