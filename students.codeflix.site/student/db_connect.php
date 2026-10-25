<?php
// Legacy compatibility alias; all database work uses the shared PDO connection.
require_once __DIR__ . '/../../htdocs/_private/bootstrap.php';
$conn = app_pdo();
