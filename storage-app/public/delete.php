<?php
require __DIR__ . '/src/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['file_id'])) {
    header('Location: dashboard.php');
    exit;
}

$fileId = (int) $_POST['file_id'];
$pdo = getDb();

// Verificamos que el archivo pertenezca al usuario
$stmt = $pdo->prepare('SELECT path FROM files WHERE id = ? AND user_id = ?');
$stmt->execute([$fileId, $userId]);
$file = $stmt->fetch();

if (!$file) {
    $_SESSION['delete_error'] = 'Archivo no encontrado o no autorizado.';
    header('Location: dashboard.php');
    exit;
}

// Borramos del sistema de ficheros
$filePath = UPLOAD_PATH . DIRECTORY_SEPARATOR . $file['path'];
if (is_file($filePath)) {
    @unlink($filePath);
}

// Borramos el registro en la base de datos
$stmt = $pdo->prepare('DELETE FROM files WHERE id = ?');
$stmt->execute([$fileId]);

$_SESSION['delete_success'] = 'Archivo eliminado correctamente.';
header('Location: dashboard.php');
exit;
