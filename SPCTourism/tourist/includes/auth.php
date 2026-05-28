<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'tourist') {
    $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
    header('Location: ../login.php');
    exit;
}
