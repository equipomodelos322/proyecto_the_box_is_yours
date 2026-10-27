<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$uid       = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? '';
$is_admin  = (($_SESSION['user_rol'] ?? '') === 'admin');

$pedido_id = (int)($_GET['pedido'] ?? $_POST['pedido_id'] ?? 0);
$error = '';

if($pedido_id <= 0) {
    header('Location: mis_pedidos.php');
    exit;
}

$stmt = $conn->prepare("SELECT _Pedido, Total, Estado, Fecha_Pedido FROM Pedidos WHERE _Pedido = ? AND _Usu = ?");
$stmt->bind_param("ii", $pedido_id, $uid);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$pedido) {
    header('Location: mis_pedidos.php');
    exit;
}
if((int)$pedido['Estado'] !== 2) {
    $error = 'Solo puedes solicitar devolución de pedidos entregados/pagados.';
}

$stmt = $conn->prepare("SELECT id, estado, fecha_solicitud, comentario_admin FROM devoluciones WHERE pedido_id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$dev_existente = $stmt->get_result()->fetch_assoc();
$stmt->close();

$exito = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_devolucion']) && !$dev_existente && !$error) {
    $motivo = trim($_POST['motivo'] ?? '');

    if(strlen($motivo) < 20) {
        $error = 'El motivo debe tener al menos 20 caracteres.';
    } elseif(strlen($motivo) > 1000) {
        $error = 'El motivo no puede exceder 1000 caracteres.';
    } else {
        $ins = $conn->prepare("INSERT INTO devoluciones (pedido_id, usuario_id, motivo, estado) VALUES (?, ?, ?, 'pendiente')");
        $ins->bind_param("iis", $pedido_id, $uid, $motivo);
        if($ins->execute()) {
            $exito = '✅ Solicitud enviada. El administrador la revisará en breve.';
            $stmt = $conn->prepare("SELECT id, estado, fecha_solicitud, comentario_admin FROM devoluciones WHERE pedido_id = ?");
            $stmt->bind_param("i", $pedido_id);
            $stmt->execute();
            $dev_existente = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $error = 'Error al registrar la solicitud: ' . $conn->error;
        }
        $ins->close();
    }
}

function badgeEstadoDev($estado) {
    $map = [
        'pendiente'   => ['⏳ Pendiente',   'background:rgba(234,179,8,0.15);color:#facc15;border:1px solid #eab308;'],
        'en_revision' => ['🔎 En revisión', 'background:rgba(0,180,216,0.15);color:#38bdf8;border:1px solid #00b4d8;'],
        'aprobada'    => ['✅ Aprobada',    'background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid #22c55e;'],
        'rechazada'   => ['❌ Rechazada',   'background:rgba(239,68,68,0.15);color:#f87171;border:1px solid #ef4444;'],
    ];
    $b = $map[$estado] ?? $map['pendiente'];
    return '<span style="display:inline-block;padding:0.3rem 0.8rem;border-radius:20px;font-size:0.8rem;font-weight:bold;'.$b[1].'">'.$b[0].'</span>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Solicitar Devolución - The Box Is Yours</title>
<link rel="stylesheet" href="styles.css">
<style>
    .nav { display:flex; justify-content:center; align-items:center; flex-wrap:wrap; gap:2rem; background:#01084a; padding:12px 5%; }
    .nav a { color:white; text-decoration:none; font-weight:600; font-size:0.9rem; text-transform:uppercase; letter-spacing:1px; padding:5px 0; }
    .nav a:hover { color:#00b4d8; }
    .user-menu { display:flex; align-items:center; gap:1rem; margin-left:1rem; }
    .user-name { color:#00b4d8; font-weight:bold; font-size:0.9rem; }
    .logout-link, .admin-link { color:white; text-decoration:none; padding:5px 12px; border-radius:8px; font-size:0.85rem; }
    .admin-link { background:#eab308; color:#03045e; font-weight:bold; }
    .dev-section { max-width: 750px; margin: 2rem auto; padding: 0 2rem; }
    .dev-card { background:#0f172a; border:1px solid #1e293b; border-radius:12px; padding:1.5rem; margin-bottom:1.2rem; }
    .dev-card h2 { color:#00b4d8; margin-bottom:1rem; }
    .info-pedido { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px,1fr)); gap:0.8rem; margin-bottom:1rem; }
    .info-pedido div { background:#020617; border-radius:8px; padding:0.7rem; }
    .info-pedido small { color:#64748b; text-transform:uppercase; font-size:0.7rem; display:block; }
    .info-pedido strong { color:white; }
    textarea { width:100%; padding:0.9rem; border-radius:8px; border:1px solid #1e293b; background:#020617; color:white; min-height:140px; resize:vertical; font-family:inherit; }
    textarea:focus { outline:none; border-color:#00b4d8; }
    .btn-enviar { background:#22c55e; color:white; border:none; padding:0.9rem 1.8rem; border-radius:50px; font-weight:bold; cursor:pointer; margin-top:1rem; }
    .btn-enviar:hover { background:#16a34a; }
    .btn-gris { background:#1e293b; color:white; padding:0.8rem 1.5rem; border-radius:50px; text-decoration:none; font-weight:bold; display:inline-block; margin-top:1rem; }
    .btn-gris:hover { background:#334155; }
    .error { background:rgba(239,68,68,0.2); color:#ef4444; padding:1rem; border-radius:8px; border:1px solid #ef4444; margin-bottom:1rem; }
    .exito { background:rgba(34,197,94,0.2); color:#4ade80; padding:1rem; border-radius:8px; border:1px solid #22c55e; margin-bottom:1rem; }
    .aviso-legal { background:rgba(234,179,8,0.1); border-left:3px solid #eab308; padding:1rem; border-radius:8px; color:#facc15; font-size:0.85rem; margin-top:1rem; }
    .estado-dev { margin:1rem 0; }
    .comentario-admin { background:#020617; padding:0.9rem; border-radius:8px; margin-top:0.8rem; color:#cbd5e1; }
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
    <a href="index.php#productos">PRODUCTOS</a>
    <a href="cart.php">🛒 CARRITO</a>
    <a href="mis_pedidos.php">📋 MIS PEDIDOS</a>
    <a href="mis_favoritos.php">❤️ FAVORITOS</a>
    <?php if($is_admin): ?><a href="admin.php" class="admin-link">👑 ADMIN</a><?php endif; ?>
    <div class="user-menu">
        <span class="user-name">👋 <?php echo htmlspecialchars($user_name); ?></span>
        <a href="logout.php" class="logout-link">🚪 Salir</a>
    </div>
</nav>

<div class="dev-section">
    <h2 style="color:#00b4d8; margin-bottom:1rem;">↩️ Solicitar Devolución</h2>

    <?php if($error): ?><div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if($exito): ?><div class="exito"><?php echo $exito; ?></div><?php endif; ?>

    <div class="dev-card">
        <h2>📦 Pedido #<?php echo $pedido['_Pedido']; ?></h2>
        <div class="info-pedido">
            <div><small>Total</small><strong>$<?php echo number_format($pedido['Total'],2); ?> MXN</strong></div>
            <div><small>Fecha del pedido</small><strong><?php echo date('d/m/Y', strtotime($pedido['Fecha_Pedido'])); ?></strong></div>
            <div><small>Estado</small><strong>✅ Entregado / Pagado</strong></div>
        </div>

        <?php if($dev_existente): ?>
            <div class="estado-dev">
                <div><strong>Estado de tu solicitud:</strong> <?php echo badgeEstadoDev($dev_existente['estado']); ?></div>
                <div style="color:#94a3b8; font-size:0.85rem; margin-top:0.5rem;">
                    Enviada el <?php echo date('d/m/Y H:i', strtotime($dev_existente['fecha_solicitud'])); ?>
                </div>
                <?php if($dev_existente['comentario_admin']): ?>
                    <div class="comentario-admin">
                        <strong style="color:#00b4d8;">💬 Respuesta del administrador:</strong><br>
                        <?php echo nl2br(htmlspecialchars($dev_existente['comentario_admin'])); ?>
                    </div>
                <?php endif; ?>
            </div>
            <a href="mis_pedidos.php" class="btn-gris">← Volver a Mis Pedidos</a>

        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="pedido_id" value="<?php echo $pedido_id; ?>">
                <label style="color:#cbd5e1; font-weight:bold; display:block; margin-bottom:0.5rem;">
                    ✍️ Motivo de la devolución *
                </label>
                <textarea name="motivo" required minlength="20" maxlength="1000"
                          placeholder="Describe el motivo: producto defectuoso, talla incorrecta, no era lo que esperaba, etc. Mínimo 20 caracteres."></textarea>
                <div style="color:#64748b; font-size:0.75rem; margin-top:0.4rem;">
                    Mín. 20 / Máx. 1000 caracteres. El administrador revisará tu caso y responderá.
                </div>
                <div class="aviso-legal">
                    ⚠️ <strong>Importante:</strong> esta solicitud <u>no genera reembolso automático</u>.
                    El administrador evaluará tu caso y te contactará con la decisión.
                    El producto debe estar sin uso, con empaque original y etiquetas.
                </div>
                <button type="submit" name="enviar_devolucion" class="btn-enviar">📩 Enviar solicitud</button>
                <a href="mis_pedidos.php" class="btn-gris">Cancelar</a>
            </form>
        <?php endif; ?>
    </div>
</div>

<footer>
    <p>The Box Is Yours - By Team Toro Zambrano | Boxeo con actitud</p>
</footer>
</body>
</html>