<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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

    // Crear nuevo documento Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Establecer título
    $sheet->setCellValue('A1', 'Reporte de Movimientos - LubriQueen 77');
    $sheet->mergeCells('A1:E1');

    // Información del período y tipo de reporte
    $sheet->setCellValue('A2', 'Período: ' . date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)));
    $sheet->mergeCells('A2:E2');

    $tipoReporteTexto = $reportType === 'all' ? 'Todos los movimientos' : 
                       ($reportType === 'entradas' ? 'Solo entradas' : 'Solo salidas');
    $sheet->setCellValue('A3', 'Tipo de reporte: ' . $tipoReporteTexto);
    $sheet->mergeCells('A3:E3');

    // Totales
    $sheet->setCellValue('A4', 'Total Entradas: ' . $totalEntradas);
    $sheet->setCellValue('C4', 'Total Salidas: ' . $totalSalidas);
    $sheet->mergeCells('A4:B4');
    $sheet->mergeCells('C4:E4');

    // Encabezados de la tabla
    $headers = ['Fecha', 'Producto', 'Tipo', 'Cantidad', 'Usuario'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '6', $header);
        $col++;
    }

    // Datos de la tabla
    $row = 7;
    foreach ($movimientos as $mov) {
        $sheet->setCellValue('A' . $row, date('d/m/Y', strtotime($mov['fecha_movimiento'])));
        $sheet->setCellValue('B' . $row, $mov['producto']);
        $sheet->setCellValue('C' . $row, ucfirst($mov['tipo_movimiento']));
        $sheet->setCellValue('D' . $row, $mov['cantidad']);
        $sheet->setCellValue('E' . $row, $mov['usuario']);
        $row++;
    }

    // Estilos
    $titleStyle = [
        'font' => [
            'bold' => true,
            'size' => 14
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ]
    ];

    $headerStyle = [
        'font' => [
            'bold' => true
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => [
                'rgb' => 'E6E6E6'
            ]
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN
            ]
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ]
    ];

    $dataStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN
            ]
        ]
    ];

    // Aplicar estilos
    $sheet->getStyle('A1:E1')->applyFromArray($titleStyle);
    $sheet->getStyle('A6:E6')->applyFromArray($headerStyle);
    $sheet->getStyle('A7:E' . ($row - 1))->applyFromArray($dataStyle);

    // Autoajustar columnas
    foreach (range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Configurar headers para la descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Reporte_Movimientos_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');

    // Crear el archivo Excel
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch(Exception $e) {
    die("Error al generar el Excel: " . $e->getMessage());
}
?>
