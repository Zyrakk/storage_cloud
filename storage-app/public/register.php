<?php
require __DIR__ . '/../src/init.php';
use OTPHP\TOTP;
use App\User;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username']);
    $p = $_POST['password'];
    $secret = TOTP::create()->getSecret();
    $user = User::create($u, $p, $secret);
    echo "<p>Registro OK. Guarda este código en Google Authenticator:<br><strong>{$secret}</strong></p>";
    echo '<p><a href="login.php">Ir a login</a></p>';
    exit;
}
?>
<form method="post">
  Usuario: <input name="username" required><br>
  Contraseña: <input name="password" type="password" required><br>
  <button>Registrar</button>
</form>
