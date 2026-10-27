<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$id_actual = (int)($_GET['id'] ?? 0);
if($id_actual <= 0) { echo json_encode([]); exit; }

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Categoría actual
$stmt = $conn->prepare("SELECT Categoria FROM productos_base WHERE id = ?");
$stmt->bind_param("i", $id_actual);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$categoria_actual = $row['Categoria'] ?? '';

$recomendaciones = [];
$vistos = [$id_actual => true];
$limite = 4;

// ---------- 1) Complementarios explícitos ----------
$stmt = $conn->prepare("
    SELECT pb.id, pb.nombre_base, pb.imagen_principal, pb.Categoria, MIN(pv.precio) AS precio_min
    FROM complementarios c
    INNER JOIN productos_base pb ON pb.id = c.complementario_id
    LEFT JOIN productos_variantes pv ON pv.producto_base_id = pb.id
    WHERE c.producto_base_id = ?
    GROUP BY pb.id
    ORDER BY RAND()
    LIMIT $limite
");
$stmt->bind_param("i", $id_actual);
$stmt->execute();
$res = $stmt->get_result();
while($r = $res->fetch_assoc()) {
    if(isset($vistos[$r['id']])) continue;
    $r['_fuente'] = 'complementario';
    $recomendaciones[] = $r; $vistos[$r['id']] = true;
}
$stmt->close();

// ---------- 2) Compras previas ----------
if($uid && count($recomendaciones) < $limite) {
    $stmt = $conn->prepare("SELECT Nombre FROM pedidos WHERE _Usu = ? AND Nombre IS NOT NULL");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $nombres_comprados = [];
    while($p = $res->fetch_assoc()) {
        $arr = json_decode($p['Nombre'], true);
        if(is_array($arr)) {
            foreach($arr as $item) {
                if(is_array($item) && !empty($item['nombre'])) {
                    $n = preg_split('/\s+-\s+|\s+\(/', $item['nombre'])[0] ?? '';
                    if($n) $nombres_comprados[] = trim($n);
                }
            }
        }
    }
    $stmt->close();

    if(!empty($nombres_comprados)) {
        $nombres_comprados = array_unique($nombres_comprados);
        $ph = implode(',', array_fill(0, count($nombres_comprados), '?'));
        $types = str_repeat('s', count($nombres_comprados));
        $stmt = $conn->prepare("SELECT DISTINCT Categoria FROM productos_base WHERE nombre_base IN ($ph)");
        $stmt->bind_param($types, ...$nombres_comprados);
        $stmt->execute();
        $res = $stmt->get_result();
        $categorias_compradas = [];
        while($r = $res->fetch_assoc()) {
            if($r['Categoria']) $categorias_compradas[] = $r['Categoria'];
        }
        $stmt->close();

        if(!empty($categorias_compradas)) {
            $faltan = $limite - count($recomendaciones);
            $ph2 = implode(',', array_fill(0, count($categorias_compradas), '?'));
            $types2 = str_repeat('s', count($categorias_compradas));
            $q2 = "SELECT pb.id, pb.nombre_base, pb.imagen_principal, pb.Categoria, MIN(pv.precio) AS precio_min
                   FROM productos_base pb
                   LEFT JOIN productos_variantes pv ON pv.producto_base_id = pb.id
                   WHERE pb.Categoria IN ($ph2) AND pb.id <> ?
                   GROUP BY pb.id ORDER BY RAND() LIMIT $faltan";
            $stmt = $conn->prepare($q2);
            $params = array_merge($categorias_compradas, [$id_actual]);
            $stmt->bind_param($types2 . 'i', ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            while($r = $res->fetch_assoc()) {
                if(isset($vistos[$r['id']])) continue;
                $r['_fuente'] = 'compra_previa';
                $recomendaciones[] = $r; $vistos[$r['id']] = true;
            }
            $stmt->close();
        }
    }
}

// ---------- 3) Navegación ----------
if($uid && count($recomendaciones) < $limite) {
    $stmt = $conn->prepare("
        SELECT DISTINCT pb.Categoria FROM navegacion_historial n
        INNER JOIN productos_base pb ON pb.id = n.producto_base_id
        WHERE n.usuario_id = ? AND pb.Categoria IS NOT NULL LIMIT 10
    ");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $cat_nav = [];
    while($r = $res->fetch_assoc()) $cat_nav[] = $r['Categoria'];
    $stmt->close();

    if(!empty($cat_nav)) {
        $faltan = $limite - count($recomendaciones);
        $ph = implode(',', array_fill(0, count($cat_nav), '?'));
        $types = str_repeat('s', count($cat_nav));
        $q = "SELECT pb.id, pb.nombre_base, pb.imagen_principal, pb.Categoria, MIN(pv.precio) AS precio_min
              FROM productos_base pb
              LEFT JOIN productos_variantes pv ON pv.producto_base_id = pb.id
              WHERE pb.Categoria IN ($ph) AND pb.id <> ?
              GROUP BY pb.id ORDER BY RAND() LIMIT $faltan";
        $stmt = $conn->prepare($q);
        $params = array_merge($cat_nav, [$id_actual]);
        $stmt->bind_param($types . 'i', ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while($r = $res->fetch_assoc()) {
            if(isset($vistos[$r['id']])) continue;
            $r['_fuente'] = 'navegacion';
            $recomendaciones[] = $r; $vistos[$r['id']] = true;
        }
        $stmt->close();
    }
}

// ---------- 4) Misma categoría ----------
if(count($recomendaciones) < $limite && $categoria_actual) {
    $faltan = $limite - count($recomendaciones);
    $stmt = $conn->prepare("
        SELECT pb.id, pb.nombre_base, pb.imagen_principal, pb.Categoria, MIN(pv.precio) AS precio_min
        FROM productos_base pb
        LEFT JOIN productos_variantes pv ON pv.producto_base_id = pb.id
        WHERE pb.Categoria = ? AND pb.id <> ?
        GROUP BY pb.id ORDER BY RAND() LIMIT $faltan
    ");
    $stmt->bind_param("si", $categoria_actual, $id_actual);
    $stmt->execute();
    $res = $stmt->get_result();
    while($r = $res->fetch_assoc()) {
        if(isset($vistos[$r['id']])) continue;
        $r['_fuente'] = 'categoria';
        $recomendaciones[] = $r; $vistos[$r['id']] = true;
    }
    $stmt->close();
}

// ---------- 5) Populares ----------
if(count($recomendaciones) < $limite) {
    $faltan = $limite - count($recomendaciones);
    $stmt = $conn->prepare("
        SELECT pb.id, pb.nombre_base, pb.imagen_principal, pb.Categoria, MIN(pv.precio) AS precio_min,
               (SELECT COUNT(*) FROM likes_producto l WHERE l.producto_base_id = pb.id) AS num_likes
        FROM productos_base pb
        LEFT JOIN productos_variantes pv ON pv.producto_base_id = pb.id
        WHERE pb.id <> ?
        GROUP BY pb.id ORDER BY num_likes DESC, RAND() LIMIT $faltan
    ");
    $stmt->bind_param("i", $id_actual);
    $stmt->execute();
    $res = $stmt->get_result();
    while($r = $res->fetch_assoc()) {
        if(isset($vistos[$r['id']])) continue;
        $r['_fuente'] = 'popular';
        $recomendaciones[] = $r; $vistos[$r['id']] = true;
    }
    $stmt->close();
}

echo json_encode(array_slice($recomendaciones, 0, $limite), JSON_UNESCAPED_UNICODE);