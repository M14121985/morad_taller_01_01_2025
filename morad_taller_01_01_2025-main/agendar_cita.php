<?php
/**
 * Sistema de Agendamiento de Citas - Talleres MORAD
 * Versión moderna con mejoras de seguridad y UX
 */

require_once 'db.php';

// Configuración
$whatsapp_number = "34699883683";
$servicios_disponibles = [
    "Diagnóstico",
    "Cambio de Aceite",
    "Distribución",
    "Frenos",
    "Mecánica General",
    "Chapa y Pintura",
    "Neumáticos",
    "Electricidad",
    "Aire Acondicionado",
    "Revisión ITV",
    "Mantenimiento Preventivo",
    "Reparación de Motor"
];

$errores = [];
$exito = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitizar y validar datos
    $nombre = sanitize_input($_POST['nombre'] ?? '');
    $telefono = sanitize_input($_POST['telefono'] ?? '');
    $fecha = sanitize_input($_POST['fecha'] ?? '');
    $hora = sanitize_input($_POST['hora'] ?? '');
    $servicio = sanitize_input($_POST['servicio'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $comentarios = sanitize_input($_POST['comentarios'] ?? '');

    // Validaciones mejoradas
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio.";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u", $nombre) || strlen($nombre) < 3) {
        $errores[] = "El nombre debe contener al menos 3 letras.";
    }

    if (empty($telefono)) {
        $errores[] = "El teléfono es obligatorio.";
    } elseif (!validate_phone($telefono)) {
        $errores[] = "El teléfono debe contener 9 dígitos.";
    }

    if (empty($fecha)) {
        $errores[] = "La fecha es obligatoria.";
    } else {
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$fecha_obj || $fecha_obj < new DateTime('today')) {
            $errores[] = "La fecha debe ser hoy o posterior.";
        }
    }

    if (empty($hora)) {
        $errores[] = "La hora es obligatoria.";
    } else {
        $hora_obj = DateTime::createFromFormat('H:i', $hora);
        if (!$hora_obj) {
            $errores[] = "Formato de hora inválido.";
        }
    }

    if (empty($servicio)) {
        $errores[] = "El servicio es obligatorio.";
    } elseif (!in_array($servicio, $servicios_disponibles)) {
        $errores[] = "Servicio no válido.";
    }

    if (!empty($email) && !validate_email($email)) {
        $errores[] = "El correo electrónico no es válido.";
    }

    // Si no hay errores, insertar en la base de datos
    if (empty($errores)) {
        try {
            $sql = "INSERT INTO citas (nombre, telefono, email, fecha, hora, servicio, comentarios, estado, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("sssssss", $nombre, $telefono, $email, $fecha, $hora, $servicio, $comentarios);
                
                if ($stmt->execute()) {
                    $cita_id = $conn->insert_id;
                    
                    // Preparar mensaje para WhatsApp
                    $mensaje = "🔧 *NUEVA CITA AGENDADA* 🔧\n"
                        . "━━━━━━━━━━━━━━━━━━━━━\n"
                        . "👤 *Nombre:* $nombre\n"
                        . "📱 *Teléfono:* $telefono\n"
                        . ($email ? "📧 *Email:* $email\n" : "")
                        . "📅 *Fecha:* " . date('d/m/Y', strtotime($fecha)) . "\n"
                        . "⏰ *Hora:* $hora\n"
                        . "🔧 *Servicio:* $servicio\n"
                        . ($comentarios ? "💬 *Comentarios:* $comentarios\n" : "")
                        . "━━━━━━━━━━━━━━━━━━━━━\n"
                        . "ID Cita: #$cita_id";

                    $mensaje_codificado = urlencode($mensaje);
                    $whatsapp_url = "https://api.whatsapp.com/send?phone=$whatsapp_number&text=$mensaje_codificado";

                    // Guardar datos en sesión para mostrar después
                    session_start();
                    $_SESSION['cita_exitosa'] = true;
                    $_SESSION['whatsapp_url'] = $whatsapp_url;
                    $_SESSION['nombre_cliente'] = $nombre;
                    
                    header("Location: agendar_cita.php?success=1");
                    exit();
                } else {
                    $errores[] = "Error al guardar la cita. Por favor, inténtelo de nuevo.";
                    error_log("Error SQL: " . $stmt->error);
                }
                $stmt->close();
            } else {
                $errores[] = "Error en la preparación de la consulta.";
                error_log("Error prepare: " . $conn->error);
            }
        } catch (Exception $e) {
            $errores[] = "Error inesperado. Por favor, contacte con el taller.";
            error_log("Excepción: " . $e->getMessage());
        }
    }
}

// Manejar redirección de éxito
if (isset($_GET['success']) && $_GET['success'] == 1) {
    session_start();
    if (isset($_SESSION['cita_exitosa']) && $_SESSION['cita_exitosa']) {
        $exito = true;
        $whatsapp_url = $_SESSION['whatsapp_url'] ?? '';
        $nombre_cliente = $_SESSION['nombre_cliente'] ?? '';
        
        // Limpiar sesión
        unset($_SESSION['cita_exitosa']);
        unset($_SESSION['whatsapp_url']);
        unset($_SESSION['nombre_cliente']);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Cita - Talleres MORAD | Taller Mecánico Profesional</title>
    <meta name="description" content="Reserva tu cita en Talleres MORAD. Servicio profesional de mecánica automotriz en Melilla. ¡Rápido, seguro y confiable!">
    
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
            --carbon-fiber: #1f1f1f;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--secondary-color) 100%);
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Navbar moderna */
        .navbar {
            background: rgba(0, 0, 0, 0.95) !important;
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            transition: all 0.3s ease;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .navbar-brand img {
            height: 70px;
            width: auto;
            transition: transform 0.3s ease;
        }
        
        .navbar-brand:hover img {
            transform: scale(1.05);
        }
        
        .nav-link {
            color: #fff !important;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.5rem 1rem !important;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .nav-link:hover::after {
            width: 80%;
        }
        
        .nav-link:hover {
            color: var(--primary-color) !important;
        }
        
        .nav-item.active .nav-link {
            color: var(--primary-color) !important;
        }
        
        /* Hero Section */
        .page-header {
            background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)), 
                        url('imagenes/fondo.jpg') center/cover;
            padding: 80px 0 60px;
            text-align: center;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .page-header h1 {
            font-family: 'Oswald', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .page-header p {
            font-size: 1.2rem;
            color: #ccc;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Form Container */
        .form-container {
            background: rgba(30, 30, 30, 0.95);
            border: 2px solid var(--primary-color);
            border-radius: 15px;
            padding: 40px;
            margin: 50px auto;
            max-width: 800px;
            box-shadow: 0 10px 40px rgba(255, 77, 0, 0.2);
        }
        
        .form-title {
            font-family: 'Oswald', sans-serif;
            color: var(--primary-color);
            font-size: 2.5rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 30px;
            text-transform: uppercase;
        }
        
        .form-label {
            font-weight: 500;
            color: #fff;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }
        
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid #444;
            color: #fff;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary-color);
            box-shadow: 0 0 15px rgba(255, 77, 0, 0.3);
            color: #fff;
        }
        
        .form-control::placeholder {
            color: #aaa;
        }
        
        .btn-agendar {
            background: linear-gradient(135deg, var(--primary-color) 0%, #ff6b35 100%);
            border: none;
            color: #fff;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 8px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }
        
        .btn-agendar:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 77, 0, 0.4);
            background: linear-gradient(135deg, #ff6b35 0%, var(--primary-color) 100%);
        }
        
        .btn-agendar:active {
            transform: translateY(-1px);
        }
        
        /* Alertas */
        .alert-custom {
            background: rgba(220, 53, 69, 0.2);
            border: 2px solid #dc3545;
            color: #ff6b6b;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        /* Footer */
        footer {
            background: linear-gradient(rgba(0,0,0,0.9), rgba(0,0,0,0.9)),
                        url('imagenes/fibra-carbono.jpeg') center/cover;
            padding: 50px 0 30px;
            margin-top: 80px;
            border-top: 3px solid var(--primary-color);
        }
        
        .footer-info p {
            margin-bottom: 10px;
            color: #ccc;
        }
        
        .footer-social a {
            display: inline-block;
            margin: 0 10px;
            transition: transform 0.3s ease;
        }
        
        .footer-social a:hover {
            transform: scale(1.2);
        }
        
        .footer-social img {
            width: 50px;
            height: 50px;
        }
        
        .copyright {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid #333;
            color: #888;
            font-size: 0.9rem;
        }
        
        /* WhatsApp Button */
        .whatsapp-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #25D366;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4);
            z-index: 1000;
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
        }
        
        .whatsapp-float:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.6);
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(37, 211, 102, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .form-container {
                padding: 25px;
                margin: 30px 15px;
            }
            
            .form-title {
                font-size: 1.8rem;
            }
            
            .navbar {
                padding: 1rem;
            }
            
            .navbar-brand img {
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.html">
                <img src="imagenes/logo morad 2.jpg" alt="Talleres MORAD">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.html"><i class="fas fa-home me-2"></i>Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="servicios.html"><i class="fas fa-tools me-2"></i>Servicios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sobre-nosotros.html"><i class="fas fa-users me-2"></i>Nosotros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contacto.html"><i class="fas fa-envelope me-2"></i>Contacto</a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="agendar_cita.php"><i class="fas fa-calendar-check me-2"></i>Agendar Cita</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <section class="page-header">
        <div class="container">
            <h1><i class="fas fa-calendar-alt me-3"></i>Agenda tu Cita</h1>
            <p>Reserva tu servicio de manera rápida y sencilla. Te confirmaremos en menos de 24 horas.</p>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container">
        <div class="form-container">
            <?php if ($exito): ?>
                <!-- Mensaje de éxito -->
                <div class="text-center py-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 5rem; margin-bottom: 20px;"></i>
                    <h2 class="text-success mb-3">¡Cita Agendada con Éxito!</h2>
                    <p class="lead mb-4">Gracias <strong><?php echo htmlspecialchars($nombre_cliente); ?></strong>, hemos recibido tu solicitud.</p>
                    <p class="mb-4">Te redirigiremos a WhatsApp para enviar los detalles de tu cita.</p>
                    <a href="<?php echo htmlspecialchars($whatsapp_url); ?>" class="btn btn-agendar" target="_blank">
                        <i class="fab fa-whatsapp me-2"></i>Continuar a WhatsApp
                    </a>
                </div>
            <?php else: ?>
                <!-- Formulario -->
                <h2 class="form-title"><i class="fas fa-calendar-plus me-2"></i>Reservar Cita</h2>
                
                <?php if (!empty($errores)): ?>
                    <div class="alert-custom">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Por favor corrige los siguientes errores:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errores as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="agendar_cita.php" method="POST" id="bookingForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-user me-2"></i>Nombre Completo *
                            </label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   placeholder="Ej: Juan García" required
                                   value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label">
                                <i class="fas fa-phone me-2"></i>Teléfono *
                            </label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                   placeholder="Ej: 699883683" required pattern="[0-9]{9}"
                                   value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-2"></i>Email (opcional)
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="tu@email.com"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="servicio" class="form-label">
                                <i class="fas fa-wrench me-2"></i>Servicio Requerido *
                            </label>
                            <select class="form-select" id="servicio" name="servicio" required>
                                <option value="">Selecciona un servicio...</option>
                                <?php foreach ($servicios_disponibles as $servicio): ?>
                                    <option value="<?php echo htmlspecialchars($servicio); ?>" 
                                            <?php echo (isset($_POST['servicio']) && $_POST['servicio'] == $servicio) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($servicio); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha" class="form-label">
                                <i class="fas fa-calendar me-2"></i>Fecha Preferida *
                            </label>
                            <input type="date" class="form-control" id="fecha" name="fecha" required
                                   min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo isset($_POST['fecha']) ? htmlspecialchars($_POST['fecha']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="hora" class="form-label">
                                <i class="fas fa-clock me-2"></i>Hora Preferida *
                            </label>
                            <input type="time" class="form-control" id="hora" name="hora" required
                                   min="08:00" max="20:00"
                                   value="<?php echo isset($_POST['hora']) ? htmlspecialchars($_POST['hora']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="comentarios" class="form-label">
                            <i class="fas fa-comment-dots me-2"></i>Comentarios Adicionales (opcional)
                        </label>
                        <textarea class="form-control" id="comentarios" name="comentarios" rows="4"
                                  placeholder="Describe brevemente el problema o servicio que necesitas..."><?php echo isset($_POST['comentarios']) ? htmlspecialchars($_POST['comentarios']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-agendar">
                        <i class="fas fa-check-circle me-2"></i>Confirmar Reserva
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 footer-info">
                    <h5 class="text-white mb-3"><i class="fas fa-map-marker-alt me-2"></i>Ubicación</h5>
                    <p>Calle de la Dalia, 39<br>52006 Melilla</p>
                </div>
                <div class="col-md-4 text-center">
                    <h5 class="text-white mb-3"><i class="fas fa-headset me-2"></i>Contacto</h5>
                    <p class="mb-1"><i class="fas fa-phone me-2"></i>+34 699883683</p>
                    <p class="mb-0"><i class="fas fa-envelope me-2"></i>talleresmoradmelilla@hotmail.com</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <h5 class="text-white mb-3"><i class="fas fa-share-alt me-2"></i>Síguenos</h5>
                    <div class="footer-social">
                        <a href="https://api.whatsapp.com/send?phone=34699883683" target="_blank">
                            <img src="imagenes/whatsapp-logo.png" alt="WhatsApp">
                        </a>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Talleres MORAD. Todos los derechos reservados. | Diseño web by zubimendi</p>
            </div>
        </div>
    </footer>

    <!-- WhatsApp Float Button -->
    <a href="https://api.whatsapp.com/send?phone=34699883683&text=Hola!%20Necesito%20información%20sobre%20una%20cita..." 
       class="whatsapp-float" target="_blank" title="Contactar por WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación del formulario
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const telefono = document.getElementById('telefono').value.trim();
            const fecha = document.getElementById('fecha').value;
            const hora = document.getElementById('hora').value;
            const servicio = document.getElementById('servicio').value;
            
            if (nombre.length < 3) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'El nombre debe tener al menos 3 caracteres',
                    confirmButtonColor: '#ff4d00'
                });
                return false;
            }
            
            if (!/^[0-9]{9}$/.test(telefono)) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'El teléfono debe contener 9 dígitos',
                    confirmButtonColor: '#ff4d00'
                });
                return false;
            }
        });
        
        // Configurar fecha mínima como hoy
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('fecha').setAttribute('min', today);
    </script>
</body>
</html>