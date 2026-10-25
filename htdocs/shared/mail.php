<?php
require_once __DIR__ . '/vendor/PHPMailer/Exception.php';
require_once __DIR__ . '/vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/vendor/PHPMailer/SMTP.php';

function send_system_email(string $to, string $subject, string $html): bool
{
    global $config;
    if (empty($config['SMTP_HOST']) || empty($config['SMTP_USERNAME']) || empty($config['SMTP_PASSWORD']) || empty($config['SMTP_FROM_EMAIL'])) {
        error_log('Email not sent: SMTP configuration is incomplete.');
        return false;
    }
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string)$config['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = (string)$config['SMTP_USERNAME'];
        $mail->Password = (string)$config['SMTP_PASSWORD'];
        $mail->Port = (int)$config['SMTP_PORT'];
        $mail->SMTPSecure = (string)$config['SMTP_ENCRYPTION'];
        $mail->CharSet = 'UTF-8';
        $mail->setFrom((string)$config['SMTP_FROM_EMAIL'], (string)$config['SMTP_FROM_NAME']);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = trim(strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $html)));
        return $mail->send();
    } catch (Throwable $e) {
        error_log('SMTP delivery failed: ' . $e->getMessage());
        return false;
    }
}
