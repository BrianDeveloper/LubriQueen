<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Lubri Queen 77</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
            <nav>
                <a href="auth/logout.php" class="logout-btn">Cerrar Sesión</a>
            </nav>
        </header>
        <div class="dashboard-content">
            <div class="welcome-card">
                <h2>Dashboard de Usuario</h2>
                <p>Has iniciado sesión exitosamente en Lubri Queen 77</p>
                <p>Rol: <?php echo htmlspecialchars($_SESSION['user_role']); ?></p>
                <p>ID de Usuario: <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
