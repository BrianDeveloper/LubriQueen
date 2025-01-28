<?php
session_start();
require_once '../config/db.php';

if (!isset($_GET['pedido_id']) || !isset($_SESSION['user_id'])) {
    header('Location: orders.php');
    exit();
}

$pedido_id = $_GET['pedido_id'];
$usuario_id = $_SESSION['user_id'];

// Obtener información del pedido y método de pago
$stmt = $conn->prepare("
    SELECT p.*, u.nombre as nombre_cliente, u.email, dp.metodo_pago, dp.direccion_envio
    FROM pedidos p
    JOIN usuarios u ON p.usuario_id = u.id
    JOIN detalles_pedido dp ON p.id = dp.pedido_id
    WHERE p.id = ? AND p.usuario_id = ?
    LIMIT 1
");
$stmt->execute([$pedido_id, $usuario_id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    header('Location: orders.php');
    exit();
}

// Obtener detalles del pedido
$stmt = $conn->prepare("
    SELECT dp.*, p.nombre as nombre_producto
    FROM detalles_pedido dp
    JOIN productos p ON dp.producto_id = p.id
    WHERE dp.pedido_id = ?
");
$stmt->execute([$pedido_id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura #<?php echo $pedido_id; ?> - LubriQueen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .factura {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: white;
        }
        .factura-header {
            border-bottom: 2px solid #ddd;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
        }
        .factura-header img {
            max-width: 200px;
        }
        .empresa-info {
            text-align: right;
        }
        .cliente-info {
            margin: 2rem 0;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .total-section {
            margin-top: 2rem;
            border-top: 2px solid #ddd;
            padding-top: 1rem;
        }
        .print-button {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
        }
        @media print {
            .no-print {
                display: none;
            }
            .factura {
                border: none;
                padding: 0;
            }
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'components/navbar.php'; ?>

    <div class="container">
        <div class="factura shadow">
            <div class="factura-header">
                <div class="row">
                    <div class="col-md-6">
                        <h1>LubriQueen</h1>
                        <p>Especialistas en Lubricantes</p>
                    </div>
                    <div class="col-md-6 empresa-info">
                        <p><strong>Factura #:</strong> <?php echo str_pad($pedido_id, 6, '0', STR_PAD_LEFT); ?></p>
                        <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($pedido['fecha_creacion'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="cliente-info">
                <h5>Información del Cliente</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($pedido['nombre_cliente']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($pedido['email']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Método de Pago:</strong> <?php echo ucfirst($pedido['metodo_pago']); ?></p>
                        <p><strong>Estado del Pedido:</strong> <?php echo ucfirst($pedido['estado']); ?></p>
                        <p><strong>Dirección de Envío:</strong> <?php echo htmlspecialchars($pedido['direccion_envio']); ?></p>
                    </div>
                </div>
            </div>

            <div class="detalles-pedido">
                <h5>Detalles del Pedido</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-end">Precio Unitario</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $detalle): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($detalle['nombre_producto']); ?></td>
                                <td class="text-center"><?php echo $detalle['cantidad']; ?></td>
                                <td class="text-end">$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($detalle['cantidad'] * $detalle['precio_unitario'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="total-section">
                <div class="row">
                    <div class="col-md-6"></div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end">$<?php echo number_format($pedido['total'] / 1.16, 2); ?></td>
                            </tr>
                            <tr>
                                <td class="text-end"><strong>IVA (16%):</strong></td>
                                <td class="text-end">$<?php echo number_format($pedido['total'] - ($pedido['total'] / 1.16), 2); ?></td>
                            </tr>
                            <tr>
                                <td class="text-end"><strong>Total:</strong></td>
                                <td class="text-end"><strong>$<?php echo number_format($pedido['total'], 2); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 mb-5 no-print">
            <button class="btn btn-primary me-2" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir Factura
            </button>
            <a href="generar_pdf.php?pedido_id=<?php echo $pedido_id; ?>" class="btn btn-success">
                <i class="fas fa-download"></i> Descargar PDF
            </a>
            <a href="orders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Pedidos
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
