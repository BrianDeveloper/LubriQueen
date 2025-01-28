<?php
session_start();
require_once '../config/db.php';

// Verificar si el usuario está logueado y es cliente
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cliente') {
    $_SESSION['error'] = "Debe iniciar sesión como cliente para acceder al checkout";
    header("Location: ../login.php");
    exit();
}

// Obtener items del carrito
$usuario_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT c.*, p.nombre, p.precio, p.imagen 
                      FROM carrito c 
                      JOIN productos p ON c.producto_id = p.id 
                      WHERE c.usuario_id = ?");
$stmt->execute([$usuario_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar si hay productos en el carrito
if (empty($items)) {
    $_SESSION['error'] = "Su carrito está vacío";
    header("Location: cart.php");
    exit();
}

// Obtener el total del carrito
$total = 0;
foreach ($items as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
$total = $total * 1.16; // Incluir IVA

// Procesar el pago
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Verificar stock disponible
        foreach ($items as $item) {
            $stmt = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
            $stmt->execute([$item['producto_id']]);
            $stock = $stmt->fetchColumn();
            if ($item['cantidad'] > $stock) {
                throw new Exception("Stock insuficiente para el producto: " . $item['nombre']);
            }
        }

        // Crear pedido
        $stmt = $conn->prepare("INSERT INTO pedidos (usuario_id, total, estado, fecha_creacion) 
                              VALUES (?, ?, ?, NOW())");
        
        $metodo_pago = $_POST['metodo_pago'];
        $direccion = $_POST['direccion'];
        $estado = ($metodo_pago === 'tarjeta') ? 'procesando' : 'pendiente';
        $stmt->execute([$usuario_id, $total, $estado]);
        $pedido_id = $conn->lastInsertId();

        // Guardar los detalles del método de pago en la tabla detalles_pedido
        $stmt = $conn->prepare("INSERT INTO detalles_pedido 
                              (pedido_id, producto_id, cantidad, precio_unitario, metodo_pago, direccion_envio) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($items as $item) {
            $stmt->execute([
                $pedido_id,
                $item['producto_id'],
                $item['cantidad'],
                $item['precio'],
                $metodo_pago,
                $direccion
            ]);

            // Actualizar stock
            $nuevo_stock = $stock - $item['cantidad'];
            $stmt_stock = $conn->prepare("UPDATE productos SET stock = ? WHERE id = ?");
            $stmt_stock->execute([$nuevo_stock, $item['producto_id']]);
        }

        // Limpiar carrito
        $stmt = $conn->prepare("DELETE FROM carrito WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);

        $conn->commit();
        $_SESSION['mensaje'] = "¡Pedido realizado con éxito!";
        header("Location: factura.php?pedido_id=" . $pedido_id);
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header('Location: checkout.php');
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - LubriQueen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .checkout-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .products-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }

        .product-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 1rem;
        }

        .product-details {
            flex: 1;
        }

        .product-name {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .product-price {
            color: #dc3545;
            font-weight: 600;
        }

        .product-quantity {
            color: #666;
            font-size: 0.9rem;
        }

        .payment-form {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
        }

        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #4a90e2;
            outline: none;
        }

        .form-control:focus + .floating-label,
        .form-control:not(:placeholder-shown) + .floating-label {
            top: -12px;
            left: 10px;
            font-size: 12px;
            background: white;
            padding: 0 5px;
            color: #4a90e2;
        }

        .floating-label {
            position: absolute;
            pointer-events: none;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            transition: 0.3s ease all;
            color: #666;
            background: transparent;
        }

        .payment-methods {
            margin-bottom: 2rem;
        }

        .payment-method-option {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-method-option:hover {
            border-color: #4a90e2;
            background: #f8f9fa;
        }

        .payment-method-option.selected {
            border-color: #4a90e2;
            background: #f0f7ff;
        }

        .submit-btn {
            width: 100%;
            background: #28a745;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: background-color 0.3s;
            margin-top: 2rem;
        }

        .submit-btn:hover {
            background: #218838;
        }

        .bank-info {
            background: #e9ecef;
            padding: 1.5rem;
            border-radius: 5px;
            margin-top: 1rem;
        }

        .total-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 5px;
            margin-bottom: 2rem;
        }

        .total-amount {
            font-size: 1.5rem;
            color: #dc3545;
            font-weight: 600;
            text-align: right;
        }

        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/navbar.php'; ?>

    <div class="checkout-container">
        <div class="products-list">
            <h3>Productos en tu Carrito</h3>
            <?php foreach ($items as $item): ?>
                <div class="product-item">
                    <img src="../assets/productos/<?php echo htmlspecialchars($item['imagen'] ?? 'default.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($item['nombre']); ?>" 
                         class="product-image">
                    <div class="product-details">
                        <div class="product-name"><?php echo htmlspecialchars($item['nombre']); ?></div>
                        <div class="product-price">$<?php echo number_format($item['precio'], 2); ?></div>
                        <div class="product-quantity">Cantidad: <?php echo $item['cantidad']; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="total-section">
                <div class="total-amount">
                    Total a Pagar: $<?php echo number_format($total, 2); ?>
                </div>
            </div>
        </div>

        <div class="payment-form">
            <h3>Método de Pago</h3>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="checkout.php" id="payment-form">
                <div class="payment-methods">
                    <label class="payment-method-option">
                        <input type="radio" name="metodo_pago" value="tarjeta" checked>
                        <i class="fas fa-credit-card"></i>
                        Tarjeta de Crédito/Débito
                    </label>
                    <label class="payment-method-option">
                        <input type="radio" name="metodo_pago" value="efectivo">
                        <i class="fas fa-money-bill-wave"></i>
                        Pago en Efectivo
                    </label>
                    <label class="payment-method-option">
                        <input type="radio" name="metodo_pago" value="transferencia">
                        <i class="fas fa-university"></i>
                        Transferencia Bancaria
                    </label>
                </div>

                <div id="tarjeta-detalles">
                    <div class="form-group">
                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder=" " required>
                        <label class="floating-label" for="nombre">Nombre en la Tarjeta</label>
                    </div>

                    <div class="form-group">
                        <input type="text" class="form-control" id="numero_tarjeta" name="numero_tarjeta" 
                               maxlength="19" placeholder=" " required
                               pattern="\d{4}\s\d{4}\s\d{4}\s\d{4}"
                               oninput="this.value = this.value.replace(/[^\d\s]/g, '').substring(0, 19)">
                        <label class="floating-label" for="numero_tarjeta">Número de Tarjeta (XXXX XXXX XXXX XXXX)</label>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <input type="text" class="form-control" id="fecha_vencimiento" 
                                   name="fecha_vencimiento" placeholder=" " required
                                   maxlength="5" pattern="(0[1-9]|1[0-2])\/([0-9]{2})">
                            <label class="floating-label" for="fecha_vencimiento">MM/YY</label>
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control" id="cvv" name="cvv" 
                                   maxlength="3" placeholder=" " required
                                   pattern="\d{3}" oninput="this.value = this.value.replace(/\D/g, '').substring(0, 3)">
                            <label class="floating-label" for="cvv">CVV (123)</label>
                        </div>
                    </div>
                </div>

                <div id="transferencia-detalles" style="display: none;">
                    <div class="bank-info">
                        <h6>Datos Bancarios:</h6>
                        <p>Banco: LubriQueen Bank</p>
                        <p>Cuenta: 1234-5678-9012-3456</p>
                        <p>Titular: LubriQueen S.A.</p>
                        <p>RIF: J-12345678-9</p>
                        <small>* Por favor, guarde su comprobante de transferencia</small>
                    </div>
                </div>

                <div class="form-group">
                    <textarea class="form-control" id="direccion" name="direccion" rows="3" placeholder=" " required></textarea>
                    <label class="floating-label" for="direccion">Dirección de Envío</label>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-lock"></i>
                    Confirmar Pedido
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Manejar cambios en el método de pago
            document.querySelectorAll('input[name="metodo_pago"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    // Remover clase selected de todas las opciones
                    document.querySelectorAll('.payment-method-option').forEach(option => {
                        option.classList.remove('selected');
                    });
                    
                    // Añadir clase selected a la opción seleccionada
                    this.closest('.payment-method-option').classList.add('selected');
                    
                    // Ocultar todos los detalles
                    document.getElementById('tarjeta-detalles').style.display = 'none';
                    document.getElementById('transferencia-detalles').style.display = 'none';
                    
                    // Mostrar los detalles del método seleccionado
                    if (this.value === 'tarjeta' || this.value === 'efectivo') {
                        document.getElementById('tarjeta-detalles').style.display = 
                            this.value === 'tarjeta' ? 'block' : 'none';
                    } else if (this.value === 'transferencia') {
                        document.getElementById('transferencia-detalles').style.display = 'block';
                    }
                    
                    // Actualizar campos requeridos
                    const tarjetaInputs = document.querySelectorAll('#tarjeta-detalles input');
                    tarjetaInputs.forEach(input => {
                        input.required = (this.value === 'tarjeta');
                    });
                });
            });

            // Formateo del número de tarjeta
            const numeroTarjeta = document.getElementById('numero_tarjeta');
            if (numeroTarjeta) {
                numeroTarjeta.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 16) value = value.slice(0, 16);
                    
                    // Agregar espacios cada 4 dígitos
                    value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
                    e.target.value = value;
                });
            }

            // Formateo automático de la fecha de vencimiento
            const fechaInput = document.getElementById('fecha_vencimiento');
            if (fechaInput) {
                fechaInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 4) value = value.slice(0, 4);
                    if (value.length > 2) {
                        value = value.slice(0,2) + '/' + value.slice(2);
                    }
                    e.target.value = value;
                });

                fechaInput.addEventListener('blur', function(e) {
                    let value = e.target.value;
                    if (value.length === 2 && !value.includes('/')) {
                        e.target.value = value + '/';
                    }
                });
            }

            // Validación del CVV
            const cvvInput = document.getElementById('cvv');
            if (cvvInput) {
                cvvInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 3) value = value.slice(0, 3);
                    e.target.value = value;
                });
            }

            // Validación del formulario
            document.getElementById('payment-form').addEventListener('submit', function(e) {
                const metodoPago = document.querySelector('input[name="metodo_pago"]:checked').value;
                
                if (metodoPago === 'tarjeta') {
                    const numero = document.getElementById('numero_tarjeta').value.replace(/\s/g, '');
                    const fecha = document.getElementById('fecha_vencimiento').value;
                    const cvv = document.getElementById('cvv').value;
                    
                    if (numero.length !== 16) {
                        e.preventDefault();
                        alert('El número de tarjeta debe tener 16 dígitos');
                        return;
                    }
                    
                    const [mes, año] = fecha.split('/');
                    if (!mes || !año || mes < 1 || mes > 12) {
                        e.preventDefault();
                        alert('Fecha de vencimiento inválida');
                        return;
                    }
                    
                    if (cvv.length !== 3) {
                        e.preventDefault();
                        alert('El CVV debe tener 3 dígitos');
                        return;
                    }
                }
            });
        });
    </script>
</body>
</html>
