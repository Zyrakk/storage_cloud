<?php
require __DIR__ . '/src/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$pdo    = getDb();

// Get username
$stmtUser = $pdo->prepare('SELECT username FROM users WHERE id = ?');
$stmtUser->execute([$userId]);
$username = $stmtUser->fetchColumn() ?: 'Usuario';

// Flash messages
$uploadError   = $_SESSION['upload_error']   ?? null; unset($_SESSION['upload_error']);
$deleteError   = $_SESSION['delete_error']   ?? null; unset($_SESSION['delete_error']);
$deleteSuccess = $_SESSION['delete_success'] ?? null; unset($_SESSION['delete_success']);
$shareUrl      = $_SESSION['share_url']      ?? null; unset($_SESSION['share_url']);

// Fetch files
$stmt = $pdo->prepare(
    'SELECT id, filename, path, uploaded_at, size
     FROM files
     WHERE user_id = ?
     ORDER BY uploaded_at DESC'
);
$stmt->execute([$userId]);
$files     = $stmt->fetchAll();
$fileCount = count($files);

// Calculate used storage
$stmtQuota = $pdo->prepare('SELECT COALESCE(SUM(size),0) FROM files WHERE user_id = ?');
$stmtQuota->execute([$userId]);
$usedBytes   = (int)$stmtQuota->fetchColumn();
$usedGB      = round($usedBytes    / (1024 ** 3), 2);
$quotaGB     = round(USER_QUOTA_BYTES / (1024 ** 3), 2);
$usedPercent = $quotaGB > 0 ? min(100, round($usedGB / $quotaGB * 100)) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mi Panel · Storage</title>
  <!-- Google Font & FontAwesome -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Loader & Dashboard CSS -->
  <link rel="stylesheet" href="./css/loader.css">
  <link rel="stylesheet" href="./css/dashboard.css">
</head>
<body>
  <script>window.loaderStart = Date.now();</script>
  <div id="loader-overlay"><div class="loader-text">STORAGE</div></div>

  <!-- Share Modal -->
  <div id="share-modal">
    <div class="modal-content">
      <button class="modal-close">&times;</button>
      <h3>Compartir archivo</h3>
      <form id="share-form" action="share.php" method="POST">
        <input type="hidden" name="file_id" id="share-file-id">
        <label>
          Caducidad:
          <select name="expiry_type" id="expiry-type">
            <option value="never">Para siempre</option>
            <option value="hours">Horas</option>
            <option value="days">Días</option>
          </select>
        </label>
        <label id="expiry-value-label" style="display:none">
          Valor:
          <input type="number" name="expiry_value" id="expiry-value" min="1" value="1">
        </label>
        <div class="modal-buttons">
          <button type="submit" class="btn-generate">Generar</button>
          <button type="button" class="modal-close">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

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
      <?php if ($shareUrl): ?>
        <p class="success">
          Enlace: <a href="<?= htmlspecialchars($shareUrl) ?>" target="_blank"><?= htmlspecialchars($shareUrl) ?></a>
        </p>
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
            <td class="action-cell">
              <!-- Kebab menu trigger -->
              <button class="btn-action btn-menu" data-file-id="<?= $f['id'] ?>" title="Más">
                <i class="fa-solid fa-ellipsis-vertical"></i>
              </button>
              <!-- Hidden action menu -->
              <div class="action-menu" id="menu-<?= $f['id'] ?>">
                <a href="/uploads/<?= urlencode($f['path']) ?>" download>
                  <i class="fa-solid fa-download"></i> Descargar
                </a>
                <form id="del-<?= $f['id'] ?>" action="delete.php" method="POST">
                  <input type="hidden" name="file_id" value="<?= (int)$f['id'] ?>">
                  <button type="submit">
                    <i class="fa-solid fa-trash-alt"></i> Eliminar
                  </button>
                </form>
                <button class="share-btn" data-file-id="<?= $f['id'] ?>">
                  <i class="fa-solid fa-share-from-square"></i> Compartir
                </button>
              </div>
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
          <label class="file-btn">
            <i class="fa-solid fa-file-import"></i>
            <input id="file-input" type="file" name="file" required>
          </label>
          <div id="file-name" class="file-name-display"></div>
          <button type="submit" class="btn-action btn-upload-submit" title="Subir">
            <i class="fa-solid fa-cloud-arrow-up"></i>
          </button>
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
        <div class="quota-bar"><div class="quota-fill" style="width: <?= $usedPercent ?>%;"></div></div>
      </div>
    </div>
  </div>

  <script>
    // Loader fade
    window.addEventListener('load', () => {
      const MIN_DURATION = 2000;
      const elapsed = Date.now() - window.loaderStart;
      const delay   = Math.max(0, MIN_DURATION - elapsed);
      setTimeout(() => {
        const l = document.getElementById('loader-overlay');
        l.style.opacity = '0';
        setTimeout(() => l.style.display = 'none', 500);
      }, delay);
    });

    // File name display
    document.getElementById('file-input').addEventListener('change', e => {
      const lbl = document.getElementById('file-name');
      lbl.textContent = e.target.files.length
        ? `Seleccionado: ${e.target.files[0].name}`
        : '';
    });

    // Kebab menu interactions
    document.querySelectorAll('.btn-menu').forEach(btn => {
      btn.addEventListener('click', e => {
        const id    = btn.dataset.fileId;
        const menu  = document.getElementById('menu-'+id);
        document.querySelectorAll('.action-menu.open').forEach(m => m.classList.remove('open'));
        menu.classList.toggle('open');
        e.stopPropagation();
      });
    });
    document.addEventListener('click', () => {
      document.querySelectorAll('.action-menu.open').forEach(m => m.classList.remove('open'));
    });

    // Share modal logic
    const modal          = document.getElementById('share-modal');
    const shareBtns      = document.querySelectorAll('.share-btn');
    const closeBtns      = modal.querySelectorAll('.modal-close');
    const fileIdInput    = document.getElementById('share-file-id');
    const expiryType     = document.getElementById('expiry-type');
    const expiryValueLbl = document.getElementById('expiry-value-label');

    shareBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        fileIdInput.value = btn.dataset.fileId;
        expiryType.value = 'never';
        expiryValueLbl.style.display = 'none';
        modal.classList.add('active');
      });
    });
    closeBtns.forEach(btn =>
      btn.addEventListener('click', () => modal.classList.remove('active'))
    );
    expiryType.addEventListener('change', () => {
      expiryValueLbl.style.display = expiryType.value === 'never' ? 'none' : 'block';
    });
  </script>
</body>
</html>
