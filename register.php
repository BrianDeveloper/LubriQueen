<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lubri Queen 77 - Registro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h1>Lubri Queen 77</h1>
            
            <?php
            session_start();
            if (isset($_SESSION['register_errors'])) {
                echo '<div class="error-container">';
                foreach ($_SESSION['register_errors'] as $error) {
                    echo '<div class="error-message">' . htmlspecialchars($error) . '</div>';
                }
                echo '</div>';
                unset($_SESSION['register_errors']);
            }
            ?>
            
            <form id="registerForm" action="auth/register.php" method="POST">
                <h2>Registro</h2>
                <div class="form-group">
                    <input type="text" name="nombre" placeholder="Nombre completo" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Correo electrónico" required>
                </div>
                <div class="form-group password-field">
                    <input type="password" name="password" placeholder="Contraseña" required>
                    <i class="toggle-password fas fa-eye" title="Mostrar contraseña"></i>
                </div>
                <div class="form-group password-field">
                    <input type="password" name="confirm_password" placeholder="Confirmar contraseña" required>
                    <i class="toggle-password fas fa-eye" title="Mostrar contraseña"></i>
                </div>
                <button type="submit">Registrarse</button>
            </form>
            <p>¿Ya tienes una cuenta? <a href="index.php">Inicia sesión aquí</a></p>
        </div>
    </div>
    <script src="js/validation.js"></script>
    <script src="js/password-toggle.js"></script>
</body>
</html>
