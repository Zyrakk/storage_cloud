<?php
require __DIR__ . '/src/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$pdo = getDb();

// Flash messages
$uploadError   = $_SESSION['upload_error']   ?? null; unset($_SESSION['upload_error']);
$deleteError   = $_SESSION['delete_error']   ?? null; unset($_SESSION['delete_error']);
$deleteSuccess = $_SESSION['delete_success'] ?? null; unset($_SESSION['delete_success']);

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
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --gradient-bg: linear-gradient(135deg, #0b0e13, #161a22);
      --card-bg: rgba(255,255,255,0.04);
      --accent: #2398f6;
      --accent-dark: #8e44ad;
      --text-light: #ffffff;
      --text-muted: #b0bac5;
    }
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: 'Montserrat', sans-serif;
      background: var(--gradient-bg);
      color: var(--text-light);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }
    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      grid-template-rows: repeat(2, auto);
      gap: 2rem;
      width: 100%;
      max-width: 1200px;
    }
    .card {
      background: var(--card-bg);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 16px 48px rgba(0,0,0,0.6);
    }
    .card h2 {
      margin-bottom: 1rem;
      font-size: 1.5rem;
      color: var(--text-light);
    }
    .btn-logout {
      padding: 0.5rem 1rem;
      border: 2px solid var(--accent);
      background: var(--accent);
      color: var(--text-light);
      border-radius: 50px;
      font-weight: 600;
      transition: background 0.3s;
      text-decoration: none;
    }
    .btn-logout:hover {
      background: var(--accent-dark);
    }
    .upload input[type=file] {
      width: 100%;
      padding: 0.5rem;
      margin-bottom: 1rem;
      border-radius: 8px;
      background: rgba(255,255,255,0.1);
      color: var(--text-light);
      border: none;
    }
    .upload button {
      padding: 0.5rem 1rem;
      border: 2px solid var(--accent);
      background: var(--accent);
      color: var(--text-light);
      border-radius: 50px;
      font-weight: 600;
      transition: background 0.3s;
    }
    .upload button:hover {
      background: var(--accent-dark);
    }
    .files-list table {
      width: 100%;
      border-collapse: collapse;
    }
    .files-list th, .files-list td {
      padding: 0.75rem;
      text-align: left;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      font-size: 0.95rem;
      color: var(--text-muted);
    }
    .files-list th { color: var(--text-light); }
    .files-list tbody tr:hover {
      background: rgba(255,255,255,0.05);
    }
    .files-list a {
      color: var(--accent);
      font-weight: 500;
      transition: color 0.3s;
      text-decoration: none;
    }
    .files-list a:hover {
      color: var(--accent-dark);
    }
    .files-list .btn-delete {
      background: none;
      border: none;
      color: var(--accent);
      cursor: pointer;
      font-size: 0.95rem;
      text-decoration: underline;
      margin-left: 0.5rem;
      padding: 0;
    }
    .error { color: #e74c3c; margin-bottom: 1rem; }
    .success { color: #2ecc71; margin-bottom: 1rem; }
    .metrics .metric-value {
      font-size: 2.5rem;
      font-weight: 600;
      margin-top: 0.5rem;
    }
  </style>
</head>
<body>
  <div class="dashboard-grid">
    <!-- Bienvenida -->
    <div class="card welcome">
      <h2>Bienvenido, usuario #<?= htmlspecialchars($userId) ?></h2>
      <p>Administra tus archivos de manera sencilla.</p>
      <a class="btn-logout" href="logout.php">Cerrar sesión</a>
    </div>
    <!-- Subir archivo -->
    <div class="card upload">
      <h2>Subir archivo</h2>
      <form action="upload.php" method="POST" enctype="multipart/form-data">
        <input type="file" name="file" required>
        <button type="submit">Subir</button>
      </form>
      <?php if ($uploadError): ?>
        <p class="error"><?= htmlspecialchars($uploadError) ?></p>
      <?php endif; ?>
    </div>
    <!-- Lista de archivos -->
    <div class="card files-list">
      <h2>Mis archivos (<?= $fileCount ?>)</h2>
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
    <!-- Métricas -->
    <div class="card metrics">
      <h2>Total de archivos</h2>
      <div class="metric-value"><?= $fileCount ?></div>
    </div>
  </div>
</body>
</html>
