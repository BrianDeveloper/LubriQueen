<?php
session_start();
require_once '../config/db.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Obtener productos en el carrito
$stmt = $conn->prepare("
    SELECT c.*, p.nombre, p.precio, p.imagen, p.stock 
    FROM carrito c 
    JOIN productos p ON c.producto_id = p.id 
    WHERE c.usuario_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular total
$total = 0;
foreach ($items as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito - LubriQueen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .client-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .cart-header {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cart-total {
            font-size: 1.25rem;
            font-weight: 600;
            color: #dc3545;
            background: #fff5f5;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cart-items {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .cart-item {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            gap: 1.5rem;
            transition: background-color 0.3s;
        }

        .cart-item:hover {
            background-color: #f8f9fa;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image-container {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .item-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .item-no-image {
            font-size: 2rem;
            color: #adb5bd;
        }

        .item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .item-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .item-price {
            font-size: 1.1rem;
            color: #dc3545;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .item-controls {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 5px;
        }

        .quantity-btn {
            background: white;
            border: 1px solid #ddd;
            padding: 0.5rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
        }

        .quantity-btn:hover {
            background: #e9ecef;
            border-color: #4a90e2;
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .quantity-input:focus {
            border-color: #4a90e2;
            outline: none;
        }

        .remove-btn {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .remove-btn:hover {
            background-color: #fff5f5;
            color: #c82333;
        }

        .empty-cart {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-cart i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .empty-cart p {
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .checkout-btn, .clear-cart-btn {
            width: 100%;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 5px;
            font-size: 1.1rem;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: background-color 0.3s;
        }

        .checkout-btn {
            background: #28a745;
        }

        .clear-cart-btn {
            background: #dc3545;
        }

        .checkout-btn:hover {
            background: #218838;
        }

        .clear-cart-btn:hover {
            background: #c82333;
        }

        .checkout-btn i, .clear-cart-btn i {
            font-size: 1.1rem;
        }

        .buttons-container {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
            margin-top: 1rem;
        }

        .cart-summary-details {
            margin-top: 1rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.2rem;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 2px solid #eee;
        }

        .summary-row.total {
            color: #dc3545;
        }

        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 5px;
            display: none;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .cart-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .cart-item {
                flex-direction: column;
                text-align: center;
                padding: 2rem 1.5rem;
            }

            .item-image-container {
                width: 120px;
                height: 120px;
            }

            .item-controls {
                flex-direction: column;
                width: 100%;
            }

            .quantity-control {
                width: 100%;
                justify-content: center;
            }

            .remove-btn {
                width: 100%;
                justify-content: center;
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/navbar.php'; ?>
    
    <div class="client-container">
        <div class="cart-header">
            <h2 class="cart-title">
                <i class="fas fa-shopping-cart"></i>
                Tu Carrito
                <span id="cart-summary" class="cart-summary">
                    (<?php echo array_sum(array_column($items, 'cantidad')); ?> productos)
                </span>
            </h2>
            <div class="cart-total">
                <i class="fas fa-dollar-sign"></i>
                Total: $<span id="cart-total"><?php echo number_format($total, 2); ?></span>
            </div>
        </div>

        <?php if (empty($items)): ?>
            <div class="cart-items">
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Tu carrito está vacío</p>
                    <a href="products.php" class="btn-primary">
                        <i class="fas fa-store"></i>
                        Ver Productos
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($items as $item): ?>
                    <div class="cart-item" data-producto-id="<?php echo $item['producto_id']; ?>">
                        <div class="item-image-container">
                            <?php 
                            $imagePath = '../uploads/products/' . $item['imagen'];
                            if ($item['imagen'] && file_exists($imagePath) && is_readable($imagePath)): ?>
                                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>" class="item-image" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                                <i class="fas fa-box item-no-image" style="display: none;"></i>
                            <?php else: ?>
                                <i class="fas fa-box item-no-image"></i>
                            <?php endif; ?>
                        </div>
                        
                        <div class="item-details">
                            <h3 class="item-name"><?php echo htmlspecialchars($item['nombre']); ?></h3>
                            <div class="item-price" data-price="<?php echo $item['precio']; ?>">
                                <i class="fas fa-tag"></i>
                                $<?php echo number_format($item['precio'], 2); ?>
                            </div>
                            <div class="item-subtotal">
                                Subtotal: $<span class="item-subtotal"><?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></span>
                            </div>
                            <div class="item-controls">
                                <div class="quantity-control">
                                    <button type="button" class="quantity-btn" onclick="updateQuantity(this, -1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" 
                                           class="quantity-input" 
                                           value="<?php echo $item['cantidad']; ?>" 
                                           min="1" 
                                           max="<?php echo $item['stock']; ?>"
                                           onchange="validateAndUpdateQuantity(this)">
                                    <button type="button" class="quantity-btn" onclick="updateQuantity(this, 1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                
                                <button class="remove-btn" onclick="removeFromCart(this)">
                                    <i class="fas fa-trash"></i>
                                    Eliminar del carrito
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="checkout-section">
                <h3>Resumen del Carrito</h3>
                <div class="cart-summary-details">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<span id="cart-subtotal"><?php echo number_format($total, 2); ?></span></span>
                    </div>
                    <div class="summary-row">
                        <span>IVA (16%):</span>
                        <span>$<span id="cart-iva"><?php echo number_format($total * 0.16, 2); ?></span></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>$<span id="cart-total-with-tax"><?php echo number_format($total * 1.16, 2); ?></span></span>
                    </div>
                </div>
                <?php if (!empty($items)): ?>
                    <div class="buttons-container">
                        <button class="checkout-btn" onclick="window.location.href='checkout.php'">
                            <i class="fas fa-shopping-cart"></i>
                            Proceder al Pago
                        </button>
                        <button class="clear-cart-btn" onclick="vaciarCarrito()">
                            <i class="fas fa-trash"></i>
                            Vaciar Carrito
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="success-message" class="success-message"></div>

    <script>
        function updateQuantity(button, change) {
            const input = button.parentElement.querySelector('.quantity-input');
            let newValue = parseInt(input.value) + change;
            newValue = Math.max(1, Math.min(newValue, parseInt(input.max)));
            
            if (input.value !== newValue.toString()) {
                input.value = newValue;
                const productoId = button.closest('.cart-item').dataset.productoId;
                
                fetch('update_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update&producto_id=${productoId}&cantidad=${newValue}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCartSummary();
                        // Actualizar el subtotal del item
                        const price = parseFloat(button.closest('.cart-item').querySelector('.item-price').dataset.price);
                        const subtotal = price * newValue;
                        button.closest('.cart-item').querySelector('.item-subtotal').textContent = `Subtotal: $${subtotal.toFixed(2)}`;
                    } else {
                        alert('Error al actualizar la cantidad');
                        input.value = input.defaultValue;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al actualizar el carrito');
                    input.value = input.defaultValue;
                });
            }
        }

        function updateCartSummary() {
            let totalQuantity = 0;
            let subtotal = 0;
            
            // Obtener todos los inputs de cantidad
            document.querySelectorAll('.quantity-input').forEach(input => {
                const quantity = parseInt(input.value);
                const price = parseFloat(input.closest('.cart-item').querySelector('.item-price').dataset.price);
                totalQuantity += quantity;
                subtotal += quantity * price;
            });

            // Calcular IVA y total
            const iva = subtotal * 0.16;
            const total = subtotal + iva;

            // Actualizar el resumen
            document.getElementById('cart-summary').textContent = `(${totalQuantity} productos)`;
            document.getElementById('cart-subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('cart-iva').textContent = iva.toFixed(2);
            document.getElementById('cart-total-with-tax').textContent = total.toFixed(2);
            
            // Actualizar el total en el encabezado
            document.getElementById('cart-total').textContent = subtotal.toFixed(2);
        }

        function validateAndUpdateQuantity(input) {
            const cartItem = input.closest('.cart-item');
            const productoId = cartItem.dataset.productoId;
            let value = parseInt(input.value);
            const max = parseInt(input.max);

            if (isNaN(value) || value < 1) value = 1;
            if (value > max) value = max;
            input.value = value;

            fetch('update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update&producto_id=${productoId}&cantidad=${value}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartSummary();
                    // Actualizar el subtotal del item
                    const price = parseFloat(cartItem.querySelector('.item-price').dataset.price);
                    const subtotal = price * value;
                    cartItem.querySelector('.item-subtotal').textContent = `Subtotal: $${subtotal.toFixed(2)}`;
                } else {
                    alert(data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar la cantidad');
            });
        }

        function updateCartCounter(count) {
            const counter = document.getElementById('cart-counter');
            if (counter) {
                counter.textContent = count;
            }
        }

        function removeFromCart(button) {
            if (!confirm('¿Estás seguro de que deseas eliminar este producto del carrito?')) {
                return;
            }
            
            const cartItem = button.closest('.cart-item');
            const productoId = cartItem.dataset.productoId;
            
            fetch('update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove&producto_id=${productoId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartSummary();
                    updateCartCounter(data.cart_count);
                    
                    // Animación de desvanecimiento
                    cartItem.style.transition = 'all 0.3s';
                    cartItem.style.opacity = '0';
                    cartItem.style.transform = 'translateX(20px)';
                    
                    setTimeout(() => {
                        cartItem.remove();
                        updateCartSummary();
                        
                        if (document.querySelectorAll('.cart-item').length === 0) {
                            location.reload();
                        }
                    }, 300);
                } else {
                    alert('Error al eliminar el producto del carrito');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al eliminar el producto');
            });
        }

        function vaciarCarrito() {
            if (!confirm('¿Estás seguro de que deseas vaciar el carrito?')) {
                return;
            }
            
            fetch('update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=clear'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartCounter(0);
                    location.reload();
                } else {
                    alert('Error al vaciar el carrito');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al vaciar el carrito');
            });
        }
        
        function showMessage(message) {
            const successMessage = document.getElementById('success-message');
            successMessage.textContent = message;
            successMessage.style.display = 'block';
            
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>