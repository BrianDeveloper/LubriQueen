<?php
if (!isset($conn)) {
    require_once __DIR__ . '/../../config/db.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);

// Obtener el contador del carrito
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM carrito WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetch()['total'];
}
?>

<nav class="client-navbar">
    <div class="navbar-content">
        <a href="dashboard.php" class="navbar-brand">
            <i class="fas fa-oil-can"></i>
            <span>LubriQueen</span>
        </a>
        
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Inicio</span>
            </a>
            <a href="products.php" class="nav-link <?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i>
                <span>Productos</span>
            </a>
            <a href="orders.php" class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i>
                <span>Mis Pedidos</span>
            </a>
            <a href="history.php" class="nav-link <?php echo $current_page == 'history.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                <span>Historial</span>
            </a>
            <a href="cart.php" class="nav-link <?php echo $current_page == 'cart.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Carrito</span>
                <span id="cart-counter" class="cart-count"><?php echo $cart_count; ?></span>
            </a>
            <div class="nav-divider"></div>
            <a href="../auth/logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </div>
</nav>

<!-- Espaciador para el navbar fijo -->
<div style="margin-top: 80px;"></div>

<style>
.client-navbar {
    background-color: white;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.navbar-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.navbar-brand {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
    text-decoration: none;
    display: flex;
    align-items: center;
}

.navbar-brand i {
    color: #dc3545;
    margin-right: 8px;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.nav-link {
    color: #666;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.nav-link:hover {
    color: #dc3545;
    background-color: rgba(220, 53, 69, 0.1);
}

.nav-link.active {
    color: #dc3545;
    font-weight: bold;
    background-color: rgba(220, 53, 69, 0.1);
}

.nav-link i {
    font-size: 1.2rem;
}

.cart-count {
    background-color: #dc3545;
    color: white;
    padding: 2px 6px;
    border-radius: 50%;
    font-size: 0.8em;
    min-width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.nav-divider {
    width: 1px;
    height: 24px;
    background-color: #ddd;
    margin: 0 0.5rem;
}

@media (max-width: 768px) {
    .nav-links {
        display: none;
    }
}
</style>
