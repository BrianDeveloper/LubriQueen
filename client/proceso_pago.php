<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cliente') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit();
}

$usuario_id = $_SESSION['user_id'];
$metodo_pago = $_POST['metodo_pago'] ?? '';
$direccion = $_POST['direccion'] ?? '';

if (empty($metodo_pago) || empty($direccion)) {
    $_SESSION['error'] = "Todos los campos son obligatorios";
    header("Location: checkout.php");
    exit();
}

try {
    $conn->beginTransaction();

    // Obtener items del carrito
    $stmt = $conn->prepare("SELECT c.*, p.nombre, p.precio, p.stock 
                          FROM carrito c 
                          JOIN productos p ON c.producto_id = p.id 
                          WHERE c.usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        throw new Exception("El carrito está vacío");
    }

    // Verificar stock disponible
    foreach ($items as $item) {
        if ($item['cantidad'] > $item['stock']) {
            throw new Exception("Stock insuficiente para el producto: " . $item['nombre']);
        }
    }

    // Calcular total
    $total = 0;
    foreach ($items as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }

    // Generar número de referencia único para el pedido
    $referencia = uniqid('ORD-');

    // Crear pedido
    $stmt = $conn->prepare("INSERT INTO pedidos (usuario_id, total, estado, fecha_creacion, metodo_pago, direccion_envio, referencia) 
                          VALUES (?, ?, ?, NOW(), ?, ?, ?)");
    
    $estado = ($metodo_pago === 'tarjeta') ? 'procesando' : 'pendiente';
    $stmt->execute([$usuario_id, $total, $estado, $metodo_pago, $direccion, $referencia]);
    $pedido_id = $conn->lastInsertId();

    // Guardar detalles del método de pago
    $detalles_pago = [];
    switch ($metodo_pago) {
        case 'tarjeta':
            $detalles_pago = [
                'nombre' => $_POST['nombre'] ?? '',
                'ultimos_digitos' => substr($_POST['numero_tarjeta'] ?? '', -4)
            ];
            break;
        case 'transferencia':
            $detalles_pago = [
                'referencia' => $_POST['comprobante'] ?? ''
            ];
            break;
        case 'efectivo':
            $detalles_pago = [
                'codigo_reserva' => $referencia
            ];
            break;
    }

    // Insertar detalles del pedido
    $stmt = $conn->prepare("INSERT INTO detalles_pedido 
                          (pedido_id, producto_id, cantidad, precio_unitario) 
                          VALUES (?, ?, ?, ?)");
    
    foreach ($items as $item) {
        $stmt->execute([
            $pedido_id,
            $item['producto_id'],
            $item['cantidad'],
            $item['precio']
        ]);

        // Actualizar stock
        $nuevo_stock = $item['stock'] - $item['cantidad'];
        $stmt_stock = $conn->prepare("UPDATE productos SET stock = ? WHERE id = ?");
        $stmt_stock->execute([$nuevo_stock, $item['producto_id']]);
    }

    // Limpiar carrito
    $stmt = $conn->prepare("DELETE FROM carrito WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);

    $conn->commit();

    // Mensaje de éxito según el método de pago
    switch ($metodo_pago) {
        case 'tarjeta':
            $_SESSION['mensaje'] = "¡Pago procesado con éxito! Su pedido está siendo procesado.";
            break;
        case 'transferencia':
            $_SESSION['mensaje'] = "Pedido registrado. Por favor, realice la transferencia usando los datos proporcionados. Su número de referencia es: " . $referencia;
            break;
        case 'efectivo':
            $_SESSION['mensaje'] = "Pedido registrado. Presente este código en caja: " . $referencia;
            break;
    }

    header('Location: pedidos.php');
    exit;

} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header('Location: checkout.php');
    exit;
}
?>
