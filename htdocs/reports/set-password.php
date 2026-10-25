<?php
require_once __DIR__ . '/../_private/bootstrap.php';
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
redirect_to('shared/reset-password.php?portal=reports&token=' . rawurlencode($token));
