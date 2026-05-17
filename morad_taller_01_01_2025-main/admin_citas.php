<?php
/**
 * Sistema de Login para Administración de Citas - Talleres MORAD
 * Versión moderna con mejoras de seguridad y UX
 */

session_start();
require_once 'db.php';

// Si ya está logueado, redirigir al panel
if (isset($_SESSION['user_id'])) {
    header("Location: ver_citas.php");
    exit();
}

$error = '';
$success = '';

// Procesar login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validaciones
    if (empty($username)) {
        $error = "El nombre de usuario es obligatorio.";
    } elseif (empty($password)) {
        $error = "La contraseña es obligatoria.";
    } else {
        try {
            $sql = "SELECT * FROM usuarios WHERE username = ? AND activo = 1";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    
                    if (password_verify($password, $user['password'])) {
                        // Regenerar ID de sesión para seguridad
                        session_regenerate_id(true);
                        
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['last_login'] = date('Y-m-d H:i:s');
                        
                        // Registrar último login
                        $update_sql = "UPDATE usuarios SET last_login = NOW() WHERE id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        if ($update_stmt) {
                            $update_stmt->bind_param("i", $user['id']);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                        
                        header("Location: ver_citas.php");
                        exit();
                    } else {
                        $error = "Contraseña inválida.";
                        error_log("Intento de login fallido para usuario: $username");
                    }
                } else {
                    $error = "No se encontró un usuario con ese nombre.";
                    error_log("Intento de login con usuario inexistente: $username");
                }
                $stmt->close();
            } else {
                $error = "Error en el sistema. Por favor, inténtelo más tarde.";
                error_log("Error prepare statement: " . $conn->error);
            }
        } catch (Exception $e) {
            $error = "Error inesperado. Por favor, contacte al administrador.";
            error_log("Excepción en login: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Talleres MORAD | Panel de Administración</title>
    <meta name="description" content="Acceso al panel de administración de citas de Talleres MORAD">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" href="imagenes/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.5 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary-color: #ff4d00;
            --secondary-color: #1a1a1a;
            --dark-bg: #0a0a0a;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }
        
        /* Fondo animado */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 77, 0, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(255, 77, 0, 0.1) 0%, transparent 50%);
        }
        
        .login-container {
            background: rgba(30, 30, 30, 0.95);
            border: 2px solid var(--primary-color);
            border-radius: 15px;
            padding: 40px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(255, 77, 0, 0.3);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header img {
            height: 80px;
            width: auto;
            margin-bottom: 20px;
        }
        
        .login-header h2 {
            font-family: 'Oswald', sans-serif;
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .login-header p {
            color: #aaa;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        
        .form-label {
            font-weight: 500;
            color: #fff;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid #444;
            color: #fff;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary-color);
            box-shadow: 0 0 15px rgba(255, 77, 0, 0.3);
            color: #fff;
        }
        
        .form-control::placeholder {
            color: #aaa;
        }
        
        .input-group-text {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid #444;
            border-right: none;
            color: var(--primary-color);
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .input-group .form-control:focus {
            border-left: none;
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color) 0%, #ff6b35 100%);
            border: none;
            color: #fff;
            padding: 14px;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 8px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 77, 0, 0.4);
            background: linear-gradient(135deg, #ff6b35 0%, var(--primary-color) 100%);
        }
        
        .alert-custom {
            background: rgba(220, 53, 69, 0.2);
            border: 2px solid #dc3545;
            color: #ff6b6b;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .alert-success-custom {
            background: rgba(40, 167, 69, 0.2);
            border: 2px solid #28a745;
            color: #5dd876;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .security-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #444;
            text-align: center;
        }
        
        .security-info p {
            color: #888;
            font-size: 0.8rem;
            margin-bottom: 10px;
        }
        
        .security-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .security-icons i {
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .back-home {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-home a {
            color: #aaa;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .back-home a:hover {
            color: var(--primary-color);
        }
        
        @media (max-width: 576px) {
            .login-container {
                padding: 30px 20px;
            }
            
            .login-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>
    
    <div class="login-container">
        <div class="login-header">
            <img src="imagenes/logo morad 2.jpg" alt="Talleres MORAD">
            <h2><i class="fas fa-user-shield me-2"></i>Admin Login</h2>
            <p>Panel de Administración de Citas</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert-custom">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert-success-custom">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form action="admin_citas.php" method="POST" autocomplete="off">
            <div class="mb-3">
                <label class="form-label" for="username">
                    <i class="fas fa-user me-2"></i>Nombre de Usuario
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" 
                           class="form-control" 
                           id="username" 
                           name="username" 
                           required 
                           autocomplete="username"
                           placeholder="Ingrese su usuario">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label" for="password">
                    <i class="fas fa-lock me-2"></i>Contraseña
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password" 
                           required 
                           autocomplete="current-password"
                           placeholder="Ingrese su contraseña">
                </div>
            </div>
            
            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
            </button>
        </form>
        
        <div class="security-info">
            <p><i class="fas fa-shield-alt me-1"></i>Conexión Segura SSL</p>
            <div class="security-icons">
                <i class="fas fa-lock" title="Encriptación"></i>
                <i class="fas fa-user-shield" title="Protección"></i>
                <i class="fas fa-fingerprint" title="Autenticación"></i>
            </div>
        </div>
        
        <div class="back-home">
            <a href="index.html">
                <i class="fas fa-arrow-left me-1"></i>Volver al Inicio
            </a>
        </div>
    </div>
    
    <script>
        // Auto-focus en el campo de usuario
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>