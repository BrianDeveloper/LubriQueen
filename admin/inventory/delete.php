<?php
session_start();
require_once '../../config/db.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

// Verificar si se proporcionó un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);

try {
    // Iniciar transacción
    $conn->beginTransaction();

    // Obtener información del producto antes de eliminarlo
    $stmt = $conn->prepare("SELECT imagen FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    // Eliminar los movimientos de inventario relacionados
    $stmt = $conn->prepare("DELETE FROM movimientos_inventario WHERE producto_id = ?");
    $stmt->execute([$id]);

    // Eliminar el producto
    $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->execute([$id]);

    // Confirmar la transacción
    $conn->commit();

    // Eliminar la imagen si existe
    if ($producto && !empty($producto['imagen'])) {
        $imagen_path = '../../uploads/products/' . $producto['imagen'];
        if (file_exists($imagen_path)) {
            unlink($imagen_path);
        }
    }

    header("Location: index.php?success=3");
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conn->rollBack();
    header("Location: index.php?error=" . urlencode($e->getMessage()));
}
exit();
