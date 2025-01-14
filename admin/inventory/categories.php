<?php
session_start();
require_once '../../config/db.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$message = '';
$messageType = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Crear categoría
        if ($_POST['action'] === 'create' && !empty($_POST['nombre'])) {
            $nombre = trim($_POST['nombre']);
            $descripcion = trim($_POST['descripcion'] ?? '');
            
            try {
                $stmt = $conn->prepare("INSERT INTO categorias (nombre, descripcion, estado) VALUES (?, ?, 1)");
                $stmt->execute([$nombre, $descripcion]);
                $message = "Categoría creada exitosamente";
                $messageType = "success";
            } catch (PDOException $e) {
                $message = "Error al crear la categoría: " . $e->getMessage();
                $messageType = "error";
            }
        }
        // Actualizar categoría
        elseif ($_POST['action'] === 'update' && !empty($_POST['id']) && !empty($_POST['nombre'])) {
            $id = (int)$_POST['id'];
            $nombre = trim($_POST['nombre']);
            $descripcion = trim($_POST['descripcion'] ?? '');
            
            try {
                $stmt = $conn->prepare("UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?");
                $stmt->execute([$nombre, $descripcion, $id]);
                $message = "Categoría actualizada exitosamente";
                $messageType = "success";
            } catch (PDOException $e) {
                $message = "Error al actualizar la categoría: " . $e->getMessage();
                $messageType = "error";
            }
        }
        // Eliminar categoría
        elseif ($_POST['action'] === 'delete' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            try {
                // Verificar si hay productos en esta categoría
                $stmt = $conn->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id = ?");
                $stmt->execute([$id]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $message = "No se puede eliminar la categoría porque tiene productos asociados";
                    $messageType = "error";
                } else {
                    $stmt = $conn->prepare("DELETE FROM categorias WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = "Categoría eliminada exitosamente";
                    $messageType = "success";
                }
            } catch (PDOException $e) {
                $message = "Error al eliminar la categoría: " . $e->getMessage();
                $messageType = "error";
            }
        }
    }
}

// Obtener estadísticas
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM categorias");
    $total_categorias = $stmt->fetchColumn();

    $stmt = $conn->query("SELECT COUNT(*) as total FROM categorias c 
                         WHERE EXISTS (SELECT 1 FROM productos p WHERE p.categoria_id = c.id)");
    $categorias_con_productos = $stmt->fetchColumn();

    $stmt = $conn->query("SELECT COUNT(*) as total FROM categorias c 
                         WHERE NOT EXISTS (SELECT 1 FROM productos p WHERE p.categoria_id = c.id)");
    $categorias_sin_productos = $stmt->fetchColumn();
} catch (PDOException $e) {
    $total_categorias = 0;
    $categorias_con_productos = 0;
    $categorias_sin_productos = 0;
}

// Obtener todas las categorías
try {
    $stmt = $conn->query("SELECT * FROM categorias ORDER BY id ASC");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error al obtener las categorías: " . $e->getMessage();
    $messageType = "error";
    $categorias = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías - Lubri Queen 77</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #666;
        }
        
        .floating {
            position: relative;
        }
        
        .floating label {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 14px;
            color: #666;
            transition: 0.2s;
        }
        
        .floating input:focus + label, .floating input:not(:placeholder-shown) + label {
            top: -15px;
            font-size: 12px;
            color: #333;
        }
        
        .floating textarea:focus + label, .floating textarea:not(:placeholder-shown) + label {
            top: -15px;
            font-size: 12px;
            color: #333;
        }
        
        /* Estilos de la tabla */
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .inventory-table th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }

        .inventory-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            color: #212529;
        }

        .inventory-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Estilos de los botones de acción */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: flex-start;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            color: white;
        }

        .btn-icon.edit {
            background-color: #0d6efd;
        }

        .btn-icon.edit:hover {
            background-color: #0b5ed7;
        }

        .btn-icon.delete {
            background-color: #dc3545;
        }

        .btn-icon.delete:hover {
            background-color: #bb2d3b;
        }

        .btn-icon i {
            font-size: 14px;
        }

        /* Estilos para los badges */
        .badge {
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-success {
            background-color: #198754;
            color: white;
        }

        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }

        /* Ajustes de columnas */
        .column-id {
            width: 80px;
        }

        .column-nombre {
            min-width: 200px;
        }

        .column-descripcion {
            min-width: 300px;
        }

        .column-productos {
            width: 120px;
        }

        .column-acciones {
            width: 100px;
        }

        /* Estilos para las alertas */
        .alert {
            position: relative;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .alert-success {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }

        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background-color: rgba(0, 0, 0, 0.2);
            width: 100%;
            transform-origin: left;
            animation: progress 3s linear forwards;
        }

        @keyframes progress {
            from {
                transform: scaleX(1);
            }
            to {
                transform: scaleX(0);
            }
        }

        .alert.fade-out {
            animation: fadeOut 0.5s ease forwards;
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-20px);
            }
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
                    <h1>Gestión de Categorías</h1>
                    <div class="inventory-stats">
                        <span class="stat-item">
                            <i class="fas fa-tags"></i> Total: <?php echo $total_categorias; ?>
                        </span>
                        <span class="stat-item success">
                            <i class="fas fa-check-circle"></i> Con Productos: <?php echo $categorias_con_productos; ?>
                        </span>
                        <span class="stat-item info">
                            <i class="fas fa-info-circle"></i> Sin Productos: <?php echo $categorias_sin_productos; ?>
                        </span>
                    </div>
                </div>
                <div class="btn-container">
                    <a href="#" class="add-btn" onclick="showModal('create')">
                        <i class="fas fa-plus"></i> Nueva Categoría
                    </a>
                </div>
            </header>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                    <div class="progress-bar"></div>
                </div>
            <?php endif; ?>

            <!-- Tabla de categorías -->
            <div class="table-container">
                <?php if (empty($categorias)): ?>
                    <div class="no-results">
                        <i class="fas fa-tag"></i>
                        <p>No hay categorías registradas.</p>
                        <div class="btn-container">
                            <a href="#" class="btn-primary" onclick="showModal('create')">
                                Crear Primera Categoría
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th class="column-id">ID</th>
                                <th class="column-nombre">Nombre</th>
                                <th class="column-descripcion">Descripción</th>
                                <th class="column-productos">Productos</th>
                                <th class="column-acciones">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $categoria): 
                                // Contar productos en esta categoría
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id = ?");
                                $stmt->execute([$categoria['id']]);
                                $num_productos = $stmt->fetchColumn();
                            ?>
                                <tr>
                                    <td class="column-id"><?php echo $categoria['id']; ?></td>
                                    <td class="column-nombre"><?php echo htmlspecialchars($categoria['nombre']); ?></td>
                                    <td class="column-descripcion"><?php echo htmlspecialchars($categoria['descripcion'] ?? ''); ?></td>
                                    <td class="column-productos">
                                        <span class="badge <?php echo $num_productos > 0 ? 'badge-success' : 'badge-secondary'; ?>">
                                            <?php echo $num_productos; ?> productos
                                        </span>
                                    </td>
                                    <td class="column-acciones">
                                        <div class="action-buttons">
                                            <button onclick="showModal('edit', <?php echo htmlspecialchars(json_encode($categoria)); ?>)" 
                                                    class="btn-icon edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($num_productos == 0): ?>
                                            <button onclick="deleteCategory(<?php echo $categoria['id']; ?>)" 
                                                    class="btn-icon delete" title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Modal para crear/editar categoría -->
            <div id="categoryModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h2 id="modalTitle">Nueva Categoría</h2>
                    <form id="categoryForm" method="POST" onsubmit="return validateForm()">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="categoryId">
                        
                        <div class="form-group floating">
                            <input type="text" id="nombre" name="nombre" required class="form-control" placeholder="Nombre de la categoría *">
                            <label for="nombre">Nombre de la categoría *</label>
                        </div>
                        
                        <div class="form-group floating">
                            <textarea id="descripcion" name="descripcion" class="form-control" placeholder="Descripción de la categoría"></textarea>
                            <label for="descripcion">Descripción de la categoría</label>
                        </div>
                        
                        <div class="form-group button-stack">
                            <button type="submit" class="btn-primary">Guardar</button>
                            <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('categoryModal');
        const form = document.getElementById('categoryForm');
        const modalTitle = document.getElementById('modalTitle');
        const formAction = document.getElementById('formAction');
        const categoryId = document.getElementById('categoryId');
        const nombreInput = document.getElementById('nombre');
        const descripcionInput = document.getElementById('descripcion');

        function showModal(action, categoria = null) {
            modal.style.display = 'block';
            form.reset();
            
            if (action === 'create') {
                modalTitle.textContent = 'Nueva Categoría';
                formAction.value = 'create';
                categoryId.value = '';
            } else if (action === 'edit') {
                modalTitle.textContent = 'Editar Categoría';
                formAction.value = 'update';
                categoryId.value = categoria.id;
                nombreInput.value = categoria.nombre;
                descripcionInput.value = categoria.descripcion || '';
            }
        }

        function closeModal() {
            modal.style.display = 'none';
            form.reset();
        }

        function validateForm() {
            const nombre = document.getElementById('nombre').value.trim();
            if (!nombre) {
                alert('Por favor, ingrese un nombre para la categoría');
                return false;
            }
            return true;
        }

        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        function deleteCategory(id) {
            if (confirm('¿Estás seguro de que deseas eliminar esta categoría?')) {
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

        function setupAlertDismissal() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                // Agregar barra de progreso
                const progressBar = document.createElement('div');
                progressBar.className = 'progress-bar';
                alert.appendChild(progressBar);

                // Configurar el temporizador para eliminar la alerta
                setTimeout(() => {
                    alert.classList.add('fade-out');
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }, 3000);
            });
        }

        // Llamar a la función cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', setupAlertDismissal);
    </script>
</body>
</html>
