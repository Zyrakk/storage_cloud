<?php
require __DIR__ . '/../src/init.php';
use App\User;
use OTPHP\TOTP;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $totpCode = trim($_POST['totp_code'] ?? '');

    // Métrica de intento
    $loginAttempts->inc();

    $user = User::findByUsername($username);
    if ($user && password_verify($password, $user->password_hash)) {
        if ($user->totp_secret) {
            $totp = TOTP::create(
                $user->totp_secret,
                ['issuer' => 'storage.stefsec.com', 'label' => $username]
            );
            if (!$totp->verify($totpCode)) {
                $totpFail->inc();
                $error = 'Código TOTP incorrecto.';
            } else {
                $totpSuccess->inc();
                $_SESSION['user_id'] = $user->id;
                header('Location: dashboard.php');
                exit;
            }
        } else {
            // Sin TOTP
            $_SESSION['user_id'] = $user->id;
            header('Location: dashboard.php');
            exit;
        }
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Iniciar sesión</title>
</head>
<body>
  <h1>Iniciar sesión</h1>
  <?php if ($error): ?>
    <p style="color:red;"><?=htmlspecialchars($error)?></p>
  <?php endif; ?>

  <form method="POST">
    <label>Usuario:<br><input name="username" type="text" required></label><br><br>
    <label>Contraseña:<br><input name="password" type="password" required></label><br><br>
    <label>Código TOTP (si lo activaste):<br>
      <input name="totp_code" type="text" pattern="\d{6}" placeholder="Opcional">
    </label><br><br>
    <button type="submit">Entrar</button>
  </form>

  <p>¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
</body>
</html>
