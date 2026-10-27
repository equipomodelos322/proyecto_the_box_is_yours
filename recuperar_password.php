<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config.php';

$paso = 1;
$error = '';
$success = '';
$email = '';
$pregunta = '';
$usuario_id = null;
$sin_pregunta = false;

if(isset($_POST['verificar_email'])) {
    $email = trim($_POST['email']);
    if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido';
    } else {
        $stmt = $conn->prepare("SELECT _Usu, Usu, pregunta_secreta FROM usuarios WHERE email = ? AND Activo = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if(empty($user['pregunta_secreta'])) {
                $sin_pregunta = true;
            } else {
                $paso = 2; $pregunta = $user['pregunta_secreta']; $usuario_id = $user['_Usu'];
            }
        } else { $error = 'No existe una cuenta activa con ese email'; }
        $stmt->close();
    }
}

if(isset($_POST['verificar_respuesta'])) {
    $email = trim($_POST['email']);
    $respuesta = trim($_POST['respuesta']);
    $usuario_id = (int)$_POST['usuario_id'];
    
    $stmt = $conn->prepare("SELECT pregunta_secreta, respuesta_secreta FROM usuarios WHERE _Usu = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $respuesta_normalizada = mb_strtolower($respuesta, 'UTF-8');
        if(password_verify($respuesta_normalizada, $user['respuesta_secreta'])) {
            $paso = 3; $pregunta = $user['pregunta_secreta'];
        } else {
            $error = '❌ Respuesta incorrecta. Inténtalo de nuevo'; $paso = 2; $pregunta = $user['pregunta_secreta'];
        }
    } else { $error = 'Usuario no encontrado'; $paso = 1; }
    $stmt->close();
}

if(isset($_POST['cambiar_password'])) {
    $usuario_id = (int)$_POST['usuario_id'];
    $respuesta = trim($_POST['respuesta']);
    $nueva = $_POST['nueva_password'];
    $confirmar = $_POST['confirmar_password'];
    
    $stmt = $conn->prepare("SELECT respuesta_secreta FROM usuarios WHERE _Usu = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $respuesta_normalizada = mb_strtolower($respuesta, 'UTF-8');
        
        if(!password_verify($respuesta_normalizada, $user['respuesta_secreta'])) {
            $error = '❌ Verificación fallida. Intenta de nuevo'; $paso = 1;
        } elseif(strlen($nueva) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres'; $paso = 3;
        } elseif($nueva !== $confirmar) {
            $error = 'Las contraseñas no coinciden'; $paso = 3;
        } else {
            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE usuarios SET Pass = ? WHERE _Usu = ?");
            $upd->bind_param("si", $hash, $usuario_id);
            if($upd->execute()) {
                $success = '✅ ¡Contraseña actualizada! Ya puedes <a href="login.php" style="color:#00b4d8;">iniciar sesión</a>';
                $paso = 4;
            } else {
                $error = 'Error al actualizar: ' . $conn->error; $paso = 3;
            }
            $upd->close();
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - The Box Is Yours</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #020617 0%, #0f172a 100%); font-family: 'Segoe UI', sans-serif; min-height: 100vh; padding: 2rem 1rem; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 480px; width: 100%; padding: 2rem; background: rgba(15, 23, 42, 0.95); border-radius: 20px; border: 1px solid #1e293b; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        h2 { color: #00b4d8; text-align: center; margin-bottom: 0.5rem; }
        .sub { color: #94a3b8; text-align: center; margin-bottom: 2rem; font-size: 0.9rem; }
        label { display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-weight: 600; font-size: 0.9rem; }
        input { width: 100%; padding: 0.8rem; border-radius: 10px; border: 1px solid #1e293b; background: #020617; color: white; font-size: 1rem; margin-bottom: 1rem; }
        input:focus { outline: none; border-color: #00b4d8; }
        .password-wrapper { position: relative; display: flex; align-items: center; margin-bottom: 1rem; }
        .password-wrapper input { margin-bottom: 0; padding-right: 45px !important; }
        .toggle-password { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem; color: #64748b; padding: 5px; line-height: 1; }
        .pregunta-box { background: #020617; border: 2px solid #00b4d8; border-radius: 12px; padding: 1.2rem; margin-bottom: 1.5rem; text-align: center; }
        .pregunta-box .pregunta-label { color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem; }
        .pregunta-box .pregunta-texto { color: white; font-size: 1rem; font-weight: bold; line-height: 1.4; }
        .btn { width: 100%; background: #00b4d8; color: #03045e; padding: 0.9rem; border: none; border-radius: 10px; font-weight: bold; font-size: 1rem; cursor: pointer; transition: 0.3s; margin-top: 0.5rem; display: block; text-decoration: none; text-align: center; }
        .btn:hover { background: #0096c7; transform: translateY(-2px); }
        .btn-verde { background: #22c55e; color: white; }
        .btn-gris { background: #334155; color: white; }
        .btn-amarillo { background: #eab308; color: #03045e; }
        .error { background: rgba(239,68,68,0.2); color: #ef4444; padding: 0.8rem; border-radius: 10px; margin-bottom: 1rem; text-align: center; border: 1px solid #ef4444; font-size: 0.9rem; }
        .success { background: rgba(34,197,94,0.15); color: #22c55e; padding: 1.2rem; border-radius: 10px; margin-bottom: 1rem; border: 1px solid #22c55e; text-align: center; font-size: 1rem; font-weight: bold; }
        .link-home { text-align: center; margin-top: 1.5rem; }
        .link-home a { color: #64748b; text-decoration: none; font-size: 0.9rem; }
        .link-home a:hover { color: #00b4d8; }
        .icono { text-align: center; font-size: 3rem; margin-bottom: 1rem; }
        .paso-indicador { display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 1.5rem; }
        .paso-dot { width: 10px; height: 10px; border-radius: 50%; background: #334155; }
        .paso-dot.active { background: #00b4d8; transform: scale(1.3); }
        .paso-dot.done { background: #22c55e; }
        .aviso-amarillo { background: rgba(234,179,8,0.15); border: 1px solid #eab308; color: #eab308; padding: 1.2rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 0.9rem; text-align: center; line-height: 1.5; }
        .aviso-amarillo strong { color: #facc15; }
        .info-texto { color: #94a3b8; font-size: 0.85rem; margin-top: 1rem; text-align: center; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icono">🔐</div>
        <h2>Recuperar Contraseña</h2>
        
        <?php if($paso <= 3 && !$success && !$sin_pregunta): ?>
        <div class="paso-indicador">
            <div class="paso-dot <?php echo $paso >= 1 ? 'active' : ''; ?> <?php echo $paso > 1 ? 'done' : ''; ?>"></div>
            <div class="paso-dot <?php echo $paso >= 2 ? 'active' : ''; ?> <?php echo $paso > 2 ? 'done' : ''; ?>"></div>
            <div class="paso-dot <?php echo $paso >= 3 ? 'active' : ''; ?> <?php echo $paso > 3 ? 'done' : ''; ?>"></div>
        </div>
        <?php endif; ?>
        
        <?php if($error): ?><div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        
        <?php if($success): ?>
            <div class="success"><?php echo $success; ?></div>
            <div class="link-home"><a href="login.php">← Ir al inicio de sesión</a></div>
            
        <?php elseif($sin_pregunta): ?>
            <p class="sub">Este email aún no tiene pregunta secreta configurada</p>
            <div class="aviso-amarillo">
                ⚠️ <strong>No tienes una pregunta secreta configurada</strong>
                <br><br>
                Para poder recuperar tu contraseña en el futuro, primero debes 
                iniciar sesión y configurar una pregunta secreta desde tu cuenta.
            </div>
            <a href="login.php" class="btn">🔐 Ir a iniciar sesión</a>
            <p class="info-texto">Si ya no recuerdas tu contraseña, contacta al administrador para que te ayude a restablecerla manualmente.</p>
            <div class="link-home"><a href="recuperar_password.php">← Intentar con otro email</a></div>
            
        <?php elseif($paso == 1): ?>
            <p class="sub">Ingresa tu email para ver tu pregunta secreta</p>
            <form method="POST">
                <label>📧 Correo electrónico</label>
                <input type="email" name="email" required placeholder="tu@email.com" autofocus value="<?php echo htmlspecialchars($email); ?>">
                <button type="submit" name="verificar_email" class="btn">Continuar →</button>
            </form>
            
        <?php elseif($paso == 2): ?>
            <p class="sub">Responde tu pregunta secreta</p>
            <div class="pregunta-box">
                <div class="pregunta-label">🔑 Tu pregunta secreta</div>
                <div class="pregunta-texto"><?php echo htmlspecialchars($pregunta); ?></div>
            </div>
            <form method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="usuario_id" value="<?php echo $usuario_id; ?>">
                <label>✍️ Tu respuesta</label>
                <input type="text" name="respuesta" required placeholder="Escribe tu respuesta" autofocus autocomplete="off">
                <button type="submit" name="verificar_respuesta" class="btn btn-verde">Verificar respuesta ✓</button>
                <button type="button" class="btn btn-gris" onclick="window.location.href='recuperar_password.php'">← Volver</button>
            </form>
            
        <?php elseif($paso == 3): ?>
            <p class="sub">Ingresa tu nueva contraseña</p>
            <form method="POST">
                <input type="hidden" name="usuario_id" value="<?php echo $usuario_id; ?>">
                <input type="hidden" name="respuesta" value="<?php echo htmlspecialchars($_POST['respuesta'] ?? ''); ?>">
                <label>🔑 Nueva contraseña (mín. 6 caracteres)</label>
                <div class="password-wrapper">
                    <input type="password" name="nueva_password" id="nueva_pass" required minlength="6" autofocus>
                    <button type="button" class="toggle-password" onclick="togglePassword('nueva_pass', this)" title="Ver contraseña">👁️</button>
                </div>
                <label>✅ Confirmar contraseña</label>
                <div class="password-wrapper">
                    <input type="password" name="confirmar_password" id="confirm_pass" required minlength="6">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_pass', this)" title="Ver contraseña">👁️</button>
                </div>
                <button type="submit" name="cambiar_password" class="btn btn-verde">💾 Guardar nueva contraseña</button>
            </form>
        <?php endif; ?>
        
        <?php if(!$success && !$sin_pregunta): ?>
        <div class="link-home"><a href="login.php">← Volver al inicio de sesión</a></div>
        <?php endif; ?>
    </div>
    
    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if(input.type === 'password') { input.type = 'text'; btn.innerText = '🙈'; }
            else { input.type = 'password'; btn.innerText = '👁️'; }
        }
    </script>
</body>
</html>