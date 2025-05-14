<?php
require __DIR__ . '/../src/init.php';
use App\User;
use OTPHP\TOTP;

$error = '';
$step  = $_SESSION['reg_step'] ?? 1;

// Si vamos al segundo paso, preparamos la URL del QR
if ($step === 2 && isset($_SESSION['reg_user'])) {
    $ru     = $_SESSION['reg_user'];
    $totp   = TOTP::create($ru['secret'], ['issuer' => 'storage.stefsec.com', 'label' => $ru['username']]);
    $qrUri  = $totp->getProvisioningUri();
    $qrUrl  = 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($qrUri);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $use2fa   = isset($_POST['use_2fa']);
        if ($username === '' || $password === '') {
            $error = 'Usuario y contraseña son obligatorios.';
        } elseif ($use2fa) {
            // Paso 2: generar secreto y mostrar QR
            $totp    = TOTP::create();
            $secret  = $totp->getSecret();
            $_SESSION['reg_user'] = [
                'username' => $username,
                'password' => $password,
                'secret'   => $secret,
            ];
            $_SESSION['reg_step'] = 2;
            header('Location: register.php');
            exit;
        } else {
            // Crear usuario sin TOTP
            User::create($username, $password, null);
            header('Location: login.php');
            exit;
        }
    } elseif ($step === 2) {
        // Verificar código TOTP
        $code = trim($_POST['totp_code'] ?? '');
        if (!isset($_SESSION['reg_user'])) {
            $error = 'Sesión de registro expirada.';
            $_SESSION['reg_step'] = 1;
        } else {
            $ru   = $_SESSION['reg_user'];
            $totp = TOTP::create($ru['secret'], ['issuer' => 'storage.stefsec.com', 'label' => $ru['username']]);
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
  <title>Registro</title>
</head>
<body>
  <h1>Crear cuenta</h1>
  <?php if ($error): ?>
    <p style="color:red;"><?=htmlspecialchars($error)?></p>
  <?php endif; ?>

  <?php if ($step === 1): ?>
    <form method="POST">
      <label>Usuario:<br><input name="username" type="text" required></label><br><br>
      <label>Contraseña:<br><input name="password" type="password" required></label><br><br>
      <label><input name="use_2fa" type="checkbox"> Activar TOTP</label><br><br>
      <button type="submit">Registrar</button>
    </form>

  <?php else: /* paso 2 */ ?>
    <h2>Configura tu app de autenticación</h2>
    <p>Escanea este código QR con Google Authenticator o Authy:</p>
    <img src="<?=$qrUrl?>" alt="QR TOTP"><br><br>
    <form method="POST">
      <label>Código de tu app:<br><input name="totp_code" pattern="\d{6}" required></label><br><br>
      <button type="submit">Finalizar registro</button>
    </form>
  <?php endif; ?>

  <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
</body>
</html>
