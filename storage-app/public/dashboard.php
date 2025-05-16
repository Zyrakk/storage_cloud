<?php
require __DIR__ . '/src/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$pdo = getDb();

// Get username
$stmtUser = $pdo->prepare('SELECT username FROM users WHERE id = ?');
$stmtUser->execute([$userId]);
$username = $stmtUser->fetchColumn() ?: 'Usuario';

// Flash messages
$uploadError   = $_SESSION['upload_error']   ?? null; unset($_SESSION['upload_error']);
$deleteError   = $_SESSION['delete_error']   ?? null; unset($_SESSION['delete_error']);
$deleteSuccess = $_SESSION['delete_success'] ?? null; unset($_SESSION['delete_success']);

// Fetch files
$stmt = $pdo->prepare(
    'SELECT id, filename, path, uploaded_at, size FROM files WHERE user_id = ? ORDER BY uploaded_at DESC'
);
$stmt->execute([$userId]);
$files = $stmt->fetchAll();
$fileCount = count($files);

// Calculate used storage
$stmtQuota = $pdo->prepare('SELECT COALESCE(SUM(size),0) FROM files WHERE user_id = ?');
$stmtQuota->execute([$userId]);
$usedBytes = (int)$stmtQuota->fetchColumn();
$usedGB  = round($usedBytes    / (1024 ** 3), 2);
$quotaGB = round(USER_QUOTA_BYTES / (1024 ** 3), 2);
$usedPercent = $quotaGB > 0 ? min(100, round($usedGB / $quotaGB * 100)) : 0;
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
      --quota-bg: rgba(255,255,255,0.1);
      --quota-fill: var(--accent);
    }
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Montserrat',sans-serif;background:var(--gradient-bg);color:var(--text-light);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
    .dashboard-container{display:grid;grid-template-columns:2fr 1fr;grid-template-rows:auto 1fr;gap:2rem;width:100%;max-width:1200px}
    /* Welcome spans two columns */
    .welcome{grid-column:1/3;}
    .card{background:var(--card-bg);backdrop-filter:blur(10px);border-radius:16px;padding:2rem;box-shadow:0 16px 48px rgba(0,0,0,0.6);}
    h1{font-size:2rem;margin-bottom:1rem}
    /* Welcome card */
    .welcome{display:flex;justify-content:space-between;align-items:center}
    .btn-logout{padding:0.5rem 1rem;border:2px solid var(--accent);background:var(--accent);color:var(--text-light);border-radius:50px;font-weight:600;transition:background 0.3s;text-decoration:none}
    .btn-logout:hover{background:var(--accent-dark)}
    /* Files list on left */
    .files-list{grid-column:1/2;overflow:auto}
    .files-list h2{margin-bottom:1rem}
    .files-list .error{color:var(--error-color);margin-bottom:1rem}
    .files-list .success{color:var(--success-color);margin-bottom:1rem}
    table{width:100%;border-collapse:collapse}
    th,td{padding:0.75rem;text-align:left;border-bottom:1px solid rgba(255,255,255,0.1);font-size:0.95rem;color:var(--text-muted)}
    th{color:var(--text-light)}
    tr:hover{background:rgba(255,255,255,0.05)}
    .btn-action{padding:0.25rem 0.75rem;margin-right:0.5rem;border:none;border-radius:50px;font-size:0.9rem;font-weight:600;cursor:pointer;transition:background 0.3s;color:var(--text-light);text-decoration:none;display:inline-block}
    .btn-download{background:var(--accent)}
    .btn-download:hover{background:var(--accent-dark)}
    .btn-delete{background:var(--error-color)}
    .btn-delete:hover{background:#c0392b}
    /* Right column: upload + metrics */
    .right-column{grid-column:2/3;display:flex;flex-direction:column;gap:2rem}
    /* Upload card */
    .upload h2{margin-bottom:1rem}
    .file-btn{display:inline-block;padding:0.5rem 1rem;border:2px solid var(--accent);background:var(--accent);color:var(--text-light);border-radius:50px;font-weight:600;cursor:pointer;transition:background 0.3s;margin-bottom:1rem;text-align:center;width:100%}
    .file-btn:hover{background:var(--accent-dark)}
    .file-btn input{display:none}
    .upload button{align-self:flex-start;padding:0.5rem 1rem;border:2px solid var(--accent);background:transparent;color:var(--accent);border-radius:50px;font-weight:600;cursor:pointer;transition:background 0.3s,color 0.3s;}
    .upload button:hover{background:var(--accent);color:var(--text-light)}
    .upload .error{color:var(--error-color);margin-top:1rem}
    /* Metrics card */
    .metrics{padding:2rem;display:flex;flex-direction:column;gap:1.5rem;align-items:center}
    .metrics h2{margin-bottom:0.5rem;font-size:1.5rem;color:var(--text-light)}
    .stat-group{width:100%;text-align:center}
    .stat-label{color:var(--text-muted);font-size:0.9rem}
    .stat-value{font-size:2.5rem;font-weight:600;margin-top:0.25rem}
    .quota-bar{width:100%;height:8px;background:var(--quota-bg);border-radius:4px;overflow:hidden;margin-top:1rem}
    .quota-fill{height:100%;width:<?= $usedPercent ?>%;background:var(--quota-fill);transition:width 0.5s ease}

    /* Responsive Mobile */
    @media (max-width: 768px) {
      body { padding: 1rem; }
      .dashboard-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
      }
      .welcome { grid-column: auto; flex-direction: column; align-items: flex-start; }
      .files-list { grid-column: auto; }
      .right-column { grid-column: auto; flex-direction: column; }
      .card { padding: 1.5rem; }
      h1 { font-size: 1.5rem; }
      .files-list table, th, td { font-size: 0.85rem; }
      .btn-logout, .file-btn, .upload button, .btn-action { width: 100%; text-align: center; }
      .metrics { padding: 1.5rem; }
      .stat-value { font-size: 2rem; }
    }
  </style>
</head>
<body>
  <script>window.loaderStart = Date.now();</script>
  <div id="loader-overlay"><div class="loader-text">STORAGE</div></div>

  <div class="dashboard-container">
    <!-- Welcome -->
    <div class="card welcome">
      <h1>Bienvenido, <?= htmlspecialchars($username) ?></h1>
      <a href="logout.php" class="btn-logout">Cerrar sesión</a>
    </div>
    <!-- Files list -->
    <div class="card files-list">
      <h2>Mis archivos (<?= $fileCount ?>)</h2>
      <?php if ($deleteError): ?><p class="error"><?= htmlspecialchars($deleteError) ?></p><?php endif; ?>
      <?php if ($deleteSuccess): ?><p class="success"><?= htmlspecialchars($deleteSuccess) ?></p><?php endif; ?>
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
    <!-- Right column -->
    <div class="right-column">
      <!-- Upload -->
      <div class="card upload">
        <h2>Subir archivo</h2>
        <form action="upload.php" method="POST" enctype="multipart/form-data">
          <label class="file-btn">Seleccionar archivo<input type="file" name="file" required></label>
          <button type="submit">Subir</button>
        </form>
        <?php if ($uploadError): ?><p class="error"><?= htmlspecialchars($uploadError) ?></p><?php endif; ?>
      </div>
      <!-- Metrics -->
      <div class="card metrics">
        <h2>Estadísticas</h2>
        <div class="stat-group">
          <div class="stat-label">Total de archivos</div>
          <div class="stat-value"><?= $fileCount ?></div>
        </div>
        <div class="stat-group">
          <div class="stat-label">Espacio utilizado</div>
          <div class="stat-value"><?= $usedGB ?> GB / <?= $quotaGB ?> GB</div>
        </div>
        <div class="quota-bar"><div class="quota-fill"></div></div>
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
