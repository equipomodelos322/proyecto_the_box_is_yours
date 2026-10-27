<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 'admin');
$user_name = $_SESSION['user_name'] ?? '';

$mensaje = '';
$error = '';

// ========== ACTUALIZAR DATOS PERSONALES ==========
if(isset($_POST['actualizar_datos'])) {
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    
    if(empty($nombre)) {
        $error = 'El nombre es obligatorio';
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET Usu = ?, telefono = ? WHERE _Usu = ?");
        $stmt->bind_param("ssi", $nombre, $telefono, $user_id);
        if($stmt->execute()) {
            $_SESSION['user_name'] = $nombre;
            $user_name = $nombre;
            $mensaje = '✅ Datos actualizados correctamente';
        } else {
            $error = 'Error al actualizar: ' . $conn->error;
        }
        $stmt->close();
    }
}

// ========== CAMBIAR CONTRASEÑA ==========
if(isset($_POST['cambiar_password'])) {
    $actual = $_POST['pass_actual'];
    $nueva = $_POST['pass_nueva'];
    $confirmar = $_POST['pass_confirmar'];
    
    if(empty($actual) || empty($nueva) || empty($confirmar)) {
        $error = 'Completa todos los campos de contraseña';
    } elseif(strlen($nueva) < 6) {
        $error = 'La nueva contraseña debe tener al menos 6 caracteres';
    } elseif($nueva !== $confirmar) {
        $error = 'Las contraseñas no coinciden';
    } else {
        $stmt = $conn->prepare("SELECT Pass FROM usuarios WHERE _Usu = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if(!password_verify($actual, $user['Pass'])) {
            $error = 'La contraseña actual es incorrecta';
        } else {
            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE usuarios SET Pass = ? WHERE _Usu = ?");
            $upd->bind_param("si", $hash, $user_id);
            if($upd->execute()) {
                $mensaje = '✅ Contraseña cambiada correctamente';
            } else {
                $error = 'Error: ' . $conn->error;
            }
            $upd->close();
        }
    }
}

// ========== ACTUALIZAR PREGUNTA SECRETA ==========
if(isset($_POST['actualizar_pregunta'])) {
    $pregunta = trim($_POST['pregunta_secreta']);
    $respuesta = trim($_POST['respuesta_secreta']);
    
    if(empty($pregunta) || empty($respuesta)) {
        $error = 'Debes seleccionar pregunta y escribir respuesta';
    } elseif(strlen($respuesta) < 3) {
        $error = 'La respuesta debe tener al menos 3 caracteres';
    } else {
        $hash_resp = password_hash(mb_strtolower($respuesta, 'UTF-8'), PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET pregunta_secreta = ?, respuesta_secreta = ? WHERE _Usu = ?");
        $stmt->bind_param("ssi", $pregunta, $hash_resp, $user_id);
        if($stmt->execute()) {
            $mensaje = '✅ Pregunta secreta configurada. Ya puedes recuperar tu contraseña si la olvidas';
        } else {
            $error = 'Error: ' . $conn->error;
        }
        $stmt->close();
    }
}

// ========== OBTENER DATOS DEL USUARIO ==========
$stmt = $conn->prepare("SELECT Usu, email, telefono, pregunta_secreta, fec_reg FROM usuarios WHERE _Usu = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

$tiene_pregunta = !empty($usuario['pregunta_secreta']);

// Obtener cantidad de pedidos
$stmt2 = $conn->prepare("SELECT COUNT(*) as total FROM pedidos WHERE _Usu = ?");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$total_pedidos = $stmt2->get_result()->fetch_assoc()['total'];
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - The Box Is Yours</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #020617; font-family: 'Segoe UI', sans-serif; color: white; }
        
        .nav {
            display: flex; justify-content: center; align-items: center;
            flex-wrap: wrap; gap: 2rem; background: #01084a; padding: 12px 5%;
        }
        .nav a {
            color: white; text-decoration: none; font-weight: 600;
            font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;
            transition: all 0.3s; padding: 5px 0;
        }
        .nav a:hover { color: #00b4d8; }
        .user-menu { display: flex; align-items: center; gap: 1rem; margin-left: 1rem; }
        .user-name { color: #00b4d8; font-weight: bold; font-size: 0.9rem; }
        .logout-link, .admin-link {
            color: white; text-decoration: none; padding: 5px 12px;
            border-radius: 8px; transition: 0.3s; font-size: 0.85rem;
        }
        .admin-link { background: #eab308; color: #03045e; font-weight: bold; }
        .logout-link:hover { background: rgba(255,255,255,0.15); }
        
        .cuenta-section { max-width: 800px; margin: 2rem auto; padding: 0 2rem; }
        .cuenta-section h2 { color: #00b4d8; margin-bottom: 1.5rem; }
        
        .card {
            background: #0f172a; border-radius: 12px; padding: 1.5rem;
            margin-bottom: 1.5rem; border: 1px solid #1e293b;
        }
        .card h3 {
            color: #00b4d8; margin-bottom: 1rem; padding-bottom: 0.5rem;
            border-bottom: 1px solid #1e293b;
        }
        
        label { display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-weight: 600; font-size: 0.9rem; }
        input, select {
            width: 100%; padding: 0.8rem; border-radius: 8px;
            border: 1px solid #1e293b; background: #020617; color: white;
            font-size: 0.95rem; margin-bottom: 1rem;
        }
        input:focus, select:focus { outline: none; border-color: #00b4d8; }
        
        .password-wrapper { position: relative; display: flex; align-items: center; margin-bottom: 1rem; }
        .password-wrapper input { margin-bottom: 0; padding-right: 45px !important; }
        .toggle-password {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; font-size: 1.2rem;
            color: #64748b; padding: 5px; line-height: 1; transition: 0.2s; user-select: none;
        }
        .toggle-password:hover { color: #00b4d8; transform: translateY(-50%) scale(1.15); }
        
        .btn {
            padding: 0.8rem 1.5rem; border: none; border-radius: 8px;
            cursor: pointer; font-weight: bold; font-size: 0.95rem;
            transition: 0.3s;
        }
        .btn-primary { background: #22c55e; color: white; }
        .btn-primary:hover { background: #16a34a; }
        .btn-warning { background: #eab308; color: #03045e; }
        .btn-warning:hover { background: #ca8a04; }
        .btn-info { background: #00b4d8; color: #03045e; }
        .btn-info:hover { background: #0096c7; }
        
        .error { background: rgba(239,68,68,0.2); color: #ef4444; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #ef4444; }
        .success { background: rgba(34,197,94,0.2); color: #4ade80; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #22c55e; }
        
        .info-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem; margin-bottom: 1rem;
        }
        .info-item {
            background: #020617; padding: 0.8rem; border-radius: 8px;
            border: 1px solid #1e293b;
        }
        .info-label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; }
        .info-value { font-weight: bold; color: white; margin-top: 0.2rem; }
        
        .aviso-pregunta {
            background: rgba(234,179,8,0.15); border: 1px solid #eab308;
            color: #eab308; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        footer { background: #01084a; text-align: center; padding: 2rem; color: #64748b; margin-top: 2rem; }
        
        @media (max-width: 768px) {
            .nav { flex-direction: column; gap: 1rem; }
            .cuenta-section { padding: 0 1rem; }
        }
    </style>
</head>
<body>

<header class="header">
    <div class="logo-container">
        <div class="logo">
            <img src="uploads/logo2.png" alt="The Box Is Yours">
            <p>By Team Toro Zambrano</p>
        </div>
    </div>
</header>

<nav class="nav">
    <a href="index.php#inicio">INICIO</a>
    <a href="index.php#productos">PRODUCTOS</a>
    <a href="index.php#contacto">CONTACTO</a>
    <a href="cart.php">🛒 CARRITO</a>
    <a href="mis_pedidos.php">📋 MIS PEDIDOS</a>
    <a href="mi_cuenta.php" style="color:#00b4d8;">👤 MI CUENTA</a>
    <?php if($is_admin): ?>
        <a href="admin.php" class="admin-link">👑 ADMIN</a>
    <?php endif; ?>
    <div class="user-menu">
        <span class="user-name">👋 <?php echo htmlspecialchars($user_name); ?></span>
        <a href="logout.php" class="logout-link">🚪 Salir</a>
    </div>
</nav>

<div class="cuenta-section">
    <h2>👤 Mi Cuenta</h2>
    
    <?php if($error): ?><div class="error">⚠️ <?php echo $error; ?></div><?php endif; ?>
    <?php if($mensaje): ?><div class="success"><?php echo $mensaje; ?></div><?php endif; ?>
    
    <div class="card">
        <h3>📋 Mis Datos</h3>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Nombre</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['Usu']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['email']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Teléfono</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['telefono'] ?? '—'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Pedidos</div>
                <div class="info-value"><?php echo $total_pedidos; ?> pedidos</div>
            </div>
        </div>
        
        <form method="POST">
            <label>Nombre completo</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['Usu']); ?>" required>
            
            <label>Teléfono</label>
            <input type="tel" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
            
            <button type="submit" name="actualizar_datos" class="btn btn-info">💾 Guardar datos</button>
        </form>
    </div>
    
    <div class="card">
        <h3>🔐 Pregunta Secreta</h3>
        
        <?php if($tiene_pregunta): ?>
            <div class="info-item" style="margin-bottom:1rem;">
                <div class="info-label">Tu pregunta actual</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['pregunta_secreta']); ?></div>
            </div>
            <p style="color:#94a3b8; font-size:0.85rem; margin-bottom:1rem;">
                ✅ Ya tienes una pregunta secreta configurada. Puedes cambiarla por una nueva si quieres.
            </p>
        <?php else: ?>
            <div class="aviso-pregunta">
                ⚠️ <strong>No tienes pregunta secreta configurada.</strong><br>
                Configúrala ahora para poder recuperar tu contraseña si la olvidas.
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <label>Selecciona una pregunta secreta</label>
            <select name="pregunta_secreta" required>
                <option value="">-- Selecciona una pregunta --</option>
                <option value="¿Cuál es tu fecha de nacimiento? (DD/MM/AAAA)" <?php echo ($usuario['pregunta_secreta'] == '¿Cuál es tu fecha de nacimiento? (DD/MM/AAAA)') ? 'selected' : ''; ?>>¿Cuál es tu fecha de nacimiento? (DD/MM/AAAA)</option>
                <option value="¿Cuál es el nombre de tu primera mascota?" <?php echo ($usuario['pregunta_secreta'] == '¿Cuál es el nombre de tu primera mascota?') ? 'selected' : ''; ?>>¿Cuál es el nombre de tu primera mascota?</option>
                <option value="¿Cuál es el nombre de tu madre?" <?php echo ($usuario['pregunta_secreta'] == '¿Cuál es el nombre de tu madre?') ? 'selected' : ''; ?>>¿Cuál es el nombre de tu madre?</option>
                <option value="¿Cuál es tu ciudad natal?" <?php echo ($usuario['pregunta_secreta'] == '¿Cuál es tu ciudad natal?') ? 'selected' : ''; ?>>¿Cuál es tu ciudad natal?</option>
                <option value="¿Cuál es tu comida favorita?" <?php echo ($usuario['pregunta_secreta'] == '¿Cuál es tu comida favorita?') ? 'selected' : ''; ?>>¿Cuál es tu comida favorita?</option>
                <option value="¿Cuál es el nombre de tu mejor amigo de la infancia?" <?php echo ($usuario['pregunta_secreta'] == '¿Cuál es el nombre de tu mejor amigo de la infancia?') ? 'selected' : ''; ?>>¿Cuál es el nombre de tu mejor amigo de la infancia?</option>
                <option value="¿Cuál es tu equipo de fútbol favorito?" <?php echo ($usuario['pregunta_secreta'] == '¿Cuál es tu equipo de fútbol favorito?') ? 'selected' : ''; ?>>¿Cuál es tu equipo de fútbol favorito?</option>
            </select>
            
            <label>Respuesta secreta</label>
            <input type="text" name="respuesta_secreta" required placeholder="Escribe tu respuesta (mín. 3 caracteres)" autocomplete="off">
            
            <button type="submit" name="actualizar_pregunta" class="btn btn-warning">
                <?php echo $tiene_pregunta ? '🔄 Cambiar pregunta secreta' : '🔐 Configurar pregunta secreta'; ?>
            </button>
        </form>
    </div>
    
    <div class="card">
        <h3>🔑 Cambiar Contraseña</h3>
        <form method="POST">
            <label>Contraseña actual</label>
            <div class="password-wrapper">
                <input type="password" name="pass_actual" id="pass_actual" required>
                <button type="button" class="toggle-password" onclick="togglePassword('pass_actual', this)" title="Ver contraseña">👁️</button>
            </div>
            
            <label>Nueva contraseña (mín. 6 caracteres)</label>
            <div class="password-wrapper">
                <input type="password" name="pass_nueva" id="pass_nueva" required minlength="6">
                <button type="button" class="toggle-password" onclick="togglePassword('pass_nueva', this)" title="Ver contraseña">👁️</button>
            </div>
            
            <label>Confirmar nueva contraseña</label>
            <div class="password-wrapper">
                <input type="password" name="pass_confirmar" id="pass_confirmar" required minlength="6">
                <button type="button" class="toggle-password" onclick="togglePassword('pass_confirmar', this)" title="Ver contraseña">👁️</button>
            </div>
            
            <button type="submit" name="cambiar_password" class="btn btn-primary">💾 Cambiar contraseña</button>
        </form>
    </div>
</div>

<footer>
    <p>The Box Is Yours - By Team Toro Zambrano | Boxeo con actitud</p>
    <p>CDMX | Guantes, botas y más | Envíos a todo México</p>
</footer>

<script>
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        if(input.type === 'password') {
            input.type = 'text';
            btn.innerText = '🙈';
            btn.title = 'Ocultar contraseña';
        } else {
            input.type = 'password';
            btn.innerText = '👁️';
            btn.title = 'Ver contraseña';
        }
    }
</script>

</body>
</html>