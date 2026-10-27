<?php
require_once 'config.php';

$email = 'admin@theboxisyours.com';
$pass_plana = 'admin123';

// Esto genera el hash correcto para "admin123"
$nuevo_hash = password_hash($pass_plana, PASSWORD_DEFAULT);

// Actualizamos la base de datos
$stmt = $conn->prepare("UPDATE usuarios SET Pass = ?, rol = 'admin', Activo = 1 WHERE email = ?");
$stmt->bind_param("ss", $nuevo_hash, $email);

if ($stmt->execute()) {
    echo "<h2>✅ ¡Contraseña actualizada con éxito!</h2>";
    echo "Ahora puedes ir a <a href='login.php'>login.php</a> e iniciar sesión.<br><br>";
    echo "<strong>Correo:</strong> admin@theboxisyours.com<br>";
    echo "<strong>Contraseña:</strong> admin123";
} else {
    echo "Error al actualizar: " . $conn->error;
}
$stmt->close();
?>