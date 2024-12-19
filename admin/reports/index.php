<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está logueado y es administrador
checkUserRole('admin');

// Obtener fechas del formulario
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$reportType = isset($_GET['report_type']) ? $_GET['report_type'] : 'all';

try {
    // Consulta base para movimientos
    $query = "SELECT 
                mi.id,
                mi.tipo_movimiento,
                mi.cantidad,
                DATE(mi.fecha) as fecha_movimiento,
                p.nombre as producto,
                u.nombre as usuario
              FROM movimientos_inventario mi
              JOIN productos p ON mi.producto_id = p.id
              JOIN usuarios u ON mi.usuario_id = u.id
              WHERE DATE(mi.fecha) BETWEEN :start_date AND :end_date";

    // Modificar consulta según el tipo de reporte
    if ($reportType === 'entradas') {
        $query .= " AND mi.tipo_movimiento = 'entrada'";
    } elseif ($reportType === 'salidas') {
        $query .= " AND mi.tipo_movimiento = 'salida'";
    }

    $query .= " ORDER BY mi.fecha DESC";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular totales
    $totalEntradas = 0;
    $totalSalidas = 0;
    foreach ($movimientos as $mov) {
        if ($mov['tipo_movimiento'] === 'entrada') {
            $totalEntradas += $mov['cantidad'];
        } else {
            $totalSalidas += $mov['cantidad'];
        }
    }

} catch(PDOException $e) {
    die("Error en la consulta: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Lubri Queen 77</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        /* Estilos del sidebar */
        .sidebar {
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            background-color: #343a40;
            color: white;
            z-index: 1000;
        }

        /* Estilos específicos para el contenedor admin */
        .admin-container {
            display: flex;
            min-height: 100vh;
            background-color: #f4f6f9;
            margin-left: 250px; /* Espacio para el sidebar */
        }

        .content {
            flex: 1;
            padding: 20px;
            background-color: #f4f6f9;
            height: 100vh;
            overflow-y: auto;
            width: 100%; /* Usar todo el espacio disponible */
        }

        .content-header {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
        }

        .content-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 500;
        }

        .reports-container {
            max-width: 100%;
            margin: 0 auto;
        }

        .filters-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filters-form .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .filters-form .form-group {
            flex: 1;
        }

        .report-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .report-summary {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
        }

        .summary-card {
            flex: 1;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }

        .summary-card.entradas {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .summary-card.salidas {
            background-color: #ffebee;
            color: #c62828;
        }

        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 1.1em;
        }

        .summary-card .value {
            font-size: 1.8em;
            font-weight: bold;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .report-table th,
        .report-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .report-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .report-table tr:hover {
            background-color: #f5f5f5;
        }

        .entrada {
            color: #2e7d32;
            font-weight: 600;
        }

        .salida {
            color: #c62828;
            font-weight: 600;
        }

        .export-btn {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 10px;
        }

        .export-btn.excel {
            background-color: #217346;
        }

        .export-btn.excel:hover {
            background-color: #1e6339;
        }

        .export-btn.pdf {
            background-color: #dc3545;
        }

        .export-btn.pdf:hover {
            background-color: #c82333;
        }

        .export-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        /* Estilos para inputs flotantes */
        .form-floating {
            position: relative;
            margin-bottom: 1rem;
        }

        .form-floating > .form-control {
            height: calc(3.5rem + 2px);
            line-height: 1.25;
            padding: 1rem 0.75rem;
        }

        .form-floating > label {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            padding: 1rem 0.75rem;
            overflow: hidden;
            text-align: start;
            text-overflow: ellipsis;
            white-space: nowrap;
            pointer-events: none;
            border: 1px solid transparent;
            transform-origin: 0 0;
            transition: opacity .1s ease-in-out,transform .1s ease-in-out;
            color: #6c757d;
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            opacity: .65;
            transform: scale(.85) translateY(-0.5rem) translateX(0.15rem);
            background-color: white;
            padding: 0 5px;
            left: 10px;
            top: -5px;
            height: auto;
        }

        .form-floating > .form-control:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        /* Ajustes responsive */
        @media (max-width: 768px) {
            .admin-container {
                margin-left: 0;
                padding-top: 60px; /* Espacio para el header móvil */
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: fixed;
                top: 0;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .content {
                width: 100%;
                padding: 10px;
            }

            .filters-form .form-row {
                flex-direction: column;
                gap: 10px;
            }

            .report-summary {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include '../components/sidebar.php'; ?>
        
        <div class="content">
            <header class="content-header">
                <h1>Reportes de Movimientos</h1>
            </header>

            <div class="reports-container">
                <form class="filters-form" method="GET">
                    <div class="form-row">
                        <div class="form-group">
                            <div class="form-floating">
                                <input type="date" id="start_date" name="start_date" class="form-control" 
                                       value="<?php echo $startDate; ?>" placeholder=" ">
                                <label for="start_date">Fecha Inicio</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-floating">
                                <input type="date" id="end_date" name="end_date" class="form-control" 
                                       value="<?php echo $endDate; ?>" placeholder=" ">
                                <label for="end_date">Fecha Fin</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-floating">
                                <select id="report_type" name="report_type" class="form-control">
                                    <option value="all" <?php echo $reportType === 'all' ? 'selected' : ''; ?>>Todos</option>
                                    <option value="entradas" <?php echo $reportType === 'entradas' ? 'selected' : ''; ?>>Entradas</option>
                                    <option value="salidas" <?php echo $reportType === 'salidas' ? 'selected' : ''; ?>>Salidas</option>
                                </select>
                                <label for="report_type">Tipo de Movimiento</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>

                <div class="report-summary">
                    <div class="summary-card entradas">
                        <h3>Total Entradas</h3>
                        <div class="value"><?php echo $totalEntradas; ?></div>
                    </div>
                    <div class="summary-card salidas">
                        <h3>Total Salidas</h3>
                        <div class="value"><?php echo $totalSalidas; ?></div>
                    </div>
                </div>

                <div class="report-actions">
                    <h2>Resultados</h2>
                    <div class="export-actions">
                        <button type="button" class="export-btn pdf" onclick="exportarPDF()">
                            <i class="fas fa-file-pdf"></i> Exportar a PDF
                        </button>
                        <button type="button" class="export-btn excel" onclick="exportarExcel()">
                            <i class="fas fa-file-excel"></i> Exportar a Excel
                        </button>
                    </div>
                </div>

                <script>
                function exportarPDF() {
                    exportarReporte('generar_pdf.php');
                }

                function exportarExcel() {
                    exportarReporte('generar_excel.php');
                }

                function exportarReporte(url) {
                    // Crear un formulario temporal
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;
                    form.target = '_blank';

                    // Agregar los filtros actuales
                    const inputs = {
                        'start_date': '<?php echo $startDate; ?>',
                        'end_date': '<?php echo $endDate; ?>',
                        'report_type': '<?php echo $reportType; ?>'
                    };

                    // Crear inputs ocultos
                    for (const [name, value] of Object.entries(inputs)) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = name;
                        input.value = value;
                        form.appendChild(input);
                    }

                    // Agregar al documento y enviar
                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                }
                </script>

                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimientos)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No hay movimientos para mostrar en el período seleccionado</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($movimientos as $mov): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($mov['fecha_movimiento'])); ?></td>
                                    <td><?php echo htmlspecialchars($mov['producto']); ?></td>
                                    <td class="<?php echo $mov['tipo_movimiento']; ?>">
                                        <?php echo ucfirst($mov['tipo_movimiento']); ?>
                                    </td>
                                    <td><?php echo $mov['cantidad']; ?></td>
                                    <td><?php echo htmlspecialchars($mov['usuario']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
