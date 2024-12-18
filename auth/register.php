<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = filter_var($_POST['nombre'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        header("Location: ../register.php?error=password_mismatch");
        exit();
    }

    try {
        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            header("Location: ../register.php?error=email_exists");
            exit();
        }

        // Insertar nuevo usuario
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$nombre, $email, $hashed_password]);

        header("Location: ../index.php?success=registration_complete");
        exit();
    } catch(PDOException $e) {
        header("Location: ../register.php?error=db_error");
        exit();
    }
} else {
    header("Location: ../register.php");
    exit();
}
