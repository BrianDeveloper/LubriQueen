<?php
session_start();
require_once '../config/db.php';

// Verificar si el usuario está logueado y es cliente
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cliente') {
    header("Location: ../login.php");
    exit();
}

// Obtener los pedidos del usuario
$stmt = $conn->prepare("
    SELECT p.*, 
           COUNT(dp.producto_id) as num_productos,
           SUM(dp.cantidad * dp.precio_unitario) as total
    FROM pedidos p
    LEFT JOIN detalles_pedido dp ON p.id = dp.pedido_id
    WHERE p.usuario_id = :usuario_id
    GROUP BY p.id
    ORDER BY p.fecha_creacion DESC
");
$stmt->execute(['usuario_id' => $_SESSION['user_id']]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - LubriQueen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php include 'components/navbar.php'; ?>

    <div class="client-container">
        <h1>Mis Pedidos</h1>

        <?php if (empty($pedidos)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                No tienes pedidos realizados aún.
            </div>
        <?php else: ?>
            <div class="orders-grid">
                <?php foreach ($pedidos as $pedido): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <h3>Pedido #<?php echo $pedido['id']; ?></h3>
                            <span class="order-date">
                                <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])); ?>
                            </span>
                        </div>
                        
                        <div class="order-info">
                            <p>
                                <i class="fas fa-box"></i>
                                Productos: <?php echo $pedido['num_productos']; ?>
                            </p>
                            <p>
                                <i class="fas fa-money-bill"></i>
                                Total: $<?php echo number_format($pedido['total'], 2); ?>
                            </p>
                            <p>
                                <i class="fas fa-clock"></i>
                                Estado: 
                                <span class="status-badge status-<?php echo $pedido['estado']; ?>">
                                    <?php echo ucfirst($pedido['estado']); ?>
                                </span>
                            </p>
                        </div>
                        
                        <div class="order-actions">
                            <a href="order_details.php?id=<?php echo $pedido['id']; ?>" class="btn-secondary">
                                <i class="fas fa-eye"></i> Ver detalles
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
