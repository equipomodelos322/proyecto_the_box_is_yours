<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['ids'=>[]]);
    exit;
}

$uid = (int)$_SESSION['user_id'];
$ids = [];
$res = $conn->query("SELECT producto_base_id FROM favoritos WHERE usuario_id = $uid");
if($res) {
    while($r = $res->fetch_assoc()) {
        $ids[] = (int)$r['producto_base_id'];
    }
}
echo json_encode(['ids'=>$ids]);