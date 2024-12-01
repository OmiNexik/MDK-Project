<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';

function sendVerificationCode($to, $code) {
    $mail = new PHPMailer(true);

    try {
        // Enable debug mode
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'kuleminson.vova@gmail.com';
        $mail->Password = 'piuu xtlh npka xstz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port = 465; 
        
        // Увеличиваем таймаут
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
        $mail->Subject = 'Код подтверждения регистрации';
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
                <h2>Добро пожаловать в CineFlow!</h2>
                <p>Ваш код подтверждения:</p>
                <div class='code'>$code</div>
                <p>Введите этот код на странице регистрации для подтверждения вашего email.</p>
            </body>
            </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Detailed error logging
        // error_log("PHPMailer Error: " . $mail->ErrorInfo);
        // error_log("Full error details: " . $e->getMessage());
        return false;
    }
}
