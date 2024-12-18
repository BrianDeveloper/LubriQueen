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

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $categoria_id = intval($_POST['categoria_id']);
    $estado = isset($_POST['estado']) ? 1 : 0;

    // Validar campos obligatorios
    if (empty($nombre) || empty($precio) || empty($stock)) {
        $error = "Por favor, complete todos los campos obligatorios.";
    } else {
        try {
            // Procesar la imagen si se subió una
            $imagen = '';
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
                    
                    // Generar nombre único para la imagen
                    $imagen = uniqid() . '.' . $filetype;
                    $destination = $upload_dir . $imagen;
                    
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $destination)) {
                        // La imagen se subió correctamente
                    } else {
                        throw new Exception("Error al subir la imagen.");
                    }
                } else {
                    throw new Exception("Tipo de archivo no permitido. Solo se permiten imágenes JPG, JPEG, PNG y GIF.");
                }
            }

            // Insertar el producto en la base de datos
            $sql = "INSERT INTO productos (nombre, descripcion, precio, stock, imagen, categoria_id, estado) 
                    VALUES (:nombre, :descripcion, :precio, :stock, :imagen, :categoria_id, :estado)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':imagen', $imagen);
            $stmt->bindParam(':categoria_id', $categoria_id);
            $stmt->bindParam(':estado', $estado);
            
            if ($stmt->execute()) {
                // Registrar el movimiento de inventario inicial
                $producto_id = $conn->lastInsertId();
                $sql_movimiento = "INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, motivo, usuario_id) 
                                 VALUES (:producto_id, 'entrada', :cantidad, 'Stock inicial', :usuario_id)";
                
                $stmt_mov = $conn->prepare($sql_movimiento);
                $stmt_mov->bindParam(':producto_id', $producto_id);
                $stmt_mov->bindParam(':cantidad', $stock);
                $stmt_mov->bindParam(':usuario_id', $_SESSION['user_id']);
                $stmt_mov->execute();

                $mensaje = "Producto agregado exitosamente.";
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
    <title>Agregar Producto - Lubri Queen 77</title>
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
                <h1>Agregar Nuevo Producto</h1>
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
                               value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                        <label for="nombre">Nombre del Producto *</label>
                    </div>

                    <div class="form-group">
                        <textarea id="descripcion" name="descripcion" rows="4" placeholder=" "><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                        <label for="descripcion">Descripción del producto</label>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <input type="number" id="precio" name="precio" step="0.01" required placeholder=" "
                                   value="<?php echo isset($_POST['precio']) ? htmlspecialchars($_POST['precio']) : ''; ?>">
                            <label for="precio">Precio *</label>
                        </div>

                        <div class="form-group">
                            <input type="number" id="stock" name="stock" required placeholder=" "
                                   value="<?php echo isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : ''; ?>">
                            <label for="stock">Stock Inicial *</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <select id="categoria_id" name="categoria_id" required>
                            <option value="">Seleccione una categoría *</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>" 
                                    <?php echo (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="categoria_id">Categoría *</label>
                    </div>

                    <div class="form-group">
                        <input type="file" id="imagen" name="imagen" accept="image/*">
                        <small>Formatos permitidos: JPG, JPEG, PNG, GIF</small>
                    </div>

                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="estado" <?php echo isset($_POST['estado']) ? 'checked' : ''; ?>>
                            Producto Activo
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Guardar Producto
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
