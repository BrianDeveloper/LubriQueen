<?php
session_start();
require_once '../config/db.php';

// Verificar si el usuario está logueado y es cliente
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cliente') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LubriQueen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="css/client.css">
    <style>
        .cart-count {
            margin-left: 8px;
            background-color: #dc3545;
            color: white;
            padding: 2px 6px;
            border-radius: 50%;
            font-size: 0.8em;
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }
        .nav-link {
            color: #666;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: #dc3545;
        }
        .nav-link.active {
            color: #dc3545;
            font-weight: bold;
        }
        .main-content {
            padding-top: 80px;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-card i {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Navbar Superior -->
    <?php include 'components/navbar.php'; ?>

    <!-- Contenido Principal -->
    <div class="container main-content">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="mb-4">Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="fas fa-shopping-cart"></i>
                    <?php
                    // Obtener productos en carrito del cliente actual
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM detalles_pedido dp 
                                          JOIN pedidos p ON dp.pedido_id = p.id 
                                          WHERE p.usuario_id = ? AND p.estado = 'pendiente'");
                    $stmt->execute([$_SESSION['user_id']]);
                    $carrito = $stmt->fetchColumn();
                    ?>
                    <h3><?php echo $carrito; ?></h3>
                    <p class="mb-0">Productos en Carrito</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="fas fa-box"></i>
                    <h3>Productos Disponibles</h3>
                    <p class="mb-0">Ver catálogo completo</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="fas fa-list"></i>
                    <?php
                    // Obtener cantidad de pedidos del cliente
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM pedidos WHERE usuario_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $pedidos = $stmt->fetchColumn();
                    ?>
                    <h3><?php echo $pedidos; ?></h3>
                    <p class="mb-0">Mis Pedidos</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/cart.js"></script>
</body>
</html>
