<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

function limpiarUtf8($valor) {
    if (is_null($valor)) return null;
    if (is_string($valor)) {
        return mb_convert_encoding($valor, 'UTF-8', 'UTF-8');
    }
    return $valor;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['error' => 'ID no válido']);
    exit;
}

// Producto base
$sqlBase = "SELECT id, nombre_base, descripcion, imagen_principal, Categoria FROM productos_base WHERE id = $id";
$resultBase = $conn->query($sqlBase);
if (!$resultBase || $resultBase->num_rows == 0) {
    echo json_encode(['error' => 'Producto no encontrado']);
    exit;
}
$producto = $resultBase->fetch_assoc();
$producto['nombre_base'] = limpiarUtf8($producto['nombre_base']);
$producto['descripcion'] = limpiarUtf8($producto['descripcion']);
$producto['imagen_principal'] = limpiarUtf8($producto['imagen_principal']);
$producto['Categoria'] = limpiarUtf8($producto['Categoria']);

// Variantes
$sqlVars = "SELECT id, talla, onza, color, precio, stock, imagen FROM productos_variantes WHERE producto_base_id = $id";
$resultVars = $conn->query($sqlVars);
$variantes = [];
$tallas = [];
$onzas = [];
$colores = [];

while ($v = $resultVars->fetch_assoc()) {
    $v['talla'] = limpiarUtf8($v['talla']);
    $v['onza'] = limpiarUtf8($v['onza']);
    $v['color'] = limpiarUtf8($v['color']);
    $v['imagen'] = limpiarUtf8($v['imagen']);
    $v['precio'] = (float)$v['precio'];
    $v['stock'] = (int)$v['stock'];
    
    // Guardar valores únicos para selects (evitar duplicados)
    if(!empty($v['talla']) && !in_array($v['talla'], $tallas)) $tallas[] = $v['talla'];
    if(!empty($v['onza']) && !in_array($v['onza'], $onzas)) $onzas[] = $v['onza'];
    if(!empty($v['color']) && !in_array($v['color'], $colores)) $colores[] = $v['color'];
    
    $variantes[] = $v;
}

// Ordenar los arrays para mejor presentación
sort($tallas);
sort($onzas);
sort($colores);

$producto['variantes'] = $variantes;
$producto['tallas_disponibles'] = $tallas;
$producto['onzas_disponibles'] = $onzas;
$producto['colores_disponibles'] = $colores;

// Agregar información de stock total para mostrar en la tarjeta
$stock_total = 0;
foreach($variantes as $v) {
    $stock_total += $v['stock'];
}
$producto['stock_total'] = $stock_total;

// Agregar rango de precios
$precios = array_column($variantes, 'precio');
$producto['precio_min'] = !empty($precios) ? min($precios) : 0;
$producto['precio_max'] = !empty($precios) ? max($precios) : 0;

echo json_encode($producto, JSON_UNESCAPED_UNICODE);
?>