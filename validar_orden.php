<?php
require_once 'config.php';

$ref = trim($_GET['ref'] ?? '');
$id  = (int)($_GET['id'] ?? 0);

if($ref === '' && $id === 0) {
    http_response_code(400);
    $error = 'No se recibió referencia ni ID de pedido.';
    $pedido = null;
} else {
    if($ref !== '') {
        $stmt = $conn->prepare("SELECT p.*, u.Usu AS cliente_nombre FROM pedidos p LEFT JOIN usuarios u ON u._Usu = p._Usu WHERE p.Comprobante = ?");
        $stmt->bind_param("s", $ref);
    } else {
        $stmt = $conn->prepare("SELECT p.*, u.Usu AS cliente_nombre FROM pedidos p LEFT JOIN usuarios u ON u._Usu = p._Usu WHERE p._Pedido = ?");
        $stmt->bind_param("i", $id);
    }
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $error = $pedido ? null : 'No encontramos ningún pedido con esa referencia.';
}

function estadoInfo($e) {
    $map = [
        0 => ['⏳ Pendiente',    '#facc15'],
        1 => ['📎 En revisión',  '#38bdf8'],
        2 => ['✅ Cotejado',     '#4ade80'],
    ];
    return $map[$e] ?? ['Desconocido', '#94a3b8'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Validar Orden - The Box Is Yours</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { background:#020617; font-family:'Segoe UI',sans-serif; color:white; padding:2rem 1rem; display:flex; justify-content:center; }
    .validador { max-width:600px; width:100%; background:#0f172a; border:1px solid #1e293b; border-radius:16px; padding:2rem; }
    .validador h1 { color:#00b4d8; text-align:center; margin-bottom:1rem; }
    .exito-banner { background:rgba(34,197,94,0.15); border:2px solid #22c55e; color:#4ade80; padding:1.2rem; border-radius:12px; text-align:center; margin-bottom:1.5rem; font-weight:bold; font-size:1.1rem; }
    .error-banner { background:rgba(239,68,68,0.15); border:2px solid #ef4444; color:#f87171; padding:1.2rem; border-radius:12px; text-align:center; margin-bottom:1.5rem; font-weight:bold; }
    .fila { display:flex; justify-content:space-between; padding:0.6rem 0; border-bottom:1px solid #1e293b; gap:1rem; }
    .fila strong { color:#cbd5e1; }
    .fila span { color:white; text-align:right; }
    .total { font-size:1.3rem; font-weight:bold; color:#22c55e; text-align:right; margin-top:1rem; }
    .aviso-fiscal { background:rgba(234,179,8,0.15); border-left:4px solid #eab308; padding:1rem; border-radius:8px; color:#facc15; margin-top:1.5rem; font-size:0.85rem; line-height:1.5; }
    .aviso-fiscal strong { display:block; margin-bottom:0.3rem; }
    .footer { text-align:center; color:#64748b; font-size:0.75rem; margin-top:2rem; }
    .btn { display:inline-block; background:#00b4d8; color:#03045e; padding:0.7rem 1.5rem; border-radius:50px; text-decoration:none; font-weight:bold; margin-top:1rem; }
</style>
</head>
<body>
<div class="validador">
    <h1>🔎 Validación de Orden</h1>

    <?php if($error): ?>
        <div class="error-banner">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php else: ?>
        <div class="exito-banner">✅ Orden válida y registrada en nuestro sistema</div>
        <?php list($estTxt, $estColor) = estadoInfo((int)$pedido['Estado']); ?>

        <div class="fila"><strong>Ticket</strong><span>#<?php echo str_pad($pedido['_Pedido'], 6, '0', STR_PAD_LEFT); ?></span></div>
        <div class="fila"><strong>Referencia</strong><span><?php echo htmlspecialchars($pedido['Comprobante'] ?? '—'); ?></span></div>
        <div class="fila"><strong>Fecha</strong><span><?php echo date('d/m/Y H:i', strtotime($pedido['Fecha_Pedido'])); ?></span></div>
        <div class="fila"><strong>Cliente</strong><span><?php echo htmlspecialchars($pedido['cliente_nombre'] ?? 'Invitado'); ?></span></div>
        <div class="fila"><strong>Estado</strong><span style="color:<?php echo $estColor; ?>;"><?php echo $estTxt; ?></span></div>
        <div class="fila"><strong>Método de pago</strong><span><?php echo htmlspecialchars(ucfirst($pedido['Metodo_Pago'])); ?></span></div>

        <?php
        $productos = json_decode($pedido['Nombre'] ?? '[]', true);
        if(!is_array($productos)) $productos = [];
        ?>
        <div style="margin-top:1rem; background:#020617; border-radius:8px; padding:0.9rem;">
            <div style="color:#00b4d8; font-weight:bold; margin-bottom:0.5rem;">🛍️ Productos</div>
            <?php foreach($productos as $prod):
                $nombre = is_array($prod) ? ($prod['nombre'] ?? 'Producto') : 'Producto';
                $cant   = is_array($prod) ? (int)($prod['cantidad'] ?? 1) : 1;
                $precio = is_array($prod) ? (float)($prod['precio'] ?? 0) : 0;
            ?>
                <div class="fila" style="font-size:0.85rem;">
                    <strong style="color:#cbd5e1;"><?php echo htmlspecialchars($nombre); ?> × <?php echo $cant; ?></strong>
                    <span>$<?php echo number_format($precio * $cant, 2); ?></span>
                </div>
            <?php endforeach; ?>
            <div class="total">TOTAL: $<?php echo number_format($pedido['Total'], 2); ?> MXN</div>
        </div>

        <div class="aviso-fiscal">
            <strong>📄 Documento de validación — SIN VALIDEZ FISCAL</strong>
            Este comprobante es únicamente informativo y de control interno de <strong>The Box Is Yours</strong>.
            No constituye un Comprobante Fiscal Digital por Internet (CFDI) ni puede usarse para fines contables, tributarios o de deducción ante el SAT.
        </div>
    <?php endif; ?>

    <div style="text-align:center;"><a href="index.php" class="btn">← Ir a la tienda</a></div>
    <div class="footer">The Box Is Yours · By Team Toro Zambrano · CDMX</div>
</div>
</body>
</html>