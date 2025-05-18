<?php
require __DIR__ . '/src/init.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$userId     = (int) $_SESSION['user_id'];
$fileId     = (int) ($_POST['file_id']    ?? 0);
$expiryType =      $_POST['expiry_type'] ?? 'never';
$expiryValue= (int) $_POST['expiry_value'] ?? 0;

// Verificar propiedad
$pdo = getDb();
$stmt = $pdo->prepare('SELECT filename FROM files WHERE id = ? AND user_id = ?');
$stmt->execute([$fileId, $userId]);
$filename = $stmt->fetchColumn();
if (!$filename) {
    $_SESSION['share_error'] = 'Archivo no encontrado o no autorizado.';
    header('Location: dashboard.php');
    exit;
}

// Calcular expires_at
switch ($expiryType) {
    case 'hours':
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryValue} hours"));
        break;
    case 'days':
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryValue} days"));
        break;
    case 'never':
    default:
        $expiresAt = null;
}

// ¿Ya hay un share válido?
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

if (!$token) {
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
    // Actualizar caducidad si cambia
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

// Montar la URL públicamente accesible
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
$shareUrl = "{$scheme}://{$host}/share.php?token={$token}";

// Guardar para mostrar en el dashboard
$_SESSION['share_url'] = $shareUrl;
header('Location: dashboard.php');
exit;
