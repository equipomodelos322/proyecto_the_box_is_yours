<?php
require_once 'config.php';

echo "<h2>Diagnóstico de Base de Datos</h2>";

// Verificar conexión
if ($conn->connect_error) {
    echo "❌ Error de conexión: " . $conn->connect_error . "<br>";
} else {
    echo "✅ Conexión exitosa a la base de datos<br><br>";
}

// Verificar tabla Usuarios
$result = $conn->query("SHOW TABLES LIKE 'Usuarios'");
if($result->num_rows > 0) {
    echo "✅ Tabla 'Usuarios' existe<br>";
    
    // Mostrar estructura de la tabla
    echo "<h3>Estructura de la tabla Usuarios:</h3>";
    $columns = $conn->query("DESCRIBE Usuarios");
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>";
    while($col = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Mostrar usuarios existentes
    echo "<h3>Usuarios registrados:</h3>";
    $users = $conn->query("SELECT _Usu, Usu, email, rol, Activo, LEFT(Pass, 20) as Pass_preview FROM Usuarios");
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Activo</th><th>Pass (primeros 20 chars)</th></tr>";
    while($user = $users->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['_Usu'] . "</td>";
        echo "<td>" . $user['Usu'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['rol'] . "</td>";
        echo "<td>" . ($user['Activo'] == 1 ? '✅ Sí' : '❌ No') . "</td>";
        echo "<td>" . $user['Pass_preview'] . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "❌ La tabla 'Usuarios' NO existe<br>";
    echo "Necesitas crear la tabla. Ejecuta este SQL:<br>";
    echo "<pre>
CREATE TABLE IF NOT EXISTS Usuarios (
    _Usu INT AUTO_INCREMENT PRIMARY KEY,
    Usu VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    Pass VARCHAR(255) NOT NULL,
    rol VARCHAR(20) DEFAULT 'cliente',
    fec_reg DATETIME DEFAULT CURRENT_TIMESTAMP,
    Activo TINYINT DEFAULT 1
);
    </pre>";
}
?>