<?php
require_once 'config/db.php';

try {
    // Limpiar la base de datos existente
    $conn->exec("DROP DATABASE IF EXISTS lubriqueen");
    
    // Crear la base de datos
    $conn->exec("CREATE DATABASE lubriqueen");
    $conn->exec("USE lubriqueen");
    
    // Crear la tabla de usuarios
    $conn->exec("
        CREATE TABLE usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            rol ENUM('admin', 'cliente') NOT NULL DEFAULT 'cliente',
            estado TINYINT(1) NOT NULL DEFAULT 1,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // Crear la tabla de categorías
    $conn->exec("
        CREATE TABLE categorias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(50) NOT NULL,
            descripcion TEXT,
            estado TINYINT(1) NOT NULL DEFAULT 1,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // Crear la tabla de productos
    $conn->exec("
        CREATE TABLE productos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT,
            precio DECIMAL(10,2) NOT NULL,
            stock INT NOT NULL DEFAULT 0,
            categoria_id INT,
            imagen VARCHAR(255),
            estado TINYINT(1) NOT NULL DEFAULT 1,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // Crear la tabla de carrito
    $conn->exec("
        CREATE TABLE carrito (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            producto_id INT NOT NULL,
            cantidad INT NOT NULL DEFAULT 1,
            fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_cart_item (usuario_id, producto_id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
            FOREIGN KEY (producto_id) REFERENCES productos(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // Crear la tabla de pedidos
    $conn->exec("
        CREATE TABLE pedidos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            estado ENUM('pendiente','procesando','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
            direccion_envio TEXT,
            metodo_pago VARCHAR(50),
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // Crear la tabla de detalles_pedido
    $conn->exec("
        CREATE TABLE detalles_pedido (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pedido_id INT NOT NULL,
            producto_id INT NOT NULL,
            cantidad INT NOT NULL,
            precio_unitario DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
            FOREIGN KEY (producto_id) REFERENCES productos(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // Crear la tabla de historial_pedidos
    $conn->exec("
        CREATE TABLE historial_pedidos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pedido_id INT NOT NULL,
            usuario_id INT NOT NULL,
            estado_anterior ENUM('pendiente','procesando','enviado','entregado','cancelado'),
            estado_nuevo ENUM('pendiente','procesando','enviado','entregado','cancelado') NOT NULL,
            comentario TEXT,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // Crear la tabla de movimientos_inventario
    $conn->exec("
        CREATE TABLE movimientos_inventario (
            id INT AUTO_INCREMENT PRIMARY KEY,
            producto_id INT NOT NULL,
            usuario_id INT NOT NULL,
            tipo_movimiento ENUM('entrada','salida') NOT NULL,
            cantidad INT NOT NULL,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (producto_id) REFERENCES productos(id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    
    // Crear usuarios de prueba
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $cliente_password = password_hash('cliente123', PASSWORD_DEFAULT);
    
    $conn->exec("
        INSERT INTO usuarios (nombre, email, password, rol, estado) VALUES 
        ('Administrador', 'admin@lubriqueen.com', '$admin_password', 'admin', 1),
        ('Cliente Demo', 'cliente@lubriqueen.com', '$cliente_password', 'cliente', 1)
    ");

    // Insertar categorías de ejemplo
    $conn->exec("
        INSERT INTO categorias (nombre, descripcion) VALUES
        ('Aceites de Motor', 'Aceites para diferentes tipos de motores'),
        ('Filtros', 'Filtros de aceite, aire y combustible'),
        ('Lubricantes', 'Lubricantes especializados para diferentes usos'),
        ('Grasas', 'Grasas para diferentes aplicaciones'),
        ('Aditivos', 'Aditivos para mejorar el rendimiento')
    ");

    // Insertar productos de ejemplo
    $conn->exec("
        INSERT INTO productos (nombre, descripcion, precio, stock, categoria_id, imagen) VALUES
        ('Aceite Sintético 5W-30', 'Aceite sintético de alta calidad para motores modernos', 45.99, 100, 1, 'aceite-sintetico.jpg'),
        ('Aceite Semi-sintético 10W-40', 'Aceite semi-sintético para motores de alto rendimiento', 35.99, 150, 1, 'aceite-semi-sintetico.jpg'),
        ('Filtro de Aceite Premium', 'Filtro de aceite de alta eficiencia', 12.99, 200, 2, 'filtro-aceite.jpg'),
        ('Filtro de Aire Deportivo', 'Filtro de aire de alto flujo', 24.99, 80, 2, 'filtro-aire.jpg'),
        ('Grasa Multiusos', 'Grasa de litio para múltiples aplicaciones', 8.99, 120, 4, 'grasa-multiusos.jpg'),
        ('Aditivo Limpiador de Inyectores', 'Limpiador de inyectores de combustible', 15.99, 90, 5, 'limpiador-inyectores.jpg')
    ");
    
    echo "<h2>Inicialización completada</h2>";
    echo "<p>Se han creado todas las tablas necesarias y los siguientes usuarios:</p>";
    echo "<strong>Administrador:</strong><br>";
    echo "Email: admin@lubriqueen.com<br>";
    echo "Contraseña: admin123<br><br>";
    echo "<strong>Cliente:</strong><br>";
    echo "Email: cliente@lubriqueen.com<br>";
    echo "Contraseña: cliente123<br><br>";
    echo "<a href='login.php'>Ir al login</a>";
    
} catch (PDOException $e) {
    die("Error en la inicialización: " . $e->getMessage());
}
?>
