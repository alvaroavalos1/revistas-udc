<?php
session_start();
require_once '../config/db.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: ../admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ? AND activo = 1');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($password, $usuario['password_hash'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre']     = $usuario['nombre'];
        $_SESSION['rol']        = $usuario['rol'];
        header('Location: ../admin/dashboard.php');
        exit;
    } else {
        $error = 'Correo o contraseña incorrectos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión — Revistas UDC</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: Arial, sans-serif;
      background: #003B7A;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .top-bar { width: 100%; height: 4px; background: #F5C518; position: fixed; top: 0; left: 0; }
    .card {
      background: #fff;
      border-radius: 16px;
      padding: 40px 36px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }
    .logo { text-align: center; margin-bottom: 28px; }
    .logo-dot-row { display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 6px; }
    .logo-dot { width: 10px; height: 10px; border-radius: 50%; background: #F5C518; }
    .logo h1 { font-size: 22px; color: #003B7A; font-weight: 500; }
    .logo p  { font-size: 12px; color: #aaa; margin-top: 4px; }
    .divider { height: 3px; background: #F5C518; border-radius: 2px; margin-bottom: 24px; }
    label { display: block; font-size: 12px; color: #555; margin-bottom: 5px; margin-top: 16px; }
    input {
      width: 100%; padding: 11px 14px;
      border: 1.5px solid #e2e8f0; border-radius: 8px;
      font-size: 14px; outline: none; transition: border-color 0.2s;
    }
    input:focus { border-color: #003B7A; }
    .btn {
      width: 100%; padding: 12px;
      background: #003B7A; color: #fff;
      border: none; border-radius: 8px;
      font-size: 15px; cursor: pointer; margin-top: 24px;
      font-weight: 500; transition: background 0.2s;
    }
    .btn:hover { background: #00306a; }
    .error {
      background: #FEF2F2; color: #B91C1C;
      border-radius: 8px; padding: 10px 14px;
      font-size: 13px; margin-top: 16px;
      border-left: 3px solid #F5C518;
    }
    .footer { margin-top: 20px; text-align: center; font-size: 11px; color: rgba(255,255,255,0.4); }
  </style>
</head>
<body>
  <div class="top-bar"></div>
  <div class="card">
    <div class="logo">
      <div class="logo-dot-row">
        <div class="logo-dot"></div>
        <div class="logo-dot" style="width:6px;height:6px;opacity:0.5;"></div>
        <div class="logo-dot" style="width:4px;height:4px;opacity:0.3;"></div>
      </div>
      <h1>Revistas UDC</h1>
      <p>Panel de administración</p>
    </div>
    <div class="divider"></div>

    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <label>Correo electrónico</label>
      <input type="email" name="email" placeholder="correo@ucol.mx" required autofocus>
      <label>Contraseña</label>
      <input type="password" name="password" placeholder="••••••••" required>
      <button type="submit" class="btn">Entrar</button>
    </form>
  </div>
  <div class="footer">Universidad de Colima &nbsp;·&nbsp; Plataforma de Revistas</div>
</body>
</html>