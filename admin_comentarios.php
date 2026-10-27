<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'admin') {
    header('Location: login.php'); exit;
}
$mensaje = '';

if(isset($_POST['eliminar_comentario'])) {
    $id = (int)$_POST['comentario_id'];
    $stmt = $conn->prepare("DELETE FROM comentarios WHERE id=?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()) $mensaje = "✅ Comentario eliminado.";
    $stmt->close();
}

$sql = "SELECT c.*, u.Usu AS autor, u.email, pb.nombre_base
        FROM comentarios c
        INNER JOIN usuarios u ON u._Usu = c.usuario_id
        INNER JOIN productos_base pb ON pb.id = c.producto_base_id
        ORDER BY c.fecha DESC";
$res = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Moderar Comentarios - Admin</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { background:#020617; font-family:'Segoe UI',sans-serif; color:white; padding:2rem; }
    .container { max-width:1200px; margin:0 auto; }
    h1 { color:#00b4d8; margin-bottom:1rem; }
    .btn-volver { background:#00b4d8; color:#03045e; padding:0.5rem 1rem; border-radius:8px; text-decoration:none; font-weight:bold; display:inline-block; margin-bottom:1.5rem; }
    .msg-ok { background:rgba(34,197,94,0.2); color:#4ade80; padding:1rem; border-radius:8px; margin-bottom:1rem; border:1px solid #22c55e; }
    .comentario-card { background:#0f172a; border:1px solid #1e293b; border-radius:10px; padding:1.2rem; margin-bottom:1rem; }
    .comentario-header { display:flex; justify-content:space-between; flex-wrap:wrap; gap:0.5rem; margin-bottom:0.5rem; }
    .autor { color:#00b4d8; font-weight:bold; }
    .producto { color:#cbd5e1; font-size:0.85rem; }
    .estrellas { color:#facc15; font-size:1rem; letter-spacing:2px; }
    .fecha { color:#64748b; font-size:0.8rem; }
    .texto { color:#e2e8f0; margin:0.8rem 0; line-height:1.5; background:#020617; padding:0.8rem; border-radius:6px; }
    .btn-eliminar { background:#ef4444; color:white; border:none; padding:0.4rem 0.9rem; border-radius:6px; cursor:pointer; font-weight:bold; font-size:0.8rem; }
    .btn-eliminar:hover { background:#b91c1c; }
    .vacio { text-align:center; padding:3rem; color:#64748b; }
</style>
</head>
<body>
<div class="container">
    <a href="admin.php" class="btn-volver">← Panel Admin</a>
    <h1>💬 Moderar Comentarios</h1>
    <?php if($mensaje): ?><div class="msg-ok"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>

    <?php if($res->num_rows === 0): ?>
        <div class="vacio">Aún no hay comentarios.</div>
    <?php else: ?>
        <?php while($c = $res->fetch_assoc()): ?>
            <div class="comentario-card">
                <div class="comentario-header">
                    <div>
                        <div class="autor">👤 <?php echo htmlspecialchars($c['autor']); ?></div>
                        <div class="producto">📦 <?php echo htmlspecialchars($c['nombre_base']); ?></div>
                    </div>
                    <div style="text-align:right;">
                        <div class="estrellas"><?php echo str_repeat('★', (int)$c['valoracion']) . str_repeat('☆', 5 - (int)$c['valoracion']); ?></div>
                        <div class="fecha"><?php echo date('d/m/Y H:i', strtotime($c['fecha'])); ?></div>
                    </div>
                </div>
                <div class="texto"><?php echo nl2br(htmlspecialchars($c['comentario'])); ?></div>
                <form method="POST" onsubmit="return confirm('¿Eliminar este comentario?')">
                    <input type="hidden" name="comentario_id" value="<?php echo $c['id']; ?>">
                    <button type="submit" name="eliminar_comentario" class="btn-eliminar">🗑️ Eliminar</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>
</body>
</html>