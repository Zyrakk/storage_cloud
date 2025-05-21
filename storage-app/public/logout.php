<?php
require __DIR__ . '/src/init.php';

// Conectar a Redis
$redis = new \Redis();
$redis->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT'));

// Eliminar el ID de sesión del set de sesiones activas
$sid = session_id();
$redis->sRem('active_sessions', $sid);

// Limpiar y destruir la sesión PHP
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', time() - 42000, '/');
}
session_destroy();

// Redirigir a la página de inicio
header('Location: index.php');
exit;
