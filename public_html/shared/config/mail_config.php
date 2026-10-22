<?php
// Central SMTP Mail Configuration
// Used by all subdomains (admin, students, counseling)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/PHPMailer/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/SMTP.php';

/**
 * Send system email using SMTP
 *
 * @param string $to
 * @param string $subject
 * @param string $bodyHtml
 * @return bool
 */
function sendSystemEmail($to, $subject, $bodyHtml) {
    $mail = new PHPMailer(true);

    try {
        // SMTP Settings
        $mail->isSMTP();
        $mail->Host       = 'mail.codeflix.site';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'no-reply@codeflix.site';
        $mail->Password   = 'codeflixmoto2025';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Email headers
        $mail->setFrom('no-reply@codeflix.site', 'Counseling System');
        $mail->addAddress($to);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = strip_tags($bodyHtml);

        // Send email
        $mail->send();
        return true;

    } catch (Exception $e) {
        return false;
    }
}
