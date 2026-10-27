<?php
session_start();
require_once 'config.php';

// Obtener estado del carrito (para AJAX)
if(isset($_GET['action']) && $_GET['action'] == 'get') {
    $total_items = 0;
    if(isset($_SESSION['carrito'])) {
        foreach($_SESSION['carrito'] as $item) {
            $total_items += $item['cantidad'];
        }
    }
    echo json_encode(['total_items' => $total_items]);
    exit;
}

// ========== AGREGAR AL CARRITO ==========
if(isset($_POST['action']) && $_POST['action'] == 'add') {
    $response = ['success' => false, 'error' => ''];
    
    // 🔒 BLOQUEAR INVITADOS
    if(!isset($_SESSION['user_id'])) {
        $response['error'] = 'Debes iniciar sesión para agregar productos';
        $response['redirect'] = 'login.php';
        echo json_encode($response);
        exit;
    }
    
    $variante_id = (int)$_POST['variante_id'];
    $nombre = $_POST['nombre'];
    $precio = (float)$_POST['precio'];
    $imagen = $_POST['imagen'] ?? '';
    $cantidad = (int)$_POST['cantidad'];
    
    // Verificar stock
    $stmt = $conn->prepare("SELECT stock FROM productos_variantes WHERE id = ?");
    $stmt->bind_param("i", $variante_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $variante = $result->fetch_assoc();
    $stmt->close();
    
    if(!$variante) {
        $response['error'] = 'Producto no encontrado';
        echo json_encode($response);
        exit;
    }
    
    if($variante['stock'] < $cantidad) {
        $response['error'] = 'Stock insuficiente. Disponibles: ' . $variante['stock'];
        echo json_encode($response);
        exit;
    }
    
    if(!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }
    
    $found = false;
    foreach($_SESSION['carrito'] as $key => $item) {
        if($item['variante_id'] == $variante_id) {
            $_SESSION['carrito'][$key]['cantidad'] += $cantidad;
            $found = true;
            break;
        }
    }
    
    if(!$found) {
        $_SESSION['carrito'][] = [
            'variante_id' => $variante_id,
            'nombre' => $nombre,
            'precio' => $precio,
            'imagen' => $imagen,
            'cantidad' => $cantidad
        ];
    }
    
    $response['success'] = true;
    echo json_encode($response);
    exit;
}

// ========== ACTUALIZAR CANTIDAD ==========
if(isset($_POST['action']) && $_POST['action'] == 'update') {
    $index = (int)$_POST['index'];
    $cantidad = (int)$_POST['cantidad'];
    
    if(isset($_SESSION['carrito'][$index])) {
        $variante_id = $_SESSION['carrito'][$index]['variante_id'];
        
        $stmt = $conn->prepare("SELECT stock FROM productos_variantes WHERE id = ?");
        $stmt->bind_param("i", $variante_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $variante = $result->fetch_assoc();
        $stmt->close();
        
        if($variante && $variante['stock'] >= $cantidad) {
            $_SESSION['carrito'][$index]['cantidad'] = $cantidad;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Stock insuficiente']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
    }
    exit;
}

// ========== ELIMINAR ==========
if(isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['index'])) {
    $index = (int)$_GET['index'];
    if(isset($_SESSION['carrito'][$index])) {
        array_splice($_SESSION['carrito'], $index, 1);
    }
    header('Location: cart.php');
    exit;
}

// ========== VACIAR ==========
if(isset($_GET['action']) && $_GET['action'] == 'clear') {
    $_SESSION['carrito'] = [];
    header('Location: cart.php');
    exit;
}

$is_logged = isset($_SESSION['user_id']);
$is_admin = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 'admin');
$user_name = $_SESSION['user_name'] ?? '';

$productos_carrito = $_SESSION['carrito'] ?? [];
$total = 0;
foreach($productos_carrito as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Carrito de Compras - The Box Is Yours</title>
<link rel="stylesheet" href="styles.css">
<style>
    .nav {
        display: flex; justify-content: center; align-items: center;
        flex-wrap: wrap; gap: 2rem; background: #01084a; padding: 12px 5%;
    }
    .nav a {
        color: white; text-decoration: none; font-weight: 600;
        font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;
        transition: all 0.3s; padding: 5px 0;
    }
    .nav a:hover { color: #00b4d8; }
    .user-menu { display: flex; align-items: center; gap: 1rem; margin-left: 1rem; }
    .user-name { color: #00b4d8; font-weight: bold; font-size: 0.9rem; }
    .logout-link, .admin-link, .login-link {
        color: white; text-decoration: none; padding: 5px 12px;
        border-radius: 8px; transition: 0.3s; font-size: 0.85rem;
    }
    .admin-link { background: #eab308; color: #03045e; font-weight: bold; }
    .login-link { background: #00b4d8; color: #03045e; font-weight: bold; }
    .logout-link:hover { background: rgba(255,255,255,0.15); }
    
    .cart-section { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
    .cart-section h2 { color: #00b4d8; margin-bottom: 1.5rem; }
    .cart-table { width: 100%; background: #0f172a; border-radius: 12px; overflow: hidden; margin-bottom: 2rem; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #1e293b; }
    th { background: #020617; color: #00b4d8; font-weight: bold; }
    .producto-imagen { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
    .cantidad-input {
        width: 70px; padding: 0.5rem; border-radius: 8px; border: 1px solid #1e293b;
        background: #020617; color: white; text-align: center;
    }
    .btn-eliminar {
        background: #ef4444; color: white; padding: 0.3rem 0.8rem;
        border: none; border-radius: 6px; cursor: pointer; font-size: 0.8rem;
    }
    .cart-summary {
        background: #0f172a; padding: 1.5rem; border-radius: 12px;
        text-align: right; margin-top: 1rem;
    }
    .cart-total { font-size: 1.5rem; font-weight: bold; color: #22c55e; margin: 1rem 0; }
    .btn-checkout {
        background: #22c55e; color: white; padding: 0.8rem 2rem;
        border: none; border-radius: 50px; font-weight: bold; cursor: pointer;
        font-size: 1rem; text-decoration: none; display: inline-block;
    }
    .btn-checkout:hover { background: #16a34a; }
    .btn-login-required {
        background: #eab308; color: #03045e; padding: 0.8rem 2rem;
        border: none; border-radius: 50px; font-weight: bold; cursor: pointer;
        font-size: 1rem; text-decoration: none; display: inline-block;
    }
    .btn-continuar {
        background: #00b4d8; color: #03045e; padding: 0.8rem 2rem;
        border: none; border-radius: 50px; font-weight: bold; cursor: pointer;
        font-size: 1rem; text-decoration: none; display: inline-block; margin-right: 1rem;
    }
    .btn-vaciar {
        background: #64748b; color: white; padding: 0.8rem 2rem;
        border: none; border-radius: 50px; font-weight: bold; cursor: pointer;
        font-size: 1rem; text-decoration: none; display: inline-block; margin-left: 1rem;
    }
    .empty-cart { text-align: center; padding: 4rem; background: #0f172a; border-radius: 12px; color: #64748b; }
    .aviso-login {
        background: rgba(234,179,8,0.15); border: 1px solid #eab308; color: #eab308;
        padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center;
    }
    footer { background: #01084a; text-align: center; padding: 2rem; color: #64748b; margin-top: 2rem; }
    @media (max-width: 768px) {
        .nav { flex-direction: column; gap: 1rem; }
        .cart-section { padding: 0 1rem; }
        th, td { padding: 0.5rem; font-size: 0.8rem; }
        .producto-imagen { width: 40px; height: 40px; }
        .cart-total { font-size: 1.2rem; }
        .btn-continuar, .btn-checkout, .btn-vaciar, .btn-login-required { padding: 0.5rem 1rem; font-size: 0.8rem; }
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
    <a href="index.php#inicio">INICIO</a>
    <a href="index.php#nosotros">NOSOTROS</a>
    <a href="index.php#productos">PRODUCTOS</a>
    <a href="index.php#contacto">CONTACTO</a>
    <a href="cart.php" style="color: #00b4d8;">🛒 CARRITO</a>
    
    <?php if($is_logged): ?>
        <a href="mis_pedidos.php">📋 MIS PEDIDOS</a>
        <?php if($is_admin): ?>
            <a href="admin_pedidos.php" class="admin-link">👑 ADMIN</a>
        <?php endif; ?>
        <div class="user-menu">
            <span class="user-name">👋 <?php echo htmlspecialchars($user_name); ?></span>
            <a href="logout.php" class="logout-link">🚪 Salir</a>
        </div>
    <?php else: ?>
        <a href="login.php" class="login-link">🔐 Iniciar Sesión</a>
    <?php endif; ?>
</nav>

<div class="cart-section">
    <h2>🛒 Carrito de Compras</h2>
    
    <?php if(!$is_logged): ?>
        <div class="aviso-login">
            🔒 <strong>Debes iniciar sesión</strong> para poder comprar. 
            <a href="login.php" style="color:#eab308; text-decoration:underline;">Iniciar sesión aquí</a>
        </div>
    <?php endif; ?>
    
    <?php if(empty($productos_carrito)): ?>
        <div class="empty-cart">
            <p>🛍️ Tu carrito está vacío</p>
            <a href="index.php#productos" style="color: #00b4d8;">← Ir a la tienda</a>
        </div>
    <?php else: ?>
        <div class="cart-table">
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($productos_carrito as $index => $item): ?>
                    <tr id="row-<?php echo $index; ?>">
                        <td>
                            <?php if($item['imagen']): ?>
                                <img src="<?php echo htmlspecialchars($item['imagen']); ?>" class="producto-imagen" alt="">
                            <?php else: ?>
                                <div class="producto-imagen" style="background:#1e293b; display:flex; align-items:center; justify-content:center;">🥊</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                        <td>$<?php echo number_format($item['precio'], 2); ?></td>
                        <td>
                            <input type="number" class="cantidad-input" value="<?php echo $item['cantidad']; ?>" 
                                   min="1" onchange="actualizarCantidad(<?php echo $index; ?>, this.value)">
                        </td>
                        <td class="subtotal-<?php echo $index; ?>">$<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></td>
                        <td>
                            <button class="btn-eliminar" onclick="eliminarProducto(<?php echo $index; ?>)">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="cart-summary">
            <div class="cart-total">
                Total: $<span id="total-carrito"><?php echo number_format($total, 2); ?></span> MXN
            </div>
            <div>
                <a href="index.php#productos" class="btn-continuar">← Seguir comprando</a>
                <?php if($is_logged): ?>
                    <a href="checkout.php" class="btn-checkout">💳 Proceder al pago</a>
                <?php else: ?>
                    <a href="login.php" class="btn-login-required">🔐 Inicia sesión para comprar</a>
                <?php endif; ?>
                <a href="?action=clear" class="btn-vaciar" onclick="return confirm('¿Vaciar todo el carrito?')">🗑️ Vaciar</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<footer>
    <p>The Box Is Yours - By Team Toro Zambrano | Boxeo con actitud</p>
    <p>CDMX | Guantes, botas y más | Envíos a todo México</p>
</footer>

<script>
    function actualizarCantidad(index, cantidad) {
        cantidad = parseInt(cantidad);
        if(isNaN(cantidad) || cantidad < 1) cantidad = 1;
        
        fetch('cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update&index=${index}&cantidad=${cantidad}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert(data.error || 'Error al actualizar');
                location.reload();
            }
        })
        .catch(() => location.reload());
    }
    
    function eliminarProducto(index) {
        if(confirm('¿Eliminar este producto del carrito?')) {
            window.location.href = `cart.php?action=remove&index=${index}`;
        }
    }
</script>

</body>
</html>