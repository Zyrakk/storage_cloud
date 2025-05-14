<?php
// public/logout.php
require __DIR__ . '/../src/init.php';

// Limpio y destruyo sesión
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    setcookie(session_name(), '', time() - 42000, '/');
}
session_destroy();

// Redirijo a página inicial
header('Location: index.php');
exit;
