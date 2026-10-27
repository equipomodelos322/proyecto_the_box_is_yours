<?php
require_once 'config.php';

header('Content-Type: application/json');

// Ahora recibimos el nombre del producto (PK) en lugar de ID numérico
$nombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';

if(empty($nombre)) {
    echo json_encode(['error' => 'Nombre de producto no proporcionado']);
    exit;
}

$nombre_escaped = $conn->real_escape_string($nombre);

// Consulta adaptada a la tabla productos (sin JOIN, la categoría es texto)
$sql = "SELECT Nombre, Precio, Stock, Categoria, Color, Talla, Onza, imagen
        FROM productos 
        WHERE Nombre = '$nombre_escaped'";

$result = $conn->query($sql);

if(!$result) {
    echo json_encode(['error' => 'Error en consulta: ' . $conn->error]);
    exit;
}

if($result->num_rows == 0) {
    echo json_encode(['error' => 'Producto no encontrado']);
    exit;
}

$producto = $result->fetch_assoc();

// Devolver los datos en un formato similar al esperado por el frontend
// (con nombres de campos en minúscula para compatibilidad)
echo json_encode([
    'id' => $producto['Nombre'],      // Usamos el nombre como identificador
    'nombre' => $producto['Nombre'],
    'precio' => $producto['Precio'],
    'descripcion' => '',               // No hay campo descripción en la tabla
    'imagen' => $producto['imagen'],
    'stock' => $producto['Stock'],
    'categoria_nombre' => $producto['Categoria'],
    'color' => $producto['Color'],     // Campo adicional
    'talla' => $producto['Talla'],
    'onza' => $producto['Onza'],
    'colores' => []                    // Vacío porque no hay múltiples colores
]);
?>