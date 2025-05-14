<?php
require __DIR__ . '/../src/init.php';
use OTPHP\TOTP;
use App\User;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginAttempts->inc();
    $u    = $_POST['username'];
    $p    = $_POST['password'];
    $code = $_POST['totp'] ?? '';
    $user = User::findByUsername($u);
    if ($user && password_verify($p, $user->password_hash)) {
        if ($user->totp_secret) {
            $totp = TOTP::create($user->totp_secret);
            if ($totp->verify($code)) {
                $totpSuccess->inc();
                $_SESSION['user_id'] = $user->id;
                header('Location: protected.php');
                exit;
            } else {
                $totpFail->inc();
                echo "<p style='color:red'>TOTP inválido</p>";
            }
        } else {
            $_SESSION['user_id'] = $user->id;
            header('Location: protected.php');
            exit;
        }
    } else {
        echo "<p style='color:red'>Credenciales inválidas</p>";
    }
}
?>
<form method="post">
  Usuario: <input name="username" required><br>
  Contraseña: <input name="password" type="password" required><br>
  TOTP (si lo configuraste): <input name="totp"><br>
  <button>Login</button>
</form>
?>