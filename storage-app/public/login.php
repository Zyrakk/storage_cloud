<?php
require __DIR__ . '/src/init.php';
use App\User;
use OTPHP\TOTP;

// Conecta a Redis
$redis = new \Redis();
$redis->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT'));

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
            $totp = TOTP::create($user->totp_secret);
            $totp->setLabel($username);
            $totp->setIssuer('storage.stefsec.com');

            if (!$totp->verify($totpCode)) {
                $totpFail->inc();
                $error = 'Código TOTP incorrecto.';
            } else {
                $totpSuccess->inc();
                // Añade el ID de sesión al set y ponle TTL de 30 min
                $sid = session_id();
                $redis->sAdd('active_sessions', $sid);
                $redis->expire('active_sessions', 30*60);

                $_SESSION['user_id'] = $user->id;
                header('Location: dashboard.php');
                exit;
            }
        } else {
            // Añade el ID de sesión al set y ponle TTL de 30 min
            $sid = session_id();
            $redis->sAdd('active_sessions', $sid);
            $redis->expire('active_sessions', 30*60);
            
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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión · Storage</title>
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
  <!-- Loader CSS si es necesario -->
  <link rel="stylesheet" href="./css/loader.css">
  <style>
    :root {
      --bg-gradient: linear-gradient(135deg, #0b0e13, #161a22);
      --card-bg: rgba(255,255,255,0.04);
      --accent: #2398f6;
      --accent-dark: #8e44ad;
      --text-light: #ffffff;
      --text-muted: #b0bac5;
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: 'Montserrat', sans-serif;
      background: var(--bg-gradient);
      color: var(--text-light);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .login-card {
      background: var(--card-bg);
      backdrop-filter: blur(10px);
      padding: 40px 30px;
      border-radius: 16px;
      box-shadow: 0 16px 48px rgba(0,0,0,0.6);
      width: 100%;
      max-width: 400px;
      text-align: center;
    }
    .login-card h1 {
      margin-bottom: 1rem;
      font-size: 1.75rem;
      letter-spacing: 1px;
    }
    .login-card .error {
      background: rgba(255,0,0,0.2);
      color: #ff6b6b;
      padding: 0.75rem;
      border-radius: 8px;
      margin-bottom: 1rem;
    }
    .login-card form { display: grid; gap: 1rem; }
    .login-card label {
      text-align: left;
      font-size: 0.9rem;
      color: var(--text-muted);
    }
    .login-card input {
      width: 100%;
      padding: 0.75rem 1rem;
      border: none;
      border-radius: 8px;
      background: rgba(255,255,255,0.1);
      color: var(--text-light);
      font-size: 1rem;
      transition: background 0.3s;
    }
    .login-card input:focus {
      background: rgba(255,255,255,0.2);
      outline: 2px solid var(--accent);
    }
    .login-card button {
      margin-top: 0.5rem;
      padding: 0.75rem 1rem;
      border: 2px solid var(--accent);
      background: var(--accent);
      color: var(--text-light);
      font-size: 1rem;
      font-weight: 600;
      border-radius: 50px;
      cursor: pointer;
      transition: background 0.3s, transform 0.2s;
    }
    .login-card button:hover {
      background: var(--accent-dark);
      transform: translateY(-2px);
    }
    .login-card .links {
      margin-top: 1rem;
      font-size: 0.9rem;
    }
    .login-card .links a {
      color: var(--accent);
      transition: color 0.3s;
    }
    .login-card .links a:hover {
      color: var(--accent-dark);
    }
  </style>
</head>
<body>
  <div class="login-card">
    <h1>Iniciar sesión</h1>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="login.php">
      <label for="username">Usuario</label>
      <input id="username" name="username" type="text" required>

      <label for="password">Contraseña</label>
      <input id="password" name="password" type="password" required>

      <label for="totp_code">Código TOTP (opcional)</label>
      <input id="totp_code" name="totp_code" type="text" pattern="\d{6}" placeholder="123456">

      <button type="submit">Entrar</button>
    </form>
    <div class="links">
      ¿No tienes cuenta? <a href="register.php">Regístrate</a>
    </div>
  </div>
</body>
</html>
