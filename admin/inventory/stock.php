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
$producto = null;

// Si se proporciona un ID específico
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT p.*, c.nombre as categoria_nombre 
                           FROM productos p 
                           LEFT JOIN categorias c ON p.categoria_id = c.id 
                           WHERE p.id = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        header("Location: index.php");
        exit();
    }
}

// Procesar el formulario de ajuste de stock
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $producto_id = intval($_POST['producto_id']);
    $tipo_movimiento = $_POST['tipo_movimiento'];
    $cantidad = intval($_POST['cantidad']);
    $motivo = trim($_POST['motivo']);

    if ($cantidad <= 0) {
        $error = "La cantidad debe ser mayor que cero.";
    } elseif (empty($motivo)) {
        $error = "Debe proporcionar un motivo para el ajuste.";
    } else {
        try {
            $conn->beginTransaction();

            // Registrar el movimiento
            $sql_movimiento = "INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, motivo, usuario_id) 
                             VALUES (:producto_id, :tipo_movimiento, :cantidad, :motivo, :usuario_id)";
            
            $stmt = $conn->prepare($sql_movimiento);
            $stmt->bindParam(':producto_id', $producto_id);
            $stmt->bindParam(':tipo_movimiento', $tipo_movimiento);
            $stmt->bindParam(':cantidad', $cantidad);
            $stmt->bindParam(':motivo', $motivo);
            $stmt->bindParam(':usuario_id', $_SESSION['user_id']);
            $stmt->execute();

            // Actualizar el stock del producto
            $sql_update = "UPDATE productos SET 
                          stock = CASE 
                              WHEN :tipo_movimiento = 'entrada' THEN stock + :cantidad 
                              WHEN :tipo_movimiento = 'salida' THEN GREATEST(0, stock - :cantidad)
                          END 
                          WHERE id = :producto_id";
            
            $stmt = $conn->prepare($sql_update);
            $stmt->bindParam(':tipo_movimiento', $tipo_movimiento);
            $stmt->bindParam(':cantidad', $cantidad);
            $stmt->bindParam(':producto_id', $producto_id);
            $stmt->execute();

            $conn->commit();
            $mensaje = "Stock actualizado exitosamente.";

            // Actualizar la información del producto si estamos en la página de un producto específico
            if ($producto) {
                $stmt = $conn->prepare("SELECT p.*, c.nombre as categoria_nombre 
                                      FROM productos p 
                                      LEFT JOIN categorias c ON p.categoria_id = c.id 
                                      WHERE p.id = ?");
                $stmt->execute([$producto_id]);
                $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Obtener productos para el select si no estamos en un producto específico
if (!$producto) {
    $stmt = $conn->query("SELECT id, nombre, stock FROM productos WHERE estado = 1 ORDER BY nombre");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener los últimos movimientos
$sql_movimientos = "SELECT m.*, p.nombre as producto_nombre, u.nombre as usuario_nombre 
                   FROM movimientos_inventario m 
                   JOIN productos p ON m.producto_id = p.id 
                   JOIN usuarios u ON m.usuario_id = u.id ";

if ($producto) {
    $sql_movimientos .= "WHERE m.producto_id = " . $producto['id'] . " ";
}

$sql_movimientos .= "ORDER BY m.created_at DESC LIMIT 10";
$movimientos = $conn->query($sql_movimientos)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Stock - Lubri Queen 77</title>
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
                <h1><?php echo $producto ? "Gestionar Stock: " . htmlspecialchars($producto['nombre']) : "Gestión de Stock"; ?></h1>
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

            <div class="stock-management-container">
                <!-- Información del producto si estamos en uno específico -->
                <?php if ($producto): ?>
                    <div class="product-info-card">
                        <div class="product-details">
                            <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                            <p><strong>Categoría:</strong> <?php echo htmlspecialchars($producto['categoria_nombre']); ?></p>
                            <p><strong>Stock Actual:</strong> 
                                <?php if ($producto['stock'] == 0): ?>
                                    <span class="stock-badge out-of-stock">Agotado</span>
                                <?php else: ?>
                                    <span class="stock-badge <?php echo $producto['stock'] < 10 ? 'low-stock' : ''; ?>">
                                        <?php echo $producto['stock']; ?>
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if (!empty($producto['imagen'])): ?>
                            <div class="product-image">
                                <img src="../../uploads/products/<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                     alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Formulario de ajuste de stock -->
                <div class="stock-adjustment-form">
                    <h2>Ajustar Stock</h2>
                    <form action="" method="POST" class="form-container">
                        <?php if (!$producto): ?>
                            <div class="form-group">
                                <label for="producto_id">Producto *</label>
                                <select id="producto_id" name="producto_id" required>
                                    <option value="">Seleccione un producto</option>
                                    <?php foreach ($productos as $prod): ?>
                                        <option value="<?php echo $prod['id']; ?>">
                                            <?php echo htmlspecialchars($prod['nombre']) . ' (Stock: ' . $prod['stock'] . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                        <?php endif; ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="tipo_movimiento">Tipo de Movimiento *</label>
                                <select id="tipo_movimiento" name="tipo_movimiento" required>
                                    <option value="entrada">Entrada</option>
                                    <option value="salida">Salida</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="cantidad">Cantidad *</label>
                                <input type="number" id="cantidad" name="cantidad" min="1" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="motivo">Motivo del Ajuste *</label>
                            <textarea id="motivo" name="motivo" rows="3" required></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Registrar Movimiento
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Historial de movimientos -->
                <div class="movement-history">
                    <h2>Últimos Movimientos</h2>
                    <div class="table-container">
                        <table class="movement-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <?php if (!$producto): ?>
                                        <th>Producto</th>
                                    <?php endif; ?>
                                    <th>Tipo</th>
                                    <th>Cantidad</th>
                                    <th>Motivo</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movimientos as $movimiento): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($movimiento['created_at'])); ?></td>
                                        <?php if (!$producto): ?>
                                            <td><?php echo htmlspecialchars($movimiento['producto_nombre']); ?></td>
                                        <?php endif; ?>
                                        <td>
                                            <span class="movement-badge <?php echo $movimiento['tipo_movimiento']; ?>">
                                                <?php echo $movimiento['tipo_movimiento'] == 'entrada' ? 'Entrada' : 'Salida'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $movimiento['cantidad']; ?></td>
                                        <td><?php echo htmlspecialchars($movimiento['motivo']); ?></td>
                                        <td><?php echo htmlspecialchars($movimiento['usuario_nombre']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
