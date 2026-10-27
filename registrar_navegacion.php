<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false, 'reason'=>'guest']);
    exit;
}
$uid = (int)$_SESSION['user_id'];
$producto_id = (int)($_POST['producto_id'] ?? 0);
if($producto_id <= 0) { echo json_encode(['success'=>false,'reason'=>'invalid']); exit; }

$stmt = $conn->prepare("SELECT id FROM productos_base WHERE id=?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
if($stmt->get_result()->num_rows === 0) { echo json_encode(['success'=>false]); exit; }
$stmt->close();

$ins = $conn->prepare("INSERT INTO navegacion_historial (usuario_id, producto_base_id) VALUES (?,?)");
$ins->bind_param("ii", $uid, $producto_id);
$ins->execute();
$ins->close();

// Limpieza: mantener solo los últimos 100 registros del usuario
$del = $conn->prepare("
    DELETE FROM navegacion_historial
    WHERE usuario_id = ?
      AND id NOT IN (
        SELECT id FROM (
            SELECT id FROM navegacion_historial WHERE usuario_id = ? ORDER BY fecha DESC LIMIT 100
        ) AS t
      )
");
$del->bind_param("ii", $uid, $uid);
$del->execute();
$del->close();

echo json_encode(['success'=>true]);