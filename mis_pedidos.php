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

$pedidos = $conn->prepare("
    SELECT _Pedido, Direccion, Ciudad, Codigo_Postal, Metodo_Pago, Total, 
           Fecha_Pedido, Estado, Comprobante, Nombre 
    FROM pedidos 
    WHERE _Usu = ? 
    ORDER BY _Pedido DESC
");
$pedidos->bind_param("i", $user_id);
$pedidos->execute();
$resultado_pedidos = $pedidos->get_result();

function decodificarProductos($json_data) {
    if(empty($json_data)) return [];
    $productos = json_decode($json_data, true);
    if(json_last_error() !== JSON_ERROR_NONE || !is_array($productos)) return [];
    $limpios = [];
    foreach($productos as $item) {
        if(is_array($item)) {
            $limpios[] = [
                'nombre' => $item['nombre'] ?? 'Producto',
                'cantidad' => intval($item['cantidad'] ?? 1),
                'precio' => floatval($item['precio'] ?? 0),
                'variante_id' => $item['variante_id'] ?? null
            ];
        }
    }
    return $limpios;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - The Box Is Yours</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .nav { display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 2rem; background: #01084a; padding: 12px 5%; }
        .nav a { color: white; text-decoration: none; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s; padding: 5px 0; }
        .nav a:hover { color: #00b4d8; }
        .user-menu { display: flex; align-items: center; gap: 1rem; margin-left: 1rem; }
        .user-name { color: #00b4d8; font-weight: bold; font-size: 0.9rem; }
        .logout-link, .admin-link { color: white; text-decoration: none; padding: 5px 12px; border-radius: 8px; transition: 0.3s; font-size: 0.85rem; }
        .admin-link { background: #eab308; color: #03045e; font-weight: bold; }
        
        .pedidos-section { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .pedidos-section h2 { color: #00b4d8; margin-bottom: 1.5rem; }
        .pedidos-grid { display: grid; gap: 1.5rem; }
        .pedido-card { background: #0f172a; border-radius: 12px; border: 1px solid #1e293b; overflow: hidden; transition: 0.3s; }
        .pedido-card:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .pedido-header { padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid #1e293b; }
        .pedido-numero { font-size: 1.2rem; font-weight: bold; color: white; }
        .pedido-fecha { color: #64748b; font-size: 0.85rem; }
        .pedido-body { padding: 1.5rem; }
        .pedido-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .info-item { background: #020617; padding: 0.8rem; border-radius: 8px; }
        .info-label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; }
        .info-value { font-weight: bold; color: white; }
        .estado-badge { display: inline-block; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.8rem; font-weight: bold; }
        .estado-pagado { background: rgba(34,197,94,0.2); color: #22c55e; border: 1px solid #22c55e; }
        .estado-enviado { background: rgba(0,180,216,0.2); color: #00b4d8; border: 1px solid #00b4d8; }
        .estado-entregado { background: rgba(34,197,94,0.3); color: #4ade80; border: 1px solid #4ade80; }
        .btn-ver-productos { background: #1e293b; color: white; padding: 0.5rem 1rem; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; margin-top: 0.5rem; }
        .btn-ver-productos:hover { background: #334155; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); }
        .modal-content { background: #0f172a; margin: 5% auto; padding: 2rem; border-radius: 12px; width: 90%; max-width: 500px; position: relative; }
        .close-modal { position: absolute; top: 10px; right: 20px; font-size: 2rem; cursor: pointer; color: #cbd5e1; }
        .close-modal:hover { color: #ef4444; }
        .producto-item { background: #020617; padding: 0.8rem; border-radius: 8px; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
        .empty-pedidos { text-align: center; padding: 3rem; background: #0f172a; border-radius: 12px; color: #64748b; }
        .btn-devolucion { background:#eab308; color:#03045e; padding:0.5rem 1rem; border-radius:20px; text-decoration:none; font-weight:bold; font-size:0.8rem; display:inline-block; }
        .btn-devolucion:hover { background:#ca8a04; }
        .dev-info { font-size:0.85rem; color:#cbd5e1; margin-top:0.3rem; }
        footer { background: #01084a; text-align: center; padding: 2rem; color: #64748b; margin-top: 2rem; }
        @media (max-width: 768px) { .nav { flex-direction: column; gap: 1rem; } .pedidos-section { padding: 0 1rem; } }
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
    <a href="index.php#nosotros">NOSOTROS</a>
    <a href="index.php#productos">PRODUCTOS</a>
    <a href="index.php#contacto">CONTACTO</a>
    <a href="cart.php">🛒 CARRITO</a>
    <a href="mis_pedidos.php" style="color: #00b4d8;">📋 MIS PEDIDOS</a>
    <a href="mis_favoritos.php">❤️ FAVORITOS</a>
    <a href="faq.php">❓ FAQ</a>
    <?php if($is_admin): ?><a href="admin_pedidos.php" class="admin-link">👑 ADMIN</a><?php endif; ?>
    <div class="user-menu">
        <span class="user-name">👋 <?php echo htmlspecialchars($user_name); ?></span>
        <a href="logout.php" class="logout-link">🚪 Salir</a>
    </div>
</nav>

<div class="pedidos-section">
    <h2>📋 Mis Pedidos</h2>
    
    <?php if($resultado_pedidos->num_rows == 0): ?>
        <div class="empty-pedidos">
            <p>🛒 No tienes pedidos realizados aún.</p>
            <a href="index.php#productos" style="color: #00b4d8;">← Ir a la tienda</a>
        </div>
    <?php else: ?>
        <div class="pedidos-grid">
            <?php while($pedido = $resultado_pedidos->fetch_assoc()): 
                $estado = $pedido['Estado'];
                $estado_texto = ''; $estado_clase = '';
                if($estado == 0) { $estado_texto = '⏳ Pendiente'; $estado_clase = 'estado-pagado'; }
                elseif($estado == 1) { $estado_texto = '📦 Enviado'; $estado_clase = 'estado-enviado'; }
                else { $estado_texto = '✅ Pagado / Entregado'; $estado_clase = 'estado-entregado'; }
                
                $productos = decodificarProductos($pedido['Nombre']);

                $dev_stmt = $conn->prepare("SELECT estado, comentario_admin FROM devoluciones WHERE pedido_id = ?");
                $dev_stmt->bind_param("i", $pedido['_Pedido']);
                $dev_stmt->execute();
                $dev_actual = $dev_stmt->get_result()->fetch_assoc();
                $dev_stmt->close();
            ?>
            <div class="pedido-card">
                <div class="pedido-header">
                    <div>
                        <span class="pedido-numero">Pedido #<?php echo $pedido['_Pedido']; ?></span>
                        <div class="pedido-fecha"><?php echo date('d/m/Y H:i', strtotime($pedido['Fecha_Pedido'])); ?></div>
                    </div>
                    <div class="estado-badge <?php echo $estado_clase; ?>"><?php echo $estado_texto; ?></div>
                </div>
                
                <div class="pedido-body">
                    <div class="pedido-info">
                        <div class="info-item"><div class="info-label">📍 Dirección</div><div class="info-value"><?php echo htmlspecialchars($pedido['Direccion']); ?></div></div>
                        <div class="info-item"><div class="info-label">🏙️ Ciudad / CP</div><div class="info-value"><?php echo htmlspecialchars($pedido['Ciudad']); ?> / <?php echo $pedido['Codigo_Postal']; ?></div></div>
                        <div class="info-item"><div class="info-label">💳 Pago</div><div class="info-value">Tarjeta <?php echo htmlspecialchars($pedido['Comprobante'] ?? '—'); ?></div></div>
                        <div class="info-item"><div class="info-label">💰 Total</div><div class="info-value" style="color:#22c55e;">$<?php echo number_format($pedido['Total'], 2); ?> MXN</div></div>
                    </div>
                    
                    <div class="info-item" style="margin-bottom: 1rem;">
                        <div class="info-label">🛍️ Productos (<?php echo count($productos); ?>)</div>
                        <?php if(count($productos) > 0): ?>
                            <button class="btn-ver-productos" onclick='verProductos(<?php echo json_encode($productos); ?>)'>📦 Ver productos</button>
                        <?php endif; ?>
                    </div>

                    <?php if($estado == 2): ?>
                        <div class="info-item">
                            <?php if($dev_actual): ?>
                                <div class="info-label">↩️ Devolución</div>
                                <div class="dev-info">
                                    <?php
                                    $bd = ['pendiente'=>['⏳ Pendiente','#facc15'],'en_revision'=>['🔎 En revisión','#38bdf8'],'aprobada'=>['✅ Aprobada','#4ade80'],'rechazada'=>['❌ Rechazada','#f87171']];
                                    $b = $bd[$dev_actual['estado']] ?? $bd['pendiente'];
                                    ?>
                                    <span style="color:<?php echo $b[1]; ?>; font-weight:bold;"><?php echo $b[0]; ?></span>
                                    <?php if($dev_actual['comentario_admin']): ?><div style="margin-top:0.4rem; color:#cbd5e1;">💬 <?php echo htmlspecialchars($dev_actual['comentario_admin']); ?></div><?php endif; ?>
                                </div>
                                <a href="solicitar_devolucion.php?pedido=<?php echo $pedido['_Pedido']; ?>" style="color:#00b4d8; font-size:0.8rem; text-decoration:none; display:inline-block; margin-top:0.5rem;">Ver detalle →</a>
                            <?php else: ?>
                                <div class="info-label">↩️ Devoluciones</div>
                                <a href="solicitar_devolucion.php?pedido=<?php echo $pedido['_Pedido']; ?>" class="btn-devolucion" style="margin-top:0.4rem;">↩️ Solicitar devolución</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<div id="modalProductos" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="cerrarModal()">&times;</span>
        <h3 style="color: #00b4d8;">📦 Productos del Pedido</h3>
        <div id="listaProductos"></div>
    </div>
</div>

<footer>
    <p>The Box Is Yours - By Team Toro Zambrano | Boxeo con actitud</p>
    <p>CDMX | Guantes, botas y más | Envíos a todo México</p>
</footer>

<script>
    function verProductos(productos) {
        let html = '', total = 0;
        if(!productos || productos.length === 0) {
            html = '<div style="text-align:center; padding:2rem; color:#64748b;">No hay productos registrados</div>';
        } else {
            productos.forEach(function(p) {
                let precio = parseFloat(p.precio) || 0;
                let cantidad = parseInt(p.cantidad) || 1;
                let subtotal = precio * cantidad;
                total += subtotal;
                html += `<div class="producto-item"><div><strong>${p.nombre}</strong><div style="font-size:0.8rem; color:#64748b;">$${precio.toFixed(2)} c/u</div></div><div style="text-align:right;"><div>x${cantidad}</div><div style="color:#22c55e; font-weight:bold;">$${subtotal.toFixed(2)}</div></div></div>`;
            });
            html += `<div style="margin-top:1rem; padding:1rem; background:#020617; border-radius:8px; text-align:right;"><strong style="color:#00b4d8;">TOTAL: $${total.toFixed(2)} MXN</strong></div>`;
        }
        document.getElementById('listaProductos').innerHTML = html;
        document.getElementById('modalProductos').style.display = 'block';
    }
    function cerrarModal() { document.getElementById('modalProductos').style.display = 'none'; }
    window.onclick = function(e) { let modal = document.getElementById('modalProductos'); if(e.target == modal) modal.style.display = 'none'; }
</script>
</body>
</html>