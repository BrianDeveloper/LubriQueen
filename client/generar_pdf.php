<?php
session_start();
require_once '../config/db.php';
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';

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

// Crear nuevo documento PDF
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 20);
        $this->Cell(0, 15, 'LubriQueen', 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->Ln(10);
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 10, 'Especialistas en Lubricantes', 0, false, 'L', 0, '', 0, false, 'M', 'M');
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Crear nuevo documento PDF
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Establecer información del documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('LubriQueen');
$pdf->SetTitle('Factura #' . $pedido_id);

// Establecer márgenes
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Establecer saltos de página automáticos
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Agregar página
$pdf->AddPage();

// Información de la factura
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Factura #: ' . str_pad($pedido_id, 6, '0', STR_PAD_LEFT), 0, 1, 'R');
$pdf->Cell(0, 10, 'Fecha: ' . date('d/m/Y', strtotime($pedido['fecha_creacion'])), 0, 1, 'R');

// Información del cliente
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Información del Cliente', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 7, 'Nombre: ' . $pedido['nombre_cliente'], 0, 1, 'L');
$pdf->Cell(0, 7, 'Email: ' . $pedido['email'], 0, 1, 'L');
$pdf->Cell(0, 7, 'Método de Pago: ' . ucfirst($pedido['metodo_pago']), 0, 1, 'L');
$pdf->Cell(0, 7, 'Dirección de Envío: ' . $pedido['direccion_envio'], 0, 1, 'L');

// Detalles del pedido
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Detalles del Pedido', 0, 1, 'L');

// Cabecera de la tabla
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(90, 7, 'Producto', 1, 0, 'L');
$pdf->Cell(30, 7, 'Cantidad', 1, 0, 'C');
$pdf->Cell(35, 7, 'Precio Unit.', 1, 0, 'R');
$pdf->Cell(35, 7, 'Total', 1, 1, 'R');

// Contenido de la tabla
$pdf->SetFont('helvetica', '', 10);
foreach ($detalles as $detalle) {
    $pdf->Cell(90, 7, $detalle['nombre_producto'], 1, 0, 'L');
    $pdf->Cell(30, 7, $detalle['cantidad'], 1, 0, 'C');
    $pdf->Cell(35, 7, '$' . number_format($detalle['precio_unitario'], 2), 1, 0, 'R');
    $pdf->Cell(35, 7, '$' . number_format($detalle['cantidad'] * $detalle['precio_unitario'], 2), 1, 1, 'R');
}

// Totales
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(155, 7, 'Subtotal:', 0, 0, 'R');
$pdf->Cell(35, 7, '$' . number_format($pedido['total'] / 1.16, 2), 0, 1, 'R');
$pdf->Cell(155, 7, 'IVA (16%):', 0, 0, 'R');
$pdf->Cell(35, 7, '$' . number_format($pedido['total'] - ($pedido['total'] / 1.16), 2), 0, 1, 'R');
$pdf->Cell(155, 7, 'Total:', 0, 0, 'R');
$pdf->Cell(35, 7, '$' . number_format($pedido['total'], 2), 0, 1, 'R');

// Generar el PDF
$pdf->Output('Factura_' . str_pad($pedido_id, 6, '0', STR_PAD_LEFT) . '.pdf', 'D');
?>
