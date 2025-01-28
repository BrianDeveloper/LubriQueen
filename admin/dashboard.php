<?php
session_start();
require_once '../config/db.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Obtener categorías para el formulario
try {
    $stmt = $conn->query("SELECT id, nombre FROM categorias WHERE estado = 1 ORDER BY nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error al obtener categorías: " . $e->getMessage();
}

// Obtener productos para el modal de stock
try {
    $stmt = $conn->query("SELECT id, nombre, stock FROM productos ORDER BY nombre");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error al obtener productos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lubri Queen 77</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <header class="content-header">
                <h1>Panel de Administración</h1>
                <div class="user-info">
                    <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
            </header>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <i class="fas fa-boxes"></i>
                    <div class="stat-info">
                        <?php
                        $stmt = $conn->query("SELECT COUNT(*) FROM productos");
                        $productCount = $stmt->fetchColumn();
                        ?>
                        <h3><?php echo $productCount; ?></h3>
                        <p>Productos</p>
                    </div>
                </div>

                <div class="stat-card">
                    <i class="fas fa-tags"></i>
                    <div class="stat-info">
                        <?php
                        $stmt = $conn->query("SELECT COUNT(*) FROM categorias");
                        $categoryCount = $stmt->fetchColumn();
                        ?>
                        <h3><?php echo $categoryCount; ?></h3>
                        <p>Categorías</p>
                    </div>
                </div>

                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="stat-info">
                        <?php
                        $stmt = $conn->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'cliente'");
                        $userCount = $stmt->fetchColumn();
                        ?>
                        <h3><?php echo $userCount; ?></h3>
                        <p>Clientes</p>
                    </div>
                </div>
            </div>

            <div class="quick-actions">
                <h2>Acciones Rápidas</h2>
                <div class="action-buttons-container">
                    <button onclick="window.location.href='inventory/index.php?action=showModal'" class="action-button primary">
                        <i class="fas fa-plus"></i> Añadir Producto
                    </button>
                    <button onclick="openQuickStockModal()" class="action-button warning">
                        <i class="fas fa-boxes"></i> Gestión de Stock
                    </button>
                    <a href="inventory/categories.php" class="action-button info">
                        <i class="fas fa-tags"></i> Nueva Categoría
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal rápido para crear producto -->
    <div id="quickCreateModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeQuickCreateModal()">&times;</span>
            <h2>Añadir Nuevo Producto</h2>
            <form id="quickCreateForm" method="POST" action="inventory/index.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                
                <div class="form-row">
                    <div class="form-group floating">
                        <input type="text" id="quickNombre" name="nombre" required class="form-control" placeholder="Nombre del producto *">
                        <label for="quickNombre">Nombre del producto *</label>
                    </div>

                    <div class="form-group floating">
                        <select id="quickCategoria" name="categoria_id" required class="form-control">
                            <option value="">Seleccione una categoría</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>">
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="quickCategoria">Categoría *</label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group floating">
                        <input type="number" id="quickPrecio" name="precio" required class="form-control" step="0.01" min="0" placeholder="Precio *">
                        <label for="quickPrecio">Precio *</label>
                    </div>

                    <div class="form-group floating">
                        <input type="number" id="quickStock" name="stock" required class="form-control" min="0" placeholder="Stock *">
                        <label for="quickStock">Stock *</label>
                    </div>
                </div>

                <div class="form-group floating">
                    <textarea id="quickDescripcion" name="descripcion" class="form-control" placeholder="Descripción del producto"></textarea>
                    <label for="quickDescripcion">Descripción del producto</label>
                </div>

                <div class="form-group">
                    <label for="quickImagen" class="file-label">
                        <i class="fas fa-image"></i>
                        <span>Seleccionar imagen</span>
                    </label>
                    <input type="file" id="quickImagen" name="imagen" accept="image/*" class="file-input">
                    <div id="quickPreviewContainer" class="preview-container" style="display: none;">
                        <img id="quickImagePreview" src="#" alt="Vista previa">
                        <button type="button" class="remove-image" onclick="removeQuickImage()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="button-stack">
                    <button type="button" class="btn btn-secondary" onclick="closeQuickCreateModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Producto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal rápido para gestión de stock -->
    <div id="quickStockModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeQuickStockModal()">&times;</span>
            <h2>Gestión Rápida de Stock</h2>
            <form id="quickStockForm" method="POST" action="inventory/index.php">
                <input type="hidden" name="action" value="update_stock">
                
                <div class="form-group floating">
                    <select id="quickProductSelect" name="id" required class="form-control" onchange="updateCurrentStock()">
                        <option value="">Seleccione un producto</option>
                        <?php foreach ($productos as $producto): ?>
                            <option value="<?php echo $producto['id']; ?>" 
                                    data-stock="<?php echo $producto['stock']; ?>">
                                <?php echo htmlspecialchars($producto['nombre']); ?> 
                                (Stock actual: <?php echo $producto['stock']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="quickProductSelect">Producto *</label>
                </div>

                <div class="form-group">
                    <p>Stock actual: <span id="currentStockDisplay">-</span></p>
                </div>

                <div class="form-row">
                    <div class="form-group floating">
                        <input type="number" id="quickStockQuantity" name="cantidad" required class="form-control" min="0">
                        <label for="quickStockQuantity">Cantidad *</label>
                    </div>

                    <div class="form-group floating">
                        <select id="quickStockAdjustment" name="tipo_ajuste" required class="form-control">
                            <option value="add">Añadir al stock</option>
                            <option value="subtract">Restar del stock</option>
                            <option value="set">Establecer stock</option>
                        </select>
                        <label for="quickStockAdjustment">Tipo de ajuste *</label>
                    </div>
                </div>

                <div class="button-stack">
                    <button type="button" class="btn btn-secondary" onclick="closeQuickStockModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <style>
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
        }

        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
        }

        .close:hover {
            color: #333;
        }

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
        }

        .form-control:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        textarea.form-control {
            min-height: 100px;
            height: auto;
        }

        .floating {
            position: relative;
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
            font-size: 14px;
            color: #495057;
        }

        .file-label:hover {
            background-color: #e9ecef;
            border-color: #ced4da;
        }

        .file-input {
            display: none;
        }

        .preview-container {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            background-color: #f8f9fa;
            text-align: center;
        }

        .preview-container img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 4px;
            display: block;
            margin: 0 auto;
        }

        .remove-image {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: #dc3545;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
            transition: background-color 0.2s;
        }

        .remove-image:hover {
            background-color: #c82333;
        }

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

        .action-buttons-container {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .action-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .action-button.primary {
            background-color: #007bff;
            color: white;
        }

        .action-button.primary:hover {
            background-color: #0056b3;
        }

        .action-button.warning {
            background-color: #ffc107;
            color: #000;
        }

        .action-button.warning:hover {
            background-color: #e0a800;
        }

        .action-button.info {
            background-color: #17a2b8;
            color: white;
        }

        .action-button.info:hover {
            background-color: #138496;
        }

        .action-button i {
            font-size: 16px;
        }
    </style>

    <script>
        // Funciones para el modal rápido de creación
        function openQuickCreateModal() {
            document.getElementById('quickCreateModal').style.display = 'block';
            document.getElementById('quickCreateForm').reset();
            document.getElementById('quickPreviewContainer').style.display = 'none';
        }

        function closeQuickCreateModal() {
            document.getElementById('quickCreateModal').style.display = 'none';
        }

        function removeQuickImage() {
            document.getElementById('quickImagen').value = '';
            document.getElementById('quickPreviewContainer').style.display = 'none';
            document.getElementById('quickImagePreview').src = '#';
        }

        // Funciones para el modal de gestión rápida de stock
        function openQuickStockModal() {
            document.getElementById('quickStockModal').style.display = 'block';
            document.getElementById('quickStockForm').reset();
            document.getElementById('currentStockDisplay').textContent = '-';
        }

        function closeQuickStockModal() {
            document.getElementById('quickStockModal').style.display = 'none';
        }

        function updateCurrentStock() {
            const select = document.getElementById('quickProductSelect');
            const option = select.options[select.selectedIndex];
            const currentStock = option.getAttribute('data-stock');
            document.getElementById('currentStockDisplay').textContent = currentStock || '-';
        }

        // Previsualización de imagen
        document.getElementById('quickImagen').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('quickImagePreview').src = e.target.result;
                    document.getElementById('quickPreviewContainer').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // Cerrar modales al hacer clic fuera
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
