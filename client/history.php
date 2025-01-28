<?php
session_start();
require_once '../config/db.php';

// Verificar si el usuario está logueado y es cliente
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cliente') {
    header("Location: ../login.php");
    exit();
}

$usuario_id = $_SESSION['user_id'];

// Obtener todos los pedidos del usuario con su último estado
$sql = "SELECT p.*, 
               COUNT(dp.id) as total_productos,
               SUM(dp.cantidad * dp.precio_unitario) as total_pedido,
               (SELECT estado_nuevo 
                FROM historial_pedidos hp 
                WHERE hp.pedido_id = p.id 
                ORDER BY hp.fecha_creacion DESC 
                LIMIT 1) as ultimo_estado
        FROM pedidos p
        LEFT JOIN detalles_pedido dp ON p.id = dp.pedido_id
        WHERE p.usuario_id = :usuario_id
        GROUP BY p.id
        ORDER BY p.fecha_creacion DESC";

$stmt = $conn->prepare($sql);
$stmt->execute(['usuario_id' => $usuario_id]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener detalles de un pedido específico si se solicita
$detalles_pedido = [];
if (isset($_GET['pedido_id'])) {
    $pedido_id = $_GET['pedido_id'];
    $sql_detalles = "SELECT dp.*, p.nombre as producto_nombre, p.imagen as producto_imagen
                     FROM detalles_pedido dp
                     JOIN productos p ON dp.producto_id = p.id
                     WHERE dp.pedido_id = :pedido_id";
    
    $stmt_detalles = $conn->prepare($sql_detalles);
    $stmt_detalles->execute(['pedido_id' => $pedido_id]);
    $detalles_pedido = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener el historial de estados si se solicita
$historial_estados = [];
if (isset($_GET['pedido_id'])) {
    $sql_historial = "SELECT hp.*, u.nombre as usuario_nombre
                      FROM historial_pedidos hp
                      JOIN usuarios u ON hp.usuario_id = u.id
                      WHERE hp.pedido_id = :pedido_id
                      ORDER BY hp.fecha_creacion DESC";
    
    $stmt_historial = $conn->prepare($sql_historial);
    $stmt_historial->execute(['pedido_id' => $_GET['pedido_id']]);
    $historial_estados = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Compras - LubriQueen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php include 'components/navbar.php'; ?>

    <div class="client-container">
        <h1>Historial de Compras</h1>

        <?php if (empty($pedidos)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                No tienes compras realizadas aún.
            </div>
        <?php else: ?>
            <div class="history-container">
                <!-- Lista de Pedidos -->
                <div class="orders-list">
                    <?php foreach ($pedidos as $pedido): ?>
                        <div class="order-card <?php echo isset($_GET['pedido_id']) && $_GET['pedido_id'] == $pedido['id'] ? 'active' : ''; ?>">
                            <div class="order-header">
                                <h3>Pedido #<?php echo $pedido['id']; ?></h3>
                                <span class="order-date">
                                    <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])); ?>
                                </span>
                            </div>
                            
                            <div class="order-info">
                                <p>
                                    <i class="fas fa-box"></i>
                                    Productos: <?php echo $pedido['total_productos']; ?>
                                </p>
                                <p>
                                    <i class="fas fa-money-bill"></i>
                                    Total: $<?php echo number_format($pedido['total_pedido'], 2); ?>
                                </p>
                                <p>
                                    <i class="fas fa-clock"></i>
                                    Estado: 
                                    <span class="status-badge status-<?php echo $pedido['ultimo_estado']; ?>">
                                        <?php echo ucfirst($pedido['ultimo_estado']); ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div class="order-actions">
                                <a href="?pedido_id=<?php echo $pedido['id']; ?>" class="btn-secondary">
                                    <i class="fas fa-eye"></i> Ver detalles
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Detalles del Pedido -->
                <?php if (!empty($detalles_pedido)): ?>
                    <div class="order-details">
                        <h2>Detalles del Pedido #<?php echo $_GET['pedido_id']; ?></h2>
                        
                        <div class="products-grid">
                            <?php foreach ($detalles_pedido as $detalle): ?>
                                <div class="product-card">
                                    <?php if ($detalle['producto_imagen']): ?>
                                        <img src="../uploads/productos/<?php echo $detalle['producto_imagen']; ?>" 
                                             alt="<?php echo $detalle['producto_nombre']; ?>"
                                             class="product-image">
                                    <?php endif; ?>
                                    
                                    <div class="product-info">
                                        <h4><?php echo $detalle['producto_nombre']; ?></h4>
                                        <p>Cantidad: <?php echo $detalle['cantidad']; ?></p>
                                        <p>Precio: $<?php echo number_format($detalle['precio_unitario'], 2); ?></p>
                                        <p>Subtotal: $<?php echo number_format($detalle['subtotal'], 2); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!empty($historial_estados)): ?>
                            <div class="status-history">
                                <h3>Seguimiento del Pedido</h3>
                                <div class="timeline">
                                    <?php foreach ($historial_estados as $estado): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-badge status-<?php echo $estado['estado_nuevo']; ?>">
                                                <i class="fas fa-circle"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <h4><?php echo ucfirst($estado['estado_nuevo']); ?></h4>
                                                <p class="timeline-date">
                                                    <?php echo date('d/m/Y H:i', strtotime($estado['fecha_creacion'])); ?>
                                                </p>
                                                <?php if ($estado['comentario']): ?>
                                                    <p class="timeline-comment"><?php echo $estado['comentario']; ?></p>
                                                <?php endif; ?>
                                                <p class="timeline-user">por <?php echo $estado['usuario_nombre']; ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif (isset($_GET['pedido_id'])): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        No se encontraron detalles para este pedido.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
