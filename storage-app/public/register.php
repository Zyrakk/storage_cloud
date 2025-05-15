<?php
// public/register.php

require __DIR__ . '/src/init.php';
use App\User;
use OTPHP\TOTP;

$error = '';

// Reset del flujo si llega ?reset=1
if (isset($_GET['reset'])) {
    unset($_SESSION['reg_step'], $_SESSION['reg_user']);
    header('Location: register.php');
    exit;
}

$step = $_SESSION['reg_step'] ?? 1;
$qrUrl = ''; // inicializamos

// Paso 2: preparamos el QR
if ($step === 2 && isset($_SESSION['reg_user'])) {
    $ru   = $_SESSION['reg_user'];
    $totp = TOTP::create($ru['secret']);
    $totp->setLabel($ru['username']);
    $totp->setIssuer('storage.stefsec.com');

    $qrUri = $totp->getProvisioningUri();
    $qrUrl = sprintf(
        'https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=%s&choe=UTF-8',
        rawurlencode($qrUri)
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $use2fa   = isset($_POST['use_2fa']);

        if ($username === '' || $password === '') {
            $error = 'Usuario y contraseña son obligatorios.';
        } elseif ($use2fa) {
            $totp   = TOTP::create();
            $secret = $totp->getSecret();
            $_SESSION['reg_user'] = [
                'username' => $username,
                'password' => $password,
                'secret'   => $secret,
            ];
            $_SESSION['reg_step'] = 2;
            header('Location: register.php');
            exit;
        } else {
            User::create($username, $password, null);
            header('Location: login.php');
            exit;
        }
    } elseif ($step === 2) {
        $code = trim($_POST['totp_code'] ?? '');
        if (!isset($_SESSION['reg_user'])) {
            $error = 'Sesión de registro expirada.';
            $_SESSION['reg_step'] = 1;
        } else {
            $ru   = $_SESSION['reg_user'];
            $totp = TOTP::create($ru['secret']);
            $totp->setLabel($ru['username']);
            $totp->setIssuer('storage.stefsec.com');

            if ($totp->verify($code)) {
                User::create($ru['username'], $ru['password'], $ru['secret']);
                unset($_SESSION['reg_user'], $_SESSION['reg_step']);
                header('Location: login.php');
                exit;
            } else {
                $error = 'Código TOTP incorrecto.';
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registro · Storage</title>
  <style>
    body { font-family: sans-serif; padding: 20px; max-width: 400px; margin: auto; }
    form label { display: block; margin-bottom: 10px; }
    .error { color: red; }
    img { border: 1px solid #ccc; margin-bottom: 10px; }
  </style>
</head>
<body>
  <h1>Crear cuenta</h1>

  <?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <?php if ($step === 1): ?>
    <form method="POST" action="register.php">
      <label>Usuario:<br>
        <input name="username" type="text" required>
      </label>
      <label>Contraseña:<br>
        <input name="password" type="password" required>
      </label>
      <label><input name="use_2fa" type="checkbox"> Activar TOTP</label>
      <button type="submit">Registrar</button>
    </form>

  <?php elseif ($step === 2): ?>
    <h2>Configura tu app de autenticación</h2>
    <p>Escanea este código QR con Google Authenticator, Authy, etc.:</p>
    <?php if ($qrUrl): ?>
      <img src="<?= htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8') ?>" alt="QR TOTP">
      <form method="POST" action="register.php">
        <label>Código de tu app:<br>
          <input name="totp_code" type="text" pattern="\d{6}" required>
        </label>
        <button type="submit">Finalizar registro</button>
        <a href="register.php?reset=1">Cancelar y volver</a>
      </form>
      <!-- Enlace de depuración: ver URL generada -->
      <p><small>Si no ves el QR, prueba este enlace:<br>
        <a href="<?= htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank">
          <?= htmlspecialchars($qrUrl) ?>
        </a>
      </small></p>
    <?php else: ?>
      <p class="error">Error al generar el QR. <a href="register.php?reset=1">Reintentar</a></p>
    <?php endif; ?>
  <?php endif; ?>

  <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
</body>
</html>
