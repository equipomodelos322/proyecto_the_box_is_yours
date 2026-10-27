<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

echo "<h2>1. Verificar columnas de la tabla Productos</h2>";
$result = $conn->query("DESCRIBE Productos");
if (!$result) {
    echo "Error: " . $conn->error;
} else {
    echo "<table border='1'><tr><th>Campo</th><th>Tipo</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
}

echo "<h2>2. Probar SELECT con las columnas exactas</h2>";
$sql = "SELECT Nombre, Precio, Stock FROM Productos";
$result = $conn->query($sql);
if (!$result) {
    echo "Error en consulta: " . $conn->error;
} else {
    echo "Número de filas: " . $result->num_rows . "<br>";
    if($result->num_rows > 0) {
        echo "<pre>";
        while($row = $result->fetch_assoc()) {
            print_r($row);
        }
        echo "</pre>";
    } else {
        echo "No se encontraron filas";
    }
}

echo "<h2>3. Verificar si la conexión es correcta</h2>";
echo "Conexión: " . ($conn->ping() ? "OK" : "Falló");
?>