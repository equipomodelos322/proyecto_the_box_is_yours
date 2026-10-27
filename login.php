<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

if(isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['user_rol'] == 'admin' ? 'admin.php' : 'index.php'));
    exit;
}

// ========== INICIO DE SESIÓN ==========
if(isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT _Usu, Usu, email, Pass, rol, Activo FROM usuarios WHERE email = ? AND Activo = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if(password_verify($password, $user['Pass'])) {
            $_SESSION['user_id'] = $user['_Usu'];
            $_SESSION['user_name'] = $user['Usu'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_rol'] = $user['rol'];
            
            header('Location: ' . ($user['rol'] == 'admin' ? 'admin.php' : 'index.php'));
            exit;
        } else {
            $error = 'Contraseña incorrecta';
        }
    } else {
        $error = 'Usuario no encontrado o cuenta inactiva';
    }
    $stmt->close();
}

// ========== REGISTRO DE CLIENTE ==========
if(isset($_POST['register'])) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $pregunta_secreta = trim($_POST['pregunta_secreta'] ?? '');
    $respuesta_secreta = trim($_POST['respuesta_secreta'] ?? '');
    
    if(empty($nombre) || empty($email) || empty($password)) {
        $error = 'Completa todos los campos obligatorios';
    } elseif(empty($pregunta_secreta) || empty($respuesta_secreta)) {
        $error = 'Debes seleccionar una pregunta secreta y escribir tu respuesta';
    } elseif(strlen($respuesta_secreta) < 3) {
        $error = 'La respuesta secreta debe tener al menos 3 caracteres';
    } elseif($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } elseif(strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido';
    } else {
        // Verificar si el correo ya existe
        $check = $conn->prepare("SELECT _Usu FROM usuarios WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        
        if($check->get_result()->num_rows > 0) {
            $error = 'Este correo ya está registrado';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $hashed_respuesta = password_hash(mb_strtolower($respuesta_secreta, 'UTF-8'), PASSWORD_DEFAULT);
            $rol = 'cliente';
            $fec_reg = date('Y-m-d H:i:s');
            $activo = 1;
            
            // Insertar con el nombre exacto de las columnas de tu base de datos
            $stmt = $conn->prepare("INSERT INTO usuarios (Usu, email, telefono, Pass, rol, fec_reg, Activo, pregunta_secreta, respuesta_secreta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssiss", $nombre, $email, $telefono, $hashed_password, $rol, $fec_reg, $activo, $pregunta_secreta, $hashed_respuesta);
            
            if($stmt->execute()) {
                // Iniciar sesión automáticamente después de registrarse
                $user_id = $conn->insert_id;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $nombre;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_rol'] = $rol;
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Error al registrar: ' . $conn->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - The Box Is Yours</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #020617 0%, #0f172a 100%); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; padding: 2rem 1rem; }
        .login-container { max-width: 500px; margin: 2rem auto; padding: 2rem; background: rgba(15, 23, 42, 0.95); border-radius: 20px; border: 1px solid #1e293b; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); }
        .login-container h2 { color: #00b4d8; text-align: center; margin-bottom: 1.5rem; }
        .tab-buttons { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid #1e293b; }
        .tab-btn { background: none; border: none; padding: 0.8rem 1.5rem; font-size: 1rem; cursor: pointer; color: #94a3b8; font-weight: bold; }
        .tab-btn.active { color: #00b4d8; border-bottom: 2px solid #00b4d8; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-weight: 600; font-size: 0.9rem; }
        .form-group input, .form-group select { width: 100%; padding: 0.8rem; border-radius: 10px; border: 1px solid #1e293b; background: #020617; color: white; font-size: 1rem; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #00b4d8; }
        .password-wrapper { position: relative; display: flex; align-items: center; }
        .password-wrapper input { padding-right: 45px !important; }
        .toggle-password { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem; color: #64748b; padding: 5px; line-height: 1; transition: 0.2s; user-select: none; }
        .toggle-password:hover { color: #00b4d8; transform: translateY(-50%) scale(1.15); }
        .btn-login { width: 100%; background: #00b4d8; color: #03045e; padding: 0.9rem; border: none; border-radius: 10px; font-weight: bold; font-size: 1rem; cursor: pointer; transition: 0.3s; }
        .btn-login:hover { background: #0096c7; transform: translateY(-2px); }
        .error { background: rgba(239,68,68,0.2); color: #ef4444; padding: 0.8rem; border-radius: 10px; margin-bottom: 1rem; text-align: center; border: 1px solid #ef4444; }
        .info-badge { background: rgba(0,180,216,0.1); border-radius: 10px; padding: 0.8rem; margin-bottom: 1.5rem; text-align: center; }
        .info-badge p { color: #94a3b8; font-size: 0.85rem; }
        .info-badge strong { color: #00b4d8; }
        .link-home { text-align: center; margin-top: 1.5rem; }
        .link-home a { color: #64748b; text-decoration: none; }
        .link-home a:hover { color: #00b4d8; }
        .link-forgot { text-align: center; margin-top: 0.8rem; }
        .link-forgot a { color: #00b4d8; font-size: 0.85rem; text-decoration: none; }
        .link-forgot a:hover { text-decoration: underline; }
        .password-requirements { font-size: 0.7rem; color: #64748b; margin-top: 0.3rem; }
        .help-text { font-size: 0.7rem; color: #64748b; margin-top: 0.3rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>🥊 The Box Is Yours</h2>
        
        <div class="info-badge">
            <p>🔐 <strong>Registro obligatorio</strong> para poder comprar</p>
        </div>
        
        <?php if($error): ?>
            <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="tab-buttons">
            <button class="tab-btn active" onclick="showTab('login')">Iniciar Sesión</button>
            <button class="tab-btn" onclick="showTab('register')">Registrarse</button>
        </div>
        
        <div id="loginTab">
            <form method="POST">
                <div class="form-group">
                    <label>📧 Correo electrónico</label>
                    <input type="email" name="email" required placeholder="tu@email.com">
                </div>
                <div class="form-group">
                    <label>🔑 Contraseña</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="login_password" required placeholder="••••••••">
                        <button type="button" class="toggle-password" onclick="togglePassword('login_password', this)" title="Ver contraseña">👁️</button>
                    </div>
                </div>
                <button type="submit" name="login" class="btn-login">Iniciar Sesión</button>
                
                <div class="link-forgot">
                    <a href="recuperar_password.php">¿Olvidaste tu contraseña?</a>
                </div>
            </form>
        </div>
        
        <div id="registerTab" style="display: none;">
            <form method="POST">
                <div class="form-group">
                    <label>👤 Nombre completo</label>
                    <input type="text" name="nombre" required placeholder="Tu nombre">
                </div>
                <div class="form-group">
                    <label>📧 Correo electrónico</label>
                    <input type="email" name="email" required placeholder="tu@email.com">
                </div>
                <div class="form-group">
                    <label>📱 Teléfono (opcional)</label>
                    <input type="tel" name="telefono" placeholder="55 1234 5678">
                </div>
                <div class="form-group">
                    <label>🔑 Contraseña</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="reg_password" required placeholder="Mínimo 6 caracteres">
                        <button type="button" class="toggle-password" onclick="togglePassword('reg_password', this)" title="Ver contraseña">👁️</button>
                    </div>
                </div>
                <div class="form-group">
                    <label>✅ Confirmar contraseña</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="reg_confirm" required placeholder="Repite tu contraseña">
                        <button type="button" class="toggle-password" onclick="togglePassword('reg_confirm', this)" title="Ver contraseña">👁️</button>
                    </div>
                </div>
                <div class="password-requirements">* La contraseña debe tener al menos 6 caracteres</div>
                
                <div class="form-group" style="margin-top:1.5rem; border-top:1px solid #1e293b; padding-top:1.5rem;">
                    <label>🔐 Pregunta secreta (para recuperar tu contraseña)</label>
                    <select name="pregunta_secreta" required>
                        <option value="">-- Selecciona una pregunta --</option>
                        <option value="¿Cuál es tu fecha de nacimiento? (DD/MM/AAAA)">¿Cuál es tu fecha de nacimiento? (DD/MM/AAAA)</option>
                        <option value="¿Cuál es el nombre de tu primera mascota?">¿Cuál es el nombre de tu primera mascota?</option>
                        <option value="¿Cuál es el nombre de tu madre?">¿Cuál es el nombre de tu madre?</option>
                        <option value="¿Cuál es tu ciudad natal?">¿Cuál es tu ciudad natal?</option>
                        <option value="¿Cuál es tu comida favorita?">¿Cuál es tu comida favorita?</option>
                        <option value="¿Cuál es el nombre de tu mejor amigo de la infancia?">¿Cuál es el nombre de tu mejor amigo de la infancia?</option>
                        <option value="¿Cuál es tu equipo de fútbol favorito?">¿Cuál es tu equipo de fútbol favorito?</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>✍️ Respuesta secreta</label>
                    <input type="text" name="respuesta_secreta" required placeholder="Tu respuesta (mín. 3 caracteres)" autocomplete="off">
                    <div class="help-text">⚠️ Recuérdala bien, la necesitarás si olvidas tu contraseña</div>
                </div>
                
                <button type="submit" name="register" class="btn-login">Registrarse</button>
            </form>
        </div>
        
        <div class="link-home">
            <a href="index.php">← Volver a la tienda</a>
        </div>
    </div>
    
    <script>
        function showTab(tab) {
            const loginTab = document.getElementById('loginTab');
            const registerTab = document.getElementById('registerTab');
            const buttons = document.querySelectorAll('.tab-btn');
            
            if(tab === 'login') {
                loginTab.style.display = 'block';
                registerTab.style.display = 'none';
                buttons[0].classList.add('active');
                buttons[1].classList.remove('active');
            } else {
                loginTab.style.display = 'none';
                registerTab.style.display = 'block';
                buttons[0].classList.remove('active');
                buttons[1].classList.add('active');
            }
        }
        
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if(input.type === 'password') { input.type = 'text'; btn.innerText = '🙈'; btn.title = 'Ocultar contraseña'; }
            else { input.type = 'password'; btn.innerText = '👁️'; btn.title = 'Ver contraseña'; }
        }
        
        <?php if(isset($_POST['register']) && $error): ?>
        showTab('register');
        <?php endif; ?>
    </script>
</body>
</html>