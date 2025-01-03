<?php
include 'db.php';

// Definir las credenciales del administrador
$admin_username = 'admin';
$admin_password = 'admin123'; // Cambia esto a una contraseña segura

// Hashear la contraseña
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Insertar el usuario administrador en la base de datos
/*
$sql = "INSERT INTO usuarios (username, password) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $admin_username, $hashed_password);

if ($stmt->execute()) {
    echo "Usuario administrador creado con éxito.";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$stmt->close();
$conn->close();
*/
?>