<?php
$servername = "sql310.infinityfree.com";
$username = "if0_43286977";
$password = "KipiTech";
$dbname = "if0_43286977_AngelCastillo";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

function isLoggedIn() { return isset($_SESSION['user_id']); }
function isAdmin() { return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 'admin'; }
function isCliente() { return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 'cliente'; }
function requireLogin() { if(!isLoggedIn()) { header('Location: login.php'); exit; } }
function requireAdmin() { if(!isLoggedIn() || !isAdmin()) { header('Location: login.php'); exit; } }
?>