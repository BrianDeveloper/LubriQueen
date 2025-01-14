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
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if ($user['estado'] != 1) {
                    $_SESSION['login_error'] = "Su cuenta está desactivada. Por favor contacte al administrador.";
                    header("Location: ../login.php");
                    exit();
                }

                // Login exitoso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nombre'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['rol'];

                // Redirigir según el rol
                if ($user['rol'] === 'admin') {
                    header("Location: ../admin/dashboard.php");
                    exit();
                } else {
                    header("Location: ../client/dashboard.php");
                    exit();
                }
            } else {
                $_SESSION['login_error'] = "Credenciales inválidas";
                header("Location: ../login.php");
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['login_error'] = "Error al procesar la solicitud: " . $e->getMessage();
            header("Location: ../login.php");
            exit();
        }
    } else {
        $_SESSION['login_error'] = implode("<br>", $errors);
        header("Location: ../login.php");
        exit();
    }
} else {
    header("Location: ../login.php");
    exit();
}
?>
