<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

// Forzar muestra de errores (por si algo falla en la consulta)
ini_set('display_errors', 0); 
error_reporting(E_ALL);

function limpiarUtf8($valor) {
    if (is_null($valor)) return null;
    if (is_string($valor)) {
        return mb_convert_encoding($valor, 'UTF-8', 'UTF-8');
    }
    return $valor;
}

$sql = "SELECT 
            b.id,
            b.nombre_base,
            b.descripcion,
            b.imagen_principal,
            b.Categoria,
            MIN(v.precio) AS precio_min,
            MAX(v.precio) AS precio_max,
            SUM(v.stock) AS stock_total
        FROM productos_base b
        LEFT JOIN productos_variantes v ON b.id = v.producto_base_id
        GROUP BY b.id
        ORDER BY b.id DESC";

$result = $conn->query($sql);

if (!$result) {
    // Si la consulta falla, devolvemos el error en formato JSON para que JS lo lea
    echo json_encode(['error' => 'Error en la base de datos: ' . $conn->error]);
    exit;
}

$productos = [];
while ($row = $result->fetch_assoc()) {
    $row['nombre_base'] = limpiarUtf8($row['nombre_base']);
    $row['descripcion'] = limpiarUtf8($row['descripcion']);
    $row['imagen_principal'] = limpiarUtf8($row['imagen_principal']);
    $row['Categoria'] = limpiarUtf8($row['Categoria']);
    $row['precio_min'] = (float)$row['precio_min'];
    $row['precio_max'] = (float)$row['precio_max'];
    $row['stock_total'] = (int)$row['stock_total'];
    $productos[] = $row;
}

echo json_encode($productos, JSON_UNESCAPED_UNICODE);
?>