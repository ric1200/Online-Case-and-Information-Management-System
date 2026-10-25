<?php
// Legacy compatibility shim; SMTP values are read only from htdocs/_private/config.php.
require_once __DIR__ . '/../../../htdocs/_private/bootstrap.php';
require_once __DIR__ . '/../../../htdocs/shared/mail.php';
if (!function_exists('sendSystemEmail')) { function sendSystemEmail(string $to, string $subject, string $html): bool { return send_system_email($to, $subject, $html); } }
