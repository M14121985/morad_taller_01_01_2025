<?php
/**
 * Configuración de conexión a base de datos
 * Talleres MORAD - Sistema moderno y seguro
 */

// Configuración desde variables de entorno o valores por defecto
$servername = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') ?: "";
$dbname = getenv('DB_NAME') ?: "taller_morad";
$port = getenv('DB_PORT') ?: 3306;

// Crear conexión usando MySQLi con opciones de seguridad
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Configurar charset UTF-8 para evitar problemas de codificación
if ($conn->connect_error) {
    error_log("Error de conexión a BD: " . $conn->connect_error);
    die("Error de conexión. Por favor, contacte al administrador.");
}

// Establecer charset UTF-8
$conn->set_charset("utf8mb4");

// Función helper para sanitizar inputs
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Función para validar email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para validar teléfono español
function validate_phone($phone) {
    return preg_match('/^[0-9]{9}$/', $phone);
}
?>