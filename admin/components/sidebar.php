<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <h2>Lubri Queen 77</h2>
    </div>
    <nav class="sidebar-nav">
        <a href="/LubriQueen/admin/dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="/LubriQueen/admin/inventory/index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], '/inventory/') !== false ? 'class="active"' : ''; ?>>
            <i class="fas fa-boxes"></i> Inventario
        </a>
        <a href="/LubriQueen/admin/inventory/categories.php" <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'class="active"' : ''; ?>>
            <i class="fas fa-tags"></i> Categorías
        </a>
        <a href="/LubriQueen/admin/reports/index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], '/reports/') !== false ? 'class="active"' : ''; ?>>
            <i class="fas fa-chart-bar"></i> Reportes
        </a>
        <a href="/LubriQueen/admin/sales.php" <?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'class="active"' : ''; ?>>
            <i class="fas fa-shopping-cart"></i> Clientes/Compras
        </a>
        <a href="/LubriQueen/auth/logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </a>
    </nav>
</div>
