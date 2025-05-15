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
    header { position: fixed; width:100%; top:0; left:0; background:rgba(0,0,0,0.3); backdrop-filter:blur(8px); z-index:1000; }
    .navbar { max-width:1200px; margin:auto; display:flex; justify-content:space-between; align-items:center; padding:1rem 2rem; }
    .logo { font-size:1.5rem; font-weight:600; background:linear-gradient(45deg, var(--clr-accent1), var(--clr-accent2)); -webkit-background-clip:text; color:transparent; }
    .nav-links { list-style:none; display:flex; gap:1.5rem; align-items:center; }
    .nav-links li a { font-weight:500; transition:color 0.3s; }
    .nav-links li a:hover { color:var(--clr-accent3); }
    .nav-links .btn { padding:0.5rem 1rem; border:2px solid var(--clr-accent1); border-radius:50px; transition:background 0.3s, color 0.3s; }
    .nav-links .btn.primary { background:var(--clr-accent1); color:var(--clr-light); }
    .nav-links .btn.primary:hover { background:var(--clr-accent2); border-color:var(--clr-accent2); }

    /* Hero section */
    .hero { display:flex; align-items:center; justify-content:space-between; padding:8rem 2rem 4rem; max-width:1200px; margin:auto; gap:2rem; }
    .hero-content { max-width:600px; }
    .hero-content h1 { font-size:3rem; margin-bottom:1rem; }
    .hero-content p { font-size:1.125rem; margin-bottom:2rem; color:var(--clr-gray); line-height:1.5; }
    .hero-content .btn { display:inline-block; padding:0.75rem 2rem; margin-right:1rem; font-weight:600; border:2px solid var(--clr-accent3); border-radius:50px; transition:background 0.3s; }
    .hero-content .btn.primary { background:var(--clr-accent3); color:var(--clr-light); }
    .hero-content .btn.primary:hover { background:var(--clr-accent2); border-color:var(--clr-accent2); }

    /* Hero image effect */
    .hero-image { position:relative; width:100%; max-width:500px; margin:auto; }
    .hero-image::before { content:''; position:absolute; top:15%; left:-10%; width:120%; height:120%; background:radial-gradient(circle, var(--clr-accent1), var(--clr-accent2) 60%, transparent); filter:blur(100px); z-index:-1; transform:rotate(-12deg); }
    .hero-image img { width:100%; border-radius:20px; box-shadow:0 16px 40px rgba(0,0,0,0.7); animation:float 6s ease-in-out infinite; }
    @keyframes float { 0%,100%{ transform:translateY(0);} 50%{ transform:translateY(-20px);} }

    /* Features section */
    .features { padding:4rem 2rem; background:rgba(0,0,0,0.25); }
    .features h2 { text-align:center; font-size:2rem; margin-bottom:2rem; }
    .feature-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:2rem; max-width:1000px; margin:auto; }
    .feature { background:rgba(255,255,255,0.06); padding:2.5rem 1.5rem; border-radius:16px; text-align:center; transition:transform 0.4s ease, box-shadow 0.4s ease; }
    .feature:hover { transform:translateY(-10px); box-shadow:0 20px 40px rgba(0,0,0,0.7); }

    /* Icon styling ampliado: tamaño aumentado y proporción mejorada */
    .icon-wrapper {
      position: relative;
      width: 160px;
      height: 160px;
      margin: 0 auto 1.5rem;
      transition: transform 0.3s ease;
    }
    .icon-wrapper::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: conic-gradient(from 0deg at 50% 50%, var(--clr-accent1), var(--clr-accent2), var(--clr-accent3), var(--clr-accent1));
      border-radius: 50%;
      filter: blur(16px);
      animation: rotate 6s linear infinite;
      z-index: 0;
    }
    @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .icon-wrapper::after {
      content: '';
      position: absolute;
      top: 10px;
      left: 10px;
      width: calc(100% - 20px);
      height: calc(100% - 20px);
      background: var(--clr-dark-alt);
      border-radius: 50%;
      z-index: 1;
    }
    .icon-wrapper img {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 70%;
      height: 70%;
      transform: translate(-50%, -50%);
      object-fit: contain;
      clip-path: circle(50% at 50% 50%);
      z-index: 2;
      filter: drop-shadow(0 6px 12px rgba(0,0,0,0.7));
    }
    .feature:hover .icon-wrapper { transform: scale(1.1); }
    .feature:hover .icon-wrapper::before { filter: blur(20px); }

    /* Títulos y textos de features */
    .feature h3 { font-size:1.25rem; margin-bottom:0.75rem; }
    .feature p { color:var(--clr-gray); line-height:1.4; }

    /* Footer */
    footer { padding:2rem; text-align:center; color:var(--clr-gray); font-size:0.9rem; }
  </style>
</head>
<body>
  <script>window.loaderStart = Date.now();</script>
  <div id="loader-overlay"><div class="loader-text">STORAGE</div></div>

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

  <main>
    <section class="hero">
      <div class="hero-content">
        <h1>Almacenamiento en la nube de siguiente nivel</h1>
        <p>Accede, comparte y protege tus archivos con una plataforma segura, rápida y disponible desde cualquier dispositivo.</p>
        <a class="btn primary" href="register.php">Empieza Gratis</a>
        <a class="btn" href="login.php">Tengo Cuenta</a>
      </div>
      <div class="hero-image">
        <img src="./img/datagif.gif" alt="Ilustración de almacenamiento">
      </div>
    </section>

    <section id="features" class="features">
      <h2>Nuestras Características</h2>
      <div class="feature-grid">
        <div class="feature">
          <div class="icon-wrapper"><img src="./img/icon-security.png" alt="Seguridad"></div>
          <h3>Seguridad Total</h3>
          <p>Cifrado de extremo a extremo para mantener tus datos siempre protegidos.</p>
        </div>
        <div class="feature">
          <div class="icon-wrapper"><img src="./img/icon-speed.png" alt="Velocidad"></div>
          <h3>Velocidad Ultra Rápida</h3>
          <p>Sube y descarga archivos al instante gracias a nuestra infraestructura optimizada.</p>
        </div>
        <div class="feature">
          <div class="icon-wrapper"><img src="./img/icon-sync.png" alt="Sincronización"></div>
          <h3>Sincronización Automática</h3>
          <p>Accede a tu contenido desde tu PC, móvil y tablet sin perder ni un byte.</p>
        </div>
      </div>
    </section>
  </main>

  <footer id="contact">
    <p>&copy; 2025 Storage. Todos los derechos reservados.</p>
  </footer>

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
