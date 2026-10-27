<?php
session_start();
require_once 'config.php';

// Verificar que se haya enviado un archivo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['comprobante'])) {
    
    // Verificar que el pedido_id esté presente
    if(!isset($_POST['pedido_id']) || empty($_POST['pedido_id'])) {
        $_SESSION['error'] = "❌ ID de pedido no especificado";
        header("Location: mis_pedidos.php");
        exit;
    }
    
    $pedido_id = (int)$_POST['pedido_id'];
    
    // Verificar que el archivo se haya subido correctamente
    if($_FILES['comprobante']['error'] != UPLOAD_ERR_OK) {
        $errores = [
            UPLOAD_ERR_INI_SIZE => "El archivo excede el tamaño máximo permitido por el servidor.",
            UPLOAD_ERR_FORM_SIZE => "El archivo excede el tamaño máximo del formulario.",
            UPLOAD_ERR_PARTIAL => "El archivo se subió parcialmente.",
            UPLOAD_ERR_NO_FILE => "No se seleccionó ningún archivo.",
            UPLOAD_ERR_NO_TMP_DIR => "Falta la carpeta temporal.",
            UPLOAD_ERR_CANT_WRITE => "Error al escribir el archivo en el disco.",
            UPLOAD_ERR_EXTENSION => "Extensión de archivo no permitida."
        ];
        $mensaje_error = $errores[$_FILES['comprobante']['error']] ?? "Error desconocido al subir el archivo.";
        $_SESSION['error'] = "❌ " . $mensaje_error;
        header("Location: mis_pedidos.php");
        exit;
    }
    
    // Crear directorio si no existe
    if (!is_dir('uploads/comprobantes')) {
        mkdir('uploads/comprobantes', 0777, true);
    }
    
    // Validar extensión del archivo
    $ext = strtolower(pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION));
    $extensions_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    
    if (!in_array($ext, $extensions_permitidas)) {
        $_SESSION['error'] = "❌ Formato no permitido. Solo imágenes (JPG, PNG, GIF, WEBP) y PDF.";
        header("Location: mis_pedidos.php");
        exit;
    }
    
    // Validar tamaño del archivo (máximo 5MB)
    if($_FILES['comprobante']['size'] > 5 * 1024 * 1024) {
        $_SESSION['error'] = "❌ El archivo es demasiado grande. Máximo 5MB.";
        header("Location: mis_pedidos.php");
        exit;
    }
    
    // Generar nombre único para el archivo
    $nombre_archivo = 'comprobante_' . $pedido_id . '_' . time() . '_' . uniqid() . '.' . $ext;
    $ruta = 'uploads/comprobantes/' . $nombre_archivo;
    
    // Mover el archivo
    if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $ruta)) {
        // Actualizar la columna Comprobante en la tabla Pedidos y cambiar estado a 1 (En revisión)
        $stmt = $conn->prepare("UPDATE Pedidos SET Comprobante = ?, Estado = 1 WHERE _Pedido = ?");
        $stmt->bind_param("si", $ruta, $pedido_id);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "✅ Comprobante subido correctamente. Tu pedido está en revisión.";
        } else {
            $_SESSION['error'] = "❌ Error al actualizar el pedido: " . $conn->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "❌ Error al mover el archivo. Verifica los permisos de la carpeta uploads/";
    }
    
    // Redirigir a mis_pedidos.php
    header("Location: mis_pedidos.php");
    exit;
} else {
    // Si no es una petición POST válida
    $_SESSION['error'] = "❌ Solicitud inválida. Por favor, intenta nuevamente.";
    header("Location: mis_pedidos.php");
    exit;
}
?>