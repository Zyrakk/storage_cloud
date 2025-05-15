<?php
require __DIR__ . '/src/init.php';

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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Storage · Inicio</title>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
  <!-- Loader CSS separado -->
  <link rel="stylesheet" href="./css/loader.css">

  <style>
    /* Variables de diseño */
    :root {
      --clr-dark: #0b0e13;
      --clr-dark-alt: #161a22;
      --clr-accent1: #2398f6;
      --clr-accent2: #8e44ad;
      --clr-accent3: #2ecc71;
      --clr-light: #ffffff;
      --clr-gray: #b0bac5;
      --gradient-bg: linear-gradient(135deg, var(--clr-dark), var(--clr-dark-alt));
    }

    /* Reset */
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Montserrat', sans-serif;
      background: var(--gradient-bg);
      color: var(--clr-light);
      overflow-x: hidden;
    }
    a { text-decoration: none; color: inherit; }

    /* Navbar */
    header { position: fixed; width: 100%; top: 0; left: 0; background: rgba(0,0,0,0.3); backdrop-filter: blur(8px); z-index: 1000; }
    .navbar { max-width: 1200px; margin: auto; display: flex; justify-content: space-between; align-items: center; padding: 1rem 2rem; }
    .logo { font-size: 1.5rem; font-weight: 600; background: linear-gradient(45deg, var(--clr-accent1), var(--clr-accent2)); -webkit-background-clip: text; color: transparent; }
    .nav-links { list-style: none; display: flex; gap: 1.5rem; align-items: center; }
    .nav-links li a { font-weight: 500; transition: color 0.3s; }
    .nav-links li a:hover { color: var(--clr-accent3); }
    .nav-links .btn { padding: 0.5rem 1rem; border: 2px solid var(--clr-accent1); border-radius: 50px; transition: background 0.3s, color 0.3s; }
    .nav-links .btn.primary { background: var(--clr-accent1); color: var(--clr-light); }
    .nav-links .btn.primary:hover { background: var(--clr-accent2); border-color: var(--clr-accent2); }

    /* Hero section */
    .hero { display: flex; align-items: center; justify-content: space-between; padding: 8rem 2rem 4rem; max-width: 1200px; margin: auto; gap: 2rem; }
    .hero-content { max-width: 600px; }
    .hero-content h1 { font-size: 3rem; margin-bottom: 1rem; }
    .hero-content p { font-size: 1.125rem; margin-bottom: 2rem; color: var(--clr-gray); line-height: 1.5; }
    .hero-content .btn { display: inline-block; padding: 0.75rem 2rem; margin-right: 1rem; font-weight: 600; border: 2px solid var(--clr-accent3); border-radius: 50px; transition: background 0.3s; }
    .hero-content .btn.primary { background: var(--clr-accent3); color: var(--clr-light); }
    .hero-content .btn.primary:hover { background: var(--clr-accent2); border-color: var(--clr-accent2); }

    /* Imagen hero con efecto visual */
    .hero-image {
      position: relative;
      width: 100%;
      max-width: 500px;
      margin: auto;
    }
    .hero-image::before {
      content: '';
      position: absolute;
      top: 15%; left: -10%;
      width: 120%; height: 120%;
      background: radial-gradient(circle at center, var(--clr-accent1), var(--clr-accent2) 60%, transparent);
      filter: blur(100px);
      z-index: -1;
      transform: rotate(-12deg);
    }
    .hero-image img {
      width: 100%;
      border-radius: 20px;
      box-shadow: 0 16px 40px rgba(0,0,0,0.7);
      animation: float 6s ease-in-out infinite;
    }
    @keyframes float {
      0%,100% { transform: translateY(0); }
      50% { transform: translateY(-20px); }
    }

    /* Features section */
    .features { padding: 4rem 2rem; background: rgba(0,0,0,0.2); }
    .features h2 { text-align: center; font-size: 2rem; margin-bottom: 2rem; }
    .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 2rem; max-width: 1000px; margin: auto; }
    .feature { background: rgba(255,255,255,0.05); padding: 2rem; border-radius: 12px; text-align: center; transition: transform 0.3s, box-shadow 0.3s; }
    .feature:hover { transform: translateY(-8px); box-shadow: 0 16px 32px rgba(0,0,0,0.6); }
    .feature img { width: 60px; margin-bottom: 1rem; }
    .feature h3 { font-size: 1.25rem; margin-bottom: 0.5rem; }
    .feature p { color: var(--clr-gray); }

    /* Footer */
    footer { padding: 2rem; text-align: center; color: var(--clr-gray); font-size: 0.9rem; }
  </style>
</head>
<body>
  <!-- Inicia medición de tiempo del loader -->
  <script>window.loaderStart = Date.now();</script>

  <!-- Loader -->
  <div id="loader-overlay">
    <div class="loader-text">STORAGE</div>
  </div>

  <!-- Navbar -->
  <header>
    <nav class="navbar">
      <div class="logo">Storage</div>
      <ul class="nav-links">
        <li><a href="#">Inicio</a></li>
        <li><a href="#features">Características</a></li>
        <li><a href="#pricing">Precios</a></li>
        <li><a href="#contact">Contacto</a></li>
        <li><a class="btn primary" href="register.php">Regístrate</a></li>
        <li><a class="btn" href="login.php">Iniciar sesión</a></li>
      </ul>
    </nav>
  </header>

  <!-- Main -->
  <main>
    <!-- Hero -->
    <section class="hero">
      <div class="hero-content">
        <h1>Almacenamiento en la nube de siguiente nivel</h1>
        <p>Accede, comparte y protege tus archivos con una plataforma segura, rápida y disponible desde cualquier dispositivo.</p>
        <a class="btn primary" href="register.php">Empieza Gratis</a>
        <a class="btn" href="login.php">Tengo Cuenta</a>
      </div>
      <div class="hero-image">
        <img src="./img/storage.jpg" alt="Ilustración de almacenamiento">
      </div>
    </section>

    <!-- Features -->
    <section id="features" class="features">
      <h2>Nuestras Características</h2>
      <div class="feature-grid">
        <div class="feature">
          <img src="/images/icon-security.svg" alt="Seguridad">
          <h3>Seguridad Total</h3>
          <p>Cifrado de extremo a extremo para mantener tus datos siempre protegidos.</p>
        </div>
        <div class="feature">
          <img src="/images/icon-speed.svg" alt="Velocidad">
          <h3>Velocidad Ultra Rápida</h3>
          <p>Sube y descarga archivos al instante gracias a nuestra infraestructura optimizada.</p>
        </div>
        <div class="feature">
          <img src="/images/icon-sync.svg" alt="Sincronización">
          <h3>Sincronización Automática</h3>
          <p>Accede a tu contenido desde tu PC, móvil y tablet sin perder ni un byte.</p>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer id="contact">
    <p>&copy; 2025 Stefsec Storage. Todos los derechos reservados.</p>
  </footer>

  <!-- Oculta loader tras mínimo 2s o al cargar -->
  <script>
    window.addEventListener('load', () => {
      const MIN_DURATION = 2000;
      const elapsed = Date.now() - window.loaderStart;
      const delay = Math.max(0, MIN_DURATION - elapsed);
      setTimeout(() => {
        const loader = document.getElementById('loader-overlay');
        loader.style.opacity = '0';
        setTimeout(() => loader.style.display = 'none', 500);
      }, delay);
    });
  </script>
</body>
</html>
