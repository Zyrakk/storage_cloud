<?php
// inicialización global: sesión, autoload, DB y métricas
session_start();
require __DIR__ . '/../vendor/autoload.php';

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

// Conexión PDO a PostgreSQL
function getDb(): PDO {
    static $pdo;
    if (!$pdo) {
        $dsn = 'pgsql:host=db;port=5432;dbname=authdb';
        $pdo = new PDO($dsn, 'auth_user', 'auth_pass', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }
    return $pdo;
}

// Métricas Prometheus en memoria
$registry = new CollectorRegistry(new \Prometheus\Storage\InMemory());
$loginAttempts = $registry->getOrRegisterCounter('', 'auth_login_attempts', 'Total login attempts');
$totpSuccess   = $registry->getOrRegisterCounter('', 'auth_totp_success',   'TOTP successes');
$totpFail      = $registry->getOrRegisterCounter('', 'auth_totp_fail',      'TOTP failures');
