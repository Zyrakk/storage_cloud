<?php
require __DIR__ . '/../src/init.php';

// Si ya hay sesión, vamos al panel
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Storage · Inicio</title>
  <style>
    body { font-family: sans-serif; text-align: center; padding: 50px; }
    a.button {
      display: inline-block;
      margin: 10px;
      padding: 15px 25px;
      background: #007bff;
      color: white;
      text-decoration: none;
      border-radius: 5px;
    }
    a.button:hover { background: #0056b3; }
  </style>
</head>
<body>
  <h1>Bienvenido a Storage</h1>
  <p>Gestiona tus archivos de forma rápida y segura.</p>
  <a class="button" href="register.php">Registrarse</a>
  <a class="button" href="login.php">Iniciar sesión</a>
</body>
</html>
