<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LubriQueen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/chatbot.css">
    <style>
        body {
            background-color: #f5f1ed;
        }
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/img/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 50px;
        }
        .about-section {
            background-color: white;
            padding: 80px 0;
            margin-bottom: 50px;
        }
        .feature-card {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .feature-card i {
            font-size: 3rem;
            color: #e59516;
            margin-bottom: 20px;
        }
        .search-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        .product-title {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #333;
        }
        .product-category {
            color: #666;
            margin-bottom: 10px;
        }
        .product-description {
            color: #666;
            margin-bottom: 10px;
        }
        .product-price {
            color: #dc3545;
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .stock-info {
            color: #666;
            margin-bottom: 15px;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .quantity-control button {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 5px 10px;
            font-size: 1rem;
        }
        .quantity-control input {
            width: 50px;
            text-align: center;
            margin: 0 10px;
            border: 1px solid #dee2e6;
        }
        .add-to-cart-btn {
            width: 100%;
            background-color: #e59516;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .add-to-cart-btn:hover {
            background-color: #d18614;
        }
        .section-title {
            position: relative;
            margin-bottom: 40px;
            padding-bottom: 20px;
            color: #333;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #e59516;
        }
        .contact-section {
            background-color: white;
            padding: 80px 0;
            margin-top: 50px;
        }
        .contact-info {
            margin-bottom: 30px;
        }
        .contact-info i {
            color: #e59516;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">Bienvenido a LubriQueen</h1>
            <p class="lead mb-4">Tu tienda de confianza para todos los lubricantes que necesitas</p>
            <a href="#productos" class="btn btn-lg" style="background-color: #e59516; color: white;">Ver Productos</a>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <h2 class="section-title">Sobre Nosotros</h2>
                    <p class="lead">LubriQueen es tu destino confiable para todos los productos de lubricación automotriz.</p>
                    <p>Nos especializamos en proporcionar productos de alta calidad y un servicio excepcional a nuestros clientes. Con años de experiencia en el mercado, garantizamos la mejor selección de lubricantes para tu vehículo.</p>
                </div>
                <div class="col-lg-6">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="feature-card">
                                <i class="fas fa-check-circle"></i>
                                <h4>Calidad Garantizada</h4>
                                <p>Productos de las mejores marcas</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-card">
                                <i class="fas fa-truck"></i>
                                <h4>Envío Rápido</h4>
                                <p>Entrega en todo el país</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-card">
                                <i class="fas fa-headset"></i>
                                <h4>Soporte 24/7</h4>
                                <p>Atención al cliente permanente</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-card">
                                <i class="fas fa-shield-alt"></i>
                                <h4>Compra Segura</h4>
                                <p>Transacciones protegidas</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section id="productos" class="container">
        <div class="search-container">
            <div class="row">
                <div class="col-md-10">
                    <input type="text" class="form-control" placeholder="Buscar productos...">
                </div>
                <div class="col-md-2">
                    <select class="form-select">
                        <option>Todas las categorías</option>
                    </select>
                </div>
            </div>
        </div>

        <h2 class="section-title">Nuestros Productos</h2>
        <div class="row">
            <?php
            require_once 'config/db.php';
            
            $sql = "SELECT p.*, c.nombre as nombre_categoria 
                    FROM productos p 
                    INNER JOIN categorias c ON p.categoria_id = c.id 
                    WHERE p.estado = 1";
            $stmt = $conn->query($sql);
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($productos) > 0) {
                foreach($productos as $row) {
                    ?>
                    <div class="col-md-4">
                        <div class="product-card">
                            <div class="product-image-container">
                                <?php 
                                $imagePath = 'uploads/products/' . $row['imagen'];
                                if ($row['imagen'] && file_exists($imagePath) && is_readable($imagePath)): ?>
                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo $row['nombre']; ?>" class="product-image" 
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                                    <i class="fas fa-box product-no-image" style="display: none;"></i>
                                <?php else: ?>
                                    <i class="fas fa-box product-no-image"></i>
                                <?php endif; ?>
                            </div>
                            <h3 class="product-title"><?php echo $row['nombre']; ?></h3>
                            <div class="product-category">
                                <i class="fas fa-tag"></i> <?php echo $row['nombre_categoria']; ?>
                            </div>
                            <p class="product-description"><?php echo $row['descripcion']; ?></p>
                            <div class="product-price">$<?php echo number_format($row['precio'], 2); ?></div>
                            <div class="stock-info">
                                <i class="fas fa-box"></i> Stock disponible: <?php echo $row['stock']; ?> unidades
                            </div>
                            <div class="quantity-control">
                                <button onclick="decrementQuantity(<?php echo $row['id']; ?>)">-</button>
                                <input type="number" id="cantidad_<?php echo $row['id']; ?>" value="1" min="1" max="<?php echo $row['stock']; ?>">
                                <button onclick="incrementQuantity(<?php echo $row['id']; ?>)">+</button>
                            </div>
                            <button onclick="addToCart(<?php echo $row['id']; ?>, document.getElementById('cantidad_<?php echo $row['id']; ?>').value)" class="add-to-cart-btn">
                                <i class="fas fa-shopping-cart"></i> Agregar al carrito
                            </button>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p class='text-center'>No hay productos disponibles</p>";
            }
            ?>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <h2 class="section-title">Contáctanos</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="contact-info">
                        <h4><i class="fas fa-map-marker-alt"></i> Dirección</h4>
                        <p>Av. Principal #123, Ciudad</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-info">
                        <h4><i class="fas fa-phone"></i> Teléfono</h4>
                        <p>+58 123-456-7890</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-info">
                        <h4><i class="fas fa-envelope"></i> Email</h4>
                        <p>info@lubriqueen.com</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="client/js/cart.js"></script>
    <script src="js/chatbot.js"></script>
    <script>
    function incrementQuantity(productId) {
        const input = document.getElementById('cantidad_' + productId);
        const max = parseInt(input.max);
        const currentValue = parseInt(input.value);
        if (currentValue < max) {
            input.value = currentValue + 1;
        }
    }

    function decrementQuantity(productId) {
        const input = document.getElementById('cantidad_' + productId);
        const currentValue = parseInt(input.value);
        if (currentValue > 1) {
            input.value = currentValue - 1;
        }
    }
    </script>
</body>
</html>