<?php
// Activar errores para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

// Solo admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 'admin') {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// ========== CREAR USUARIO ==========
if(isset($_POST['crear_usuario'])) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];
    $rol = $_POST['rol'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    $pregunta_secreta = trim($_POST['pregunta_secreta'] ?? '');
    $respuesta_secreta = trim($_POST['respuesta_secreta'] ?? '');
    
    if(empty($nombre) || empty($email) || empty($password)) {
        $error = 'Nombre, email y contraseña son obligatorios';
    } elseif(empty($pregunta_secreta) || empty($respuesta_secreta)) {
        $error = 'Debes seleccionar pregunta secreta y escribir respuesta';
    } elseif(strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido';
    } elseif(!in_array($rol, ['admin', 'cliente'])) {
        $error = 'Rol inválido';
    } else {
        $check = $conn->prepare("SELECT _Usu FROM usuarios WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        
        if($check->get_result()->num_rows > 0) {
            $error = 'Este correo ya está registrado';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $hashed_respuesta = password_hash(mb_strtolower($respuesta_secreta, 'UTF-8'), PASSWORD_DEFAULT);
            $fec_reg = date('Y-m-d H:i:s');
            
            $stmt = $conn->prepare("INSERT INTO usuarios (Usu, email, telefono, Pass, rol, fec_reg, Activo, pregunta_secreta, respuesta_secreta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssiss", $nombre, $email, $telefono, $hashed_password, $rol, $fec_reg, $activo, $pregunta_secreta, $hashed_respuesta);
            
            if($stmt->execute()) {
                $success = "✅ Usuario <strong>" . htmlspecialchars($nombre) . "</strong> creado con rol <strong>$rol</strong>";
            } else {
                $error = 'Error al crear usuario: ' . $conn->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}

// ========== CAMBIAR ROL / ESTADO ==========
if(isset($_POST['cambiar_rol'])) {
    $uid = (int)$_POST['usuario_id'];
    $nuevo_rol = $_POST['nuevo_rol'];
    $activo = isset($_POST['activo_usuario']) ? 1 : 0;
    
    if($uid == $_SESSION['user_id']) {
        $error = '❌ No puedes modificar tu propia cuenta';
    } elseif(!in_array($nuevo_rol, ['admin', 'cliente'])) {
        $error = 'Rol inválido';
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET rol = ?, Activo = ? WHERE _Usu = ?");
        $stmt->bind_param("sii", $nuevo_rol, $activo, $uid);
        if($stmt->execute()) {
            $success = "✅ Usuario actualizado correctamente";
        } else {
            $error = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}

// ========== RESET DE CONTRASEÑA POR ADMIN ==========
if(isset($_POST['reset_password'])) {
    $uid = (int)$_POST['usuario_id'];
    $nueva_pass = $_POST['nueva_password'];
    
    if(strlen($nueva_pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        $hash = password_hash($nueva_pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET Pass = ? WHERE _Usu = ?");
        $stmt->bind_param("si", $hash, $uid);
        if($stmt->execute()) {
            $success = "✅ Contraseña reseteada. Nueva contraseña: <strong style='color:#eab308; font-size:1.1rem;'>$nueva_pass</strong>";
        } else {
            $error = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}

// ========== ACTUALIZAR PREGUNTA SECRETA ==========
if(isset($_POST['actualizar_pregunta'])) {
    $uid = (int)$_POST['usuario_id'];
    $nueva_pregunta = trim($_POST['nueva_pregunta']);
    $nueva_respuesta = trim($_POST['nueva_respuesta']);
    
    if(empty($nueva_pregunta) || empty($nueva_respuesta)) {
        $error = 'Pregunta y respuesta son obligatorias';
    } elseif(strlen($nueva_respuesta) < 3) {
        $error = 'La respuesta debe tener al menos 3 caracteres';
    } else {
        $hash_resp = password_hash(mb_strtolower($nueva_respuesta, 'UTF-8'), PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET pregunta_secreta = ?, respuesta_secreta = ? WHERE _Usu = ?");
        $stmt->bind_param("ssi", $nueva_pregunta, $hash_resp, $uid);
        if($stmt->execute()) {
            $success = "✅ Pregunta secreta actualizada";
        } else {
            $error = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}

// ========== ELIMINAR USUARIO ==========
if(isset($_GET['eliminar'])) {
    $uid = (int)$_GET['eliminar'];
    if($uid == $_SESSION['user_id']) {
        $error = '❌ No puedes eliminar tu propia cuenta';
    } else {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE _Usu = ?");
        $stmt->bind_param("i", $uid);
        if($stmt->execute()) {
            $success = "✅ Usuario eliminado";
        }
        $stmt->close();
    }
}

$usuarios = $conn->query("SELECT _Usu, Usu, email, telefono, rol, Activo, fec_reg, pregunta_secreta FROM usuarios ORDER BY _Usu DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #020617; font-family: 'Segoe UI', sans-serif; padding: 20px; color: white; }
        .container { max-width: 1400px; margin: 0 auto; }
        .form-card, .table-card { background: #0f172a; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #1e293b; }
        h2 { color: #00b4d8; margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.3rem; color: #cbd5e1; font-size: 0.85rem; font-weight: bold; }
        input, select { width: 100%; padding: 0.8rem; margin-bottom: 0.5rem; border-radius: 8px; border: 1px solid #1e293b; background: #020617; color: white; font-size: 0.9rem; }
        input:focus, select:focus { outline: none; border-color: #00b4d8; }
        .row-2cols { display: flex; gap: 1rem; }
        .row-2cols > div { flex: 1; }
        .password-wrapper { position: relative; display: flex; align-items: center; margin-bottom: 0.5rem; }
        .password-wrapper input { margin-bottom: 0; padding-right: 45px !important; }
        .toggle-password { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem; color: #64748b; padding: 5px; }
        .btn-primary { background: #22c55e; color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 0.95rem; transition: 0.3s; }
        .btn-primary:hover { background: #16a34a; }
        .btn-volver { background: #00b4d8; color: #03045e; padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; display: inline-block; margin-bottom: 1rem; font-weight: bold; }
        .error { background: rgba(239,68,68,0.2); color: #ef4444; padding: 0.8rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #ef4444; }
        .success { background: rgba(34,197,94,0.2); color: #4ade80; padding: 0.8rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #22c55e; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.8rem; text-align: left; border-bottom: 1px solid #1e293b; font-size: 0.85rem; }
        th { color: #00b4d8; background: #020617; }
        tr:hover td { background: #0a192f; }
        .badge { padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.7rem; font-weight: bold; }
        .badge-admin { background: #eab308; color: #03045e; }
        .badge-cliente { background: #22c55e; color: white; }
        .badge-activo { background: #22c55e; color: white; }
        .badge-inactivo { background: #ef4444; color: white; }
        .form-inline { display: flex; gap: 0.3rem; align-items: center; flex-wrap: wrap; }
        .form-inline select { padding: 0.3rem; font-size: 0.75rem; margin: 0; width: auto; }
        .form-inline input[type="checkbox"] { width: auto; margin: 0; }
        .form-inline button { padding: 0.3rem 0.6rem; font-size: 0.7rem; background: #00b4d8; color: #03045e; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-danger { background: #ef4444 !important; color: white !important; text-decoration: none; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.7rem; display: inline-block; }
        .btn-warning { background: #eab308 !important; color: #03045e !important; text-decoration: none; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.7rem; border: none; cursor: pointer; font-weight: bold; }
        .btn-info { background: #a855f7 !important; color: white !important; text-decoration: none; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.7rem; border: none; cursor: pointer; font-weight: bold; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); }
        .modal-content { background: #0f172a; margin: 8% auto; padding: 2rem; border-radius: 12px; width: 90%; max-width: 480px; position: relative; border: 1px solid #1e293b; }
        .close-modal { position: absolute; top: 10px; right: 20px; font-size: 2rem; cursor: pointer; color: #cbd5e1; }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin.php" class="btn-volver">← Volver al Panel</a>
        <div class="form-card">
            <h2>➕ Crear Nuevo Usuario</h2>
            <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
            <?php if($success): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>
            
            <form method="POST">
                <div class="row-2cols">
                    <div><label>Nombre completo *</label><input type="text" name="nombre" required></div>
                    <div><label>Correo electrónico *</label><input type="email" name="email" required></div>
                </div>
                <div class="row-2cols">
                    <div><label>Teléfono</label><input type="tel" name="telefono"></div>
                    <div><label>Rol *</label><select name="rol" required><option value="cliente">Cliente</option><option value="admin">Administrador</option></select></div>
                </div>
                <div class="row-2cols">
                    <div>
                        <label>Contraseña * (mín. 6 caracteres)</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="crear_pass" required minlength="6">
                            <button type="button" class="toggle-password" onclick="togglePassword('crear_pass', this)">👁️</button>
                        </div>
                    </div>
                    <div><label>Estado</label><label style="display:flex; align-items:center; gap:0.5rem; color:#cbd5e1; font-weight:normal; padding-top:0.5rem;"><input type="checkbox" name="activo" checked style="width:auto;"> Cuenta activa</label></div>
                </div>
                <div class="row-2cols">
                    <div>
                        <label>🔐 Pregunta secreta *</label>
                        <select name="pregunta_secreta" required>
                            <option value="">-- Selecciona --</option>
                            <option value="¿Cuál es tu fecha de nacimiento? (DD/MM/AAAA)">¿Fecha de nacimiento?</option>
                            <option value="¿Cuál es el nombre de tu primera mascota?">¿Nombre de tu primera mascota?</option>
                            <option value="¿Cuál es el nombre de tu madre?">¿Nombre de tu madre?</option>
                            <option value="¿Cuál es tu ciudad natal?">¿Cuál es tu ciudad natal?</option>
                            <option value="¿Cuál es tu comida favorita?">¿Cuál es tu comida favorita?</option>
                            <option value="¿Cuál es el nombre de tu mejor amigo de la infancia?">¿Mejor amigo de infancia?</option>
                        </select>
                    </div>
                    <div><label>✍️ Respuesta secreta *</label><input type="text" name="respuesta_secreta" required placeholder="Respuesta (mín. 3 caracteres)" autocomplete="off"></div>
                </div>
                <button type="submit" name="crear_usuario" class="btn-primary">✅ Crear Usuario</button>
            </form>
        </div>
        
        <div class="table-card">
            <h2>📋 Usuarios Registrados (<?php echo $usuarios->num_rows; ?>)</h2>
            <table>
                <thead>
                    <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Rol</th><th>Estado</th><th>Pregunta</th><th>Registro</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php while($user = $usuarios->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $user['_Usu']; ?></td>
                        <td><?php echo htmlspecialchars($user['Usu']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['telefono'] ?? '—'); ?></td>
                        <td><span class="badge <?php echo $user['rol'] == 'admin' ? 'badge-admin' : 'badge-cliente'; ?>"><?php echo $user['rol']; ?></span></td>
                        <td><span class="badge <?php echo $user['Activo'] ? 'badge-activo' : 'badge-inactivo'; ?>"><?php echo $user['Activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                        <td><?php if($user['pregunta_secreta']): ?><span class="badge badge-cliente" title="<?php echo htmlspecialchars($user['pregunta_secreta']); ?>">✅ Sí</span><?php else: ?><span class="badge badge-inactivo">⚠️ No</span><?php endif; ?></td>
                        <td><?php echo $user['fec_reg'] ? date('d/m/Y', strtotime($user['fec_reg'])) : '—'; ?></td>
                        <td>
                            <?php if($user['_Usu'] != $_SESSION['user_id']): ?>
                                <form method="POST" class="form-inline" style="margin-bottom:0.3rem;">
                                    <input type="hidden" name="usuario_id" value="<?php echo $user['_Usu']; ?>">
                                    <select name="nuevo_rol"><option value="cliente" <?php echo $user['rol']=='cliente'?'selected':''; ?>>Cliente</option><option value="admin" <?php echo $user['rol']=='admin'?'selected':''; ?>>Admin</option></select>
                                    <label style="display:flex; align-items:center; gap:0.2rem; color:#cbd5e1; font-size:0.7rem;"><input type="checkbox" name="activo_usuario" <?php echo $user['Activo']?'checked':''; ?>> Activo</label>
                                    <button type="submit" name="cambiar_rol" title="Guardar cambios">💾</button>
                                </form>
                                <button class="btn-info" onclick="abrirPregunta(<?php echo $user['_Usu']; ?>, '<?php echo addslashes($user['Usu']); ?>')" title="Editar pregunta secreta">🔐</button>
                                <button class="btn-warning" onclick="abrirResetPass(<?php echo $user['_Usu']; ?>, '<?php echo addslashes($user['Usu']); ?>')" title="Resetear contraseña">🔑</button>
                                <a href="?eliminar=<?php echo $user['_Usu']; ?>" class="btn-danger" onclick="return confirm('¿Eliminar este usuario?')" title="Eliminar">🗑️</a>
                            <?php else: ?>
                                <span style="color:#64748b; font-size:0.75rem;">(Tú)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div id="modalResetPass" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="cerrarResetPass()">&times;</span>
            <h3 style="color:#00b4d8; margin-bottom:1rem;">🔑 Resetear Contraseña</h3>
            <p style="color:#94a3b8; font-size:0.85rem; margin-bottom:1rem;">Usuario: <strong id="nombreUsuarioReset" style="color:white;"></strong></p>
            <form method="POST">
                <input type="hidden" name="usuario_id" id="reset_usuario_id">
                <label>Nueva contraseña (mín. 6 caracteres)</label>
                <div class="password-wrapper">
                    <input type="text" name="nueva_password" id="nueva_pass_input" required minlength="6">
                    <button type="button" class="toggle-password" onclick="togglePassword('nueva_pass_input', this)">👁️</button>
                </div>
                <button type="submit" name="reset_password" class="btn-primary" style="width:100%;">✅ Cambiar Contraseña</button>
            </form>
            <p style="color:#64748b; font-size:0.75rem; margin-top:1rem;">💡 Copia la contraseña generada y compártela con el usuario.</p>
        </div>
    </div>
    
    <div id="modalPregunta" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="cerrarPregunta()">&times;</span>
            <h3 style="color:#00b4d8; margin-bottom:1rem;">🔐 Editar Pregunta Secreta</h3>
            <p style="color:#94a3b8; font-size:0.85rem; margin-bottom:1rem;">Usuario: <strong id="nombreUsuarioPregunta" style="color:white;"></strong></p>
            <form method="POST">
                <input type="hidden" name="usuario_id" id="pregunta_usuario_id">
                <label>Pregunta secreta</label>
                <select name="nueva_pregunta" required>
                    <option value="">-- Selecciona --</option>
                    <option value="¿Cuál es tu fecha de nacimiento? (DD/MM/AAAA)">¿Fecha de nacimiento?</option>
                    <option value="¿Cuál es el nombre de tu primera mascota?">¿Nombre de tu primera mascota?</option>
                    <option value="¿Cuál es el nombre de tu madre?">¿Nombre de tu madre?</option>
                    <option value="¿Cuál es tu ciudad natal?">¿Cuál es tu ciudad natal?</option>
                    <option value="¿Cuál es tu comida favorita?">¿Cuál es tu comida favorita?</option>
                    <option value="¿Cuál es el nombre de tu mejor amigo de la infancia?">¿Mejor amigo de infancia?</option>
                </select>
                <label>Respuesta secreta</label>
                <input type="text" name="nueva_respuesta" required placeholder="Mín. 3 caracteres" autocomplete="off">
                <button type="submit" name="actualizar_pregunta" class="btn-primary" style="width:100%;">✅ Guardar</button>
            </form>
        </div>
    </div>
    
    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if(input.type === 'password') { input.type = 'text'; btn.innerText = '🙈'; }
            else { input.type = 'password'; btn.innerText = '👁️'; }
        }
        function abrirResetPass(uid, nombre) {
            document.getElementById('reset_usuario_id').value = uid;
            document.getElementById('nombreUsuarioReset').innerText = nombre;
            document.getElementById('nueva_pass_input').value = generarPassword();
            document.getElementById('modalResetPass').style.display = 'block';
        }
        function cerrarResetPass() { document.getElementById('modalResetPass').style.display = 'none'; }
        function abrirPregunta(uid, nombre) {
            document.getElementById('pregunta_usuario_id').value = uid;
            document.getElementById('nombreUsuarioPregunta').innerText = nombre;
            document.getElementById('modalPregunta').style.display = 'block';
        }
        function cerrarPregunta() { document.getElementById('modalPregunta').style.display = 'none'; }
        function generarPassword() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
            let pass = '';
            for(let i = 0; i < 8; i++) { pass += chars.charAt(Math.floor(Math.random() * chars.length)); }
            return pass;
        }
        window.onclick = function(e) {
            if(e.target == document.getElementById('modalResetPass')) cerrarResetPass();
            if(e.target == document.getElementById('modalPregunta')) cerrarPregunta();
        }
    </script>
</body>
</html>