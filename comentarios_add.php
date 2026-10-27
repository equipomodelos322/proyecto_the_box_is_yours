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
$comentario  = trim($_POST['comentario'] ?? '');
$valoracion  = (int)($_POST['valoracion'] ?? 0);

if($producto_id <= 0) { echo json_encode(['success'=>false,'error'=>'Producto inválido']); exit; }
if($valoracion < 1 || $valoracion > 5) { echo json_encode(['success'=>false,'error'=>'Valoración debe ser 1-5']); exit; }
if(strlen($comentario) < 5) { echo json_encode(['success'=>false,'error'=>'El comentario debe tener al menos 5 caracteres']); exit; }
if(strlen($comentario) > 1000) { echo json_encode(['success'=>false,'error'=>'Máximo 1000 caracteres']); exit; }

$chk = $conn->prepare("SELECT id FROM comentarios WHERE producto_base_id=? AND usuario_id=?");
$chk->bind_param("ii", $producto_id, $uid);
$chk->execute();
if($chk->get_result()->num_rows > 0) {
    echo json_encode(['success'=>false,'error'=>'Ya dejaste un comentario en este producto']);
    exit;
}
$chk->close();

$ins = $conn->prepare("INSERT INTO comentarios (producto_base_id, usuario_id, comentario, valoracion) VALUES (?,?,?,?)");
$ins->bind_param("iisi", $producto_id, $uid, $comentario, $valoracion);

if($ins->execute()) {
    echo json_encode(['success'=>true, 'id'=>$ins->insert_id]);
} else {
    echo json_encode(['success'=>false,'error'=>'Error al guardar: '.$conn->error]);
}
$ins->close();