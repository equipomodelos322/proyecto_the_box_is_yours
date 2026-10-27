<?php
session_start();
require_once 'config.php';

$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$user_rol  = isset($_SESSION['user_rol'])  ? $_SESSION['user_rol']  : '';
$is_admin  = ($user_rol == 'admin');
$is_logged = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
<title>The Box Is Yours | Tienda de Boxeo CDMX</title>
<link rel="stylesheet" href="styles.css">
<link href="uploads/favicon.png" rel="icon" />
<style>
/* ========== NAVBAR ========== */
.nav { display:flex; justify-content:center; align-items:center; flex-wrap:wrap; gap:2rem; background:#01084a; padding:12px 5%; }
.nav a { color:white; text-decoration:none; font-weight:600; font-size:0.9rem; text-transform:uppercase; letter-spacing:1px; transition:all 0.3s; padding:5px 0; }
.nav a:hover { color:#00b4d8; }
.user-menu { display:flex; align-items:center; gap:1rem; margin-left:1rem; }
.user-name { color:#00b4d8; font-weight:bold; font-size:0.9rem; }
.logout-link, .login-link, .admin-link { color:white; text-decoration:none; padding:5px 12px; border-radius:8px; transition:0.3s; font-size:0.85rem; }
.admin-link { background:#eab308; color:#03045e; font-weight:bold; }
.admin-link:hover { background:#ca8a04; color:#03045e; }
.logout-link:hover, .login-link:hover { background:rgba(255,255,255,0.15); }
.login-link { background:#00b4d8; color:#03045e; font-weight:bold; }
.login-link:hover { background:#0096c7; }

/* ========== PRODUCTOS ========== */
.producto-stock { margin-top:0.5rem; padding:0.3rem 0.6rem; border-radius:20px; font-size:0.7rem; font-weight:bold; text-align:center; display:inline-block; }
.stock-disponible { background:#22c55e20; color:#22c55e; border:1px solid #22c55e; }
.stock-poco       { background:#eab30820; color:#eab308; border:1px solid #eab308; }
.stock-agotado    { background:#ef444420; color:#ef4444; border:1px solid #ef4444; }

.btn-ver-detalles { margin-top:0.8rem; padding:0.5rem 1rem; background:#0414D4; color:white; border:none; border-radius:25px; cursor:pointer; font-size:0.75rem; font-weight:bold; width:100%; transition:all 0.3s; }
.btn-ver-detalles:hover { background:#020a6b; transform:translateY(-2px); }

.btn-fav { background:#1e293b; color:#fff; border:1px solid #334155; width:40px; height:38px; border-radius:25px; cursor:pointer; font-size:1rem; transition:0.2s; margin-top:0.8rem; }
.btn-fav:hover { border-color:#ef4444; transform:scale(1.06); }
.btn-fav.activo { background:#ef4444; border-color:#ef4444; }

.btn-fav-modal { display:inline-block; margin:0.5rem 0 0.8rem 0; padding:0.4rem 0.9rem; background:#1e293b; color:#fff; border:1px solid #334155; border-radius:20px; cursor:pointer; font-size:0.8rem; }
.btn-fav-modal.activo { background:#ef4444; border-color:#ef4444; }

/* ========== NOTIFICACIONES ========== */
.notification { position:fixed; bottom:90px; right:20px; background:#22c55e; color:white; padding:12px 20px; border-radius:8px; z-index:9999; animation:slideIn 0.3s ease; box-shadow:0 5px 20px rgba(0,0,0,0.3); max-width:300px; font-size:0.9rem; }
@keyframes slideIn { from { transform:translateX(100%); opacity:0; } to { transform:translateX(0); opacity:1; } }

/* ========== CARRITO FLOTANTE ========== */
.cart-icon { position:fixed; bottom:20px; right:20px; background:#0414D4; color:white; width:55px; height:55px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.5rem; cursor:pointer; z-index:999; box-shadow:0 4px 15px rgba(4,20,212,0.5); transition:0.3s; }
.cart-icon:hover { transform:scale(1.1); }
.cart-icon span { position:absolute; top:-5px; right:-5px; background:#ef4444; color:white; border-radius:50%; width:22px; height:22px; font-size:0.75rem; display:flex; align-items:center; justify-content:center; font-weight:bold; }

/* ========== MODAL ========== */
.modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.9); overflow-y:auto; }
.modal-content { background:#0f172a; margin:3% auto; padding:2rem; border-radius:12px; width:92%; max-width:850px; position:relative; }
.close-modal { position:absolute; top:10px; right:20px; font-size:2rem; cursor:pointer; color:#cbd5e1; transition:0.3s; }
.close-modal:hover { color:#ef4444; transform:rotate(90deg); }
.modal-producto { display:grid; grid-template-columns:1fr 1fr; gap:2rem; }
.modal-imagen img { width:100%; border-radius:12px; }
.modal-info h2 { color:white; margin-bottom:0.5rem; }
.modal-categoria { color:#00b4d8; margin-bottom:0.5rem; }
.modal-precio { font-size:1.8rem; font-weight:bold; color:#22c55e; margin:0.5rem 0; }
.modal-descripcion { color:#cbd5e1; margin:1rem 0; line-height:1.5; }
.modal-buttons { display:flex; gap:1rem; margin-top:1rem; flex-wrap:wrap; }
.btn-comprar, .btn-whatsapp { padding:0.8rem 1.5rem; border-radius:50px; font-weight:bold; cursor:pointer; border:none; flex:1; font-size:0.9rem; transition:0.3s; }
.btn-comprar { background:#0414D4; color:white; }
.btn-comprar:hover:not(:disabled) { background:#020a6b; transform:translateY(-2px); }
.btn-whatsapp { background:#25D366; color:white; }
.btn-whatsapp:hover { background:#1ebc57; }

.cantidad-group { margin:1rem 0; }
.cantidad-group label { display:block; margin-bottom:0.5rem; color:#00b4d8; font-weight:bold; }
.cantidad-group input { background:#0a192f; color:white; border:1px solid #1e293b; padding:0.8rem; border-radius:8px; width:100px; font-size:1rem; }
.cantidad-group input:disabled { opacity:0.5; cursor:not-allowed; }

.variantes-group { margin-bottom:1rem; }
.variantes-group label { display:block; margin-bottom:0.5rem; color:#64ffda; font-weight:bold; }

/* ========== SELECTORES DE COLOR ========== */
.color-selector { display:flex; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem; }
.color-option { display:flex; flex-direction:column; align-items:center; cursor:pointer; transition:all 0.2s ease; }
.color-circle { width:50px; height:50px; border-radius:50%; border:2px solid #334155; transition:all 0.2s ease; box-shadow:0 2px 5px rgba(0,0,0,0.2); }
.color-option.selected .color-circle { border:3px solid #00b4d8; box-shadow:0 0 0 2px rgba(0,180,216,0.3); transform:scale(1.05); }
.color-name { font-size:0.7rem; color:#cbd5e1; margin-top:0.3rem; text-align:center; }
.color-option.selected .color-name { color:#00b4d8; font-weight:bold; }

.variantes-group select { width:100%; padding:0.75rem 1rem; border-radius:12px; border:1px solid #334155; background:#1e293b; color:#e2e8f0; font-size:0.9rem; cursor:pointer; transition:all 0.2s ease; appearance:none; -webkit-appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 1rem center; background-size:1rem; }
.variantes-group select:hover { border-color:#00b4d8; background-color:#334155; }
.variantes-group select:focus { outline:none; border-color:#00b4d8; box-shadow:0 0 0 2px rgba(0,180,216,0.25); }
.variantes-group select option { background:#0f172a; color:#cbd5e1; padding:0.75rem; }

.stock-info { padding:0.8rem; border-radius:8px; margin-bottom:1rem; font-weight:bold; font-size:0.9rem; }

/* ========== REVIEWS / LIKES (RF-07) ========== */
.reviews-section { margin-top:2rem; border-top:1px solid #1e293b; padding-top:1.5rem; }
.reviews-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1rem; }
.reviews-titulo { color:#00b4d8; font-size:1.1rem; font-weight:bold; }
.reviews-puntaje { display:flex; align-items:center; gap:0.6rem; color:#facc15; font-size:1rem; }
.reviews-puntaje .prom { color:white; font-size:1.4rem; font-weight:bold; }
.reviews-puntaje .total { color:#94a3b8; font-size:0.8rem; }
.btn-like { background:#1e293b; color:#cbd5e1; border:1px solid #334155; padding:0.4rem 0.9rem; border-radius:20px; cursor:pointer; font-size:0.85rem; font-weight:bold; transition:0.2s; }
.btn-like:hover { border-color:#ef4444; }
.btn-like.activo { background:rgba(239,68,68,0.15); border-color:#ef4444; color:#f87171; }
.comentario-item { background:#020617; border-radius:8px; padding:0.9rem; margin-bottom:0.7rem; }
.comentario-item .autor { color:#00b4d8; font-weight:bold; font-size:0.9rem; }
.comentario-item .fecha { color:#64748b; font-size:0.75rem; margin-left:0.5rem; }
.comentario-item .stars { color:#facc15; }
.comentario-item .texto { color:#cbd5e1; margin-top:0.4rem; line-height:1.5; font-size:0.9rem; }
.review-form { background:#020617; border:1px solid #1e293b; border-radius:8px; padding:1rem; margin-top:1rem; }
.review-form textarea { width:100%; padding:0.7rem; border-radius:6px; background:#0f172a; color:white; border:1px solid #1e293b; resize:vertical; min-height:80px; font-family:inherit; font-size:0.9rem; }
.review-form textarea:focus { outline:none; border-color:#00b4d8; }
.stars-input { display:flex; gap:0.3rem; font-size:1.6rem; cursor:pointer; user-select:none; margin-bottom:0.6rem; }
.stars-input span { color:#334155; transition:0.15s; }
.stars-input span.hover, .stars-input span.selected { color:#facc15; }
.btn-enviar-review { background:#22c55e; color:white; border:none; padding:0.6rem 1.4rem; border-radius:20px; cursor:pointer; font-weight:bold; font-size:0.85rem; margin-top:0.6rem; }
.btn-enviar-review:hover { background:#16a34a; }
.aviso-login-review { background:rgba(234,179,8,0.1); border-left:3px solid #eab308; padding:0.7rem; border-radius:6px; color:#facc15; font-size:0.85rem; margin-top:1rem; }

/* ========== SECCIONES ========== */
.hero { background:linear-gradient(135deg,#020617 0%,#0f172a 100%); padding:4rem 2rem; text-align:center; }
.hero-content h2 { font-size:2.5rem; color:#00b4d8; margin-bottom:1rem; }
.tagline { font-size:1.2rem; color:#cbd5e1; margin-bottom:2rem; }
.hero-buttons { display:flex; gap:1rem; justify-content:center; flex-wrap:wrap; }
.btn { background:#00b4d8; color:#03045e; padding:0.8rem 1.5rem; border-radius:50px; text-decoration:none; font-weight:bold; transition:0.3s; display:inline-block; }
.btn-outline { background:transparent; border:2px solid #00b4d8; color:#00b4d8; }
.btn:hover { transform:translateY(-2px); box-shadow:0 5px 15px rgba(0,180,216,0.3); }

/* ========== SECCIÓN NOSOTROS (MODIFICADA PARA SER MÁS PEQUEÑA) ========== */
.section-nosotros { padding:2rem 2rem; background:#0f172a; border-top:1px solid #1e293b; }
.nosotros-container { max-width:900px; margin:0 auto; display:grid; grid-template-columns:1fr 1fr; gap:2rem; align-items:center; }
.nosotros-imagen img { width:100%; max-width:300px; border-radius:12px; display:block; margin:0 auto; }
.nosotros-info h2 { color:#00b4d8; margin-bottom:0.3rem; font-size:1.1rem; }
.nosotros-info h3 { color:white; font-size:1.3rem; margin-bottom:0.8rem; }
.frase { font-style:italic; color:#94a3b8; margin-bottom:0.8rem; border-left:3px solid #00b4d8; padding-left:1rem; font-size:0.85rem; }
.nosotros-info p { font-size:0.85rem; color:#cbd5e1; line-height:1.5; }

.section-productos { padding:4rem 2rem; background:#020617; }
.productos-header-titulo { text-align:center; margin-bottom:3rem; }
.productos-header-titulo h2 { color:#00b4d8; font-size:2rem; }
.productos-header-titulo p { color:#94a3b8; }
.productos-wrapper { display:flex; gap:2rem; max-width:1400px; margin:0 auto; }
.filtros-sidebar { width:250px; background:#0f172a; padding:1.5rem; border-radius:12px; height:fit-content; position:sticky; top:80px; }
.filtros-sidebar h3 { color:#00b4d8; margin-bottom:1rem; margin-top:1rem; }
.filtros-sidebar h3:first-child { margin-top:0; }
.filtros-sidebar ul { list-style:none; padding:0; }
.filtros-sidebar li { margin-bottom:0.5rem; }
.filtros-sidebar a { color:#cbd5e1; text-decoration:none; transition:0.3s; font-size:0.9rem; }
.filtros-sidebar a:hover { color:#00b4d8; }
.contador { color:#00b4d8; font-size:0.8rem; }

.productos-main { flex:1; min-width:0; }
.barra-busqueda { display:flex; gap:1rem; margin-bottom:1rem; }
.barra-busqueda input { flex:1; padding:0.8rem; border-radius:8px; border:1px solid #1e293b; background:#0f172a; color:white; font-size:0.95rem; }
.barra-busqueda input:focus { outline:none; border-color:#00b4d8; }
.barra-busqueda button { padding:0.8rem 1.5rem; background:#00b4d8; border:none; border-radius:8px; font-weight:bold; cursor:pointer; color:#03045e; transition:0.3s; }
.barra-busqueda button:hover { background:#0096c7; }

.productos-header { display:flex; justify-content:space-between; margin-bottom:1rem; flex-wrap:wrap; gap:1rem; align-items:center; }
.resultados { color:#94a3b8; font-size:0.9rem; }
#ordenSelect { padding:0.5rem 1rem; background:#0f172a; color:white; border:1px solid #1e293b; border-radius:8px; cursor:pointer; }

.productos-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:1.5rem; }
.producto-card { background:#0f172a; border-radius:12px; overflow:hidden; transition:0.3s; border:1px solid #1e293b; }
.producto-card:hover { transform:translateY(-5px); border-color:#00b4d8; }
.producto-imagen { width:100%; height:200px; object-fit:cover; background:#1e293b; display:flex; align-items:center; justify-content:center; font-size:3rem; }
.producto-info { padding:1rem; }
.producto-titulo { font-weight:bold; color:white; margin-bottom:0.3rem; }
.producto-categoria { font-size:0.8rem; color:#00b4d8; margin-bottom:0.5rem; }
.producto-precio { font-size:1.2rem; font-weight:bold; color:#22c55e; }
.producto-precio span { font-size:0.75rem; color:#64748b; }

.paginacion { display:flex; justify-content:center; gap:0.5rem; margin-top:2rem; flex-wrap:wrap; }
.paginacion button { padding:0.5rem 1rem; background:#0f172a; border:1px solid #1e293b; color:white; border-radius:8px; cursor:pointer; transition:0.3s; font-weight:bold; }
.paginacion button:hover { background:#1e293b; }
.paginacion button.active { background:#00b4d8; color:#03045e; border-color:#00b4d8; }

.section-contacto { padding:4rem 2rem; background:#0f172a; }
.contacto-container { max-width:1200px; margin:0 auto; display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:2rem; }
.contacto-info { background:#020617; padding:1.5rem; border-radius:12px; }
.contacto-info h2 { color:#00b4d8; margin-bottom:1rem; }
.contacto-info p { margin-bottom:0.5rem; color:#cbd5e1; }
.contacto-buttons { display:flex; gap:1rem; margin-top:1rem; flex-wrap:wrap; }

footer { background:#01084a; text-align:center; padding:2rem; color:#64748b; }
footer p { margin-bottom:0.5rem; }

@media (max-width:768px) {
    .nav { flex-direction:column; gap:1rem; }
    .productos-wrapper { flex-direction:column; }
    .filtros-sidebar { width:100%; position:static; }
    .modal-producto { grid-template-columns:1fr; }
    .user-menu { margin-left:0; }
    .color-circle { width:40px; height:40px; }
    .nosotros-container { grid-template-columns:1fr; }
    .hero-content h2 { font-size:1.8rem; }
    .modal-content { padding:1.2rem; margin:5% auto; }
}
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
    <a href="#inicio">INICIO</a>
    <a href="#productos">PRODUCTOS</a>
    <a href="#nosotros">NOSOTROS</a>
    <a href="#contacto">CONTACTO</a>
    <a href="cart.php">🛒 CARRITO</a>
    <a href="faq.php">❓ FAQ</a>

    <?php if($is_logged): ?>
        <a href="mis_favoritos.php">❤️ FAVORITOS</a>
        <?php if($is_admin): ?>
            <a href="admin_pedidos.php">PEDIDOS</a>
            <a href="admin.php" class="admin-link">👑 ADMIN</a>
        <?php else: ?>
            <a href="mis_pedidos.php">📋 MIS PEDIDOS</a>
        <?php endif; ?>
        <div class="user-menu">
            <span class="user-name">👋 <?php echo htmlspecialchars($user_name); ?></span>
            <a href="logout.php" class="logout-link">🚪 Salir</a>
        </div>
    <?php else: ?>
        <a href="login.php" class="login-link">🔐 Iniciar Sesión</a>
    <?php endif; ?>
</nav>

<section id="inicio" class="hero">
    <div class="hero-content">
        <h2>EL RING ES TUYO</h2>
        <p class="tagline">Nosotros te equipamos con lo mejor para que des todo en el ring</p>
        <div class="hero-buttons">
            <a href="#productos" class="btn">Ver productos</a>
            <a href="#contacto" class="btn btn-outline">Visitar tienda física</a>
        </div>
    </div>
</section>

<!-- PRODUCTOS PRIMERO -->
<section id="productos" class="section-productos">
    <div class="productos-header-titulo">
        <h2>NUESTROS PRODUCTOS</h2>
        <p>Equipo profesional para boxeo y artes marciales</p>
    </div>
    <div class="productos-wrapper">
        <aside class="filtros-sidebar">
            <h3>Categorías</h3>
            <ul id="categorias-lista">
            <?php
            $categorias = $conn->query("SELECT c.Categoria, COUNT(pb.Categoria) as total
                            FROM categorias c
                            LEFT JOIN productos_base pb ON c.Categoria = pb.Categoria
                            GROUP BY c.Categoria ORDER BY c.Categoria ASC");
            if($categorias && $categorias->num_rows > 0):
                while($cat = $categorias->fetch_assoc()):
            ?>
                <li><a href="#" onclick="filtrarCategoria('<?php echo addslashes($cat['Categoria']); ?>'); return false;"><?php echo strtoupper($cat['Categoria']); ?> <span class="contador">(<?php echo $cat['total']; ?>)</span></a></li>
            <?php endwhile; endif; ?>
            <li><a href="#" onclick="cargarTodos(); return false;">VER TODOS</a></li>
            </ul>
            <h3>Marca</h3>
            <ul>
                <li><a href="#" onclick="filtrarMarca('Venum'); return false;">Venum</a></li>
                <li><a href="#" onclick="filtrarMarca('Everlast'); return false;">Everlast</a></li>
                <li><a href="#" onclick="filtrarMarca('RIVAL'); return false;">RIVAL</a></li>
                <li><a href="#" onclick="filtrarMarca('Cleto Reyes'); return false;">Cleto Reyes</a></li>
                <li><a href="#" onclick="filtrarMarca('Nike'); return false;">Nike</a></li>
                <li><a href="#" onclick="filtrarMarca('Adidas'); return false;">Adidas</a></li>
            </ul>
            <h3>Precio</h3>
            <ul>
                <li><a href="#" onclick="filtrarPrecio(0, 1000); return false;">Menos de $1,000</a></li>
                <li><a href="#" onclick="filtrarPrecio(1000, 2500); return false;">$1,000 - $2,500</a></li>
                <li><a href="#" onclick="filtrarPrecio(2500, 5000); return false;">$2,500 - $5,000</a></li>
                <li><a href="#" onclick="filtrarPrecio(5000, 999999); return false;">Más de $5,000</a></li>
            </ul>
        </aside>

        <div class="productos-main">
            <div class="barra-busqueda">
                <input type="text" id="searchInput" placeholder="Buscar productos...">
                <button onclick="buscarProducto()">BUSCAR</button>
            </div>
            <div class="productos-header">
                <div class="resultados" id="resultados-count">Mostrando 0 productos</div>
                <select id="ordenSelect" onchange="ordenarProductos()">
                    <option value="nombre_asc">Ordenar por: Nombre A-Z</option>
                    <option value="precio_asc">Precio: menor a mayor</option>
                    <option value="precio_desc">Precio: mayor a menor</option>
                </select>
            </div>
            <div id="productos-container"><div style="text-align:center; padding:3rem;">Cargando productos...</div></div>
            <div id="paginacion" class="paginacion"></div>
        </div>
    </div>
</section>

<!-- NOSOTROS MOVIDO AQUÍ -->
<section id="nosotros" class="section-nosotros">
    <div class="nosotros-container">
        <div class="nosotros-imagen">
            <img src="uploads/joel_zambrano.jpeg" alt="Joel El Toro Zambrano" onerror="this.src='uploads/default.jpg'">
        </div>
        <div class="nosotros-info">
            <h2>¿QUIEN LA FUNDÓ?</h2>
            <h3>Joel "El Toro" Zambrano</h3>
            <p class="frase">"El boxeo me enseñó disciplina, y esta tienda es mi forma de devolverle al deporte que me formó."</p>
            <p>Apasionado del boxeo desde los 12 años. Joel entendió que en México hacía falta una tienda con equipo profesional, accesible y con trato directo. Después de años entrenando y compitiendo, decidió crear <strong>The Box Is Yours</strong> para que cada peleador tenga las herramientas justas para triunfar.</p>
            <div style="margin-top:1.5rem;">
                <a href="https://www.instagram.com/theboxisyours" target="_blank" style="padding:0.5rem 1rem; background:#0414D4; color:white; border-radius:20px; text-decoration:none; font-size:0.85rem; display:inline-block;">📸 Instagram</a>
            </div>
        </div>
    </div>
</section>

<section id="contacto" class="section-contacto">
    <div class="contacto-container">
        <div class="contacto-info">
            <h2>TIENDA FÍSICA</h2>
            <p><strong>📍 Dirección:</strong> Centro de CDMX</p>
            <p><strong>⏰ Horario:</strong> 10:30 am - 5:30 pm | Lunes a Sábado</p>
            <a href="https://g.co/kgs/duvBENE" target="_blank" style="color:#00b4d8;">Ver en Google Maps</a>
        </div>
        <div class="contacto-info">
            <h2>ENVÍOS</h2>
            <p><strong>📦 Cobertura:</strong> Enviamos a todo México</p>
            <p><strong>🚚 Tiempos:</strong> CDMX 1-2 días / Resto 3-6 días</p>
            <a href="faq.php" style="color:#00b4d8;">Ver FAQ de envíos →</a>
        </div>
        <div class="contacto-info">
            <h2>CONTACTO DIRECTO</h2>
            <p>📱 Escríbenos por WhatsApp o Instagram</p>
            <div class="contacto-buttons">
                <a href="https://wa.me/5215564442192" target="_blank" class="btn">WhatsApp</a>
                <a href="https://www.instagram.com/theboxisyours" target="_blank" class="btn btn-outline">Instagram</a>
            </div>
        </div>
    </div>
</section>

<footer>
    <p>The Box Is Yours - By Team Toro Zambrano | Boxeo con actitud</p>
    <p>CDMX | Guantes, botas y más | Envíos a todo México</p>
    <?php if($is_admin): ?>
    <div style="margin-top:15px;">
        <a href="admin.php" style="color:#00b4d8; text-decoration:none; font-size:0.7rem;">⚙️ Panel Admin</a>
    </div>
    <?php endif; ?>
</footer>

<div id="productModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="cerrarModal()">&times;</span>
        <div id="modalBody"></div>
    </div>
</div>

<div class="cart-icon" onclick="window.location.href='cart.php'">🛒<span id="cartCount">0</span></div>

<script>
// ========== VARIABLES GLOBALES ==========
let todosProductos = [];
let productosFiltrados = [];
let paginaActual = 1;
let productosPorPagina = 12;
let categoriaFiltro = '';
let marcaFiltro = '';
let precioMin = 0;
let precioMax = 999999;
let ordenActual = 'nombre_asc';
let busquedaActual = '';
let productoActual = null;
let variantesActuales = [];
let varianteSeleccionada = null;
let favoritosSet = new Set();

// ========== RF-05: FAVORITOS ==========
async function cargarFavoritos() {
    try {
        const res = await fetch('favoritos_ids.php');
        const data = await res.json();
        favoritosSet = new Set((data.ids || []).map(Number));
    } catch(e) { favoritosSet = new Set(); }
}

async function toggleFavorito(productoId, btn) {
    <?php if(!isset($_SESSION['user_id'])): ?>
        mostrarNotificacion('🔐 Inicia sesión para guardar favoritos', 'warning');
        setTimeout(() => window.location.href = 'login.php', 1200);
        return;
    <?php endif; ?>
    const fd = new FormData();
    fd.append('producto_id', productoId);
    const res = await fetch('favoritos_toggle.php', { method:'POST', body: fd });
    const data = await res.json();
    if(data.success) {
        if(data.es_favorito) {
            favoritosSet.add(Number(productoId));
            if(btn) {
                btn.classList.add('activo');
                if(btn.classList.contains('btn-fav')) btn.textContent = '❤️';
                else btn.textContent = '❤️ Quitar de favoritos';
            }
            mostrarNotificacion('❤️ Añadido a favoritos', 'success');
        } else {
            favoritosSet.delete(Number(productoId));
            if(btn) {