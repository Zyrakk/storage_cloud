<?php
require __DIR__ . '/../src/init.php';
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
echo "<h1>¡Bienvenido, usuario #{$_SESSION['user_id']}!</h1>";
echo '<p><a href="login.php">Salir</a></p>';
