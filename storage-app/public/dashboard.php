<?php
require __DIR__ . '/src/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$pdo = getDb();

// Flash messages
$uploadError = $_SESSION['upload_error'] ?? null;
unset($_SESSION['upload_error']);
$deleteError = $_SESSION['delete_error'] ?? null;
unset($_SESSION['delete_error']);
$deleteSuccess = $_SESSION['delete_success'] ?? null;
unset($_SESSION['delete_success']);

// Fetch files
$stmt = $pdo->prepare('
    SELECT id, filename, path, uploaded_at
    FROM files
    WHERE user_id = ?
    ORDER BY uploaded_at DESC
');
$stmt->execute([$userId]);
$files = $stmt->fetchAll();
$fileCount = count($files);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mi Panel · Storage</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
  <style>
    /* (… estilos existentes …) */
    .files-list a { color: var(--accent); }
    .files-list .btn-delete {
      background: none;
      border: none;
      color: var(--accent);
      cursor: pointer;
      font-size: 0.95rem;
      text-decoration: underline;
      margin-left: 0.5rem;
    }
    .error { color: #e74c3c; margin-bottom: 1rem; }
    .success { color: #2ecc71; margin-bottom: 1rem; }
  </style>
</head>
<body>
  <div class="dashboard-grid">
    <!-- … tarjetas de bienvenida y subida … -->

    <!-- Lista de archivos -->
    <div class="card files-list">
      <h2>Mis archivos (<?= $fileCount ?>)</h2>

      <?php if ($uploadError): ?>
        <p class="error"><?= htmlspecialchars($uploadError) ?></p>
      <?php endif; ?>

      <?php if ($deleteError): ?>
        <p class="error"><?= htmlspecialchars($deleteError) ?></p>
      <?php elseif ($deleteSuccess): ?>
        <p class="success"><?= htmlspecialchars($deleteSuccess) ?></p>
      <?php endif; ?>

      <table>
        <thead>
          <tr>
            <th>Archivo</th>
            <th>Fecha</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($files as $f): ?>
          <tr>
            <td><?= htmlspecialchars($f['filename']) ?></td>
            <td><?= htmlspecialchars(substr($f['uploaded_at'], 0, 19)) ?></td>
            <td>
              <a href="/uploads/<?= urlencode($f['path']) ?>" download>Descargar</a>
              <form action="delete.php" method="POST" style="display:inline">
                <input type="hidden" name="file_id" value="<?= (int)$f['id'] ?>">
                <button type="submit" class="btn-delete">Eliminar</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- … tarjeta de métricas … -->
  </div>
</body>
</html>
