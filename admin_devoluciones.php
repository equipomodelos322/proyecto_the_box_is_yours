<?php
// --- ACTIVAR ERRORES PARA DIAGNÓSTICO ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ---------------------------------------

session_start();
require_once 'config.php';

if(!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}
$user_name = $_SESSION['user_name'] ?? '';
$mensaje = $error = '';

if(isset($_POST['cambiar_estado_dev'])) {
    $dev_id = (int)$_POST['dev_id'];
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';
    $comentario = trim($_POST['comentario_admin'] ?? '');

    $validos = ['pendiente','en_revision','aprobada','rechazada'];
    if(!in_array($nuevo_estado, $validos)) {
        $error = 'Estado inválido.';
    } else {
        $fecha = in_array($nuevo_estado, ['aprobada','rechazada']) ? date('Y-m-d H:i:s') : null;
        $stmt = $conn->prepare("UPDATE devoluciones SET estado=?, comentario_admin=?, fecha_respuesta=? WHERE id=?");
        $stmt->bind_param("sssi", $nuevo_estado, $comentario, $fecha, $dev_id);
        if($stmt->execute()) $mensaje = "✅ Devolución #$dev_id actualizada a: $nuevo_estado";
        else $error = 'Error: ' . $conn->error;
        $stmt->close();
    }
}

$filtro = $_GET['estado'] ?? 'todas';
$where = '';
$valid_filtros = ['pendiente','en_revision','aprobada','rechazada'];
if(in_array($filtro, $valid_filtros)) {
    $where = "WHERE d.estado = '" . $conn->real_escape_string($filtro) . "'";
}

$sql = "SELECT d.*, p.Total, p.Fecha_Pedido, u.Usu AS cliente, u.email AS cliente_email
        FROM devoluciones d
        INNER JOIN pedidos p ON p._Pedido = d.pedido_id
        INNER JOIN usuarios u ON u._Usu = d.usuario_id
        $where
        ORDER BY d.fecha_solicitud DESC";

$devoluciones = $conn->query($sql);

// --- DIAGNÓSTICO SI FALLA LA CONSULTA ---
if (!$devoluciones) {
    die("<h3 style='color:red; padding:20px;'>Error en la consulta principal:<br>" . $conn->error . "<br><br>¿Ya ejecutaste el SQL para crear la tabla 'devoluciones'?</h3>");
}

$stats_query = $conn->query("
    SELECT
        SUM(CASE WHEN estado='pendiente' THEN 1 ELSE 0 END) AS pend,
        SUM(CASE WHEN estado='en_revision' THEN 1 ELSE 0 END) AS rev,
        SUM(CASE WHEN estado='aprobada' THEN 1 ELSE 0 END) AS apro,
        SUM(CASE WHEN estado='rechazada' THEN 1 ELSE 0 END) AS rech,
        COUNT(*) AS total
    FROM devoluciones
");

if (!$stats_query) {
    die("<h3 style='color:red; padding:20px;'>Error en consulta de estadísticas:<br>" . $conn->error . "</h3>");
}

$stats = $stats_query->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Devoluciones - Admin</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { background:#020617; font-family:'Segoe UI',sans-serif; color:white; padding:2rem; }
    .container { max-width:1400px; margin:0 auto; }
    h1 { color:#00b4d8; margin-bottom:1rem; }
    .btn-volver { background:#00b4d8; color:#03045e; padding:0.5rem 1rem; border-radius:8px; text-decoration:none; font-weight:bold; display:inline-block; margin-bottom:1.5rem; }
    .btn-volver:hover { background:#0096c7; }
    .stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(130px,1fr)); gap:1rem; margin-bottom:2rem; }
    .stat { background:#0f172a; border:1px solid #1e293b; border-radius:10px; padding:1rem; text-align:center; }
    .stat strong { font-size:1.8rem; display:block; }
    .stat small { color:#64748b; font-size:0.75rem; text-transform:uppercase; }
    .filtros { display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:1.5rem; }
    .filtros a { padding:0.5rem 1rem; background:#0f172a; color:#cbd5e1; text-decoration:none; border-radius:8px; border:1px solid #1e293b; font-size:0.85rem; }
    .filtros a:hover, .filtros a.activo { background:#00b4d8; color:#03045e; border-color:#00b4d8; font-weight:bold; }
    .msg-ok { background:rgba(34,197,94,0.2); color:#4ade80; padding:1rem; border-radius:8px; margin-bottom:1rem; border:1px solid #22c55e; }
    .msg-err { background:rgba(239,68,68,0.2); color:#f87171; padding:1rem; border-radius:8px; margin-bottom:1rem; border:1px solid #ef4444; }
    table { width:100%; border-collapse:collapse; background:#0f172a; border-radius:10px; overflow:hidden; }
    th, td { padding:0.9rem 0.7rem; text-align:left; border-bottom:1px solid #1e293b; font-size:0.85rem; vertical-align:top; }
    th { background:#020617; color:#00b4d8; }
    .badge { display:inline-block; padding:0.25rem 0.7rem; border-radius:20px; font-size:0.75rem; font-weight:bold; }
    .pendiente   { background:rgba(234,179,8,0.15); color:#facc15; border:1px solid #eab308; }
    .en_revision { background:rgba(0,180,216,0.15); color:#38bdf8; border:1px solid #00b4d8; }
    .aprobada    { background:rgba(34,197,94,0.15); color:#4ade80; border:1px solid #22c55e; }
    .rechazada   { background:rgba(239,68,68,0.15); color:#f87171; border:1px solid #ef4444; }
    .motivo { max-width:280px; color:#cbd5e1; line-height:1.4; }
    .form-estado select, .form-estado textarea { background:#020617; color:white; border:1px solid #1e293b; border-radius:6px; padding:0.4rem; font-size:0.8rem; width:100%; margin-bottom:0.3rem; }
    .form-estado textarea { min-height:50px; resize:vertical; font-family:inherit; }
    .form-estado button { background:#22c55e; color:white; border:none; padding:0.4rem 0.8rem; border-radius:6px; cursor:pointer; font-weight:bold; font-size:0.75rem; }
    .form-estado button:hover { background:#16a34a; }
</style>
</head>
<body>
<div class="container">
    <a href="admin.php" class="btn-volver">← Panel Admin</a>
    <h1>↩️ Gestión de Devoluciones</h1>

    <?php if($mensaje): ?><div class="msg-ok"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>
    <?php if($error): ?><div class="msg-err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="stats">
        <div class="stat"><strong style="color:#facc15;"><?php echo (int)($stats['pend'] ?? 0); ?></strong><small>Pendientes</small></div>
        <div class="stat"><strong style="color:#38bdf8;"><?php echo (int)($stats['rev'] ?? 0); ?></strong><small>En revisión</small></div>
        <div class="stat"><strong style="color:#4ade80;"><?php echo (int)($stats['apro'] ?? 0); ?></strong><small>Aprobadas</small></div>
        <div class="stat"><strong style="color:#f87171;"><?php echo (int)($stats['rech'] ?? 0); ?></strong><small>Rechazadas</small></div>
        <div class="stat"><strong style="color:#00b4d8;"><?php echo (int)($stats['total'] ?? 0); ?></strong><small>Total</small></div>
    </div>

    <div class="filtros">
        <a href="?estado=todas" class="<?php echo $filtro==='todas'?'activo':''; ?>">Todas</a>
        <a href="?estado=pendiente" class="<?php echo $filtro==='pendiente'?'activo':''; ?>">⏳ Pendientes</a>
        <a href="?estado=en_revision" class="<?php echo $filtro==='en_revision'?'activo':''; ?>">🔎 En revisión</a>
        <a href="?estado=aprobada" class="<?php echo $filtro==='aprobada'?'activo':''; ?>">✅ Aprobadas</a>
        <a href="?estado=rechazada" class="<?php echo $filtro==='rechazada'?'activo':''; ?>">❌ Rechazadas</a>
    </div>

    <?php if($devoluciones->num_rows === 0): ?>
        <p style="color:#64748b; padding:2rem; text-align:center;">No hay devoluciones registradas en este filtro.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>ID</th><th>Pedido</th><th>Cliente</th><th>Motivo</th><th>Estado</th><th>Fecha solicitud</th><th>Gestionar</th></tr>
        </thead>
        <tbody>
        <?php while($d = $devoluciones->fetch_assoc()): ?>
            <tr>
                <td>#<?php echo $d['id']; ?></td>
                <td>
                    <strong>#<?php echo $d['pedido_id']; ?></strong><br>
                    <small style="color:#22c55e;">$<?php echo number_format($d['Total'],2); ?></small>
                </td>
                <td>
                    <?php echo htmlspecialchars($d['cliente']); ?><br>
                    <small style="color:#64748b;"><?php echo htmlspecialchars($d['cliente_email']); ?></small>
                </td>
                <td class="motivo"><?php echo nl2br(htmlspecialchars($d['motivo'])); ?></td>
                <td><span class="badge <?php echo $d['estado']; ?>"><?php echo str_replace('_',' ', $d['estado']); ?></span></td>
                <td><?php echo date('d/m/Y H:i', strtotime($d['fecha_solicitud'])); ?></td>
                <td>
                    <form method="POST" class="form-estado">
                        <input type="hidden" name="dev_id" value="<?php echo $d['id']; ?>">
                        <select name="nuevo_estado">
                            <option value="pendiente"   <?php echo $d['estado']==='pendiente'?'selected':''; ?>>⏳ Pendiente</option>
                            <option value="en_revision" <?php echo $d['estado']==='en_revision'?'selected':''; ?>>🔎 En revisión</option>
                            <option value="aprobada"    <?php echo $d['estado']==='aprobada'?'selected':''; ?>>✅ Aprobada</option>
                            <option value="rechazada"   <?php echo $d['estado']==='rechazada'?'selected':''; ?>>❌ Rechazada</option>
                        </select>
                        <textarea name="comentario_admin" placeholder="Comentario para el cliente (opcional)"><?php echo htmlspecialchars($d['comentario_admin'] ?? ''); ?></textarea>
                        <button type="submit" name="cambiar_estado_dev">💾 Actualizar</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>