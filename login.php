<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lubri Queen 77 - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Estilos para el botón de mostrar/ocultar contraseña */
        .password-field {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: #495057;
        }

        .toggle-password.active {
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-card-inner">
                <!-- Formulario de Login -->
                <form class="auth-form login-form" action="auth/login.php" method="POST">
                    <div class="auth-form-content">
                        <h2>Bienvenido</h2>
                        
                        <?php
                        session_start();
                        if (isset($_SESSION['login_error'])) {
                            echo '<div class="alert alert-danger">';
                            echo '<div class="error-message">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
                            echo '</div>';
                            unset($_SESSION['login_error']);
                        }
                        ?>
                        
                        <div class="form-group">
                            <input type="email" name="email" id="email" placeholder=" " required>
                            <label for="email">Correo electrónico</label>
                        </div>
                        <div class="form-group password-field">
                            <input type="password" name="password" id="password" placeholder=" " required>
                            <label for="password">Contraseña</label>
                            <i class="toggle-password fas fa-eye" title="Mostrar contraseña"></i>
                        </div>
                        <button type="submit" class="btn-primary">Acceder</button>
                        <div class="switch-form">
                            <p>¿No tienes una cuenta? <a href="#" class="switch-to-register">Regístrate aquí</a></p>
                        </div>
                    </div>
                </form>

                <!-- Formulario de Registro -->
                <form class="auth-form register-form" action="auth/register.php" method="POST">
                    <div class="auth-form-content">
                        <h2>Crear cuenta</h2>
                        <div class="form-group">
                            <input type="text" name="name" id="reg-name" placeholder=" " required>
                            <label for="reg-name">Nombre completo</label>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" id="reg-email" placeholder=" " required>
                            <label for="reg-email">Correo electrónico</label>
                        </div>
                        <div class="form-group password-field">
                            <input type="password" name="password" id="reg-password" placeholder=" " required>
                            <label for="reg-password">Contraseña</label>
                            <i class="toggle-password fas fa-eye" title="Mostrar contraseña"></i>
                        </div>
                        <div class="form-group password-field">
                            <input type="password" name="confirm_password" id="reg-confirm-password" placeholder=" " required>
                            <label for="reg-confirm-password">Confirmar contraseña</label>
                            <i class="toggle-password fas fa-eye" title="Mostrar contraseña"></i>
                        </div>
                        <button type="submit" class="btn-primary">Registrarse</button>
                        <div class="switch-form">
                            <p>¿Ya tienes una cuenta? <a href="#" class="switch-to-login">Inicia sesión aquí</a></p>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Decoración -->
            <div class="auth-decoration">
                <div>
                    <h3>Lubri Queen 77</h3>
                    <p>Inicio de Sesión</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Script para alternar entre formularios
        document.querySelector('.switch-to-register').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.auth-card').classList.add('flipped');
        });

        document.querySelector('.switch-to-login').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.auth-card').classList.remove('flipped');
        });

        // Script para mostrar/ocultar contraseña
        document.addEventListener('DOMContentLoaded', function() {
            const passwordToggles = document.querySelectorAll('.toggle-password');
            
            passwordToggles.forEach(function(toggle) {
                toggle.addEventListener('click', function() {
                    // Encontrar el input de contraseña que está dentro del mismo form-group
                    const passwordField = this.parentElement.querySelector('input[type="password"], input[type="text"]');
                    
                    if (passwordField) {
                        // Cambiar el tipo de input
                        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                        passwordField.setAttribute('type', type);
                        
                        // Actualizar el icono y su estado
                        if (type === 'text') {
                            this.classList.remove('fa-eye');
                            this.classList.add('fa-eye-slash', 'active');
                            this.setAttribute('title', 'Ocultar contraseña');
                        } else {
                            this.classList.remove('fa-eye-slash', 'active');
                            this.classList.add('fa-eye');
                            this.setAttribute('title', 'Mostrar contraseña');
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
