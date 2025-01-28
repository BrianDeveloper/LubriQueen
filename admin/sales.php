<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificar si el usuario está logueado y es administrador
checkUserRole('admin');

// Obtener filtros
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // Consulta base para ventas
    $query = "SELECT 
                mi.id,
                mi.tipo_movimiento,
                mi.cantidad,
                p.precio as precio_unitario,
                (mi.cantidad * p.precio) as total,
                mi.fecha,
                p.nombre as producto,
                u.nombre as cliente,
                u.email as email_cliente
              FROM movimientos_inventario mi
              JOIN productos p ON mi.producto_id = p.id
              JOIN usuarios u ON mi.usuario_id = u.id
              WHERE mi.tipo_movimiento = 'salida'
              AND DATE(mi.fecha) BETWEEN :start_date AND :end_date";

    // Agregar búsqueda si existe
    if (!empty($searchTerm)) {
        $query .= " AND (p.nombre LIKE :search 
                   OR u.nombre LIKE :search 
                   OR u.email LIKE :search)";
    }

    $query .= " ORDER BY mi.fecha DESC";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    
    if (!empty($searchTerm)) {
        $searchParam = "%$searchTerm%";
        $stmt->bindParam(':search', $searchParam);
    }

    $stmt->execute();
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular totales
    $totalVentas = array_reduce($ventas, function($carry, $item) {
        return $carry + $item['total'];
    }, 0);

    $totalProductos = array_reduce($ventas, function($carry, $item) {
        return $carry + $item['cantidad'];
    }, 0);

} catch(PDOException $e) {
    die("Error en la consulta: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes y Compras - LubriQueen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/LubriQueen/css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'components/sidebar.php'; ?>
        
        <div class="content-container">
            <div class="content-header">
                <h1>Clientes y Compras</h1>
            </div>

            <!-- Filtros -->
            <div class="filters-container">
                <form action="" method="GET" class="filters-form">
                    <div class="form-group">
                        <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>" placeholder=" ">
                        <label for="start_date">Fecha Inicio</label>
                    </div>
                    <div class="form-group">
                        <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>" placeholder=" ">
                        <label for="end_date">Fecha Fin</label>
                    </div>
                    <div class="form-group">
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder=" ">
                        <label for="search">Buscar</label>
                    </div>
                    <button type="submit" class="btn-primary">Filtrar</button>
                </form>
            </div>

            <!-- Resumen -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="summary-info">
                        <h3>Total Ventas</h3>
                        <p>$<?php echo number_format($totalVentas, 2); ?></p>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="summary-info">
                        <h3>Productos Vendidos</h3>
                        <p><?php echo number_format($totalProductos); ?></p>
                    </div>
                </div>
            </div>

            <!-- Tabla de Ventas -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Cliente</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ventas)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No se encontraron ventas en este período</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($ventas as $venta): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?></td>
                                <td><?php echo htmlspecialchars($venta['producto']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($venta['cliente']); ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($venta['email_cliente']); ?></small>
                                </td>
                                <td><?php echo number_format($venta['cantidad']); ?></td>
                                <td>$<?php echo number_format($venta['precio_unitario'], 2); ?></td>
                                <td>$<?php echo number_format($venta['total'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        /* Estilos del layout principal */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            background-color: #f5f6fa;
        }

        .content-container {
            flex: 1;
            padding: 30px;
            margin-left: 280px; /* Ancho del sidebar + margen */
            background-color: #f5f6fa;
        }

        /* Estilos de las tarjetas de resumen */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .summary-icon {
            background: var(--primary-color);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .summary-info h3 {
            margin: 0;
            font-size: 0.9rem;
            color: #666;
        }

        .summary-info p {
            margin: 0.5rem 0 0;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .text-muted {
            color: #6c757d;
            font-size: 0.85em;
        }

        /* Estilos de los filtros */
        .filters-container {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filters-form {
            display: flex;
            gap: 1.5rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filters-form .form-group {
            flex: 1;
            min-width: 200px;
            position: relative;
            margin-bottom: 0;
        }

        .filters-form .form-group label {
            position: absolute;
            left: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background-color: white;
            padding: 0 0.5rem;
            color: #666;
            transition: all 0.2s ease-in-out;
            pointer-events: none;
            font-size: 0.9rem;
        }

        .filters-form .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: all 0.2s ease-in-out;
        }

        .filters-form .form-group input:focus,
        .filters-form .form-group input:not(:placeholder-shown) {
            border-color: var(--primary-color);
            outline: none;
        }

        .filters-form .form-group input:focus + label,
        .filters-form .form-group input:not(:placeholder-shown) + label {
            top: 0;
            font-size: 0.8rem;
            color: var(--primary-color);
        }

        .filters-form button {
            padding: 0.8rem 2rem;
            height: 45px;
            margin-bottom: 0;
        }

        /* Estilos del contenido */
        .content-header {
            margin-bottom: 2rem;
        }

        .content-header h1 {
            margin: 0;
            font-size: 1.8rem;
            color: #2d3748;
        }

        /* Estilos de la tabla */
        .table-container {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #edf2f7;
        }

        .data-table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #4a5568;
        }

        .data-table tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-container {
                margin-left: 80px; /* Ancho del sidebar colapsado + margen */
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .content-container {
                margin-left: 70px;
                padding: 15px;
            }

            .filters-form {
                flex-direction: column;
                gap: 1rem;
            }

            .filters-form .form-group {
                width: 100%;
            }

            .filters-form button {
                width: 100%;
            }
        }
    </style>
</body>
</html>
