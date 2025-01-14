<?php
session_start();
require_once '../config/db.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Obtener categorías para filtros
$stmt = $conn->prepare("SELECT * FROM categorias WHERE estado = 1 ORDER BY nombre");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configurar filtros y búsqueda
$where_conditions = ["p.estado = 1"];
$params = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where_conditions[] = "(p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $search_term = "%" . $_GET['search'] . "%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $where_conditions[] = "p.categoria_id = ?";
    $params[] = $_GET['category'];
}

// Construir y ejecutar la consulta
$sql = "SELECT p.*, c.nombre as category_name 
        FROM productos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        WHERE " . implode(" AND ", $where_conditions) . " 
        ORDER BY p.nombre";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar si es una solicitud AJAX
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($productos);
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - LubriQueen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .client-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .search-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .search-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .search-input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .search-input:focus {
            border-color: #4a90e2;
            outline: none;
        }

        .category-select {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            min-width: 200px;
            transition: border-color 0.3s;
        }

        .category-select:focus {
            border-color: #4a90e2;
            outline: none;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .product-image-container {
            width: 100%;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .product-no-image {
            font-size: 5rem;
            color: #adb5bd;
        }

        .product-details {
            padding: 1.5rem;
        }

        .product-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
            color: #333;
        }

        .product-category {
            display: inline-block;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
        }

        .product-description {
            color: #666;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .product-price {
            font-size: 1.25rem;
            font-weight: 600;
            color: #dc3545;
            margin-bottom: 1rem;
        }

        .stock {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
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

        .btn-add-cart {
            width: 100%;
            padding: 0.75rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-add-cart:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-add-cart:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
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

        .no-products {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .no-products i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }

            .search-input,
            .category-select {
                width: 100%;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .product-card {
                max-width: 400px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/navbar.php'; ?>
    
    <div class="client-container">
        <div class="search-section">
            <form id="search-form" class="search-form">
                <input type="text" 
                       id="search-input" 
                       name="search" 
                       class="search-input" 
                       placeholder="Buscar productos..."
                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                
                <select id="category-select" 
                        name="category" 
                        class="category-select">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"
                                <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div id="products-grid" class="products-grid">
            <?php if (empty($productos)): ?>
                <div class="no-products">
                    <i class="fas fa-box-open"></i>
                    <p>No se encontraron productos</p>
                </div>
            <?php else: ?>
                <?php foreach ($productos as $producto): ?>
                    <div class="product-card">
                        <div class="product-image-container">
                            <?php 
                            $imagePath = '../uploads/products/' . $producto['imagen'];
                            if ($producto['imagen'] && file_exists($imagePath) && is_readable($imagePath)): ?>
                                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="product-image" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                                <i class="fas fa-box product-no-image" style="display: none;"></i>
                            <?php else: ?>
                                <i class="fas fa-box product-no-image"></i>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-details">
                            <h3 class="product-title"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                            <span class="product-category">
                                <i class="fas fa-tag"></i>
                                <?php echo htmlspecialchars($producto['category_name'] ?? 'Sin categoría'); ?>
                            </span>
                            <p class="product-description"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                            <p class="product-price">$<?php echo number_format($producto['precio'], 2); ?></p>
                            <p class="stock">
                                <i class="fas fa-box"></i>
                                Stock disponible: <?php echo $producto['stock']; ?> unidades
                            </p>
                            
                            <div class="quantity-control">
                                <button type="button" class="quantity-btn" onclick="updateQuantity(this, -1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" 
                                       class="quantity-input" 
                                       value="1" 
                                       min="1" 
                                       max="<?php echo $producto['stock']; ?>"
                                       onchange="validateQuantity(this)">
                                <button type="button" class="quantity-btn" onclick="updateQuantity(this, 1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            
                            <button class="btn-add-cart" 
                                    onclick="addToCart(this)" 
                                    data-producto-id="<?php echo $producto['id']; ?>"
                                    <?php echo $producto['stock'] > 0 ? '' : 'disabled'; ?>>
                                <i class="fas fa-cart-plus"></i>
                                <?php echo $producto['stock'] > 0 ? 'Agregar al carrito' : 'Sin stock'; ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="success-message" class="success-message"></div>

    <script>
        let searchTimeout;
        let lastSearchTerm = '';
        let lastCategory = '';

        function displayProducts(productos) {
            const grid = document.getElementById('products-grid');
            grid.innerHTML = '';

            if (productos.length === 0) {
                grid.innerHTML = `
                    <div class="no-products">
                        <i class="fas fa-box-open"></i>
                        <p>No se encontraron productos</p>
                    </div>`;
                return;
            }

            productos.forEach(producto => {
                const card = document.createElement('div');
                card.className = 'product-card';
                card.innerHTML = `
                    <div class="product-image-container">
                        ${producto.imagen ? 
                            `<img src="../uploads/products/${producto.imagen}" 
                                 alt="${producto.nombre}" 
                                 class="product-image"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                             <i class="fas fa-box product-no-image" style="display: none;"></i>` :
                            `<i class="fas fa-box product-no-image"></i>`
                        }
                    </div>
                    <div class="product-details">
                        <h3 class="product-title">${producto.nombre}</h3>
                        <span class="product-category">
                            <i class="fas fa-tag"></i>
                            ${producto.category_name || 'Sin categoría'}
                        </span>
                        <p class="product-description">${producto.descripcion || ''}</p>
                        <p class="product-price">$${parseFloat(producto.precio).toFixed(2)}</p>
                        <p class="stock">
                            <i class="fas fa-box"></i>
                            Stock disponible: ${producto.stock} unidades
                        </p>
                        
                        <div class="quantity-control">
                            <button type="button" class="quantity-btn" onclick="updateQuantity(this, -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" 
                                   class="quantity-input" 
                                   value="1" 
                                   min="1" 
                                   max="${producto.stock}"
                                   onchange="validateQuantity(this)">
                            <button type="button" class="quantity-btn" onclick="updateQuantity(this, 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        
                        <button class="btn-add-cart" 
                                onclick="addToCart(this)" 
                                data-producto-id="${producto.id}"
                                ${producto.stock > 0 ? '' : 'disabled'}>
                            <i class="fas fa-cart-plus"></i>
                            ${producto.stock > 0 ? 'Agregar al carrito' : 'Sin stock'}
                        </button>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        function updateProducts(searchTerm, category) {
            if (searchTerm === lastSearchTerm && category === lastCategory) {
                return;
            }

            lastSearchTerm = searchTerm;
            lastCategory = category;

            const url = new URL(window.location.href);
            url.searchParams.set('search', searchTerm);
            url.searchParams.set('category', category);

            fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(productos => {
                displayProducts(productos);
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Event listeners
        const searchForm = document.getElementById('search-form');
        const searchInput = document.getElementById('search-input');
        const categorySelect = document.getElementById('category-select');

        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            clearTimeout(searchTimeout);
            updateProducts(searchInput.value, categorySelect.value);
        });

        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                updateProducts(e.target.value, categorySelect.value);
            }, 300);
        });

        categorySelect.addEventListener('change', () => {
            updateProducts(searchInput.value, categorySelect.value);
        });

        function updateQuantity(button, change) {
            const input = button.parentElement.querySelector('.quantity-input');
            let value = parseInt(input.value) + change;
            const max = parseInt(input.max);
            
            value = Math.max(1, Math.min(value, max));
            input.value = value;
        }

        function validateQuantity(input) {
            let value = parseInt(input.value);
            const max = parseInt(input.max);
            
            if (isNaN(value) || value < 1) value = 1;
            if (value > max) value = max;
            
            input.value = value;
        }

        function updateCartCounter(count) {
            const counter = document.getElementById('cart-counter');
            if (counter) {
                counter.textContent = count;
            }
        }

        function addToCart(button) {
            const cartItem = button.closest('.product-card');
            const productoId = button.dataset.productoId;
            const quantity = cartItem.querySelector('.quantity-input').value;
            
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `producto_id=${productoId}&cantidad=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartCounter(data.cart_count);
                    const successMessage = document.createElement('div');
                    successMessage.className = 'success-message';
                    successMessage.textContent = 'Producto añadido al carrito';
                    document.body.appendChild(successMessage);
                    
                    setTimeout(() => {
                        successMessage.remove();
                    }, 3000);
                } else {
                    alert(data.error || 'Error al añadir al carrito');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al añadir al carrito');
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