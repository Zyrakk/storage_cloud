<?php
// Inicialización global: sesión, config, autoload, DB y métricas
session_start();

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/config.php';

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\Redis as RedisStorage;

// ------------------------------------------------------
// 1) Configura Redis como adaptador por defecto
// ------------------------------------------------------
RedisStorage::setDefaultOptions([
    'host'                   => getenv('REDIS_HOST')     ?: '127.0.0.1',
    'port'                   => getenv('REDIS_PORT')     ?: 6379,
    'password'               => getenv('REDIS_PASS')     ?: null,
    'timeout'                => 0.1,       // segundos para conectar
    'read_timeout'           => '10',      // segundos para lectura
    'persistent_connections' => false,
]);

// ------------------------------------------------------
// 2) Conexión PDO a PostgreSQL
// ------------------------------------------------------
function getDb(): PDO {
    static $pdo;
    if (!$pdo) {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

// ------------------------------------------------------
// 3) Métricas Prometheus usando el Default Registry
// ------------------------------------------------------
// CollectorRegistry::getDefault() ya usa el adaptador Redis configurado
$registry      = CollectorRegistry::getDefault();

$loginAttempts = $registry->getOrRegisterCounter(
    'auth',            // namespace
    'login_attempts',  // nombre de la métrica
    'Total login attempts'
);

$totpSuccess = $registry->getOrRegisterCounter(
    'auth',
    'totp_success',
    'TOTP successes'
);

$totpFail = $registry->getOrRegisterCounter(
    'auth',
    'totp_fail',
    'TOTP failures'
);
