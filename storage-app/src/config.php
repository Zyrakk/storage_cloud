<?php

// Directorio de subida
define('UPLOAD_PATH', getenv('UPLOAD_PATH') ?: '/mnt/remote_storage');

// Límite máximo de subida por archivo
define('MAX_UPLOAD_SIZE', 1 * 1024 * 1024 * 1024); // 1 GB

// Parámetros de conexión
define('DB_HOST',     getenv('DB_HOST')     ?: 'db');
define('DB_PORT',     getenv('DB_PORT')     ?: '5432');
define('DB_NAME',     getenv('POSTGRES_DB') ?: 'authdb');
define('DB_USER',     getenv('POSTGRES_USER') ?: 'auth_user');
define('DB_PASS',     getenv('POSTGRES_PASSWORD') ?: 'auth_pass');
