<?php
/**
 * Script de Creación de Usuario Administrador - Talleres MORAD
 * Versión segura con validaciones y logging
 * 
 * INSTRUCCIONES DE USO:
 * 1. Cambia las credenciales por defecto abajo
 * 2. Ejecuta este script UNA VEZ desde el navegador
 * 3. Elimina o comenta este archivo después de crear el admin
 * 4. Nunca subas este archivo al repositorio público
 */

require_once 'db.php';

// ============================================
// CONFIGURACIÓN - CAMBIA ESTOS VALORES
// ============================================
$admin_username = 'admin_morad';  // Cambia por un nombre de usuario seguro
$admin_password = 'Morad2024@Seguro!';  // Cambia por una contraseña MUY segura
$admin_email = 'talleresmoradmelilla@hotmail.com';
// ============================================

// Validar que las credenciales sean seguras
if (strlen($admin_username) < 6) {
    die("ERROR: El nombre de usuario debe tener al menos 6 caracteres.");
}

if (strlen($admin_password) < 12 || !preg_match('/[A-Z]/', $admin_password) || !preg_match('/[a-z]/', $admin_password) || !preg_match('/[0-9]/', $admin_password)) {
    die("ERROR: La contraseña debe tener al menos 12 caracteres e incluir mayúsculas, minúsculas y números.");
}

// Verificar si ya existe un administrador
try {
    $check_sql = "SELECT COUNT(*) as count FROM usuarios WHERE rol = 'admin' AND activo = 1";
    $check_result = $conn->query($check_sql);
    
    if ($check_result) {
        $row = $check_result->fetch_assoc();
        if ($row['count'] > 0) {
            die("⚠️ ADVERTENCIA: Ya existe un usuario administrador activo. No se creará otro.");
        }
    }
} catch (Exception $e) {
    // Si la tabla no existe, continuar para crearla
}

// Hashear la contraseña con algoritmo seguro
$hashed_password = password_hash($admin_password, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,
    'time_cost' => 4,
    'threads' => 3
]);

// Insertar el usuario administrador en la base de datos
$sql = "INSERT INTO usuarios (username, password, email, rol, activo, created_at) VALUES (?, ?, ?, 'admin', 1, NOW())";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("sss", $admin_username, $hashed_password, $admin_email);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        
        // Registrar en log
        error_log("Administrador creado exitosamente: $admin_username (ID: $user_id) - " . date('Y-m-d H:i:s'));
        
        echo "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Administrador Creado - Talleres MORAD</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
            <style>
                body {
                    background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
                    color: #fff;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .success-container {
                    background: rgba(30, 30, 30, 0.95);
                    border: 2px solid #28a745;
                    border-radius: 15px;
                    padding: 40px;
                    max-width: 600px;
                    text-align: center;
                }
                .success-icon {
                    font-size: 4rem;
                    color: #28a745;
                    margin-bottom: 20px;
                }
                .credentials-box {
                    background: rgba(40, 167, 69, 0.1);
                    border: 1px solid #28a745;
                    border-radius: 10px;
                    padding: 20px;
                    margin: 20px 0;
                    text-align: left;
                }
                .warning-box {
                    background: rgba(255, 193, 7, 0.1);
                    border: 1px solid #ffc107;
                    border-radius: 10px;
                    padding: 20px;
                    margin: 20px 0;
                    text-align: left;
                }
                h1 { color: #28a745; font-family: 'Arial', sans-serif; }
                .label { color: #ffc107; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='success-container'>
                <div class='success-icon'>✅</div>
                <h1>¡Administrador Creado Exitosamente!</h1>
                <p class='lead'>El usuario administrador ha sido registrado en la base de datos.</p>
                
                <div class='credentials-box'>
                    <h3>🔐 Credenciales de Acceso:</h3>
                    <p><span class='label'>👤 Usuario:</span> <strong>$admin_username</strong></p>
                    <p><span class='label'>🔑 Contraseña:</span> <strong>$admin_password</strong></p>
                    <p><span class='label'>📧 Email:</span> $admin_email</p>
                    <p class='mt-3'><small>ID de Usuario: $user_id</small></p>
                </div>
                
                <div class='warning-box'>
                    <h4>⚠️ IMPORTANTE - Pasos a Seguir:</h4>
                    <ol>
                        <li><strong>Guarda estas credenciales</strong> en un lugar seguro</li>
                        <li><strong>Elimina este archivo</strong> (create_admin.php) del servidor</li>
                        <li><strong>Nunca compartas</strong> estas credenciales públicamente</li>
                        <li>Cambia la contraseña desde el panel de administración</li>
                    </ol>
                </div>
                
                <div class='alert alert-info mt-3'>
                    <strong>📍 URL de Acceso:</strong><br>
                    <a href='admin_citas.php' class='btn btn-success mt-2'>Ir al Login de Administración</a>
                </div>
                
                <p class='mt-4 text-muted'>
                    <small>Este mensaje solo se muestra una vez. Por seguridad, elimina este archivo inmediatamente.</small>
                </p>
            </div>
        </body>
        </html>
        ";
    } else {
        $error_msg = "Error: " . $stmt->error;
        error_log("Error al crear administrador: $error_msg");
        echo "<script>alert('❌ Error al crear el administrador: $error_msg'); window.history.back();</script>";
    }
    
    $stmt->close();
} else {
    $error_msg = "Error en la preparación: " . $conn->error;
    error_log("Error prepare statement: $error_msg");
    echo "<script>alert('❌ Error en el sistema: $error_msg'); window.history.back();</script>";
}

$conn->close();
?>