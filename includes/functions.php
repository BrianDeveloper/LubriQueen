<?php

function checkUserRole($requiredRole) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $requiredRole) {
        header('Location: /LubriQueen/login.php');
        exit;
    }
}

function updateStock($conn, $productoId, $cantidad, $tipo) {
    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Actualizar el stock
        $query = "UPDATE productos SET stock = stock + ? WHERE id = ?";
        if ($tipo === 'salida') {
            $cantidad = -$cantidad; // Convertir a negativo para salidas
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $cantidad, $productoId);
        $stmt->execute();

        // Registrar el movimiento
        $tipoMovimiento = $tipo;
        $cantidadAbs = abs($cantidad); // Usar valor absoluto para el registro
        $usuarioId = $_SESSION['user_id'];
        
        $queryMovimiento = "INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo_movimiento, cantidad) 
                           VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($queryMovimiento);
        $stmt->bind_param("iisi", $productoId, $usuarioId, $tipoMovimiento, $cantidadAbs);
        $stmt->execute();

        // Confirmar transacción
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Revertir cambios si hay error
        $conn->rollback();
        return false;
    }
}
?>
