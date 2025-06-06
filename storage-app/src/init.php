<?php

// Inicialización global: sesión, configuración, autoload, DB y métricas
session_start();

$redis = new \Redis();
$redis->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT'));
$sid = session_id();
if ($redis->sIsMember('active_sessions', $sid)) {
    $redis->expire('active_sessions', 30*60);
}

// 1) Configuración de constantes y parámetros
require __DIR__ . '/config.php';

// 2) Autoload de Composer
require __DIR__ . '/../vendor/autoload.php';

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\Redis as RedisAdapter;
use Prometheus\Exception\MetricAlreadyRegistered;

// ------------------------------------------------------
// 3) Creamos el adaptador Redis para Prometheus
// ------------------------------------------------------
$redisAdapter = new RedisAdapter([
    'host'                   => getenv('REDIS_HOST')     ?: '127.0.0.1',
    'port'                   => getenv('REDIS_PORT')     ?: 6379,
    'password'               => getenv('REDIS_PASS')     ?: null,
    'timeout'                => 0.1,
    'read_timeout'           => '10',
    'persistent_connections' => false,
]);

// ------------------------------------------------------
// 4) Instanciamos el CollectorRegistry con Redis
// ------------------------------------------------------
$registry = new CollectorRegistry($redisAdapter);

// ------------------------------------------------------
// 5) Helpers
// ------------------------------------------------------

// Registrar o recuperar un Counter
function getOrRegisterCounter(string $namespace, string $name, string $help) {
    global $registry;
    try {
        return $registry->registerCounter($namespace, $name, $help);
    } catch (MetricAlreadyRegistered $e) {
        return $registry->getCounter($namespace, $name);
    }
}

// Incrementar o reducir un Counter
function getOrRegisterGauge(string $namespace, string $name, string $help) {
    global $registry;
    try {
        return $registry->registerGauge($namespace, $name, $help);
    } catch (\Prometheus\Exception\MetricAlreadyRegistered $e) {
        return $registry->getGauge($namespace, $name);
    }
}

// ------------------------------------------------------
// 6) Registramos (o recuperamos) nuestros contadores
// ------------------------------------------------------
$loginAttempts = getOrRegisterCounter(
    'auth',
    'login_attempts',
    'Total login attempts'
);

$totpSuccess = getOrRegisterCounter(
    'auth',
    'totp_success',
    'TOTP successes'
);

$totpFail = getOrRegisterCounter(
    'auth',
    'totp_fail',
    'TOTP failures'
);

$fileUploads = getOrRegisterCounter(
    'storage',               // namespace
    'file_uploads_total',    // métrica
    'Total de archivos subidos'
);

$activeSessions = getOrRegisterGauge(
    'auth',
    'active_sessions',
    'Número de sesiones activas'
);

// ------------------------------------------------------
// 7) Función de conexión a PostgreSQL
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
