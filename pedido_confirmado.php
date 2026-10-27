<?php
session_start();

if(!isset($_SESSION['pedido_realizado']) || !$_SESSION['pedido_realizado']) {
    header('Location: index.php');
    exit;
}

$mensaje = $_SESSION['pedido_mensaje'];
$instagram_usuario = $_SESSION['instagram_usuario'];

unset($_SESSION['pedido_realizado']);
unset($_SESSION['pedido_mensaje']);
unset($_SESSION['instagram_usuario']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - The Box Is Yours</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .confirmado-container { max-width: 600px; margin: 3rem auto; padding: 2rem; background: #0f172a; border-radius: 16px; text-align: center; border: 1px solid #1e293b; }
        .confirmado-container h1 { color: #22c55e; margin-bottom: 1rem; }
        .mensaje-box { background: #0a192f; padding: 1.5rem; border-radius: 12px; margin: 1.5rem 0; text-align: left; font-family: monospace; white-space: pre-wrap; font-size: 0.85rem; border: 1px solid #1e293b; max-height: 400px; overflow: auto; }
        .btn-copiar { background: #00b4d8; color: #03045e; border: none; padding: 0.8rem 1.5rem; border-radius: 50px; font-weight: bold; cursor: pointer; margin: 0.5rem; }
        .btn-ir-instagram { background: #E4405F; color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 50px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-volver { background: #1e293b; color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 50px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 1rem; }
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
    <div class="nav-links">
        <a href="index.php#inicio">INICIO</a>
        <a href="index.php#nosotros">NOSOTROS</a>
        <a href="index.php#productos">PRODUCTOS</a>
        <a href="index.php#contacto">CONTACTO</a>
        <a href="cart.php">🛒 CARRITO</a>
    </div>
</nav>

<div class="confirmado-container">
    <h1>✅ ¡Pedido Confirmado!</h1>
    <p>Tu pedido ha sido registrado correctamente.</p>
    
    <?php if($instagram_usuario): ?>
    <p>📸 Te contactaremos a través de Instagram: <strong>@<?php echo htmlspecialchars($instagram_usuario); ?></strong></p>
    <?php endif; ?>
    
    <p>Copia el siguiente mensaje y envíalo por Instagram:</p>
    
    <div class="mensaje-box" id="mensajeParaCopiar"><?php echo htmlspecialchars($mensaje); ?></div>
    
    <button class="btn-copiar" onclick="copiarMensaje()">📋 Copiar mensaje</button>
    <a href="https://www.instagram.com/joel_zambrano/" target="_blank" class="btn-ir-instagram">📸 Ir a Instagram</a>
    <br>
    <a href="index.php" class="btn-volver">← Volver a la tienda</a>
</div>

<footer>
    <p>The Box Is Yours - By Team Toro Zambrano | Boxeo con actitud</p>
    <p>CDMX | Guantes, botas y mas | Envios a todo Mexico</p>
</footer>

<script>
    function copiarMensaje() {
        const mensaje = document.getElementById('mensajeParaCopiar').innerText;
        navigator.clipboard.writeText(mensaje).then(() => {
            alert('✅ Mensaje copiado. Ahora pégalo en Instagram.');
        });
    }
</script>

</body>
</html>