<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 'admin') {
    header('Location: login.php');
    exit;
}

$mensaje = '';
$error = '';

if(isset($_POST['cambiar_estado'])) {
    $pedido_id = (int)$_POST['pedido_id'];
    $nuevo_estado = (int)$_POST['nuevo_estado'];
    
    $stmt = $conn->prepare("UPDATE pedidos SET Estado = ? WHERE _Pedido = ?");
    $stmt->bind_param("ii", $nuevo_estado, $pedido_id);
    
    if($stmt->execute()) {
        $mensaje = "✅ Estado del pedido #$pedido_id actualizado correctamente";
    } else {
        $error = "❌ Error al actualizar: " . $conn->error;
    }
    $stmt->close();
}

$pedidos = $conn->query("
    SELECT p.*, u.Usu as nombre_cliente, u.email as email_cliente 
    FROM pedidos p
    LEFT JOIN usuarios u ON p._Usu = u._Usu
    ORDER BY p.Estado ASC, p._Pedido DESC
");

$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$is_admin = true;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Pedidos - The Box Is Yours</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .nav { display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 2rem; background: #01084a; padding: 12px 5%; }
        .nav a { color: white; text-decoration: none; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s; padding: 5px 0; }
        .nav a:hover { color: #00b4d8; }
        .user-menu { display: flex; align-items: center; gap: 1rem; margin-left: 1rem; }
        .user-name { color: #00b4d8; font-weight: bold; font-size: 0.9rem; }
        .logout-link { color: white; text-decoration: none; padding: 5px 12px; border-radius: 8px; transition: 0.3s; font-size: 0.85rem; }
        .logout-link:hover { background: rgba(255,255,255,0.15); }
        
        .pedidos-section { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .pedidos-section h2 { color: #00b4d8; margin-bottom: 1.5rem; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: #0f172a; padding: 1rem; border-radius: 12px; text-align: center; border: 1px solid #1e293b; }
        .stat-number { font-size: 2rem; font-weight: bold; }
        .stat-label { font-size: 0.8rem; color: #64748b; }
        .pedidos-table { width: 100%; background: #0f172a; border-radius: 12px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #1e293b; }
        th { background: #020617; color: #00b4d8; font-weight: bold; }
        .estado-badge { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.75rem; font-weight: bold; }
        .estado-pendiente { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid #ef4444; }
        .estado-revision { background: rgba(234, 179, 8, 0.2); color: #eab308; border: 1px solid #eab308; }
        .estado-cotejado { background: rgba(34, 197, 94, 0.2); color: #22c55e; border: 1px solid #22c55e; }
        .btn-estado { background: #00b4d8; color: #03045e; padding: 0.3rem 0.6rem; border: none; border-radius: 6px; cursor: pointer; font-size: 0.7rem; font-weight: bold; margin: 0 0.2rem; }
        .btn-ver { background: #1e293b; color: white; padding: 0.3rem 0.6rem; border: none; border-radius: 6px; cursor: pointer; font-size: 0.7rem; text-decoration: none; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); }
        .modal-content { background: #0f172a; margin: 5% auto; padding: 2rem; border-radius: 12px; width: 90%; max-width: 600px; position: relative; }
        .close-modal { position: absolute; top: 10px; right: 20px; font-size: 2rem; cursor: pointer; color: #cbd5e1; }
        .close-modal:hover { color: #ef4444; }
        .detalle-pedido { max-height: 400px; overflow-y: auto; }
        .detalle-item { background: #020617; padding: 0.8rem; border-radius: 8px; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
        .comprobante-img { max-width: 100%; border-radius: 8px; margin-top: 1rem; }
        .alert-success { background: rgba(34, 197, 94, 0.2); color: #22c55e; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-error { background: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .info-label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; margin-bottom: 0.3rem; }
        .info-value { font-weight: bold; color: white; }
        .info-item { background: #020617; padding: 0.8rem; border-radius: 8px; margin-bottom: 0.5rem; }
        footer { background: #01084a; text-align: center; padding: 2rem; color: #64748b; margin-top: 2rem; }
        @media (max-width: 768px) { .nav { flex-direction: column; gap: 1rem; } .pedidos-section { padding: 0 1rem; } th, td { padding: 0.5rem; font-size: 0.8rem; } }
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
    <a href="cart.php">🛒 CARRITO</a>
    <a href="admin.php">⚙️ PANEL</a>
    <div class="user-menu">
        <span class="user-name">👑 <?php echo htmlspecialchars($user_name); ?></span>
        <a href="logout.php" class="logout-link">🚪 Salir</a>
    </div>
</nav>

<div class="pedidos-section">
    <h2>👑 Administrar Pedidos</h2>
    
    <?php if($mensaje): ?><div class="alert-success"><?php echo $mensaje; ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
    
    <?php
    $stats = $conn->query("
        SELECT 
            SUM(CASE WHEN Estado = 0 THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN Estado = 1 THEN 1 ELSE 0 END) as revision,
            SUM(CASE WHEN Estado = 2 THEN 1 ELSE 0 END) as cotejados,
            COUNT(*) as total
        FROM pedidos
    ")->fetch_assoc();
    ?>
    
    <div class="stats">
        <div class="stat-card"><div class="stat-number" style="color: #ef4444;"><?php echo $stats['pendientes']; ?></div><div class="stat-label">⏳ Pendientes</div></div>
        <div class="stat-card"><div class="stat-number" style="color: #eab308;"><?php echo $stats['revision']; ?></div><div class="stat-label">📎 En revisión</div></div>
        <div class="stat-card"><div class="stat-number" style="color: #22c55e;"><?php echo $stats['cotejados']; ?></div><div class="stat-label">✅ Cotejados</div></div>
        <div class="stat-card"><div class="stat-number" style="color: #00b4d8;"><?php echo $stats['total']; ?></div><div class="stat-label">📦 Total pedidos</div></div>
    </div>
    
    <div class="pedidos-table">
        <table>
            <thead>
                <tr><th>ID</th><th>Cliente</th><th>Email</th><th>Dirección</th><th>Total</th><th>Fecha</th><th>Estado</th><th>Comprobante</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php while($pedido = $pedidos->fetch_assoc()): 
                    $estado_clase = ''; $estado_texto = '';
                    if($pedido['Estado'] == 0) { $estado_clase = 'estado-pendiente'; $estado_texto = '⏳ Pendiente'; }
                    elseif($pedido['Estado'] == 1) { $estado_clase = 'estado-revision'; $estado_texto = '📎 En revisión'; }
                    else { $estado_clase = 'estado-cotejado'; $estado_texto = '✅ Cotejado'; }
                ?>
                <tr>
                    <td>#<?php echo $pedido['_Pedido']; ?></td>
                    <td><?php echo htmlspecialchars($pedido['nombre_cliente'] ?? 'Invitado'); ?></td>
                    <td><?php echo htmlspecialchars($pedido['email_cliente'] ?? 'No registrado'); ?></td>
                    <td><?php echo htmlspecialchars(substr($pedido['Direccion'], 0, 30)); ?>...</td>
                    <td>$<?php echo number_format($pedido['Total'], 2); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($pedido['Fecha_Pedido'])); ?></td>
                    <td><span class="estado-badge <?php echo $estado_clase; ?>"><?php echo $estado_texto; ?></span></td>
                    <td>
                        <?php if($pedido['Comprobante']): ?><a href="<?php echo $pedido['Comprobante']; ?>" target="_blank" style="color:#00b4d8;">📎 Ver</a><?php else: ?><span style="color:#64748b;">—</span><?php endif; ?>
                    </td>
                    <td>
                        <button class="btn-estado" onclick='verDetalle(<?php echo json_encode($pedido); ?>)'>👁️ Ver</button>
                        <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="pedido_id" value="<?php echo $pedido['_Pedido']; ?>">
                            <select name="nuevo_estado" onchange="this.form.submit()" style="background:#1e293b; color:white; border:none; padding:0.3rem; border-radius:4px;">
                                <option value="0" <?php echo $pedido['Estado'] == 0 ? 'selected' : ''; ?>>⏳ Pendiente</option>
                                <option value="1" <?php echo $pedido['Estado'] == 1 ? 'selected' : ''; ?>>📎 En revisión</option>
                                <option value="2" <?php echo $pedido['Estado'] == 2 ? 'selected' : ''; ?>>✅ Cotejado</option>
                            </select>
                            <input type="hidden" name="cambiar_estado" value="1">
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modalDetalle" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="cerrarModalDetalle()">&times;</span>
        <h3 style="color: #00b4d8;">📋 Detalle del Pedido</h3>
        <div id="detalleContenido" class="detalle-pedido"></div>
    </div>
</div>

<footer>
    <p>The Box Is Yours - By Team Toro Zambrano | Boxeo con actitud</p>
</footer>

<script>
    function verDetalle(pedido) {
        let productos = [];
        let jsonData = pedido.Nombre || '';
        if(jsonData) {
            let regex = /\{"variante_id":\d+,"nombre":"([^"]+)","precio":([\d.]+),"cantidad":(\d+),[^}]*\}/g;
            let match;
            while ((match = regex.exec(jsonData)) !== null) {
                productos.push({ nombre: match[1], precio: parseFloat(match[2]), cantidad: parseInt(match[3]) });
            }
            if(productos.length === 0) {
                let simpleRegex = /"nombre":"([^"]+)","precio":([\d.]+),"cantidad":(\d+)/g;
                while ((match = simpleRegex.exec(jsonData)) !== null) {
                    productos.push({ nombre: match[1], precio: parseFloat(match[2]), cantidad: parseInt(match[3]) });
                }
            }
        }
        
        let productosHtml = ''; let totalProductos = 0;
        if(productos.length > 0) {
            productosHtml = '<div class="info-label" style="margin-top:1rem;">🛍️ Productos:</div>';
            productos.forEach(item => {
                let subtotal = item.precio * item.cantidad;
                totalProductos += subtotal;
                productosHtml += `<div class="detalle-item"><div><strong>${item.nombre}</strong></div><div>Cantidad: ${item.cantidad} x $${item.precio.toFixed(2)} = <strong>$${subtotal.toFixed(2)}</strong></div></div>`;
            });
        } else {
            productosHtml = '<div class="info-label" style="margin-top:1rem;">🛍️ Productos:</div><div class="detalle-item">⚠️ No se pudieron cargar los productos.</div>';
        }
        
        let comprobanteHtml = '';
        if(pedido.Comprobante && pedido.Comprobante != '') {
            comprobanteHtml = `<div class="info-label" style="margin-top:1rem;">📎 Comprobante:</div><a href="${pedido.Comprobante}" target="_blank" style="color:#00b4d8;">Ver comprobante</a><br><img src="${pedido.Comprobante}" class="comprobante-img" style="max-width:100%; margin-top:0.5rem;">`;
        } else {
            comprobanteHtml = '<div class="info-label" style="margin-top:1rem;">📎 Comprobante: <span style="color:#64748b;">No subido</span></div>';
        }
        
        let html = `
            <div class="info-item"><div class="info-label">📦 Pedido #${pedido._Pedido}</div><div class="info-value">Fecha: ${pedido.Fecha_Pedido}</div></div>
            <div class="info-item"><div class="info-label">👤 Cliente</div><div class="info-value">${pedido.nombre_cliente || 'Invitado'}</div><div class="info-value">📧 ${pedido.email_cliente || 'No registrado'}</div></div>
            <div class="info-item"><div class="info-label">📍 Dirección</div><div class="info-value">${pedido.Direccion}</div><div class="info-value">${pedido.Ciudad}, CP ${pedido.Codigo_Postal}</div></div>
            <div class="info-item"><div class="info-label">💳 Método de pago</div><div class="info-value">${pedido.Metodo_Pago == 'transferencia' ? '🏦 Transferencia' : '🏪 OXXO'}</div></div>
            <div class="info-item"><div class="info-label">💰 Total</div><div class="info-value" style="font-size:1.2rem;">$${parseFloat(pedido.Total).toLocaleString('es-MX', {minimumFractionDigits:2})} MXN</div></div>
            ${productosHtml} ${comprobanteHtml}
        `;
        document.getElementById('detalleContenido').innerHTML = html;
        document.getElementById('modalDetalle').style.display = 'block';
    }
    function cerrarModalDetalle() { document.getElementById('modalDetalle').style.display = 'none'; }
    window.onclick = function(e) { let modal = document.getElementById('modalDetalle'); if(e.target == modal) modal.style.display = 'none'; }
</script>
</body>
</html>