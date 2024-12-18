<?php
session_start();
require_once '../../config/db.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

$mensaje = '';
$error = '';

// Verificar si se proporcionó un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);

// Obtener información del producto
try {
    $stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    $error = "Error al obtener el producto: " . $e->getMessage();
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $categoria_id = intval($_POST['categoria_id']);
    $estado = isset($_POST['estado']) ? 1 : 0;

    // Validar campos obligatorios
    if (empty($nombre) || empty($precio)) {
        $error = "Por favor, complete todos los campos obligatorios.";
    } else {
        try {
            // Procesar la imagen si se subió una nueva
            $imagen = $producto['imagen']; // Mantener la imagen existente por defecto
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['imagen']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($filetype), $allowed)) {
                    // Crear directorio si no existe
                    $upload_dir = '../../uploads/products/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Eliminar imagen anterior si existe
                    if (!empty($producto['imagen']) && file_exists($upload_dir . $producto['imagen'])) {
                        unlink($upload_dir . $producto['imagen']);
                    }
                    
                    // Generar nombre único para la nueva imagen
                    $imagen = uniqid() . '.' . $filetype;
                    $destination = $upload_dir . $imagen;
                    
                    if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $destination)) {
                        throw new Exception("Error al subir la imagen.");
                    }
                } else {
                    throw new Exception("Tipo de archivo no permitido. Solo se permiten imágenes JPG, JPEG, PNG y GIF.");
                }
            }

            // Actualizar el producto en la base de datos
            $sql = "UPDATE productos SET 
                    nombre = :nombre, 
                    descripcion = :descripcion, 
                    precio = :precio, 
                    categoria_id = :categoria_id, 
                    imagen = :imagen,
                    estado = :estado 
                    WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':categoria_id', $categoria_id);
            $stmt->bindParam(':imagen', $imagen);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                // Si el stock ha cambiado, registrar el movimiento
                if ($stock != $producto['stock']) {
                    $diferencia = $stock - $producto['stock'];
                    $tipo_movimiento = $diferencia > 0 ? 'entrada' : 'salida';
                    $cantidad = abs($diferencia);
                    
                    $sql_movimiento = "INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, motivo, usuario_id) 
                                     VALUES (:producto_id, :tipo_movimiento, :cantidad, 'Ajuste de inventario', :usuario_id)";
                    
                    $stmt_mov = $conn->prepare($sql_movimiento);
                    $stmt_mov->bindParam(':producto_id', $id);
                    $stmt_mov->bindParam(':tipo_movimiento', $tipo_movimiento);
                    $stmt_mov->bindParam(':cantidad', $cantidad);
                    $stmt_mov->bindParam(':usuario_id', $_SESSION['user_id']);
                    $stmt_mov->execute();

                    // Actualizar el stock
                    $sql_stock = "UPDATE productos SET stock = :stock WHERE id = :id";
                    $stmt_stock = $conn->prepare($sql_stock);
                    $stmt_stock->bindParam(':stock', $stock);
                    $stmt_stock->bindParam(':id', $id);
                    $stmt_stock->execute();
                }

                $mensaje = "Producto actualizado exitosamente.";
                header("Location: index.php?success=1");
                exit();
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Obtener categorías para el select
$stmt = $conn->query("SELECT id, nombre FROM categorias WHERE estado = 1 ORDER BY nombre");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - Lubri Queen 77</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Lubri Queen 77</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="../dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="index.php" class="active">
                    <i class="fas fa-boxes"></i> Inventario
                </a>
                <a href="../categories.php">
                    <i class="fas fa-tags"></i> Categorías
                </a>
                <a href="../users.php">
                    <i class="fas fa-users"></i> Usuarios
                </a>
                <a href="../../auth/logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header class="content-header">
                <h1>Editar Producto</h1>
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </header>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($mensaje): ?>
                <div class="alert alert-success">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form action="" method="POST" enctype="multipart/form-data" class="product-form dashboard-form">
                    <div class="form-group">
                        <input type="text" id="nombre" name="nombre" required placeholder=" "
                               value="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <label for="nombre">Nombre del Producto *</label>
                    </div>

                    <div class="form-group">
                        <textarea id="descripcion" name="descripcion" rows="4" placeholder=" "><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                        <label for="descripcion">Descripción del producto</label>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <input type="number" id="precio" name="precio" step="0.01" required placeholder=" "
                                   value="<?php echo htmlspecialchars($producto['precio']); ?>">
                            <label for="precio">Precio *</label>
                        </div>

                        <div class="form-group">
                            <input type="number" id="stock" name="stock" required placeholder=" "
                                   value="<?php echo htmlspecialchars($producto['stock']); ?>">
                            <label for="stock">Stock *</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <select id="categoria_id" name="categoria_id" required>
                            <option value="">Seleccione una categoría *</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>" 
                                    <?php echo ($producto['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="categoria_id">Categoría *</label>
                    </div>

                    <div class="form-group">
                        <input type="file" id="imagen" name="imagen" accept="image/*">
                        <small>Formatos permitidos: JPG, JPEG, PNG, GIF</small>
                        <?php if (!empty($producto['imagen'])): ?>
                            <div class="current-image">
                                <img src="../../uploads/products/<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                     alt="Imagen actual del producto">
                                <p>Imagen actual</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="estado" <?php echo $producto['estado'] ? 'checked' : ''; ?>>
                            Producto Activo
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Vista previa de la imagen
    document.getElementById('imagen').onchange = function(e) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if(document.querySelector('.image-preview')) {
                document.querySelector('.image-preview').remove();
            }
            const preview = document.createElement('div');
            preview.className = 'image-preview';
            preview.innerHTML = `<img src="${e.target.result}" alt="Vista previa">`;
            document.querySelector('.form-group:nth-of-type(5)').appendChild(preview);
        }
        reader.readAsDataURL(e.target.files[0]);
    };
    </script>
</body>
</html>
