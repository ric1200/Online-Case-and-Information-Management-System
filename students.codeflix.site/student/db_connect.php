<?php
$host     = 'localhost';
$dbname   = 'code2025_login_system';
$username = 'code2025';
$password = 'codeflixmoto2025';
$charset  = 'utf8mb4';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset($charset);