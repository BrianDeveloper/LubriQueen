<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit;
}

// Verificar si se recibieron los datos necesarios
if (!isset($_POST['producto_id']) || !isset($_POST['cantidad'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

$usuario_id = $_SESSION['user_id'];
$producto_id = $_POST['producto_id'];
$cantidad = $_POST['cantidad'];

try {
    // Verificar si el producto existe y tiene stock suficiente
    $stmt = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch();

    if (!$producto) {
        echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
        exit;
    }

    if ($producto['stock'] < $cantidad) {
        echo json_encode(['success' => false, 'error' => 'Stock insuficiente']);
        exit;
    }

    // Verificar si el producto ya está en el carrito
    $stmt = $conn->prepare("SELECT cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?");
    $stmt->execute([$usuario_id, $producto_id]);
    $carrito = $stmt->fetch();

    if ($carrito) {
        // Actualizar cantidad
        $nueva_cantidad = $carrito['cantidad'] + $cantidad;
        if ($nueva_cantidad > $producto['stock']) {
            echo json_encode(['success' => false, 'error' => 'Stock insuficiente']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?");
        $stmt->execute([$nueva_cantidad, $usuario_id, $producto_id]);
    } else {
        // Insertar nuevo item
        $stmt = $conn->prepare("INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)");
        $stmt->execute([$usuario_id, $producto_id, $cantidad]);
    }

    // Obtener el nuevo contador del carrito
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM carrito WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $cart_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    echo json_encode([
        'success' => true,
        'message' => 'Producto añadido al carrito',
        'cart_count' => $cart_count
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al añadir al carrito'
    ]);
}
