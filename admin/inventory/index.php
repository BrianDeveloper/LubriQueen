<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

$message = '';
$messageType = '';

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                if (isset($_POST['nombre'], $_POST['categoria_id'], $_POST['precio'], $_POST['stock'])) {
                    $nombre = trim($_POST['nombre']);
                    $categoria_id = (int)$_POST['categoria_id'];
                    $descripcion = trim($_POST['descripcion'] ?? '');
                    $precio = (float)$_POST['precio'];
                    $stock = (int)$_POST['stock'];
                    $imagen = null;

                    // Procesar imagen si se subió una
                    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../../uploads/products/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }

                        $fileInfo = pathinfo($_FILES['imagen']['name']);
                        $extension = strtolower($fileInfo['extension']);
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                        $maxFileSize = 2 * 1024 * 1024; // 2MB en bytes

                        if ($_FILES['imagen']['size'] > $maxFileSize) {
                            $message = "El archivo es demasiado grande. El tamaño máximo permitido es 2MB.";
                            $messageType = "error";
                            break;
                        }

                        if (in_array($extension, $allowedExtensions)) {
                            $imagen = uniqid() . '.' . $extension;
                            $uploadFile = $uploadDir . $imagen;

                            // Procesar y redimensionar la imagen
                            list($width, $height) = getimagesize($_FILES['imagen']['tmp_name']);
                            $maxWidth = 800;
                            $maxHeight = 800;

                            if ($width > $maxWidth || $height > $maxHeight) {
                                // Calcular nuevas dimensiones manteniendo proporción
                                if ($width > $height) {
                                    $newWidth = $maxWidth;
                                    $newHeight = ($height / $width) * $maxWidth;
                                } else {
                                    $newHeight = $maxHeight;
                                    $newWidth = ($width / $height) * $maxHeight;
                                }

                                // Crear nueva imagen
                                $sourceImage = imagecreatefromstring(file_get_contents($_FILES['imagen']['tmp_name']));
                                $newImage = imagecreatetruecolor($newWidth, $newHeight);

                                // Preservar transparencia para PNG
                                if ($extension === 'png') {
                                    imagealphablending($newImage, false);
                                    imagesavealpha($newImage, true);
                                }

                                // Redimensionar
                                imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                                // Guardar imagen
                                switch($extension) {
                                    case 'jpg':
                                    case 'jpeg':
                                        imagejpeg($newImage, $uploadFile, 85);
                                        break;
                                    case 'png':
                                        imagepng($newImage, $uploadFile, 8);
                                        break;
                                    case 'gif':
                                        imagegif($newImage, $uploadFile);
                                        break;
                                }

                                imagedestroy($sourceImage);
                                imagedestroy($newImage);
                            } else {
                                // Si la imagen es más pequeña que el máximo, solo moverla
                                move_uploaded_file($_FILES['imagen']['tmp_name'], $uploadFile);
                            }
                        } else {
                            $message = "Tipo de archivo no permitido. Solo se permiten imágenes JPG, JPEG, PNG y GIF";
                            $messageType = "error";
                            break;
                        }
                    }

                    try {
                        $stmt = $conn->prepare("INSERT INTO productos (nombre, categoria_id, descripcion, precio, stock, imagen) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$nombre, $categoria_id, $descripcion, $precio, $stock, $imagen]);
                        $message = "Producto creado exitosamente";
                        $messageType = "success";
                    } catch (PDOException $e) {
                        $message = "Error al crear el producto: " . $e->getMessage();
                        $messageType = "error";
                    }
                }
                break;

            case 'update':
                if (isset($_POST['id'], $_POST['nombre'], $_POST['categoria_id'], $_POST['precio'], $_POST['stock'])) {
                    $id = (int)$_POST['id'];
                    $nombre = trim($_POST['nombre']);
                    $categoria_id = (int)$_POST['categoria_id'];
                    $descripcion = trim($_POST['descripcion'] ?? '');
                    $precio = (float)$_POST['precio'];
                    $stock = (int)$_POST['stock'];

                    try {
                        // Primero obtener la imagen actual
                        $stmt = $conn->prepare("SELECT imagen FROM productos WHERE id = ?");
                        $stmt->execute([$id]);
                        $producto = $stmt->fetch();
                        $imagen = $producto['imagen'];

                        // Procesar nueva imagen si se subió una
                        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                            $uploadDir = '../../uploads/products/';
                            if (!file_exists($uploadDir)) {
                                mkdir($uploadDir, 0777, true);
                            }

                            $fileInfo = pathinfo($_FILES['imagen']['name']);
                            $extension = strtolower($fileInfo['extension']);
                            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                            $maxFileSize = 2 * 1024 * 1024; // 2MB en bytes

                            if ($_FILES['imagen']['size'] > $maxFileSize) {
                                $message = "El archivo es demasiado grande. El tamaño máximo permitido es 2MB.";
                                $messageType = "error";
                                break;
                            }

                            if (in_array($extension, $allowedExtensions)) {
                                // Eliminar imagen anterior si existe
                                if ($imagen && file_exists($uploadDir . $imagen)) {
                                    unlink($uploadDir . $imagen);
                                }

                                $imagen = uniqid() . '.' . $extension;
                                $uploadFile = $uploadDir . $imagen;

                                // Procesar y redimensionar la imagen
                                list($width, $height) = getimagesize($_FILES['imagen']['tmp_name']);
                                $maxWidth = 800;
                                $maxHeight = 800;

                                if ($width > $maxWidth || $height > $maxHeight) {
                                    // Calcular nuevas dimensiones manteniendo proporción
                                    if ($width > $height) {
                                        $newWidth = $maxWidth;
                                        $newHeight = ($height / $width) * $maxWidth;
                                    } else {
                                        $newHeight = $maxHeight;
                                        $newWidth = ($width / $height) * $maxHeight;
                                    }

                                    // Crear nueva imagen
                                    $sourceImage = imagecreatefromstring(file_get_contents($_FILES['imagen']['tmp_name']));
                                    $newImage = imagecreatetruecolor($newWidth, $newHeight);

                                    // Preservar transparencia para PNG
                                    if ($extension === 'png') {
                                        imagealphablending($newImage, false);
                                        imagesavealpha($newImage, true);
                                    }

                                    // Redimensionar
                                    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                                    // Guardar imagen
                                    switch($extension) {
                                        case 'jpg':
                                        case 'jpeg':
                                            imagejpeg($newImage, $uploadFile, 85);
                                            break;
                                        case 'png':
                                            imagepng($newImage, $uploadFile, 8);
                                            break;
                                        case 'gif':
                                            imagegif($newImage, $uploadFile);
                                            break;
                                    }

                                    imagedestroy($sourceImage);
                                    imagedestroy($newImage);
                                } else {
                                    // Si la imagen es más pequeña que el máximo, solo moverla
                                    move_uploaded_file($_FILES['imagen']['tmp_name'], $uploadFile);
                                }
                            } else {
                                $message = "Tipo de archivo no permitido. Solo se permiten imágenes JPG, JPEG, PNG y GIF";
                                $messageType = "error";
                                break;
                            }
                        }

                        $stmt = $conn->prepare("UPDATE productos SET nombre = ?, categoria_id = ?, descripcion = ?, precio = ?, stock = ?, imagen = ? WHERE id = ?");
                        $stmt->execute([$nombre, $categoria_id, $descripcion, $precio, $stock, $imagen, $id]);
                        $message = "Producto actualizado exitosamente";
                        $messageType = "success";
                    } catch (PDOException $e) {
                        $message = "Error al actualizar el producto: " . $e->getMessage();
                        $messageType = "error";
                    }
                }
                break;

            case 'delete':
                if (isset($_POST['id'])) {
                    $id = (int)$_POST['id'];
                    try {
                        // Primero obtener la imagen para eliminarla
                        $stmt = $conn->prepare("SELECT imagen FROM productos WHERE id = ?");
                        $stmt->execute([$id]);
                        $producto = $stmt->fetch();

                        // Eliminar la imagen si existe
                        if ($producto && $producto['imagen']) {
                            $imagePath = '../../uploads/products/' . $producto['imagen'];
                            if (file_exists($imagePath)) {
                                unlink($imagePath);
                            }
                        }

                        // Eliminar el producto
                        $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
                        $stmt->execute([$id]);
                        $message = "Producto eliminado exitosamente";
                        $messageType = "success";
                    } catch (PDOException $e) {
                        $message = "Error al eliminar el producto: " . $e->getMessage();
                        $messageType = "error";
                    }
                }
                break;

            case 'update_stock':
                if (isset($_POST['id'], $_POST['cantidad'], $_POST['tipo_ajuste'])) {
                    $id = (int)$_POST['id'];
                    $cantidad = (int)$_POST['cantidad'];
                    $tipoAjuste = $_POST['tipo_ajuste'];

                    try {
                        // Iniciar transacción
                        $conn->beginTransaction();

                        // Obtener stock actual
                        $stmt = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
                        $stmt->execute([$id]);
                        $producto = $stmt->fetch();
                        $stockActual = $producto['stock'];

                        // Determinar el tipo de movimiento y el nuevo stock
                        $tipoMovimiento = '';
                        switch ($tipoAjuste) {
                            case 'add':
                                $nuevoStock = $stockActual + $cantidad;
                                $tipoMovimiento = 'entrada';
                                break;
                            case 'subtract':
                                $nuevoStock = $stockActual - $cantidad;
                                if ($nuevoStock < 0) {
                                    $nuevoStock = 0;
                                    $cantidad = $stockActual; // Ajustar la cantidad al stock disponible
                                }
                                $tipoMovimiento = 'salida';
                                break;
                            case 'set':
                                if ($cantidad > $stockActual) {
                                    $tipoMovimiento = 'entrada';
                                    $cantidad = $cantidad - $stockActual;
                                } else {
                                    $tipoMovimiento = 'salida';
                                    $cantidad = $stockActual - $cantidad;
                                }
                                $nuevoStock = $cantidad;
                                break;
                            default:
                                throw new Exception("Tipo de ajuste no válido");
                        }

                        // Actualizar el stock
                        $stmt = $conn->prepare("UPDATE productos SET stock = ? WHERE id = ?");
                        $stmt->execute([$nuevoStock, $id]);

                        // Registrar el movimiento
                        $stmt = $conn->prepare("INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo_movimiento, cantidad) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$id, $_SESSION['user_id'], $tipoMovimiento, $cantidad]);

                        // Confirmar transacción
                        $conn->commit();
                        
                        $message = "Stock actualizado exitosamente";
                        $messageType = "success";
                    } catch (Exception $e) {
                        // Revertir cambios si hay error
                        $conn->rollBack();
                        $message = "Error al actualizar el stock: " . $e->getMessage();
                        $messageType = "error";
                    }
                }
                break;
        }
    }
}

// Obtener parámetros de búsqueda y filtrado
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$stock_filter = isset($_GET['stock_filter']) ? $_GET['stock_filter'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'nombre_asc';

// Obtener estadísticas
try {
    $stats = [
        'total_productos' => 0,
        'productos_activos' => 0,
        'productos_agotados' => 0
    ];

    $stmt = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN stock > 0 THEN 1 ELSE 0 END) as activos,
        SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as agotados
        FROM productos");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $stats['total_productos'] = $result['total'];
    $stats['productos_activos'] = $result['activos'];
    $stats['productos_agotados'] = $result['agotados'];
} catch (PDOException $e) {
    $message = "Error al obtener estadísticas: " . $e->getMessage();
    $messageType = "error";
}

// Obtener categorías para el formulario
try {
    $stmt = $conn->query("SELECT id, nombre FROM categorias WHERE estado = 1 ORDER BY nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error al obtener categorías: " . $e->getMessage();
    $messageType = "error";
}

// Construir consulta para productos con filtros
try {
    $sql = "SELECT p.*, c.nombre as categoria_nombre 
            FROM productos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE 1=1";
    $params = array();

    // Aplicar filtros
    if (!empty($search)) {
        $sql .= " AND (p.nombre LIKE :search OR p.descripcion LIKE :search)";
        $params[':search'] = "%{$search}%";
    }

    if (!empty($category)) {
        $sql .= " AND p.categoria_id = :category";
        $params[':category'] = $category;
    }

    // Filtro de stock
    switch ($stock_filter) {
        case 'out_of_stock':
            $sql .= " AND p.stock = 0";
            break;
        case 'low_stock':
            $sql .= " AND p.stock > 0 AND p.stock < 10";
            break;
        case 'in_stock':
            $sql .= " AND p.stock >= 10";
            break;
    }

    // Ordenamiento
    switch ($sort) {
        case 'nombre_desc':
            $sql .= " ORDER BY p.nombre DESC";
            break;
        case 'precio_asc':
            $sql .= " ORDER BY p.precio ASC";
            break;
        case 'precio_desc':
            $sql .= " ORDER BY p.precio DESC";
            break;
        case 'stock_asc':
            $sql .= " ORDER BY p.stock ASC";
            break;
        case 'stock_desc':
            $sql .= " ORDER BY p.stock DESC";
            break;
        case 'categoria_asc':
            $sql .= " ORDER BY c.nombre ASC, p.nombre ASC";
            break;
        case 'categoria_desc':
            $sql .= " ORDER BY c.nombre DESC, p.nombre ASC";
            break;
        default:
            $sql .= " ORDER BY p.nombre ASC";
    }

    $stmt = $conn->prepare($sql);
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error al obtener productos: " . $e->getMessage();
    $messageType = "error";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario - Lubri Queen 77</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        /* Estilos para las alertas */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
            position: relative;
            overflow: hidden;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background-color: rgba(0, 0, 0, 0.1);
            width: 100%;
            animation: progress-bar-shrink 3s linear forwards;
        }

        @keyframes progress-bar-shrink {
            from { width: 100%; }
            to { width: 0%; }
        }

        @keyframes fade-out {
            from {
                opacity: 1;
                max-height: 100px;
                margin-bottom: 20px;
                padding: 15px;
            }
            to {
                opacity: 0;
                max-height: 0;
                margin-bottom: 0;
                padding: 0;
            }
        }

        .alert.fade-out {
            animation: fade-out 0.5s ease-out forwards;
        }

        /* Estilos para los modales */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
            padding: 20px;
        }

        .modal-content {
            background-color: #fff;
            margin: 20px auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            position: relative;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }

        /* Estilizar la barra de desplazamiento */
        .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #666;
        }

        /* Para Firefox */
        .modal-content {
            scrollbar-width: thin;
            scrollbar-color: #888 #f1f1f1;
        }

        .close {
            position: sticky;
            top: 0;
            right: 0;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
            margin: -20px -20px 10px 0;
            padding: 0 10px;
            z-index: 1;
        }

        .close:hover {
            color: #333;
        }

        /* Formularios */
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-row .form-group {
            flex: 1;
            margin: 0;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-control {
            width: 100%;
            height: 42px;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.2s ease;
            background-color: #fff;
            margin-top: 0;
        }

        select.form-control {
            padding-right: 30px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 8.825L1.175 4 2.238 2.938 6 6.7l3.763-3.762L10.825 4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-color: #fff;
        }

        .floating {
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .floating label {
            position: absolute;
            top: -8px;
            left: 10px;
            background: white;
            padding: 0 5px;
            font-size: 12px;
            color: #666;
            z-index: 1;
            margin: 0;
            line-height: 1;
        }

        .floating input.form-control,
        .floating select.form-control {
            margin-top: 0;
            margin-bottom: 0;
            padding-top: 8px;
            padding-bottom: 8px;
            height: 42px;
            line-height: 1.5;
        }

        /* Manejo de archivos */
        .file-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            width: auto;
        }

        .file-label:hover {
            background-color: #e9ecef;
        }

        .preview-container {
            margin-top: 15px;
            display: inline-block;
            position: relative;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 4px;
        }

        .preview-container img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 4px;
            display: block;
        }

        .remove-image {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            padding: 0;
        }

        .remove-image:hover {
            background-color: #c82333;
        }

        /* Botones */
        .button-stack {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .button-stack button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        /* Estilos específicos para inputs numéricos */
        input[type="number"] {
            -moz-appearance: textfield;
        }

        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../components/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <header class="content-header">
                <div class="header-title">
                    <h1>Gestión de Inventario</h1>
                    <div class="header-actions">
                        <div class="inventory-stats">
                            <span class="stat-item">
                                <i class="fas fa-box"></i> Total: <?php echo $stats['total_productos']; ?>
                            </span>
                            <span class="stat-item warning">
                                <i class="fas fa-exclamation-triangle"></i> Bajo Stock: <?php echo $stats['productos_agotados']; ?>
                            </span>
                            <span class="stat-item danger">
                                <i class="fas fa-times-circle"></i> Agotados: <?php echo $stats['productos_agotados']; ?>
                            </span>
                        </div>
                        <div class="actions-container">
                            <div class="action-buttons">
                                <button onclick="showModal('create')" class="action-button primary">
                                    <i class="fas fa-plus"></i>
                                    Agregar Producto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Filtros y Búsqueda -->
            <div class="filters-container">
                <form action="" method="GET" class="filters-form" id="filterForm">
                    <div class="form-row">
                        <div class="form-group search-box">
                            <input type="text" name="search" placeholder="Buscar productos..." 
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                            <button type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div class="form-group">
                            <select name="category" onchange="this.form.submit()">
                                <option value="">Todas las Categorías</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>" 
                                            <?php echo isset($_GET['category']) && $_GET['category'] == $categoria['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <select name="stock_filter" onchange="this.form.submit()">
                                <option value="">Filtrar por Stock</option>
                                <option value="in_stock" <?php echo isset($_GET['stock_filter']) && $_GET['stock_filter'] == 'in_stock' ? 'selected' : ''; ?>>Disponible</option>
                                <option value="low_stock" <?php echo isset($_GET['stock_filter']) && $_GET['stock_filter'] == 'low_stock' ? 'selected' : ''; ?>>Bajo Stock</option>
                                <option value="out_of_stock" <?php echo isset($_GET['stock_filter']) && $_GET['stock_filter'] == 'out_of_stock' ? 'selected' : ''; ?>>Sin Stock</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <select name="sort" onchange="this.form.submit()">
                                <option value="">Ordenar por...</option>
                                <option value="nombre_asc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'nombre_asc' ? 'selected' : ''; ?>>Nombre A-Z</option>
                                <option value="nombre_desc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'nombre_desc' ? 'selected' : ''; ?>>Nombre Z-A</option>
                                <option value="precio_asc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'precio_asc' ? 'selected' : ''; ?>>Menor Precio</option>
                                <option value="precio_desc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'precio_desc' ? 'selected' : ''; ?>>Mayor Precio</option>
                                <option value="stock_asc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'stock_asc' ? 'selected' : ''; ?>>Menor Stock</option>
                                <option value="stock_desc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'stock_desc' ? 'selected' : ''; ?>>Mayor Stock</option>
                                <option value="categoria_asc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'categoria_asc' ? 'selected' : ''; ?>>Categoría A-Z</option>
                                <option value="categoria_desc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'categoria_desc' ? 'selected' : ''; ?>>Categoría Z-A</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabla de Productos -->
            <div class="table-container">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php echo $message; ?>
                        <div class="progress-bar"></div>
                    </div>
                <?php endif; ?>

                <?php if (empty($productos)): ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <p>No se encontraron productos que coincidan con los criterios de búsqueda.</p>
                        <a href="index.php" class="btn btn-secondary">Limpiar filtros</a>
                    </div>
                <?php else: ?>
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th class="column-imagen">Imagen</th>
                                <th class="column-nombre">Nombre</th>
                                <th class="column-categoria">Categoría</th>
                                <th class="column-precio">Precio</th>
                                <th class="column-stock">Stock</th>
                                <th class="column-estado">Estado</th>
                                <th class="column-acciones">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td class="column-imagen">
                                        <img src="<?php echo !empty($producto['imagen']) ? '../../uploads/products/' . $producto['imagen'] : '../../images/no-image.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                    </td>
                                    <td class="column-nombre"><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td class="column-categoria"><?php echo htmlspecialchars($producto['categoria_nombre']); ?></td>
                                    <td class="column-precio">$<?php echo number_format($producto['precio'], 2); ?></td>
                                    <td class="column-stock">
                                        <?php if ($producto['stock'] == 0): ?>
                                            <span class="badge badge-agotado">Agotado</span>
                                        <?php elseif ($producto['stock'] < 10): ?>
                                            <span class="badge badge-stock"><?php echo $producto['stock']; ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-stock-alto"><?php echo $producto['stock']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-estado">
                                        <span class="badge badge-activo">Activo</span>
                                    </td>
                                    <td class="column-acciones">
                                        <div class="actions">
                                            <button onclick="showModal('edit', <?php echo htmlspecialchars(json_encode([
                                                'id' => $producto['id'],
                                                'nombre' => $producto['nombre'],
                                                'categoria_id' => $producto['categoria_id'],
                                                'descripcion' => $producto['descripcion'],
                                                'precio' => $producto['precio'],
                                                'stock' => $producto['stock'],
                                                'imagen' => $producto['imagen']
                                            ]), ENT_QUOTES, 'UTF-8'); ?>)" class="action-icon edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="showStockModal(<?php echo $producto['id']; ?>)" class="action-icon adjust" title="Ajustar Stock">
                                                <i class="fas fa-boxes"></i>
                                            </button>
                                            <button onclick="deleteProduct(<?php echo $producto['id']; ?>)" class="action-icon delete" title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar producto -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Nuevo Producto</h2>
            <form id="productForm" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="productId">
                
                <div class="form-row">
                    <div class="form-group floating">
                        <input type="text" id="nombre" name="nombre" required class="form-control" placeholder="Nombre del producto *">
                        <label for="nombre">Nombre del producto *</label>
                    </div>

                    <div class="form-group floating">
                        <select id="categoria" name="categoria_id" required class="form-control">
                            <option value="">Seleccione una categoría</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>">
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="categoria">Categoría *</label>
                    </div>
                </div>

                <div class="form-group floating">
                    <textarea id="descripcion" name="descripcion" class="form-control" placeholder="Descripción del producto"></textarea>
                    <label for="descripcion">Descripción del producto</label>
                </div>

                <div class="form-row">
                    <div class="form-group floating">
                        <input type="number" id="precio" name="precio" required class="form-control" step="0.01" min="0" placeholder="Precio *">
                        <label for="precio">Precio *</label>
                    </div>

                    <div class="form-group floating">
                        <input type="number" id="stock" name="stock" required class="form-control" min="0" placeholder="Stock *">
                        <label for="stock">Stock *</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="imagen" class="file-label">
                        <i class="fas fa-image"></i>
                        <span>Seleccionar imagen</span>
                    </label>
                    <input type="file" id="imagen" name="imagen" accept="image/*" class="file-input">
                    <div id="preview-container" class="preview-container" style="display: none;">
                        <img id="image-preview" src="#" alt="Vista previa">
                        <button type="button" class="remove-image" onclick="removeImage()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group button-stack">
                    <button type="submit" class="btn-primary">Guardar</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para ajustar stock -->
    <div id="stockModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeStockModal()">&times;</span>
            <h2>Ajustar Stock</h2>
            <form id="stockForm" method="POST" onsubmit="return validateStockForm()">
                <input type="hidden" name="action" value="update_stock">
                <input type="hidden" name="id" id="stockProductId">
                
                <div class="form-group floating">
                    <input type="number" id="cantidad" name="cantidad" required class="form-control" min="0" placeholder="Cantidad *">
                    <label for="cantidad">Cantidad *</label>
                </div>

                <div class="form-group floating">
                    <select id="tipo_ajuste" name="tipo_ajuste" required class="form-control">
                        <option value="">Seleccione tipo de ajuste</option>
                        <option value="add">Agregar al stock actual</option>
                        <option value="subtract">Restar del stock actual</option>
                        <option value="set">Establecer nuevo valor</option>
                    </select>
                    <label for="tipo_ajuste">Tipo de Ajuste *</label>
                </div>
                
                <div class="form-group button-stack">
                    <button type="submit" class="btn-primary">Guardar</button>
                    <button type="button" class="btn-secondary" onclick="closeStockModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('productModal');
        const stockModal = document.getElementById('stockModal');
        const form = document.getElementById('productForm');
        const stockForm = document.getElementById('stockForm');
        const modalTitle = document.getElementById('modalTitle');
        const formAction = document.getElementById('formAction');
        const productId = document.getElementById('productId');
        const stockProductId = document.getElementById('stockProductId');
        const previewContainer = document.getElementById('preview-container');
        const imagePreview = document.getElementById('image-preview');

        // Mostrar modal si viene de acciones rápidas
        document.addEventListener('DOMContentLoaded', function() {
            if (new URLSearchParams(window.location.search).get('action') === 'showModal') {
                showModal('create');
                // Limpiar el parámetro de la URL sin recargar la página
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });

        function showModal(action, product = null) {
            modal.style.display = 'block';
            form.reset();
            previewContainer.style.display = 'none';
            
            if (action === 'create') {
                modalTitle.textContent = 'Nuevo Producto';
                formAction.value = 'create';
                productId.value = '';
            } else if (action === 'edit' && product) {
                modalTitle.textContent = 'Editar Producto';
                formAction.value = 'update';
                productId.value = product.id;
                
                // Llenar el formulario con los datos del producto
                document.getElementById('nombre').value = product.nombre;
                document.getElementById('categoria').value = product.categoria_id;
                document.getElementById('descripcion').value = product.descripcion || '';
                document.getElementById('precio').value = product.precio;
                document.getElementById('stock').value = product.stock;

                // Mostrar imagen si existe
                if (product.imagen) {
                    imagePreview.src = '../../uploads/products/' + product.imagen;
                    previewContainer.style.display = 'block';
                }
            }
        }

        function showStockModal(id) {
            stockModal.style.display = 'block';
            stockForm.reset();
            stockProductId.value = id;
        }

        function closeModal() {
            modal.style.display = 'none';
            form.reset();
            previewContainer.style.display = 'none';
        }

        function closeStockModal() {
            stockModal.style.display = 'none';
            stockForm.reset();
        }

        function validateForm() {
            const nombre = document.getElementById('nombre').value.trim();
            const categoria = document.getElementById('categoria').value;
            const precio = document.getElementById('precio').value;
            const stock = document.getElementById('stock').value;

            if (!nombre) {
                alert('Por favor, ingrese un nombre para el producto');
                return false;
            }
            if (!categoria) {
                alert('Por favor, seleccione una categoría');
                return false;
            }
            if (!precio || precio <= 0) {
                alert('Por favor, ingrese un precio válido');
                return false;
            }
            if (!stock || stock < 0) {
                alert('Por favor, ingrese una cantidad válida de stock');
                return false;
            }
            return true;
        }

        function validateStockForm() {
            const cantidad = document.getElementById('cantidad').value;
            const tipoAjuste = document.getElementById('tipo_ajuste').value;

            if (!cantidad || cantidad < 0) {
                alert('Por favor, ingrese una cantidad válida');
                return false;
            }
            if (!tipoAjuste) {
                alert('Por favor, seleccione un tipo de ajuste');
                return false;
            }
            return true;
        }

        // Previsualización de imagen
        document.getElementById('imagen').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    previewContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        function removeImage() {
            document.getElementById('imagen').value = '';
            previewContainer.style.display = 'none';
        }

        // Cerrar modales al hacer clic fuera
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            } else if (event.target == stockModal) {
                closeStockModal();
            }
        }

        function deleteProduct(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este producto?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Función para manejar los mensajes de alerta
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.add('fade-out');
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }, 2500); // Desaparece después de 2.5s (más 0.5s de la animación = 3s total)
            });
        });
    </script>
</body>
</html>
