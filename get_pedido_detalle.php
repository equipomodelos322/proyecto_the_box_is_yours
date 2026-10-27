<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if(!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID no proporcionado']);
    exit;
}

$pedido_id = (int)$_GET['id'];

// Verificar permisos (admin o dueño del pedido)
$user_id = $_SESSION['user_id'] ?? 0;
$user_rol = $_SESSION['user_rol'] ?? '';

$sql = "SELECT p.*, u.Usu as nombre_cliente, u.email as email_cliente 
        FROM Pedidos p
        LEFT JOIN Usuarios u ON p._Usu = u._Usu
        WHERE p._Pedido = ?";

if($user_rol != 'admin') {
    $sql .= " AND p._Usu = ?";
}

$stmt = $conn->prepare($sql);
if($user_rol != 'admin') {
    $stmt->bind_param("ii", $pedido_id, $user_id);
} else {
    $stmt->bind_param("i", $pedido_id);
}

$stmt->execute();
$result = $stmt->get_result();
$pedido = $result->fetch_assoc();

if(!$pedido) {
    echo json_encode(['error' => 'Pedido no encontrado']);
    exit;
}

// Decodificar productos
$productos = [];
if(!empty($pedido['Nombre'])) {
    $productos_data = json_decode($pedido['Nombre'], true);
    if(is_array($productos_data)) {
        // Si es objeto con claves, convertir a array
        if(!isset($productos_data[0]) && is_array($productos_data)) {
            $productos = array_values($productos_data);
        } else {
            $productos = $productos_data;
        }
    }
}

$pedido['productos'] = $productos;
echo json_encode($pedido);
?>