<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está logueado y es administrador
checkUserRole('admin');

// Obtener parámetros
$startDate = $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_POST['end_date'] ?? date('Y-m-d');
$reportType = $_POST['report_type'] ?? 'all';

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

    require_once '../../vendor/autoload.php';

    // Crear nuevo PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Configurar PDF
    $pdf->SetCreator('LubriQueen 77');
    $pdf->SetAuthor('Administrador');
    $pdf->SetTitle('Reporte de Movimientos');

    // Eliminar cabecera y pie de página predeterminados
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Agregar página
    $pdf->AddPage();

    // Establecer fuente
    $pdf->SetFont('helvetica', 'B', 16);

    // Título del reporte
    $pdf->Cell(0, 10, 'Reporte de Movimientos - LubriQueen 77', 0, 1, 'C');
    $pdf->Ln(5);

    // Información del período
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Período: ' . date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)), 0, 1, 'L');
    
    // Tipo de reporte
    $tipoReporteTexto = $reportType === 'all' ? 'Todos los movimientos' : 
                       ($reportType === 'entradas' ? 'Solo entradas' : 'Solo salidas');
    $pdf->Cell(0, 10, 'Tipo de reporte: ' . $tipoReporteTexto, 0, 1, 'L');
    
    // Totales
    $pdf->Cell(0, 10, 'Total Entradas: ' . $totalEntradas, 0, 1, 'L');
    $pdf->Cell(0, 10, 'Total Salidas: ' . $totalSalidas, 0, 1, 'L');
    $pdf->Ln(5);

    // Encabezados de la tabla
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(40, 7, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell(60, 7, 'Producto', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Tipo', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Cantidad', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Usuario', 1, 1, 'C', true);

    // Datos de la tabla
    $pdf->SetFont('helvetica', '', 10);
    foreach ($movimientos as $mov) {
        $pdf->Cell(40, 6, date('d/m/Y', strtotime($mov['fecha_movimiento'])), 1, 0, 'C');
        $pdf->Cell(60, 6, $mov['producto'], 1, 0, 'L');
        $pdf->Cell(30, 6, ucfirst($mov['tipo_movimiento']), 1, 0, 'C');
        $pdf->Cell(30, 6, $mov['cantidad'], 1, 0, 'C');
        $pdf->Cell(30, 6, $mov['usuario'], 1, 1, 'L');
    }

    // Nombre sugerido para el archivo
    $nombreArchivo = 'Reporte_Movimientos_' . date('Y-m-d') . '.pdf';

    // Configurar headers para forzar el diálogo de guardado
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Content-Transfer-Encoding: binary');
    header('Accept-Ranges: bytes');

    // Generar y enviar el PDF
    $pdf->Output($nombreArchivo, 'I');
    exit;

} catch(PDOException $e) {
    die("Error en la consulta: " . $e->getMessage());
}
?>
