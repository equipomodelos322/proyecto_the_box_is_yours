<?php
session_start();
require_once 'config.php';

// Forzar que la conexion a la base de datos use UTF-8
$conn->set_charset("utf8mb4");

$user_name = $_SESSION['user_name'] ?? '';
$user_rol  = $_SESSION['user_rol']  ?? '';
$is_admin  = ($user_rol === 'admin');
$is_logged = isset($_SESSION['user_id']);

$por_categoria = ['tallas'=>[], 'onzas'=>[], 'envios'=>[], 'devoluciones'=>[]];
$res = $conn->query("SELECT * FROM faq ORDER BY categoria, orden, id");
if($res) {
    while($row = $res->fetch_assoc()) {
        if(isset($por_categoria[$row['categoria']])) {
            $por_categoria[$row['categoria']][] = $row;
        }
    }
}

$titulos = [
    'tallas'       => 'Tallas',
    'onzas'        => 'Onzas',
    'envios'       => 'Envios',
    'devoluciones' => 'Devoluciones',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Preguntas Frecuentes - The Box Is Yours</title>
<link rel="stylesheet" href="styles.css">
<style>
    .nav { display:flex; justify-content:center; align-items:center; flex-wrap:wrap; gap:2rem; background:#01084a; padding:12px 5%; }
    .nav a { color:white; text-decoration:none; font-weight:600; font-size:0.9rem; text-transform:uppercase; letter-spacing:1px; padding:5px 0; }
    .nav a:hover { color:#00b4d8; }
    .user-menu { display:flex; align-items:center; gap:1rem; margin-left:1rem; }
    .user-name { color:#00b4d8; font-weight:bold; font-size:0.9rem; }
    .logout-link, .login-link, .admin-link { color:white; text-decoration:none; padding:5px 12px; border-radius:8px; font-size:0.85rem; }
    .admin-link { background:#eab308; color:#03045e; font-weight:bold; }
    .login-link { background:#00b4d8; color:#03045e; font-weight:bold; }
    
    .faq-section { max-width: 900px; margin: 2rem auto; padding: 0 2rem; }
    .faq-section h1 { color:#00b4d8; margin-bottom:0.5rem; }
    .faq-section > p { color:#94a3b8; margin-bottom:2rem; }
    .faq-categoria { margin-bottom:2.5rem; }
    .faq-categoria h2 { color:#00b4d8; border-bottom:1px solid #1e293b; padding-bottom:0.5rem; margin-bottom:1rem; }
    .faq-item { background:#0f172a; border:1px solid #1e293b; border-radius:10px; margin-bottom:0.8rem; overflow:hidden; }
    .faq-item summary { cursor:pointer; padding:1rem 1.2rem; font-weight:bold; color:white; list-style:none; display:flex; justify-content:space-between; align-items:center; gap:1rem; }
    .faq-item summary::-webkit-details-marker { display:none; }
    .faq-item summary:hover { background:#112240; }
    .faq-item summary::after { content:"+"; color:#00b4d8; font-size:1.2rem; }
    .faq-item[open] summary::after { content:"-"; }
    .faq-item .respuesta { padding: 0 1.2rem 1rem 1.2rem; color:#cbd5e1; line-height:1.6; }
    .pendiente { background: rgba(234,179,8,0.12); border:1px solid #eab308; color:#facc15; padding:0.6rem 0.9rem; border-radius:8px; font-size:0.85rem; margin-top:0.6rem; }
    .aviso-legal { background: rgba(239,68,68,0.1); border-left:3px solid #ef4444; padding:1rem; border-radius:8px; color:#fca5a5; font-size:0.85rem; margin-top:2rem; }
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
    <a href="cart.php">CARRITO</a>
    <a href="faq.php" style="color:#00b4d8;">FAQ</a>
    <?php if($is_logged): ?>
        <a href="mis_favoritos.php">FAVORITOS</a>
        <?php if($is_admin): ?>
            <a href="admin.php" class="admin-link">ADMIN</a>
        <?php endif; ?>
        <div class="user-menu">
            <span class="user-name">Hola, <?php echo htmlspecialchars($user_name); ?></span>
            <a href="logout.php" class="logout-link">SALIR</a>
        </div>
    <?php else: ?>
        <a href="login.php" class="login-link">INICIAR SESION</a>
    <?php endif; ?>
</nav>

<div class="faq-section">
    <h1>Preguntas Frecuentes</h1>
    <p>Resuelve tus dudas sobre tallas, onzas, envios y devoluciones. Las politicas marcadas en amarillo aun estan por definir.</p>

    <?php foreach($titulos as $cat => $titulo): if(empty($por_categoria[$cat])) continue; ?>
        <div class="faq-categoria">
            <h2><?php echo $titulo; ?></h2>
            <?php foreach($por_categoria[$cat] as $item): ?>
                <details class="faq-item">
                    <summary><?php echo htmlspecialchars($item['pregunta']); ?></summary>
                    <div class="respuesta">
                        <?php echo nl2br(htmlspecialchars($item['respuesta'] ?? 'Sin respuesta registrada.')); ?>
                        <?php if((int)$item['definida'] === 0): ?>
                            <div class="pendiente"><strong>Politica por definir.</strong> Esta informacion aun no esta formalizada por la tienda. Consulta por WhatsApp para mas detalles.</div>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <div class="aviso-legal">
        Las respuestas aqui mostradas no constituyen un contrato vinculante. Para casos particulares contacta directamente a la tienda por WhatsApp o Instagram.
    </div>
</div>

<footer>
    <p>The Box Is Yours - By Team Toro Zambrano | Boxeo con actitud</p>
    <p>CDMX | Guantes, botas y mas | Envios a todo Mexico</p>
</footer>

</body>
</html>