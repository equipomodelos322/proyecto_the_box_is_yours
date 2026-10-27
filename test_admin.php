<?php
// Forzar la muestra de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>1. Probando config.php...</h2>";
require_once 'config.php';
echo "✅ config.php cargado correctamente.<br>";

echo "<h2>2. Probando conexión a la base de datos...</h2>";
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}
echo "✅ Conexión exitosa a la base de datos: " . $dbname . "<br>";

echo "<h2>3. Iniciando sesión...</h2>";
session_start();
echo "✅ Sesión iniciada.<br>";

echo "<h2>4. Probando tablas...</h2>";
$res = $conn->query("SELECT COUNT(*) as total FROM productos_base");
if($res) {
    $row = $res->fetch_assoc();
    echo "✅ La tabla 'productos_base' existe y tiene " . $row['total'] . " productos.<br>";
} else {
    echo "❌ Error al leer 'productos_base': " . $conn->error . "<br>";
}

$res2 = $conn->query("SELECT COUNT(*) as total FROM usuarios");
if($res2) {
    $row2 = $res2->fetch_assoc();
    echo "✅ La tabla 'usuarios' existe y tiene " . $row2['total'] . " usuarios.<br>";
} else {
    echo "❌ Error al leer 'usuarios': " . $conn->error . "<br>";
}

echo "<h2>5. Probando variables de sesión...</h2>";
$_SESSION['user_id'] = 3; // Simulamos que eres el admin
$_SESSION['user_rol'] = 'admin';
$_SESSION['user_name'] = 'Administrador Test';
echo "✅ Variables de sesión asignadas.<br>";

echo "<h2>🎉 Todo parece estar bien en la base de datos y la sesión.</h2>";
echo "Si ves esta pantalla, el problema está en el código de admin.php.";
?>