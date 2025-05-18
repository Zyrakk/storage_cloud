<?php
require __DIR__ . '/src/init.php';

// ——————— MODO DESCARGA (GET) ———————
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    $token = $_GET['token'];
    $pdo   = getDb();

    $stmt = $pdo->prepare("
        SELECT f.filename, f.path
          FROM shares s
          JOIN files  f ON f.id = s.file_id
         WHERE s.token = ?
           AND (s.expires_at IS NULL OR s.expires_at > now())
         LIMIT 1
    ");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (! $row) {
        http_response_code(404);
        exit('Enlace inválido o caducado.');
    }

    $fullPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . $row['path'];
    if (! is_file($fullPath)) {
        http_response_code(404);
        exit('Archivo no encontrado.');
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($row['filename']).'"');
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
}

// ——————— MODO GENERACIÓN (POST) ———————
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$pdo        = getDb();
$userId     = (int) $_SESSION['user_id'];
$fileId     = (int) ($_POST['file_id']    ?? 0);
$expiryType =      $_POST['expiry_type'] ?? 'never';
$expiryValue= (int) $_POST['expiry_value'] ?? 0;

// 1) Validar que el usuario es propietario
$stmt = $pdo->prepare('SELECT filename FROM files WHERE id = ? AND user_id = ?');
$stmt->execute([$fileId, $userId]);
if (! $stmt->fetchColumn()) {
    $_SESSION['share_error'] = 'Archivo no encontrado o no autorizado.';
    header('Location: dashboard.php');
    exit;
}

// 2) Calcular expires_at
switch ($expiryType) {
    case 'hours':
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryValue} hours"));
        break;
    case 'days':
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryValue} days"));
        break;
    default:
        $expiresAt = null;
}

// 3) ¿Ya existía un share válido?
$stmt = $pdo->prepare("
    SELECT token
      FROM shares
     WHERE file_id = ?
       AND (expires_at IS NULL OR expires_at > now())
     ORDER BY created_at DESC
     LIMIT 1
");
$stmt->execute([$fileId]);
$token = $stmt->fetchColumn();

if (! $token) {
    $token = bin2hex(random_bytes(16));
    $insert = $pdo->prepare('
        INSERT INTO shares (file_id, token, expires_at)
        VALUES (:fid, :tok, :exp)
    ');
    $insert->execute([
        ':fid' => $fileId,
        ':tok' => $token,
        ':exp' => $expiresAt,
    ]);
} else {
    // actualizar caducidad
    $update = $pdo->prepare('
        UPDATE shares
           SET expires_at = :exp
         WHERE token = :tok
    ');
    $update->execute([
        ':exp' => $expiresAt,
        ':tok' => $token,
    ]);
}

// 4) Construir URL pública
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
$shareUrl = "{$scheme}://{$host}/share.php?token={$token}";

$_SESSION['share_url'] = $shareUrl;
header('Location: dashboard.php');
exit;
