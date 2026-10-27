<?php
require_once 'config.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 'admin') {
    header('Location: login.php');
    exit;
}

$mensaje = '';
$error = '';

// ========== AGREGAR PRODUCTO BASE ==========
if(isset($_POST['agregar_producto_base'])) {
    $nombre_base = $conn->real_escape_string($_POST['nombre_base']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $categoria   = $conn->real_escape_string($_POST['categoria']);

    $imagen_principal = '';
    if(isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] == 0) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)) {
            $new_filename = time() . '_' . uniqid() . '.' . $ext;
            $upload_path  = 'uploads/' . $new_filename;
            if(!is_dir('uploads')) mkdir('uploads', 0777, true);
            if(move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $upload_path)) {
                $imagen_principal = $upload_path;
            }
        }
    }

    $sql = "INSERT INTO productos_base (nombre_base, descripcion, imagen_principal, Categoria)
            VALUES ('$nombre_base', '$descripcion', '$imagen_principal', '$categoria')";
    if($conn->query($sql)) $mensaje = '<div class="alert-success">✅ Producto base agregado correctamente</div>';
    else $error = '<div class="alert-error">❌ Error: ' . $conn->error . '</div>';
}

// ========== EDITAR PRODUCTO BASE (NUEVO) ==========
if(isset($_POST['editar_producto_base'])) {
    $id = (int)$_POST['edit_id'];
    $nombre_base = $conn->real_escape_string($_POST['edit_nombre_base']);
    $descripcion = $conn->real_escape_string($_POST['edit_descripcion']);
    $categoria   = $conn->real_escape_string($_POST['edit_categoria']);
    
    // Verificar si se subió una nueva imagen
    $nueva_imagen_sql = "";
    if(isset($_FILES['edit_imagen_principal']) && $_FILES['edit_imagen_principal']['error'] == 0) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($_FILES['edit_imagen_principal']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)) {
            $new_filename = time() . '_' . uniqid() . '.' . $ext;
            $upload_path  = 'uploads/' . $new_filename;
            if(move_uploaded_file($_FILES['edit_imagen_principal']['tmp_name'], $upload_path)) {
                $nueva_imagen_sql = ", imagen_principal = '$upload_path'";
            }
        }
    }

    $sql = "UPDATE productos_base SET 
                nombre_base = '$nombre_base', 
                descripcion = '$descripcion', 
                Categoria = '$categoria' 
                $nueva_imagen_sql
            WHERE id = $id";
            
    if($conn->query($sql)) $mensaje = '<div class="alert-success">✅ Producto actualizado correctamente</div>';
    else $error = '<div class="alert-error">❌ Error al actualizar: ' . $conn->error . '</div>';
}

// ========== AGREGAR VARIANTE ==========
if(isset($_POST['agregar_variante'])) {
    $producto_base_id    = (int)$_POST['producto_base_id'];
    $categoria_producto  = $conn->real_escape_string($_POST['categoria_producto']);
    $tipo_medida         = $_POST['tipo_medida'];
    $talla = ($tipo_medida == 'talla' && !empty($_POST['talla'])) ? $conn->real_escape_string($_POST['talla']) : null;
    $onza  = ($tipo_medida == 'onza'  && !empty($_POST['onza']))  ? $conn->real_escape_string($_POST['onza'])  : null;
    $color = !empty($_POST['color']) ? $conn->real_escape_string($_POST['color']) : null;
    $precio = floatval($_POST['precio']);
    $stock  = (int)$_POST['stock'];

    $categorias_solo_talla    = ['Botas','Playera','Conchas','Careteas','Tennis'];
    $categorias_solo_onza     = ['Guantes'];
    $categorias_sin_medida    = ['Accesorio','Herramienta','Mochila'];
    $error_validacion = false;

    if(in_array($categoria_producto, $categorias_solo_talla) && $tipo_medida != 'talla') {
        $error = '<div class="alert-error">❌ Esta categoría requiere TALLA</div>'; $error_validacion = true;
    } elseif(in_array($categoria_producto, $categorias_solo_onza) && $tipo_medida != 'onza') {
        $error = '<div class="alert-error">❌ Esta categoría requiere ONZA</div>'; $error_validacion = true;
    } elseif(in_array($categoria_producto, $categorias_sin_medida) && $tipo_medida != 'ninguna') {
        $error = '<div class="alert-error">❌ Esta categoría NO requiere talla ni onza</div>'; $error_validacion = true;
    }

    if(!$error_validacion) {
        $imagen = '';
        if(isset($_FILES['imagen_variante']) && $_FILES['imagen_variante']['error'] == 0) {
            $allowed = ['jpg','jpeg','png','gif','webp'];
            $ext = strtolower(pathinfo($_FILES['imagen_variante']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, $allowed)) {
                $new_filename = time() . '_' . uniqid() . '.' . $ext;
                $upload_path  = 'uploads/' . $new_filename;
                if(move_uploaded_file($_FILES['imagen_variante']['tmp_name'], $upload_path)) $imagen = $upload_path;
            }
        }

        $sql = "INSERT INTO productos_variantes (producto_base_id, talla, onza, color, precio, stock, imagen)
                VALUES ($producto_base_id, " .
                ($talla ? "'$talla'" : "NULL") . ", " .
                ($onza  ? "'$onza'"  : "NULL") . ", " .
                ($color ? "'$color'" : "NULL") . ", $precio, $stock, " .
                ($imagen ? "'$imagen'" : "NULL") . ")";
        if($conn->query($sql)) $mensaje = '<div class="alert-success">✅ Variante agregada correctamente</div>';
        else $error = '<div class="alert-error">❌ Error: ' . $conn->error . '</div>';
    }
}

// ========== ELIMINAR PRODUCTO BASE ==========
if(isset($_GET['eliminar_producto_base'])) {
    $id = (int)$_GET['eliminar_producto_base'];
    $conn->query("DELETE FROM productos_variantes WHERE producto_base_id = $id");
    $conn->query("DELETE FROM productos_base WHERE id = $id");
    header('Location: admin.php'); exit;
}

// ========== ELIMINAR VARIANTE ==========
if(isset($_GET['eliminar_variante'])) {
    $id = (int)$_GET['eliminar_variante'];
    $conn->query("DELETE FROM productos_variantes WHERE id = $id");
    header('Location: admin.php'); exit;
}

// ========== OBTENER DATOS ==========
$categorias    = $conn->query("SELECT Categoria FROM categorias ORDER BY Categoria");
$productos_base = $conn->query("SELECT pb.id, pb.nombre_base, pb.Categoria FROM productos_base pb ORDER BY pb.nombre_base");
$colores       = $conn->query("SELECT DISTINCT color FROM productos_variantes WHERE color IS NOT NULL AND color != '' ORDER BY color");

$productos_con_variantes = $conn->query("
    SELECT pb.*, COUNT(pv.id) AS total_variantes,
           MIN(pv.precio) AS precio_min, MAX(pv.precio) AS precio_max, SUM(pv.stock) AS stock_total
    FROM productos_base pb
    LEFT JOIN productos_variantes pv ON pb.id = pv.producto_base_id
    GROUP BY pb.id ORDER BY pb.nombre_base
");

$tallas_disponibles = ['XS','S','M','L','XL','XXL','3','4','5','6','7','8','9','10','11','12','36','37','38','39','40','41','42','43','44'];
$onzas_disponibles  = ['8 oz','10 oz','12 oz','14 oz','16 oz'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel Admin - The Box Is Yours</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { background:#020617; font-family:'Segoe UI',sans-serif; color:white; }
    .admin-header { background:#112240; padding:1rem 2rem; border-bottom:1px solid #1f3a5f; margin-bottom:2rem; }
    .header-content { display:flex; justify-content:space-between; align-items:center; max-width:1400px; margin:0 auto; flex-wrap:wrap; gap:1rem; }
    .header-content h1 { color:white; font-size:1.5rem; }
    .user-info { color:#00b4d8; font-weight:bold; font-size:0.9rem; }
    .header-buttons { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .btn-nav { padding:8px 16px; border-radius:8px; text-decoration:none; font-weight:bold; color:white; font-size:0.85rem; display:inline-block; }
    .btn-volver   { background:#00b4d8; }
    .btn-salir    { background:#e63946; }
    .btn-usuarios { background:#eab308; color:#03045e !important; }
    .btn-pedidos  { background:#00b4d8; color:#03045e !important; }
    .btn-devs     { background:#ef4444; }
    .btn-coment   { background:#a855f7; }
    .admin-container { max-width:1400px; margin:0 auto; padding:0 2rem; }
    .form-card { background:#0f172a; padding:1.5rem; border-radius:12px; margin-bottom:2rem; border:1px solid #1e293b; }
    .form-card h3 { color:#00b4d8; margin-bottom:1rem; border-bottom:1px solid #1e293b; padding-bottom:0.5rem; }
    .form-group { margin-bottom:1rem; }
    .form-group label { display:block; margin-bottom:0.5rem; color:#cbd5e1; font-weight:bold; }
    .form-group input, .form-group select, .form-group textarea { width:100%; padding:0.8rem; border-radius:8px; border:1px solid #1e293b; background:#020617; color:white; }
    .form-group textarea { resize:vertical; min-height:80px; }
    .form-group select:disabled { opacity:0.5; cursor:not-allowed; }
    .row-2cols { display:flex; gap:1rem; }
    .row-2cols .form-group { flex:1; }
    .btn-primary   { background:#22c55e; color:white; padding:0.8rem 1.5rem; border:none; border-radius:8px; cursor:pointer; font-weight:bold; width:100%; }
    .btn-secondary { background:#00b4d8; color:white; padding:0.8rem 1.5rem; border:none; border-radius:8px; cursor:pointer; font-weight:bold; width:100%; }
    .alert-success { background:rgba(34,197,94,0.2); color:#22c55e; padding:1rem; border-radius:8px; margin-bottom:1rem; border:1px solid #22c55e; }
    .alert-error   { background:rgba(239,68,68,0.2); color:#ef4444; padding:1rem; border-radius:8px; margin-bottom:1rem; border:1px solid #ef4444; }
    table { width:100%; border-collapse:collapse; background:#0f172a; border-radius:12px; overflow:hidden; margin-bottom:1rem; }
    th, td { padding:0.8rem; text-align:left; border-bottom:1px solid #1e293b; }
    th { background:#020617; color:#00b4d8; }
    .btn-danger { background:#ef4444; color:white; padding:0.3rem 0.8rem; border-radius:6px; text-decoration:none; display:inline-block; font-size:0.8rem; }
    .btn-edit { background:#eab308; color:#03045e; padding:0.3rem 0.8rem; border-radius:6px; text-decoration:none; display:inline-block; font-size:0.8rem; font-weight:bold; cursor:pointer; border:none; }
    .variantes-table { margin-top:0.5rem; background:#020617; border-radius:8px; }
    .variantes-table th { background:#0f172a; font-size:0.8rem; }
    .variantes-table td { font-size:0.8rem; padding:0.5rem; }
    .producto-card { margin-bottom:1.5rem; border:1px solid #1e293b; border-radius:12px; overflow:hidden; }
    .producto-header { background:#112240; padding:1rem; cursor:pointer; display:flex; justify-content:space-between; align-items:center; }
    .producto-body { padding:1rem; display:none; }
    .producto-body.show { display:block; }
    .badge { display:inline-block; padding:0.2rem 0.5rem; border-radius:20px; font-size:0.7rem; font-weight:bold; }
    .info-categoria { font-size:0.8rem; color:#00b4d8; margin-top:0.3rem; }
    .medida-info { background:#1e293b; padding:0.3rem 0.6rem; border-radius:6px; font-size:0.75rem; color:#cbd5e1; }
    
    /* MODAL PARA EDITAR */
    .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.8); }
    .modal-content { background:#0f172a; margin:5% auto; padding:2rem; border-radius:12px; width:90%; max-width:600px; position:relative; border:1px solid #1e293b; }
    .close-modal { position:absolute; top:10px; right:20px; font-size:2rem; cursor:pointer; color:#cbd5e1; }
</style>
</head>
<body>

<header class="admin-header">
    <div class="header-content">
        <h1>⚙️ Panel de Administración</h1>
        <div class="header-buttons">
            <span class="user-info">👑 <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
            <a href="registro.php" class="btn-nav btn-usuarios">👥 Usuarios</a>
            <a href="admin_pedidos.php" class="btn-nav btn-pedidos">📦 Pedidos</a>
            <a href="admin_devoluciones.php" class="btn-nav btn-devs">↩️ Devoluciones</a>
            <a href="admin_comentarios.php" class="btn-nav btn-coment">💬 Comentarios</a>
            <a href="index.php" class="btn-nav btn-volver" target="_blank">Ver Tienda</a>
            <a href="logout.php" class="btn-nav btn-salir">Salir</a>
        </div>
    </div>
</header>

<div class="admin-container">
    <?php if($mensaje): echo $mensaje; endif; ?>
    <?php if($error): echo $error; endif; ?>

    <div class="form-card">
        <h3>➕ Agregar Nuevo Producto Base</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Nombre del producto *</label>
                <input type="text" name="nombre_base" required placeholder="Ej: Guantes Venum Elite">
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" placeholder="Descripción del producto..."></textarea>
            </div>
            <div class="row-2cols">
                <div class="form-group">
                    <label>Categoría *</label>
                    <select name="categoria" required>
                        <option value="">Selecciona una categoría</option>
                        <?php if($categorias && $categorias->num_rows > 0): while($row = $categorias->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['Categoria']); ?>"><?php echo htmlspecialchars($row['Categoria']); ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Imagen principal</label>
                    <input type="file" name="imagen_principal" accept="image/*">
                </div>
            </div>
            <button type="submit" name="agregar_producto_base" class="btn-primary">💾 Guardar Producto Base</button>
        </form>
    </div>

    <div class="form-card">
        <h3>➕ Agregar Variante a Producto</h3>
        <form method="POST" enctype="multipart/form-data" id="formVariante">
            <div class="row-2cols">
                <div class="form-group">
                    <label>Producto *</label>
                    <select name="producto_base_id" id="producto_base_id" required>
                        <option value="">Selecciona un producto</option>
                        <?php if($productos_base && $productos_base->num_rows > 0): $productos_base->data_seek(0); while($row = $productos_base->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>" data-categoria="<?php echo htmlspecialchars($row['Categoria'] ?? ''); ?>">
                                <?php echo htmlspecialchars($row['nombre_base']); ?> (<?php echo htmlspecialchars($row['Categoria'] ?? 'Sin cat'); ?>)
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Precio *</label>
                    <input type="number" step="0.01" name="precio" required placeholder="0.00">
                </div>
            </div>

            <input type="hidden" name="categoria_producto" id="categoria_producto">
            <input type="hidden" name="tipo_medida" id="tipo_medida">

            <div class="row-2cols">
                <div class="form-group">
                    <label>Stock *</label>
                    <input type="number" name="stock" value="10" required>
                </div>
                <div class="form-group">
                    <label>Imagen de variante (opcional)</label>
                    <input type="file" name="imagen_variante" accept="image/*">
                </div>
            </div>

            <div id="info-categoria-seleccionada" class="info-categoria" style="margin-bottom:1rem;"></div>

            <div class="row-2cols">
                <div class="form-group">
                    <label>Talla</label>
                    <select name="talla" id="select_talla">
                        <option value="">-- Sin talla --</option>
                        <?php foreach($tallas_disponibles as $talla): ?>
                            <option value="<?php echo htmlspecialchars($talla); ?>"><?php echo htmlspecialchars($talla); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Onza</label>
                    <select name="onza" id="select_onza">
                        <option value="">-- Sin onza --</option>
                        <?php foreach($onzas_disponibles as $onza): ?>
                            <option value="<?php echo htmlspecialchars($onza); ?>"><?php echo htmlspecialchars($onza); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row-2cols">
                <div class="form-group">
                    <label>Color</label>
                    <select name="color">
                        <option value="">-- Sin color --</option>
                        <?php if($colores && $colores->num_rows > 0): $colores->data_seek(0); while($row = $colores->fetch_assoc()): if($row['color'] && $row['color'] != ''): ?>
                            <option value="<?php echo htmlspecialchars($row['color']); ?>"><?php echo htmlspecialchars($row['color']); ?></option>
                        <?php endif; endwhile; endif; ?>
                        <option value="Rojo">Rojo</option>
                        <option value="Azul">Azul</option>
                        <option value="Negro">Negro</option>
                        <option value="Blanco">Blanco</option>
                        <option value="Gris">Gris</option>
                    </select>
                </div>
                <div class="form-group"><label>&nbsp;</label></div>
            </div>

            <button type="submit" name="agregar_variante" class="btn-secondary">➕ Agregar Variante</button>
        </form>
    </div>

    <h3 style="color:#00b4d8; margin-bottom:1rem;">📦 Productos Registrados</h3>

    <?php if($productos_con_variantes && $productos_con_variantes->num_rows > 0): while($producto = $productos_con_variantes->fetch_assoc()):
        $variantes = $conn->query("SELECT * FROM productos_variantes WHERE producto_base_id = {$producto['id']}");
        $total_variantes = $variantes ? $variantes->num_rows : 0;
    ?>
    <div class="producto-card">
        <div class="producto-header">
            <div onclick="toggleProducto(<?php echo $producto['id']; ?>)" style="flex:1; cursor:pointer;">
                <strong style="color:#00b4d8;"><?php echo htmlspecialchars($producto['nombre_base']); ?></strong>
                <?php if($producto['Categoria']): ?>
                    <span class="badge" style="background:#1e3a5f; margin-left:0.5rem;"><?php echo htmlspecialchars($producto['Categoria']); ?></span>
                <?php endif; ?>
                <div style="font-size:0.8rem; color:#64748b; margin-top:0.3rem;">
                    💰 $<?php echo number_format($producto['precio_min'] ?? 0, 2); ?> - $<?php echo number_format($producto['precio_max'] ?? 0, 2); ?> |
                    📦 Stock: <?php echo $producto['stock_total'] ?? 0; ?> |
                    🔄 Variantes: <?php echo $total_variantes; ?>
                </div>
            </div>
            <div style="display:flex; gap:0.5rem;">
                <!-- BOTÓN DE EDITAR -->
                <button class="btn-edit" onclick='abrirModalEditar(<?php echo json_encode($producto); ?>)'>✏️ Editar</button>
                <a href="?eliminar_producto_base=<?php echo $producto['id']; ?>" class="btn-danger" onclick="return confirm('¿Eliminar este producto y todas sus variantes?')">🗑️ Eliminar</a>
            </div>
        </div>
        <div class="producto-body" id="producto-<?php echo $producto['id']; ?>">
            <?php if($producto['imagen_principal']): ?>
                <div style="margin-bottom:1rem;"><img src="<?php echo $producto['imagen_principal']; ?>" style="width:80px;height:80px;object-fit:cover;border-radius:8px;"></div>
            <?php endif; ?>
            <?php if($producto['descripcion']): ?>
                <p style="color:#94a3b8; margin-bottom:1rem;"><?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?></p>
            <?php endif; ?>
            <?php if($total_variantes > 0): ?>
                <table class="variantes-table">
                    <thead>
                        <tr><th>ID</th><th>Talla</th><th>Onza</th><th>Color</th><th>Precio</th><th>Stock</th><th>Imagen</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php $variantes->data_seek(0); while($variante = $variantes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $variante['id']; ?></td>
                            <td><?php echo $variante['talla'] ?: '—'; ?></td>
                            <td><?php echo $variante['onza'] ?: '—'; ?></td>
                            <td><?php echo $variante['color'] ?: '—'; ?></td>
                            <td>$<?php echo number_format($variante['precio'], 2); ?></td>
                            <td><?php echo $variante['stock']; ?></td>
                            <td><?php if($variante['imagen']): ?><img src="<?php echo $variante['imagen']; ?>" style="width:30px;height:30px;object-fit:cover;border-radius:4px;"><?php else: ?>—<?php endif; ?></td>
                            <td><a href="?eliminar_variante=<?php echo $variante['id']; ?>" class="btn-danger" style="padding:0.2rem 0.5rem;" onclick="return confirm('¿Eliminar esta variante?')">🗑️</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color:#64748b;">⚠️ No hay variantes para este producto.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endwhile; else: ?>
        <p style="color:#64748b;">No hay productos registrados aún.</p>
    <?php endif; ?>
</div>

<!-- MODAL PARA EDITAR PRODUCTO BASE -->
<div id="modalEditar" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="cerrarModalEditar()">&times;</span>
        <h3 style="color:#00b4d8; margin-bottom:1.5rem;">✏️ Editar Producto</h3>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="edit_id" id="edit_id">
            
            <div class="form-group">
                <label>Nombre del producto *</label>
                <input type="text" name="edit_nombre_base" id="edit_nombre_base" required>
            </div>
            
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="edit_descripcion" id="edit_descripcion"></textarea>
            </div>
            
            <div class="form-group">
                <label>Categoría *</label>
                <select name="edit_categoria" id="edit_categoria" required>
                    <option value="">Selecciona una categoría</option>
                    <?php 
                    $categorias->data_seek(0);
                    while($row = $categorias->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($row['Categoria']); ?>"><?php echo htmlspecialchars($row['Categoria']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Cambiar Imagen Principal (opcional)</label>
                <input type="file" name="edit_imagen_principal" accept="image/*">
                <small style="color:#64748b; font-size:0.75rem; display:block; margin-top:0.3rem;">Si no seleccionas nada, se mantendrá la imagen actual.</small>
            </div>
            
            <button type="submit" name="editar_producto_base" class="btn-primary">💾 Guardar Cambios</button>
        </form>
    </div>
</div>

<script>
    // ==== LÓGICA DE EDICIÓN ====
    function abrirModalEditar(producto) {
        document.getElementById('edit_id').value = producto.id;
        document.getElementById('edit_nombre_base').value = producto.nombre_base;
        document.getElementById('edit_descripcion').value = producto.descripcion || '';
        document.getElementById('edit_categoria').value = producto.Categoria || '';
        document.getElementById('modalEditar').style.display = 'block';
    }
    
    function cerrarModalEditar() {
        document.getElementById('modalEditar').style.display = 'none';
    }

    // ==== LÓGICA DE VARIANTES ====
    const soloTalla  = ['Botas','Playera','Conchas','Careteas','Tennis'];
    const soloOnza   = ['Guantes'];
    const sinMedida  = ['Accesorio','Herramienta','Mochila'];

    const selectProducto = document.getElementById('producto_base_id');
    const selectTalla    = document.getElementById('select_talla');
    const selectOnza     = document.getElementById('select_onza');
    const inputCategoria = document.getElementById('categoria_producto');
    const inputTipoMedida= document.getElementById('tipo_medida');
    const infoCategoria  = document.getElementById('info-categoria-seleccionada');

    function actualizarCombosPorCategoria() {
        const selectedOption = selectProducto.options[selectProducto.selectedIndex];
        const categoria = selectedOption ? selectedOption.getAttribute('data-categoria') : '';
        inputCategoria.value = categoria;
        if (!categoria) {
            selectTalla.disabled = false; selectOnza.disabled = false;
            selectTalla.value = ''; selectOnza.value = '';
            infoCategoria.innerHTML = ''; inputTipoMedida.value = '';
            return;
        }
        infoCategoria.innerHTML = `<span class="medida-info">📁 Categoría: <strong>${categoria}</strong></span>`;
        if (soloTalla.includes(categoria)) {
            selectTalla.disabled = false; selectOnza.disabled = true; selectOnza.value = '';
            inputTipoMedida.value = 'talla';
            infoCategoria.innerHTML += `<span class="medida-info" style="margin-left:0.5rem;">📏 Requiere TALLA</span>`;
        } else if (soloOnza.includes(categoria)) {
            selectTalla.disabled = true; selectOnza.disabled = false; selectTalla.value = '';
            inputTipoMedida.value = 'onza';
            infoCategoria.innerHTML += `<span class="medida-info" style="margin-left:0.5rem;">⚖️ Requiere ONZA</span>`;
        } else if (sinMedida.includes(categoria)) {
            selectTalla.disabled = true; selectOnza.disabled = true;
            selectTalla.value = ''; selectOnza.value = '';
            inputTipoMedida.value = 'ninguna';
            infoCategoria.innerHTML += `<span class="medida-info" style="margin-left:0.5rem;">🚫 No requiere talla ni onza</span>`;
        } else {
            selectTalla.disabled = false; selectOnza.disabled = false;
            inputTipoMedida.value = '';
            infoCategoria.innerHTML += `<span class="medida-info" style="margin-left:0.5rem;">📌 Selecciona talla O onza</span>`;
        }
    }

    function validarSeleccionUnica() {
        const categoria = inputCategoria.value;
        if (!soloTalla.includes(categoria) && !soloOnza.includes(categoria) && !sinMedida.includes(categoria)) {
            if (selectTalla.value && selectOnza.value) {
                alert('⚠️ Solo puedes seleccionar TALLA o ONZA, no ambas.');
                if (document.activeElement === selectTalla) selectOnza.value = '';
                else selectTalla.value = '';
            }
        }
    }

    selectProducto.addEventListener('change', actualizarCombosPorCategoria);
    selectTalla.addEventListener('change', validarSeleccionUnica);
    selectOnza.addEventListener('change', validarSeleccionUnica);

    function toggleProducto(id) {
        document.getElementById('producto-' + id).classList.toggle('show');
    }
    actualizarCombosPorCategoria();
</script>
</body>
</html>