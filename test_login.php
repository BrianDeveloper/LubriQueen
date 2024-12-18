<?php
require_once 'config/db.php';

echo "<h2>Test de Conexión y Login</h2>";

try {
    // Test 1: Verificar conexión
    echo "<h3>Test 1: Conexión a la base de datos</h3>";
    if ($conn) {
        echo "✅ Conexión exitosa a la base de datos<br>";
    }

    // Test 2: Verificar si existe la tabla usuarios
    echo "<h3>Test 2: Verificar tabla usuarios</h3>";
    $stmt = $conn->query("SHOW TABLES LIKE 'usuarios'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabla usuarios existe<br>";
    } else {
        echo "❌ La tabla usuarios no existe<br>";
    }

    // Test 3: Verificar usuario admin
    echo "<h3>Test 3: Verificar usuario admin</h3>";
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
    $email = 'admin@lubriqueen.com';
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "✅ Usuario admin encontrado<br>";
        echo "ID: " . $user['id'] . "<br>";
        echo "Nombre: " . $user['nombre'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Rol: " . $user['rol'] . "<br>";
        echo "Estado: " . ($user['estado'] ? 'Activo' : 'Inactivo') . "<br>";
        
        // Test 4: Verificar hash de contraseña
        echo "<h3>Test 4: Verificar contraseña</h3>";
        $password = 'admin123';
        if (password_verify($password, $user['password'])) {
            echo "✅ La contraseña coincide<br>";
        } else {
            echo "❌ La contraseña no coincide<br>";
            echo "Generando nuevo hash para 'admin123': " . password_hash('admin123', PASSWORD_DEFAULT) . "<br>";
        }
    } else {
        echo "❌ Usuario admin no encontrado<br>";
    }

} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
