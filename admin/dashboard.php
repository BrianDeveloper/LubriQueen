<?php
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
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
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Lubri Queen 77</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="inventory/index.php">
                    <i class="fas fa-boxes"></i> Inventario
                </a>
                <a href="inventory/categories.php">
                    <i class="fas fa-tags"></i> Categorías
                </a>
                <a href="users.php">
                    <i class="fas fa-users"></i> Usuarios
                </a>
                <a href="../auth/logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </nav>
        </div>

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
                        require_once '../config/db.php';
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
                <div class="action-buttons">
                    <a href="inventory/add.php" class="action-btn">
                        <i class="fas fa-plus"></i> Agregar Producto
                    </a>
                    <a href="inventory/stock.php" class="action-btn">
                        <i class="fas fa-boxes"></i> Gestionar Stock
                    </a>
                    <a href="inventory/categories.php" class="action-btn">
                        <i class="fas fa-tag"></i> Nueva Categoría
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
