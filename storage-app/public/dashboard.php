<?php
require __DIR__ . '/src/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id, filename, path, uploaded_at FROM files WHERE user_id = ? ORDER BY uploaded_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$files = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mi Panel</title>
</head>
<body>
  <h1>Bienvenido, usuario #<?=htmlspecialchars($_SESSION['user_id'])?></h1>
  <p><a href="logout.php">Cerrar sesión</a></p>

  <h2>Subir archivo</h2>
  <form action="upload.php" method="POST" enctype="multipart/form-data">
    <input type="file" name="file" required>
    <button type="submit">Subir</button>
  </form>

  <h2>Mis archivos</h2>
  <?php if (!empty($_SESSION['upload_error'])): ?>
    <p style="color:red;"><?= htmlspecialchars($_SESSION['upload_error']) ?></p>
    <?php unset($_SESSION['upload_error']); ?>
  <?php endif; ?>

  <ul>
    <?php foreach ($files as $f): ?>
      <li>
        <?=htmlspecialchars($f['filename'])?> —
        <a href="/uploads/<?=urlencode($f['path'])?>" download>Descargar</a>
        (<?=htmlspecialchars(substr($f['uploaded_at'],0,19))?>)
      </li>
    <?php endforeach; ?>
  </ul>
</body>
</html>
