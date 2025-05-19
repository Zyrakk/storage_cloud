<?php
require __DIR__ . '/src/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_FILES['file']) &&
    $_FILES['file']['error'] === UPLOAD_ERR_OK
) {
    // 1) Comprobar cuota
    $stmt = getDb()->prepare('SELECT COALESCE(SUM(size),0) FROM files WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $used = (int)$stmt->fetchColumn();
    if ($used + $_FILES['file']['size'] > USER_QUOTA_BYTES) {
        $_SESSION['upload_error'] = 'Superas tu cuota de almacenamiento.';
        header('Location: dashboard.php');
        exit;
    }

    // 2) Comprobar tamaño máximo por archivo
    if ($_FILES['file']['size'] > MAX_UPLOAD_SIZE) {
        $_SESSION['upload_error'] = 'El archivo supera el tamaño máximo permitido.';
        header('Location: dashboard.php');
        exit;
    }

    $userId = (int) $_SESSION['user_id'];
    $name   = basename($_FILES['file']['name']);
    // Generar nombre único para almacenar
    $stored = time() . "_{$userId}_" .
        preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $name);
    $dest   = UPLOAD_PATH . DIRECTORY_SEPARATOR . $stored;
    $size   = (int) $_FILES['file']['size']; // tamaño en bytes

    // 3) Mover el archivo
    if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        // darle permisos de lectura a Nginx
        @chmod($dest, 0644);

        $fileUploads->inc();

        // 4) Insertar en la base de datos incluyendo el tamaño
        $pdo = getDb();
        $stmt = $pdo->prepare('
            INSERT INTO files (user_id, filename, path, size)
            VALUES (:uid, :fname, :path, :size)
        ');
        $stmt->execute([
            ':uid'   => $userId,
            ':fname' => $name,
            ':path'  => $stored,
            ':size'  => $size,
        ]);
    } else {
        $_SESSION['upload_error'] = 'Error al mover el archivo.';
    }
}

header('Location: dashboard.php');
exit;
