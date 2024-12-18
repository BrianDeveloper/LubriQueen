<?php
session_start();
require_once '../../config/db.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../index.php");
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
    $stmt = $conn->query("SELECT * FROM categorias ORDER BY nombre");
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
                <a href="index.php">
                    <i class="fas fa-boxes"></i> Inventario
                </a>
                <a href="categories.php" class="active">
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
                <button class="add-btn" onclick="showModal('create')">
                    <i class="fas fa-plus"></i> Nueva Categoría
                </button>
            </header>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Tabla de categorías -->
            <div class="table-container">
                <?php if (empty($categorias)): ?>
                    <div class="no-results">
                        <i class="fas fa-tag"></i>
                        <p>No hay categorías registradas.</p>
                        <button class="btn-primary" onclick="showModal('create')">
                            Crear Primera Categoría
                        </button>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Productos</th>
                                <th>Acciones</th>
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
                                    <td><?php echo $categoria['id']; ?></td>
                                    <td><?php echo htmlspecialchars($categoria['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($categoria['descripcion'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge <?php echo $num_productos > 0 ? 'badge-success' : 'badge-secondary'; ?>">
                                            <?php echo $num_productos; ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <button class="btn-icon edit" onclick="showModal('edit', <?php echo htmlspecialchars(json_encode($categoria)); ?>)" title="Editar categoría">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($num_productos == 0): ?>
                                            <button class="btn-icon delete" onclick="deleteCategory(<?php echo $categoria['id']; ?>, '<?php echo htmlspecialchars($categoria['nombre']); ?>')" title="Eliminar categoría">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-icon delete disabled" title="No se puede eliminar - tiene productos asociados">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
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
                        
                        <div class="form-group">
                            <label for="nombre">Nombre de la Categoría <span class="required">*</span></label>
                            <input type="text" id="nombre" name="nombre" required 
                                   placeholder="Ingrese el nombre de la categoría">
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción de la Categoría</label>
                            <textarea id="descripcion" name="descripcion" rows="4"
                                    placeholder="Ingrese una descripción detallada de la categoría"></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                            <button type="submit" class="btn-primary" id="submitBtn">Guardar Categoría</button>
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
            if (action === 'edit' && categoria) {
                modalTitle.textContent = 'Editar Categoría';
                formAction.value = 'update';
                categoryId.value = categoria.id;
                nombreInput.value = categoria.nombre;
                descripcionInput.value = categoria.descripcion || '';
            } else {
                modalTitle.textContent = 'Nueva Categoría';
                formAction.value = 'create';
                categoryId.value = '';
                form.reset();
            }
            modal.style.display = 'block';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
            nombreInput.focus();
        }

        function closeModal() {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                form.reset();
            }, 300);
        }

        function validateForm() {
            const nombre = nombreInput.value.trim();
            if (!nombre) {
                alert('Por favor, ingrese un nombre para la categoría');
                nombreInput.focus();
                return false;
            }
            return true;
        }

        function deleteCategory(id, nombre) {
            if (confirm(`¿Está seguro de que desea eliminar la categoría "${nombre}"?`)) {
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

        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        // Cerrar modal con la tecla Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.style.display === 'block') {
                closeModal();
            }
        });
    </script>
</body>
</html>
