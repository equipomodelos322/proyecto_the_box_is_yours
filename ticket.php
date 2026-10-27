<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['ultimo_pedido'])) {
    header('Location: index.php');
    exit;
}

$p = $_SESSION['ultimo_pedido'];

// URL pública para validar la orden
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host  = $_SERVER['HTTP_HOST'];
$path  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$url_val = $proto . '://' . $host . $path . '/validar_orden.php?ref=' . urlencode($p['referencia']);
$qr_src  = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($url_val);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ticket #<?php echo $p['id']; ?> - The Box Is Yours</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { background:#020617; font-family:'Courier New',monospace; color:#e2e8f0; padding:2rem 1rem; }
    .ticket { max-width:600px; margin:0 auto; background:#fff; color:#000; padding:2rem; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.5); }
    .ticket h1 { text-align:center; font-size:1.4rem; margin-bottom:0.3rem; color:#0414D4; letter-spacing:2px; }
    .ticket .sub { text-align:center; font-size:0.8rem; color:#555; margin-bottom:1.5rem; }
    .exito { background:#22c55e; color:white; text-align:center; padding:1rem; border-radius:8px; margin-bottom:1.5rem; font-weight:bold; font-size:1.1rem; }
    .linea { border-top:1px dashed #999; margin:1rem 0; }
    .linea-doble { border-top:2px solid #000; margin:1rem 0; }
    .fila { display:flex; justify-content:space-between; padding:0.3rem 0; font-size:0.9rem; gap:1rem; }
    .fila strong { white-space:nowrap; }
    .fila span:last-child { text-align:right; }
    .total { font-size:1.3rem; font-weight:bold; border-top:2px solid #000; padding-top:0.7rem; margin-top:0.5rem; color:#000; }
    .footer-ticket { text-align:center; font-size:0.75rem; color:#666; margin-top:1.5rem; line-height:1.6; }
    .qr-box { text-align:center; margin:1.2rem 0; }
    .qr-box img { display:inline-block; border:1px solid #ccc; border-radius:6px; padding:4px; background:#fff; }
    .qr-caption { font-size:0.7rem; color:#555; margin-top:0.4rem; }
    .aviso-fiscal { background:#fffbe6; border:1px solid #facc15; border-radius:6px; padding:0.8rem; font-size:0.72rem; color:#7c5400; line-height:1.5; margin:1rem 0; }
    .aviso-fiscal strong { display:block; margin-bottom:0.3rem; color:#7c3a00; }
    .actions { text-align:center; margin-top:2rem; display:flex; flex-wrap:wrap; gap:0.5rem; justify-content:center; }
    .btn { display:inline-block; padding:0.8rem 1.5rem; background:#0414D4; color:white; text-decoration:none; border-radius:50px; font-weight:bold; border:none; cursor:pointer; font-size:0.9rem; }
    .btn-print { background:#22c55e; }
    .btn-gris  { background:#1e293b; }
    @media print {
        body { background:white; padding:0; }
        .actions, .exito { display:none !important; }
        .ticket { box-shadow:none; max-width:100%; padding:1rem; }
        .footer-ticket, .aviso-fiscal, .qr-box { page-break-inside:avoid; }
    }
    @media (max-width:600px) {
        body { padding:1rem 0.5rem; }
        .ticket { padding:1.2rem; }
        .fila { font-size:0.8rem; }
    }
</style>
</head>
<body>
<div class="ticket">
    <div class="exito">✅ ¡PAGO APROBADO!</div>

    <h1>🥊 THE BOX IS YOURS</h1>
    <div class="sub">By Team Toro Zambrano<br>CDMX | Envíos a todo México</div>

    <div class="linea"></div>
    <div class="fila">
        <strong>TICKET #<?php echo str_pad($p['id'], 6, '0', STR_PAD_LEFT); ?></strong>
        <span><?php echo date('d/m/Y H:i', strtotime($p['fecha'])); ?></span>
    </div>
    <div class="fila"><span>Referencia:</span><span><?php echo htmlspecialchars($p['referencia']); ?></span></div>
    <div class="linea"></div>

    <div class="fila"><strong>Cliente:</strong><span><?php echo htmlspecialchars($p['nombre']); ?></span></div>
    <div class="fila"><strong>Email:</strong><span><?php echo htmlspecialchars($p['email']); ?></span></div>
    <div class="fila"><strong>Teléfono:</strong><span><?php echo htmlspecialchars($p['telefono']); ?></span></div>
    <div class="linea"></div>

    <div class="fila"><strong>Envío:</strong></div>
    <div style="font-size:0.85rem; color:#333; padding-left:1rem; line-height:1.5;">
        <?php echo htmlspecialchars($p['direccion']); ?><br>
        <?php echo htmlspecialchars($p['ciudad']); ?>, CP <?php echo htmlspecialchars($p['codigo_postal']); ?>
    </div>
    <div class="linea"></div>

    <div class="fila"><strong>PRODUCTOS</strong></div>
    <?php foreach($p['productos'] as $item): ?>
    <div class="fila">
        <span><?php echo htmlspecialchars($item['nombre']); ?> x<?php echo $item['cantidad']; ?></span>
        <span>$<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></span>
    </div>
    <?php endforeach; ?>
    <div class="linea-doble"></div>

    <div class="fila total"><span>TOTAL</span><span>$<?php echo number_format($p['total'], 2); ?> MXN</span></div>

    <div class="linea"></div>
    <div class="fila">
        <strong>Método de pago:</strong>
        <span>💳 Tarjeta ****<?php echo htmlspecialchars($p['tarjeta_ultimos4']); ?></span>
    </div>
    <div class="fila">
        <strong>Estado:</strong>
        <span style="color:#22c55e; font-weight:bold;">✅ PAGADO</span>
    </div>

    <div class="qr-box">
        <img src="<?php echo htmlspecialchars($qr_src); ?>" alt="QR de validación" width="150" height="150">
        <div class="qr-caption">Escanea para validar la orden</div>
    </div>

    <div class="aviso-fiscal">
        <strong>⚠️ SIN VALIDEZ FISCAL</strong>
        Este comprobante es únicamente informativo y de control interno.
        No constituye un Comprobante Fiscal Digital por Internet (CFDI) ni puede
        usarse para fines contables, tributarios o de deducción ante el SAT.
    </div>

    <div class="footer-ticket">
        ━━━━━━━━━━━━━━━━━━━━━━━━<br>
        ¡Gracias por tu compra! 🥊<br>
        Conserva este ticket para cualquier aclaración.<br>
        ━━━━━━━━━━━━━━━━━━━━━━━━
    </div>
</div>

<div class="actions">
    <button onclick="window.print()" class="btn btn-print">🖨️ Imprimir / Guardar PDF</button>
    <a href="mis_pedidos.php" class="btn">📋 Mis Pedidos</a>
    <a href="index.php" class="btn btn-gris">← Seguir comprando</a>
</div>

<?php unset($_SESSION['ultimo_pedido']); ?>
</body>
</html>