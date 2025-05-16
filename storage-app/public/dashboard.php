<?php
require __DIR__ . '/src/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$pdo    = getDb();

// Obtenemos el nombre de usuario
$stmtUser = $pdo->prepare('SELECT username FROM users WHERE id = ?');
$stmtUser->execute([$userId]);
$username = $stmtUser->fetchColumn() ?: 'Usuario';

// Flash messages
$uploadError   = $_SESSION['upload_error']   ?? null; unset($_SESSION['upload_error']);
$deleteError   = $_SESSION['delete_error']   ?? null; unset($_SESSION['delete_error']);
$deleteSuccess = $_SESSION['delete_success'] ?? null; unset($_SESSION['delete_success']);

// Fetch files
$stmt = $pdo->prepare(
    'SELECT id, filename, path, uploaded_at FROM files WHERE user_id = ? ORDER BY uploaded_at DESC'
);
$stmt->execute([$userId]);
$files     = $stmt->fetchAll();
$fileCount = count($files);

// Calculate storage used
$stmtQuota = $pdo->prepare('SELECT COALESCE(SUM(size),0) FROM files WHERE user_id = ?');
$stmtQuota->execute([$userId]);
$usedBytes = (int)$stmtQuota->fetchColumn();

// Convert to GB
$usedGB  = round($usedBytes    / (1024 ** 3), 2);
$quotaGB = round(USER_QUOTA_BYTES / (1024 ** 3), 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mi Panel · Storage</title>
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
  <!-- Loader CSS separado -->
  <link rel="stylesheet" href="./css/loader.css">
  <style>
    :root {
      --gradient-bg: linear-gradient(135deg, #0b0e13, #161a22);
      --card-bg: rgba(255,255,255,0.04);
      --accent: #2398f6;
      --accent-dark: #8e44ad;
      --text-light: #ffffff;
      --text-muted: #b0bac5;
      --error-color: #e74c3c;
      --success-color: #2ecc71;
    }
    *,*::before,*::after { margin:0; padding:0; box-sizing:border-box }
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
      grid-template-columns: 1fr 1fr;
      grid-template-rows: auto auto;
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
    .card h2 { margin-bottom:1rem; font-size:1.5rem; color:var(--text-light) }

    /* Welcome Card */
    .welcome { display:flex; flex-direction:column; justify-content:space-between }
    .welcome-header {
      display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;
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
    .btn-logout:hover { background: var(--accent-dark) }

    /* Upload Card */
    .upload form { display:flex; flex-direction:column }
    .file-btn {
      display:inline-block;
      padding:0.5rem 1rem;
      border:2px solid var(--accent);
      background:var(--accent);
      color:var(--text-light);
      border-radius:50px;
      font-weight:600;
      cursor:pointer;
      transition:background 0.3s;
      margin-bottom:1rem;
      text-align:center;
    }
    .file-btn input { display:none }
    .upload button {
      align-self:flex-start;
      padding:0.5rem 1rem;
      border:2px solid var(--accent);
      background:transparent;
      color:var(--accent);
      border-radius:50px;
      font-weight:600;
      cursor:pointer;
      transition:background 0.3s,color 0.3s;
    }
    .upload button:hover {
      background:var(--accent);
      color:var(--text-light);
    }
    .upload .error { color: var(--error-color); margin-top:1rem }

    /* Files List Card */
    .files-list .error { color: var(--error-color); margin-bottom:1rem }
    .files-list .success { color: var(--success-color); margin-bottom:1rem }
    .files-list table {
      width:100%; border-collapse:collapse;
    }
    .files-list th, .files-list td {
      padding:0.75rem;
      text-align:left;
      border-bottom:1px solid rgba(255,255,255,0.1);
      font-size:0.95rem;
      color:var(--text-muted);
    }
    .files-list th { color:var(--text-light) }
    .files-list tr:hover { background:rgba(255,255,255,0.05) }
    .btn-action {
      padding:0.25rem 0.75rem;
      margin-right:0.5rem;
      border:none;
      border-radius:50px;
      font-size:0.9rem;
      font-weight:600;
      cursor:pointer;
      transition:background 0.3s,color 0.3s;
      text-decoration:none;
      display:inline-block;
    }
    .btn-download {
      background:var(--accent);
      color:var(--text-light);
    }
    .btn-download:hover { background:var(--accent-dark) }
    .btn-delete {
      background:var(--error-color);
      color:var(--text-light);
    }
    .btn-delete:hover { background:#c0392b }

    /* Metrics Card */
    .metrics {
      display: flex;
      flex-direction: column;
      gap: 2rem;            /* espacio entre las dos métricas */
      align-items: center;
      justify-content: center;
    }
    .metric-group h2 {
      margin-bottom: 0.5rem;
      font-size: 1.25rem;
    }
    .metric-value {
      font-size: 2.5rem;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <script>window.loaderStart = Date.now();</script>
  <div id="loader-overlay"><div class="loader-text">STORAGE</div></div>

  <div class="dashboard-grid">
    <!-- Bienvenida -->
    <div class="card welcome">
      <div class="welcome-header">
        <h2>Bienvenido, <?= htmlspecialchars($username) ?></h2>
        <a href="logout.php" class="btn-logout">Cerrar sesión</a>
      </div>
      <p>Administra tus archivos de manera sencilla.</p>
    </div>
    <!-- Subir archivo -->
    <div class="card upload">
      <h2>Subir archivo</h2>
      <form action="upload.php" method="POST" enctype="multipart/form-data">
        <label class="file-btn">
          Seleccionar archivo
          <input type="file" name="file" required>
        </label>
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
          <tr><th>Archivo</th><th>Fecha</th><th>Acción</th></tr>
        </thead>
        <tbody>
          <?php foreach ($files as $f): ?>
          <tr>
            <td><?= htmlspecialchars($f['filename']) ?></td>
            <td><?= htmlspecialchars(substr($f['uploaded_at'], 0, 19)) ?></td>
            <td>
              <a href="/uploads/<?= urlencode($f['path']) ?>" download class="btn-action btn-download">Descargar</a>
              <form action="delete.php" method="POST" style="display:inline">
                <input type="hidden" name="file_id" value="<?= (int)$f['id'] ?>">
                <button type="submit" class="btn-action btn-delete">Eliminar</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <!-- Métricas -->
    <div class="card metrics">
      <div class="metric-group">
        <h2>Total de archivos</h2>
        <div class="metric-value"><?= $fileCount ?></div>
      </div>

      <div class="metric-group">
        <h2>Espacio utilizado</h2>
        <div class="metric-value">
          <?= $usedGB ?> GB de <?= $quotaGB ?> GB
        </div>
      </div>
    </div>
  </div>
  <script>
    window.addEventListener('load', () => {
      const MIN_DURATION = 2000;
      const elapsed = Date.now() - window.loaderStart;
      const delay = Math.max(0, MIN_DURATION - elapsed);
      setTimeout(() => {
        const loader = document.getElementById('loader-overlay');
        loader.style.opacity = '0';
        setTimeout(() => loader.style.display = 'none', 500);
      }, delay);
    });
  </script>
</body>
</html>
