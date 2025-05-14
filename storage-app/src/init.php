<?php
// Inicialización global: sesión, autoload, DB y métricas
session_start();
require __DIR__ . '/config.php';
require __DIR__ . '/../vendor/autoload.php';


use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;

// Conexión PDO a PostgreSQL
function getDb(): PDO {
    static $pdo;
    if (!$pdo) {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            DB_HOST, DB_PORT, DB_NAME
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

// Métricas Prometheus en memoria
$registry      = new CollectorRegistry(new InMemory());
$loginAttempts = $registry->getOrRegisterCounter('auth', 'login_attempts', 'Total login attempts');
$totpSuccess   = $registry->getOrRegisterCounter('auth', 'totp_success',   'TOTP successes');
$totpFail      = $registry->getOrRegisterCounter('auth', 'totp_fail',      'TOTP failures');
