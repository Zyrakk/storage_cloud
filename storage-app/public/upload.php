<?php
// public/upload.php
require __DIR__ . '/../src/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Solo POST y fichero sin errores
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_FILES['file'])
    && $_FILES['file']['error'] === UPLOAD_ERR_OK
) {
    // Compruebo tamaño
    if ($_FILES['file']['size'] > MAX_UPLOAD_SIZE) {
        $_SESSION['upload_error'] = 'El archivo supera el tamaño máximo permitido.';
        header('Location: dashboard.php');
        exit;
    }

    $userId   = (int)$_SESSION['user_id'];
    $name     = basename($_FILES['file']['name']);
    // Genero nombre único en disco
    $stored   = time() . "_{$userId}_" . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $name);
    $dest     = UPLOAD_PATH . DIRECTORY_SEPARATOR . $stored;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        // Inserto en base de datos
        $pdo = getDb();
        $stmt = $pdo->prepare('
            INSERT INTO files (user_id, filename, path)
            VALUES (:uid, :fname, :path)
        ');
        $stmt->execute([
            ':uid'   => $userId,
            ':fname' => $name,
            ':path'  => $stored,
        ]);
    } else {
        $_SESSION['upload_error'] = 'Error al mover el archivo.';
    }
}

header('Location: dashboard.php');
exit;
