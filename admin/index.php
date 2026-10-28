<?php
session_start();
require_once 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['usuario'] ?? '';
    $pass = $_POST['contrasena'] ?? '';
    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION['admin'] = true;
        header('Location: panel.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
if (isset($_SESSION['admin'])) {
    header('Location: panel.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin-libros</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { 
      font-family: Plus Jakarta Sans,
      sans-serif; background: #0d3d4f; 
      min-height: 100vh; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
    }
    .login-box { 
      background: #fff; 
      border-radius: 20px; 
      padding: 48px 40px; 
      width: 100%; 
      max-width: 400px; 
      box-shadow: 0 24px 60px rgba(0,0,0,0.3); 
    }
    .login-logo { 
      text-align: center; 
      margin-bottom: 32px;
    }
    .login-logo img { 
      height: 60px; 
    }
    .login-title { 
      font-size: 22px; 
      font-weight: 900; 
      color: #0d3d4f; 
      text-align: center; 
      margin-bottom: 6px; 
    }
    .login-sub { 
      font-size: 13px; 
      color: #4a7a8a; 
      text-align: center; 
      margin-bottom: 28px; 
    }
    .form-group { 
      margin-bottom: 16px; 
    }
    label { 
      font-size: 13px; 
      font-weight: 700; 
      color: #0d3d4f; 
      display: block; 
      margin-bottom: 6px; 
    }
    input { 
      width: 100%; 
      padding: 12px 16px; 
      border: 1.5px solid #d0e8f0; 
      border-radius: 10px; 
      font-size: 14px; 
      font-family: 'Nunito', sans-serif; 
      color: #0d3d4f; 
      outline: none; 
    }
    input:focus { 
      border-color: #1a5f7a; 
    }
    .btn { 
      width: 100%; 
      padding: 14px; 
      background: #e6a817; 
      color: #fff; 
      border: none; 
      border-radius: 10px; 
      font-size: 15px; 
      font-weight: 800; 
      font-family: 'Nunito', sans-serif; 
      cursor: pointer; 
      margin-top: 8px; 
    }
    .btn:hover { 
      background: #d4950f; 
    }
    .error { 
      background: #fee; 
      color: #c00; 
      padding: 10px 16px; 
      border-radius: 8px; 
      font-size: 13px; 
      margin-bottom: 16px; 
      font-weight: 700; 
    }
  </style>
</head>
<body>
<div class="login-box">
  <div class="login-logo">
    <img src="../images/logolibros.png" alt="Libros Vitrina" onerror="this.style.display='none'">
  </div>
  <div class="login-title">Panel de Administración</div>
  <div class="login-sub">Ingresa tus credenciales para continuar</div>
  <?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="form-group">
      <label>Usuario</label>
      <input type="text" name="usuario" placeholder="Usuario" required>
    </div>
    <div class="form-group">
      <label>Contraseña</label>
      <input type="password" name="contrasena" placeholder="Contraseña" required>
    </div>
    <button type="submit" class="btn">Ingresar</button>
  </form>
</div>
</body>
</html>