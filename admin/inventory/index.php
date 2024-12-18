<?php
session_start();
require_once '../../config/db.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

// Obtener parámetros de búsqueda y filtrado
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'nombre_asc';
$stock_filter = isset($_GET['stock_filter']) ? $_GET['stock_filter'] : '';

// Construir la consulta SQL base
$sql = "SELECT p.*, c.nombre as categoria_nombre 
        FROM productos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        WHERE 1=1";

$params = array();

// Agregar condiciones de búsqueda
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

// Agregar ordenamiento
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

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($sql);
foreach ($params as $param => $value) {
    $stmt->bindValue($param, $value);
}
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías para el filtro
$stmtCat = $conn->query("SELECT id, nombre FROM categorias WHERE estado = 1 ORDER BY nombre");
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas para mostrar en el encabezado
$stats = $conn->query("SELECT 
    COUNT(*) as total_productos,
    SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as productos_agotados,
    SUM(CASE WHEN stock > 0 AND stock < 10 THEN 1 ELSE 0 END) as productos_bajo_stock
    FROM productos")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario - Lubri Queen 77</title>
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
                <a href="categories.php">
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
                <div class="header-title">
                    <h1>Gestión de Inventario</h1>
                    <div class="inventory-stats">
                        <span class="stat-item">
                            <i class="fas fa-box"></i> Total: <?php echo $stats['total_productos']; ?>
                        </span>
                        <span class="stat-item warning">
                            <i class="fas fa-exclamation-triangle"></i> Bajo Stock: <?php echo $stats['productos_bajo_stock']; ?>
                        </span>
                        <span class="stat-item danger">
                            <i class="fas fa-times-circle"></i> Agotados: <?php echo $stats['productos_agotados']; ?>
                        </span>
                    </div>
                </div>
                <a href="add.php" class="add-btn">
                    <i class="fas fa-plus"></i> Agregar Producto
                </a>
            </header>

            <!-- Filtros y Búsqueda -->
            <div class="filters-container">
                <form action="" method="GET" class="filters-form" id="filterForm">
                    <div class="form-row">
                        <div class="form-group search-box">
                            <input type="text" name="search" placeholder="Buscar productos..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div class="form-group">
                            <select name="category" onchange="this.form.submit()">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <select name="stock_filter" onchange="this.form.submit()">
                                <option value="" <?php echo $stock_filter == '' ? 'selected' : ''; ?>>Todos los productos</option>
                                <option value="in_stock" <?php echo $stock_filter == 'in_stock' ? 'selected' : ''; ?>>En stock</option>
                                <option value="low_stock" <?php echo $stock_filter == 'low_stock' ? 'selected' : ''; ?>>Bajo stock</option>
                                <option value="out_of_stock" <?php echo $stock_filter == 'out_of_stock' ? 'selected' : ''; ?>>Agotados</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <select name="sort" onchange="this.form.submit()">
                                <option value="nombre_asc" <?php echo $sort == 'nombre_asc' ? 'selected' : ''; ?>>Nombre ↑</option>
                                <option value="nombre_desc" <?php echo $sort == 'nombre_desc' ? 'selected' : ''; ?>>Nombre ↓</option>
                                <option value="precio_asc" <?php echo $sort == 'precio_asc' ? 'selected' : ''; ?>>Precio ↑</option>
                                <option value="precio_desc" <?php echo $sort == 'precio_desc' ? 'selected' : ''; ?>>Precio ↓</option>
                                <option value="stock_asc" <?php echo $sort == 'stock_asc' ? 'selected' : ''; ?>>Stock ↑</option>
                                <option value="stock_desc" <?php echo $sort == 'stock_desc' ? 'selected' : ''; ?>>Stock ↓</option>
                                <option value="categoria_asc" <?php echo $sort == 'categoria_asc' ? 'selected' : ''; ?>>Categoría ↑</option>
                                <option value="categoria_desc" <?php echo $sort == 'categoria_desc' ? 'selected' : ''; ?>>Categoría ↓</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabla de Productos -->
            <div class="table-container">
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
                                        <div class="action-buttons">
                                            <a href="edit.php?id=<?php echo $producto['id']; ?>" class="action-icon" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="stock.php?id=<?php echo $producto['id']; ?>" class="action-icon" title="Ajustar Stock">
                                                <i class="fas fa-boxes"></i>
                                            </a>
                                            <a href="#" onclick="deleteProduct(<?php echo $producto['id']; ?>)" class="action-icon" title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
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

    <script>
    function deleteProduct(productId) {
        if (confirm('¿Está seguro de que desea eliminar este producto?')) {
            window.location.href = 'delete.php?id=' + productId;
        }
    }

    // Limpiar filtros
    document.querySelector('.btn-secondary')?.addEventListener('click', function(e) {
        e.preventDefault();
        window.location.href = 'index.php';
    });
    </script>
</body>
</html>
