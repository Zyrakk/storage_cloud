<?php
// public/register.php

require __DIR__ . '/src/init.php';
use App\User;
use OTPHP\TOTP;

$error = '';

// Si viene ?reset=1, reiniciamos el flujo de registro
if (isset($_GET['reset'])) {
    unset($_SESSION['reg_step'], $_SESSION['reg_user']);
    header('Location: register.php');
    exit;
}

$step = $_SESSION['reg_step'] ?? 1;

// Preparamos la URL del QR solo en el paso 2
if ($step === 2 && isset($_SESSION['reg_user'])) {
    $ru = $_SESSION['reg_user'];

    // Creamos y configuramos el objeto TOTP
    $totp = TOTP::create($ru['secret']);
    $totp->setLabel($ru['username']);
    $totp->setIssuer('storage.stefsec.com');

    // Obtenemos el URI y lo codificamos para Google Charts
    $qrUri = $totp->getProvisioningUri();
    $qrUrl = sprintf(
        'https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=%s&choe=UTF-8',
        rawurlencode($qrUri)
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        // Paso 1: recibimos usuario/clave y opción 2FA
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $use2fa   = isset($_POST['use_2fa']);

        if ($username === '' || $password === '') {
            $error = 'Usuario y contraseña son obligatorios.';
        } elseif ($use2fa) {
            // Generamos secreto y avanzamos al paso 2
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
            // Registro sin 2FA
            User::create($username, $password, null);
            header('Location: login.php');
            exit;
        }
    } elseif ($step === 2) {
        // Paso 2: verificamos el código TOTP
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registro · Storage</title>
  <style>
    body { font-family: sans-serif; padding: 20px; max-width: 400px; margin: auto; }
    form label { display: block; margin-bottom: 10px; }
    input[type="text"], input[type="password"], input[type="checkbox"] { margin-top: 5px; }
    button, a { margin-top: 15px; display: inline-block; }
    .error { color: red; }
  </style>
</head>
<body>
  <h1>Crear cuenta</h1>

  <?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <?php if ($step === 1): ?>
    <!-- Paso 1: formulario básico -->
    <form method="POST" action="register.php">
      <label>
        Usuario:<br>
        <input name="username" type="text" required>
      </label>
      <label>
        Contraseña:<br>
        <input name="password" type="password" required>
      </label>
      <label>
        <input name="use_2fa" type="checkbox">
        Activar TOTP (app de autenticación)
      </label>
      <button type="submit">Registrar</button>
    </form>

  <?php elseif ($step === 2): ?>
    <!-- Paso 2: configuración 2FA -->
    <h2>Configura tu app de autenticación</h2>
    <p>Escanea este código QR con Google Authenticator, Authy, etc.:</p>
    <img src="<?= htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8') ?>" alt="QR TOTP"><br>

    <form method="POST" action="register.php">
      <label>
        Código de tu app:<br>
        <input name="totp_code" type="text" pattern="\d{6}" required>
      </label>
      <button type="submit">Finalizar registro</button>
      <a href="register.php?reset=1">Cancelar y volver</a>
    </form>
  <?php endif; ?>

  <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
</body>
</html>
