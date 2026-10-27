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
if($producto_id <= 0) {
    echo json_encode(['success'=>false,'error'=>'Producto inválido']);
    exit;
}

$chk = $conn->prepare("SELECT id FROM productos_base WHERE id = ?");
$chk->bind_param("i", $producto_id);
$chk->execute();
if($chk->get_result()->num_rows === 0) {
    echo json_encode(['success'=>false,'error'=>'Producto no encontrado']);
    exit;
}
$chk->close();

$stmt = $conn->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND producto_base_id = ?");
$stmt->bind_param("ii", $uid, $producto_id);
$stmt->execute();
$existe = $stmt->get_result()->num_rows > 0;
$stmt->close();

if($existe) {
    $del = $conn->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND producto_base_id = ?");
    $del->bind_param("ii", $uid, $producto_id);
    $del->execute();
    $del->close();
    echo json_encode(['success'=>true,'es_favorito'=>false,'action'=>'removed']);
} else {
    $ins = $conn->prepare("INSERT INTO favoritos (usuario_id, producto_base_id) VALUES (?, ?)");
    $ins->bind_param("ii", $uid, $producto_id);
    $ins->execute();
    $ins->close();
    echo json_encode(['success'=>true,'es_favorito'=>true,'action'=>'added']);
}