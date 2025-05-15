<?php
// src/init.php

// Inicialización global: sesión, configuración, autoload, DB y métricas
session_start();

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
// 5) Helper para registrar o recuperar un Counter
// ------------------------------------------------------
function getOrRegisterCounter(string $namespace, string $name, string $help) {
    global $registry;
    try {
        return $registry->registerCounter($namespace, $name, $help);
    } catch (MetricAlreadyRegistered $e) {
        return $registry->getCounter($namespace, $name);
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
