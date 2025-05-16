<?php
require __DIR__ . '/src/init.php';
use App\User;
use OTPHP\TOTP;

$error = '';
// Reset del flujo
if (isset($_GET['reset'])) {
    unset($_SESSION['reg_step'], $_SESSION['reg_user']);
    header('Location: register.php'); exit;
}
$step = $_SESSION['reg_step'] ?? 1;
$qrUrl = '';
if ($step === 2 && isset($_SESSION['reg_user'])) {
    $ru = $_SESSION['reg_user'];
    $totp = TOTP::create($ru['secret']);
    $totp->setLabel($ru['username']);
    $totp->setIssuer('storage.stefsec.com');
    $qrUri = $totp->getProvisioningUri();
    $qrUrl = sprintf(
      'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=%s',
      rawurlencode($qrUri)
    );
}
// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $use2fa = isset($_POST['use_2fa']);
        if ($username === '' || $password === '') {
            $error = 'Usuario y contraseña son obligatorios.';
        } elseif ($use2fa) {
            $totp = TOTP::create();
            $_SESSION['reg_user'] = ['username'=>$username,'password'=>$password,'secret'=>$totp->getSecret()];
            $_SESSION['reg_step'] = 2;
            header('Location: register.php'); exit;
        } else {
            User::create($username, $password, null);
            header('Location: login.php'); exit;
        }
    } elseif ($step === 2) {
        $code = trim($_POST['totp_code'] ?? '');
        if (!isset($_SESSION['reg_user'])) {
            $error = 'Sesión expirada.'; $_SESSION['reg_step']=1;
        } else {
            $ru = $_SESSION['reg_user'];
            $totp = TOTP::create($ru['secret']);
            $totp->setLabel($ru['username']);
            $totp->setIssuer('storage.stefsec.com');
            if ($totp->verify($code)) {
                User::create($ru['username'],$ru['password'],$ru['secret']);
                unset($_SESSION['reg_user'],$_SESSION['reg_step']);
                header('Location: login.php'); exit;
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
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Registro · Storage</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./css/loader.css">
  <style>
    :root {
      --bg-gradient: linear-gradient(135deg,#0b0e13,#161a22);
      --card-bg: rgba(255,255,255,0.04);
      --accent: #2398f6;
      --accent-dark: #8e44ad;
      --text-light: #ffffff;
      --text-muted: #b0bac5;
    }
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Montserrat',sans-serif;background:var(--bg-gradient);color:var(--text-light);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
    .register-card{background:var(--card-bg);backdrop-filter:blur(10px);padding:40px 30px;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,0.6);width:100%;max-width:400px;}
    .register-card h1{font-size:1.75rem;margin-bottom:1rem;text-align:center;}
    .register-card h2{font-size:1.5rem;margin-bottom:1rem;text-align:center;}
    .error{background:rgba(255,0,0,0.2);color:#ff6b6b;padding:0.75rem;border-radius:8px;margin-bottom:1rem;text-align:center;}
    .register-card form{display:grid;gap:1rem;}
    label{font-size:0.9rem;color:var(--text-muted);display:block;}
    input[type=text],input[type=password]{width:100%;padding:0.75rem 1rem;border:none;border-radius:8px;background:rgba(255,255,255,0.1);color:var(--text-light);transition:background 0.3s;}
    input:focus{background:rgba(255,255,255,0.2);outline:2px solid var(--accent);}
    .btn{
      padding:0.75rem 1rem;border:2px solid var(--accent);background:var(--accent);color:var(--text-light);font-weight:600;border-radius:50px;cursor:pointer;transition:background 0.3s,transform 0.2s;display:inline-block;text-align:center;
    }
    .btn:hover{background:var(--accent-dark);transform:translateY(-2px);}
    .links{margin-top:1rem;text-align:center;font-size:0.9rem;}
    .links a{color:var(--accent);transition:color 0.3s;}
    .links a:hover{color:var(--accent-dark);}
    img.qr{display:block;margin:1rem auto;border-radius:8px;}
    .reset-link{font-size:0.9rem;color:var(--accent);display:block;text-align:center;margin-top:0.5rem;}
  </style>
</head>
<body>
  <div class="register-card">
    <?php if($step===1):?>
      <h1>Crear cuenta</h1>
      <?php if($error):?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>
      <form method="POST">
        <label for="username">Usuario</label>
        <input id="username" name="username" type="text" required>
        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password" required>
        <label><input type="checkbox" name="use_2fa"> Activar TOTP</label>
        <button class="btn" type="submit">Registrar</button>
      </form>
      <div class="links">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></div>
    <?php else:?>
      <h2>Configurar TOTP</h2>
      <?php if($error):?><div class="error"><?=htmlspecialchars($error)?></div><?php endif;?>
      <?php if($qrUrl):?>
        <img class="qr" src="<?=htmlspecialchars($qrUrl,ENT_QUOTES)?>" alt="Código QR">
        <form method="POST">
          <label for="totp_code">Código de tu app</label>
          <input id="totp_code" name="totp_code" type="text" pattern="\d{6}" required>
          <button class="btn" type="submit">Finalizar registro</button>
        </form>
        <a class="reset-link" href="register.php?reset=1">Cancelar y volver</a>
        <div class="links"><small>¿No ves el QR? <a href="<?=htmlspecialchars($qrUrl,ENT_QUOTES)?>" target="_blank" rel="noopener">Ver enlace</a></small></div>
      <?php else:?>
        <div class="error">Error generando QR. <a class="reset-link" href="register.php?reset=1">Reintentar</a></div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>
