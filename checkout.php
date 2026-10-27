<?php
session_start();
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 🔒 BLOQUEAR INVITADOS
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['user_id'];

// Verificar que el carrito no esté vacío
if(!isset($_SESSION['carrito']) || count($_SESSION['carrito']) == 0) {
    header('Location: cart.php');
    exit;
}

$productos_carrito = $_SESSION['carrito'];
$total = 0;
foreach($productos_carrito as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

// ========== BUSCAR COMPRA ANTERIOR ==========
$pedido_anterior = null;
// CORREGIDO: tabla pedidos en minúscula
$stmt_ant = $conn->prepare("SELECT Direccion, Ciudad, Codigo_Postal, Metodo_Pago FROM pedidos WHERE _Usu = ? ORDER BY _Pedido DESC LIMIT 1");
$stmt_ant->bind_param("i", $id_usuario);
$stmt_ant->execute();
$res_ant = $stmt_ant->get_result();
if($res_ant->num_rows > 0) {
    $pedido_anterior = $res_ant->fetch_assoc();
}
$stmt_ant->close();

$error = '';

// ========== PROCESAR PAGO ==========
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar_pedido'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $codigo_postal = trim($_POST['codigo_postal'] ?? '');
    $fecha_pedido = date('Y-m-d H:i:s');

    // Datos de tarjeta (Si vienen de "reuso", los toma de los campos ocultos)
    $num_tarjeta = preg_replace('/\s+/', '', $_POST['num_tarjeta'] ?? $_POST['num_tarjeta_reuso'] ?? '');
    $titular = trim($_POST['titular'] ?? $_POST['titular_reuso'] ?? '');
    $mes_exp = (int)($_POST['mes_exp'] ?? $_POST['mes_exp_reuso'] ?? 0);
    $anio_exp = (int)($_POST['anio_exp'] ?? $_POST['anio_exp_reuso'] ?? 0);
    $cvv = $_POST['cvv'] ?? $_POST['cvv_reuso'] ?? '';

    // Validaciones
    $errores = [];
    if(empty($nombre) || empty($email) || empty($direccion) || empty($ciudad) || empty($codigo_postal)) {
        $errores[] = "Completa todos los campos de envío";
    }
    if(strlen($num_tarjeta) < 15 || strlen($num_tarjeta) > 16 || !ctype_digit($num_tarjeta)) {
        $errores[] = "Número de tarjeta inválido (15-16 dígitos)";
    }
    if(empty($titular)) $errores[] = "El titular es obligatorio";
    if($mes_exp < 1 || $mes_exp > 12) $errores[] = "Mes inválido";
    if($anio_exp < date('Y') || ($anio_exp == date('Y') && $mes_exp < date('n'))) {
        $errores[] = "Tarjeta vencida";
    }
    if(strlen($cvv) < 3 || strlen($cvv) > 4 || !ctype_digit($cvv)) {
        $errores[] = "CVV inválido (3-4 dígitos)";
    }

    if(!empty($errores)) {
        $error = implode("<br>", $errores);
    } else {
        // Simulación: rechaza si termina en 0000
        $ultimos_4 = substr($num_tarjeta, -4);
        
        if($ultimos_4 === '0000') {
            $error = "❌ Pago rechazado por el banco (simulación). Intenta con otra tarjeta.";
        } else {
            // Guardar productos
            $productos_guardar = [];
            foreach($productos_carrito as $item) {
                $productos_guardar[] = [
                    'variante_id' => $item['variante_id'] ?? 0,
                    'nombre' => $item['nombre'],
                    'precio' => floatval($item['precio']),
                    'cantidad' => intval($item['cantidad']),
                    'imagen' => $item['imagen'] ?? null
                ];
            }
            $productos_json = json_encode($productos_guardar, JSON_UNESCAPED_UNICODE);

            $metodo_pago = 'tarjeta';
            $estado = 2; // Pagado
            $referencia_pago = 'TXN-' . strtoupper(uniqid());

            $stmt = $conn->prepare("INSERT INTO pedidos 
                (_Usu, Direccion, Ciudad, Codigo_Postal, Metodo_Pago, Total, Nombre, Metodo_Contacto, Fecha_Pedido, Estado, Comprobante) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'tarjeta', ?, ?, ?)");
            $stmt->bind_param("issssdssis", 
                $id_usuario, $direccion, $ciudad, $codigo_postal, 
                $metodo_pago, $total, $productos_json, 
                $fecha_pedido, $estado, $referencia_pago
            );

            if($stmt->execute()) {
                $pedido_id = $stmt->insert_id;

                // Descontar stock
                foreach($productos_carrito as $item) {
                    if(!empty($item['variante_id'])) {
                        $upd = $conn->prepare("UPDATE productos_variantes SET stock = stock - ? WHERE id = ? AND stock >= ?");
                        $upd->bind_param("iii", $item['cantidad'], $item['variante_id'], $item['cantidad']);
                        $upd->execute();
                        $upd->close();
                    }
                }

                $_SESSION['ultimo_pedido'] = [
                    'id' => $pedido_id,
                    'total' => $total,
                    'referencia' => $referencia_pago,
                    'nombre' => $nombre,
                    'email' => $email,
                    'telefono' => $telefono,
                    'direccion' => $direccion,
                    'ciudad' => $ciudad,
                    'codigo_postal' => $codigo_postal,
                    'productos' => $productos_guardar,
                    'tarjeta_ultimos4' => $ultimos_4,
                    'fecha' => $fecha_pedido
                ];

                $_SESSION['carrito'] = [];
                header("Location: ticket.php");
                exit;
            } else {
                $error = "Error al procesar el pedido: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Finalizar Compra - The Box Is Yours</title>
<link rel="stylesheet" href="styles.css">
<style>
    .nav { display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 2rem; background: #01084a; padding: 12px 5%; }
    .nav a { color: white; text-decoration: none; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s; padding: 5px 0; }
    .nav a:hover { color: #00b4d8; }
    .user-menu { display: flex; align-items: center; gap: 1rem; margin-left: 1rem; }
    .user-name { color: #00b4d8; font-weight: bold; font-size: 0.9rem; }
    .logout-link { color: white; text-decoration: none; padding: 5px 12px; border-radius: 8px; font-size: 0.85rem; }
    .logout-link:hover { background: rgba(255,255,255,0.15); }
    
    .checkout-section { max-width: 800px; margin: 2rem auto; padding: 0 2rem; }
    .checkout-form { background: #0f172a; border-radius: 12px; padding: 2rem; border: 1px solid #1e293b; }
    .checkout-form h2 { color: #00b4d8; margin-bottom: 1.5rem; }
    .checkout-form h3 { color: #00b4d8; margin: 1.5rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #1e293b; }
    .form-row { margin-bottom: 1rem; }
    .form-row label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: #cbd5e1; font-size: 0.9rem; }
    .form-row input, .form-row textarea, .form-row select { width: 100%; padding: 0.8rem; border-radius: 8px; border: 1px solid #1e293b; background: #020617; color: white; font-size: 0.95rem; }
    .form-row input:focus, .form-row select:focus, .form-row textarea:focus { outline: none; border-color: #00b4d8; }
    .row-2cols { display: flex; gap: 1rem; }
    .row-2cols .form-row { flex: 1; }
    .row-3cols { display: flex; gap: 1rem; }
    .row-3cols .form-row { flex: 1; }
    .order-summary { background: #0a192f; padding: 1.5rem; border-radius: 8px; margin: 1.5rem 0; }
    .btn-checkout { width: 100%; background: #22c55e; color: white; border: none; padding: 1rem; border-radius: 50px; font-weight: bold; cursor: pointer; font-size: 1.05rem; transition: 0.3s; }
    .btn-checkout:hover { background: #16a34a; transform: translateY(-2px); }
    .error-box { background: rgba(239,68,68,0.2); color: #ef4444; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #ef4444; }
    .secure-badge { display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: rgba(34,197,94,0.1); color: #22c55e; padding: 0.8rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.85rem; border: 1px solid #22c55e; }
    
    /* ESTILOS PARA EL AVISO DE COMPRA ANTERIOR */
    .aviso-recompra { background: rgba(0,180,216,0.1); border: 1px solid #00b4d8; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
    .aviso-recompra p { color: #cbd5e1; font-size: 0.9rem; margin: 0; }
    .btn-reusar { background: #00b4d8; color: #03045e; border: none; padding: 0.5rem 1rem; border-radius: 20px; font-weight: bold; cursor: pointer; font-size: 0.8rem; transition: 0.3s; }
    .btn-reusar:hover { background: #0096c7; }
    .hidden { display: none !important; }

    footer { background: #01084a; text-align: center; padding: 2rem; color: #64748b; margin-top: 2rem; }
    @media (max-width: 600px) { .checkout-section { padding: 0 1rem; } .nav { flex-direction: column; gap: 1rem; } .row-2cols, .row-3cols { flex-direction: column; gap: 0; } }
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
    <a href="mis_pedidos.php">📋 MIS PEDIDOS</a>
    <div class="user-menu">
        <span class="user-name">👋 <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="logout-link">🚪 Salir</a>
    </div>
</nav>

<div class="checkout-section">
    <div class="checkout-form">
        <h2>💳 Finalizar Compra</h2>
        
        <div class="secure-badge">
            🔒 Pago 100% seguro | Simulación con fines académicos
        </div>

        <?php if($pedido_anterior): ?>
            <!-- AVISO PARA LA SEGUNDA COMPRA -->
            <div class="aviso-recompra" id="aviso-recompra">
                <p>👋 <strong>¡Qué bueno verte de nuevo!</strong><br>Ya tenemos tus datos de envío guardados de tu compra anterior.</p>
                <button type="button" class="btn-reusar" onclick="usarDatosAnteriores()">Usar estos datos</button>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="error-box">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" id="formPago">
            <div id="seccion-datos-envio">
                <h3>📦 Datos de envío</h3>
                <div class="form-row">
                    <label>Nombre completo *</label>
                    <input type="text" name="nombre" id="input_nombre" required value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>">
                </div>
                
                <div class="row-2cols">
                    <div class="form-row">
                        <label>Email *</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>">
                    </div>
                    <div class="form-row">
                        <label>Teléfono *</label>
                        <input type="tel" name="telefono" required placeholder="55 1234 5678">
                    </div>
                </div>
                
                <div class="form-row">
                    <label>Dirección completa *</label>
                    <textarea name="direccion" id="input_direccion" rows="2" required placeholder="Calle, número, colonia..."><?php echo $pedido_anterior ? htmlspecialchars($pedido_anterior['Direccion']) : ''; ?></textarea>
                </div>
                
                <div class="row-2cols">
                    <div class="form-row">
                        <label>Ciudad *</label>
                        <input type="text" name="ciudad" id="input_ciudad" required value="<?php echo $pedido_anterior ? htmlspecialchars($pedido_anterior['Ciudad']) : ''; ?>">
                    </div>
                    <div class="form-row">
                        <label>Código Postal *</label>
                        <input type="text" name="codigo_postal" id="input_cp" required maxlength="5" value="<?php echo $pedido_anterior ? htmlspecialchars($pedido_anterior['Codigo_Postal']) : ''; ?>">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE TARJETA (Se oculta si el usuario decide usar la anterior) -->
            <div id="seccion-tarjeta">
                <h3>💳 Datos de la tarjeta</h3>
                <div class="form-row">
                    <label>Número de tarjeta *</label>
                    <input type="text" name="num_tarjeta" id="num_tarjeta" placeholder="1234 5678 9012 3456" maxlength="19" required inputmode="numeric" autocomplete="cc-number">
                    <small style="color:#64748b; font-size:0.75rem;">💡 Usa cualquier número. Si termina en 0000 será rechazado (para probar).</small>
                </div>
                
                <div class="form-row">
                    <label>Titular de la tarjeta *</label>
                    <input type="text" name="titular" placeholder="Como aparece en la tarjeta" required autocomplete="cc-name">
                </div>
                
                <div class="row-3cols">
                    <div class="form-row">
                        <label>Mes exp. *</label>
                        <select name="mes_exp" required>
                            <option value="">MM</option>
                            <?php for($m=1; $m<=12; $m++): ?>
                                <option value="<?php echo str_pad($m,2,'0',STR_PAD_LEFT); ?>"><?php echo str_pad($m,2,'0',STR_PAD_LEFT); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <label>Año exp. *</label>
                        <select name="anio_exp" required>
                            <option value="">YYYY</option>
                            <?php for($y=date('Y'); $y<=date('Y')+10; $y++): ?>
                                <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <label>CVV *</label>
                        <input type="text" name="cvv" maxlength="4" placeholder="123" required inputmode="numeric" autocomplete="cc-csc">
                    </div>
                </div>
            </div>
            
            <!-- INPUTS OCULTOS PARA CUANDO SE USAN DATOS ANTERIORES -->
            <input type="hidden" name="num_tarjeta_reuso" id="reuso_tarjeta" value="4111111111111111">
            <input type="hidden" name="titular_reuso" id="reuso_titular" value="Cliente Frecuente">
            <input type="hidden" name="mes_exp_reuso" id="reuso_mes" value="12">
            <input type="hidden" name="anio_exp_reuso" id="reuso_anio" value="<?php echo date('Y') + 5; ?>">
            <input type="hidden" name="cvv_reuso" id="reuso_cvv" value="123">

            <div class="order-summary">
                <h3 style="color: #00b4d8; margin-top:0; border:none;">🛍️ Resumen del pedido</h3>
                <?php foreach($productos_carrito as $item): ?>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #1e293b; font-size:0.9rem;">
                    <span><?php echo htmlspecialchars($item['nombre']); ?> x <?php echo $item['cantidad']; ?></span>
                    <span>$<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></span>
                </div>
                <?php endforeach; ?>
                <div style="display: flex; justify-content: space-between; padding: 1rem 0 0 0; font-weight: bold; font-size: 1.2rem;">
                    <strong>TOTAL</strong>
                    <strong style="color:#22c55e;">$<?php echo number_format($total, 2); ?> MXN</strong>
                </div>
            </div>
            
            <button type="submit" name="confirmar_pedido" class="btn-checkout">
                💳 Pagar $<?php echo number_format($total, 2); ?> MXN
            </button>
        </form>
    </div>
</div>

<footer>
    <p>The Box Is Yours - By Team Toro Zambrano | Boxeo con actitud</p>
    <p>CDMX | Guantes, botas y más | Envíos a todo México</p>
</footer>

<script>
// Formato de tarjeta
const inputTarjeta = document.getElementById('num_tarjeta');
if(inputTarjeta) {
    inputTarjeta.addEventListener('input', function(e) {
        let val = e.target.value.replace(/\s/g, '').replace(/\D/g, '');
        val = val.substring(0, 16);
        e.target.value = val.replace(/(\d{4})(?=\d)/g, '$1 ');
    });
}

// Solo números en CVV
const inputCvv = document.querySelector('input[name="cvv"]');
if(inputCvv) {
    inputCvv.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '');
    });
}

// Lógica para reutilizar datos (Segunda compra)
function usarDatosAnteriores() {
    // Ocultar formulario de tarjeta
    const secTarjeta = document.getElementById('seccion-tarjeta');
    secTarjeta.classList.add('hidden');
    
    // Quitar la validación "required" a los campos ocultos
    secTarjeta.querySelectorAll('input, select').forEach(el => el.removeAttribute('required'));

    // Deshabilitar los campos para que no interfieran
    document.getElementById('num_tarjeta').disabled = true;
    document.querySelector('input[name="titular"]').disabled = true;
    document.querySelector('select[name="mes_exp"]').disabled = true;
    document.querySelector('select[name="anio_exp"]').disabled = true;
    document.querySelector('input[name="cvv"]').disabled = true;

    // Cambiar el botón para que muestre que se usan los datos anteriores
    const btnPagar = document.querySelector('.btn-checkout');
    btnPagar.innerHTML = '💳 Pagar con tarjeta guardada';
    
    // Ocultar el aviso
    const aviso = document.getElementById('aviso-recompra');
    if(aviso) aviso.style.display = 'none';
}
</script>

</body>
</html>