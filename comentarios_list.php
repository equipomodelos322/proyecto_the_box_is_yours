<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$producto_id = (int)($_GET['producto_id'] ?? 0);
if($producto_id <= 0) { echo json_encode(['success'=>false,'error'=>'Producto inválido']); exit; }

$stmt = $conn->prepare("
    SELECT c.id, c.comentario, c.valoracion, c.fecha, u.Usu AS autor
    FROM comentarios c
    INNER JOIN usuarios u ON u._Usu = c.usuario_id
    WHERE c.producto_base_id = ?
    ORDER BY c.fecha DESC
    LIMIT 50
");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$res = $stmt->get_result();

$comentarios = [];
$suma = 0;
while($row = $res->fetch_assoc()) {
    $comentarios[] = [
        'id'         => (int)$row['id'],
        'comentario' => $row['comentario'],
        'valoracion' => (int)$row['valoracion'],
        'fecha'      => $row['fecha'],
        'autor'      => $row['autor']
    ];
    $suma += (int)$row['valoracion'];
}
$stmt->close();

$promedio = count($comentarios) > 0 ? round($suma / count($comentarios), 2) : 0;

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM likes_producto WHERE producto_base_id = ?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$total_likes = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$yo_di_like = false;
if(isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM likes_producto WHERE producto_base_id=? AND usuario_id=?");
    $stmt->bind_param("ii", $producto_id, $uid);
    $stmt->execute();
    $yo_di_like = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

echo json_encode([
    'success'     => true,
    'comentarios' => $comentarios,
    'promedio'    => $promedio,
    'total'       => count($comentarios),
    'likes'       => $total_likes,
    'yo_di_like'  => $yo_di_like
]);