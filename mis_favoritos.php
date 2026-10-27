<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$uid = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? '';
$is_admin  = (($_SESSION['user_rol'] ?? '') === 'admin');

$sql = "SELECT pb.id, pb.nombre_base, pb.imagen_principal, pb.Categoria,
               MIN(pv.precio) AS precio_min, MAX(pv.precio) AS precio_max,
               SUM(pv.stock)  AS stock_total, f.fecha_agregado
        FROM favoritos f
        INNER JOIN productos_base pb ON pb.id = f.producto_base_id
        LEFT JOIN productos_variantes pv ON pv.producto_base_id = pb.id
        WHERE f.usuario_id = ?
        GROUP BY pb.id
        ORDER BY f.fecha_agregado DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $uid);
$stmt->execute();
$favoritos = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis Favoritos - The Box Is Yours</title>
<link rel="stylesheet" href="styles.css">
<style>
    .nav { display:flex; justify-content:center; align-items:center; flex-wrap:wrap; gap:2rem; background:#01084a; padding:12px 5%; }
    .nav a { color:white; text-decoration:none; font-weight:600; font-size:0.9rem; text-transform:uppercase; letter-spacing:1px; padding:5px 0; }
    .nav a:hover { color:#00b4d8; }
    .user-menu { display:flex; align-items:center; gap:1rem; margin-left:1rem; }
    .user-name { color:#00b4d8; font-weight:bold; font-size:0.9rem; }
    .logout-link, .admin-link { color:white; text-decoration:none; padding:5px 12px; border-radius:8px; font-size:0.85rem; }
    .admin-link { background:#eab308; color:#03045e; font-weight:bold; }
    .fav-section { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
    .fav-section h1 { color:#00b4d8; margin-bottom:1.5rem; }
    .grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px,1fr)); gap:1.2rem; }
    .card { background:#0f172a; border:1px solid #1e293b; border-radius:12px; overflow:hidden; display:flex; flex-direction:column; }
    .card img { width:100%; height:180px; object-fit:cover; background:#1e293b; }
    .card .sin-img { width:100%; height:180px; background:#1e293b; display:flex; align-items:center; justify-content:center; font-size:3rem; }
    .card .info { padding:1rem; display:flex; flex-direction:column; gap:0.4rem; flex:1; }
    .card .titulo { color:white; font-weight:bold; }
    .card .cat { color:#00b4d8; font-size:0.8rem; }
    .card .precio { color:#22c55e; font-weight:bold; }
    .card .acciones { display:flex; gap:0.5rem; margin-top:auto; padding-top:0.6rem; }
    .card .acciones a, .card .acciones button { flex:1; padding:0.6rem; border:none; border-radius:8px; cursor:pointer; font-weight:bold; font-size:0.8rem; text-decoration:none; text-align:center; }
    .btn-comprar { background:#0414D4; color:white; }
    .btn-comprar:hover { background:#020a6b; }
    .btn-quitar { background:#ef4444; color:white; }
    .btn-quitar:hover { background:#b91c1c; }
    .vacio { text-align:center; padding:4rem; background:#0f172a; border-radius:12px; color:#64748b; }
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
    <a href="faq.php">❓ FAQ</a>
    <a href="mis_favoritos.php" style="color:#00b4d8;">❤️ FAVORITOS</a>
    <?php if($is_admin): ?>
        <a href="admin.php" class="admin-link">👑 ADMIN</a>
    <?php endif; ?>
    <div class="user-menu">
        <span class="user-name">👋 <?php echo htmlspecialchars($user_name); ?></span>
        <a href="logout.php" class="logout-link">🚪 Salir</a>
    </div>
</nav>

<div class="fav-section">
    <h1>❤️ Mis Favoritos</h1>

    <?php if($favoritos->num_rows === 0): ?>
        <div class="vacio">
            <p>Todavía no tienes favoritos.</p>
            <a href="index.php#productos" style="color:#00b4d8;">← Explorar productos</a>
        </div>
    <?php else: ?>
        <div class="grid">
        <?php while($p = $favoritos->fetch_assoc()):
            $img = $p['imagen_principal'] ?: '';
            if($p['precio_min'] && $p['precio_max'] && $p['precio_min'] != $p['precio_max']) {
                $rango = '$'.number_format($p['precio_min'],2).' - $'.number_format($p['precio_max'],2);
            } elseif($p['precio_min']) {
                $rango = '$'.number_format($p['precio_min'],2);
            } else {
                $rango = 'Consultar';
            }
        ?>
            <div class="card" id="fav-card-<?php echo $p['id']; ?>">
                <?php if($img): ?>
                    <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($p['nombre_base']); ?>"
                         onerror="this.outerHTML='<div class=\'sin-img\'>🥊</div>'">
                <?php else: ?>
                    <div class="sin-img">🥊</div>
                <?php endif; ?>
                <div class="info">
                    <div class="titulo"><?php echo htmlspecialchars($p['nombre_base']); ?></div>
                    <div class="cat"><?php echo htmlspecialchars($p['Categoria'] ?? 'Sin categoría'); ?></div>
                    <div class="precio"><?php echo $rango; ?> <small style="color:#64748b;">MXN</small></div>
                    <div class="acciones">
                        <a class="btn-comprar" href="index.php?producto=<?php echo $p['id']; ?>#productos">🛒 Ver variantes</a>
                        <button class="btn-quitar" onclick="quitarFavorito(<?php echo $p['id']; ?>)">💔 Quitar</button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<footer>
    <p>The Box Is Yours - By Team Toro Zambrano | Boxeo con actitud</p>
    <p>CDMX | Guantes, botas y más | Envíos a todo México</p>
</footer>

<script>
async function quitarFavorito(productoId) {
    const fd = new FormData();
    fd.append('producto_id', productoId);
    const res = await fetch('favoritos_toggle.php', { method:'POST', body: fd });
    const data = await res.json();
    if(data.success && data.es_favorito === false) {
        const card = document.getElementById('fav-card-' + productoId);
        if(card) card.remove();
    }
}
</script>
</body>
</html>