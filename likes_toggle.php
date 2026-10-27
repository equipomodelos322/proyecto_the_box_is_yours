<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Debes iniciar sesión','redirect'=>'login.php']);
    exit;
}
$uid = (int)$_SESSION['user_id'];
$producto_id = (int)($_POST['producto_id'] ?? 0);
if($producto_id <= 0) { echo json_encode(['success'=>false,'error'=>'Producto inválido']); exit; }

$stmt = $conn->prepare("SELECT id FROM likes_producto WHERE producto_base_id=? AND usuario_id=?");
$stmt->bind_param("ii", $producto_id, $uid);
$stmt->execute();
$existe = $stmt->get_result()->num_rows > 0;
$stmt->close();

if($existe) {
    $del = $conn->prepare("DELETE FROM likes_producto WHERE producto_base_id=? AND usuario_id=?");
    $del->bind_param("ii", $producto_id, $uid);
    $del->execute(); $del->close();
} else {
    $ins = $conn->prepare("INSERT INTO likes_producto (producto_base_id, usuario_id) VALUES (?,?)");
    $ins->bind_param("ii", $producto_id, $uid);
    $ins->execute(); $ins->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM likes_producto WHERE producto_base_id=?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

echo json_encode(['success'=>true, 'yo_di_like'=>!$existe, 'total'=>$total]);