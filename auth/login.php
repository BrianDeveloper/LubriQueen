<?php
session_start();
require_once '../config/db.php';

// Función para validar email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para sanitizar inputs
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    
    // Validar email
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    if (empty($email)) {
        $errors[] = "El correo electrónico es requerido";
    } elseif (!validateEmail($email)) {
        $errors[] = "El formato del correo electrónico no es válido";
    }
    
    // Validar contraseña
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if (empty($password)) {
        $errors[] = "La contraseña es requerida";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT id, nombre, email, password, estado, rol FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Para depuración
            error_log("Intento de login - Email: " . $email);
            error_log("Usuario encontrado: " . ($user ? "Sí" : "No"));
            
            if ($user && password_verify($password, $user['password'])) {
                error_log("Verificación de contraseña exitosa");
                
                if (!$user['estado']) {
                    $errors[] = "Su cuenta está desactivada. Por favor contacte al administrador.";
                } else {
                    // Login exitoso
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nombre'];
                    $_SESSION['user_role'] = $user['rol'];
                    
                    // Registrar el último acceso
                    $stmt = $conn->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Redirigir según el rol
                    $redirect = $user['rol'] === 'admin' ? '../admin/dashboard.php' : '../dashboard.php';
                    header("Location: " . $redirect);
                    exit();
                }
            } else {
                error_log("Verificación de contraseña fallida");
                $errors[] = "Credenciales inválidas";
            }
        } catch(PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            $errors[] = "Error en el servidor. Por favor intente más tarde.";
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
        header("Location: ../index.php");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}
