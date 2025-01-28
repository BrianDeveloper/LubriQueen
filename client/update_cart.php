<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit;
}

$usuario_id = $_SESSION['user_id'];
$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update':
                $producto_id = $_POST['producto_id'] ?? 0;
                $cantidad = $_POST['cantidad'] ?? 0;
                
                if ($cantidad > 0) {
                    $stmt = $conn->prepare("UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?");
                    $stmt->execute([$cantidad, $usuario_id, $producto_id]);
                } else {
                    $stmt = $conn->prepare("DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?");
                    $stmt->execute([$usuario_id, $producto_id]);
                }
                break;
                
            case 'remove':
                $producto_id = $_POST['producto_id'] ?? 0;
                $stmt = $conn->prepare("DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?");
                $stmt->execute([$usuario_id, $producto_id]);
                break;
                
            case 'clear':
                $stmt = $conn->prepare("DELETE FROM carrito WHERE usuario_id = ?");
                $stmt->execute([$usuario_id]);
                break;
                
            default:
                throw new Exception('Acción no válida');
        }

        // Obtener el nuevo contador del carrito
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM carrito WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        $cart_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $response = [
            'success' => true,
            'message' => 'Carrito actualizado correctamente',
            'cart_count' => $cart_count
        ];
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

echo json_encode($response);
