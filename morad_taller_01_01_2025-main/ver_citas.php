<?php
/**
 * Panel de Administración de Citas - Talleres MORAD
 * Versión moderna con calendario interactivo y gestión de citas
 */

session_start();

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: admin_citas.php");
    exit();
}

require_once 'db.php';

// Configuración
$citas = [];
$estadisticas = [
    'total' => 0,
    'pendientes' => 0,
    'confirmadas' => 0,
    'hoy' => 0
];

try {
    // Obtener todas las citas
    $sql = "SELECT * FROM citas ORDER BY fecha DESC, hora DESC";
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Preparar mensaje para WhatsApp
            $mensaje = "🔧 *Detalles de la Cita* 🔧\n"
                . "━━━━━━━━━━━━━━━━━━━━━\n"
                . "👤 *Nombre:* " . $row['nombre'] . "\n"
                . "📱 *Teléfono:* " . $row['telefono'] . "\n"
                . ($row['email'] ? "📧 *Email:* " . $row['email'] . "\n" : "")
                . "📅 *Fecha:* " . date('d/m/Y', strtotime($row['fecha'])) . "\n"
                . "⏰ *Hora:* " . $row['hora'] . "\n"
                . "🔧 *Servicio:* " . $row['servicio'] . "\n"
                . ($row['comentarios'] ? "💬 *Comentarios:* " . $row['comentarios'] . "\n" : "")
                . "━━━━━━━━━━━━━━━━━━━━━\n"
                . "ID Cita: #" . $row['id'];
            
            $mensaje_codificado = urlencode($mensaje);
            $whatsapp_url = "https://api.whatsapp.com/send?phone=34699883683&text=$mensaje_codificado";
            
            // Determinar si es cita pasada o futura
            $fecha_cita = new DateTime($row['fecha'] . ' ' . $row['hora']);
            $ahora = new DateTime();
            $es_pasada = $fecha_cita < $ahora;
            
            // Contar estadísticas
            $estadisticas['total']++;
            if ($row['estado'] == 'pendiente') $estadisticas['pendientes']++;
            if ($row['estado'] == 'confirmada') $estadisticas['confirmadas']++;
            
            $fecha_hoy = date('Y-m-d');
            if ($row['fecha'] == $fecha_hoy) $estadisticas['hoy']++;
            
            $citas[] = [
                'id' => $row['id'],
                'title' => $row['nombre'] . ' - ' . $row['servicio'],
                'start' => $row['fecha'] . 'T' . $row['hora'],
                'className' => $es_pasada ? 'past-event' : 'upcoming-event',
                'backgroundColor' => $es_pasada ? '#6c757d' : '#ff4d00',
                'borderColor' => $es_pasada ? '#6c757d' : '#ff4d00',
                'extendedProps' => [
                    'id' => $row['id'],
                    'nombre' => $row['nombre'],
                    'telefono' => $row['telefono'],
                    'email' => $row['email'] ?? '',
                    'fecha' => $row['fecha'],
                    'hora' => $row['hora'],
                    'servicio' => $row['servicio'],
                    'comentarios' => $row['comentarios'] ?? '',
                    'estado' => $row['estado'],
                    'whatsapp_url' => $whatsapp_url
                ]
            ];
        }
    }
} catch (Exception $e) {
    error_log("Error al obtener citas: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Citas - Talleres MORAD | Administración</title>
    <meta name="description" content="Panel de administración de citas de Talleres MORAD">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" href="imagenes/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.5 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary-color: #ff4d00;
            --secondary-color: #1a1a1a;
            --dark-bg: #0a0a0a;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
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
        }
        
        /* Navbar */
        .navbar {
            background: rgba(0, 0, 0, 0.95) !important;
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .navbar-brand img {
            height: 60px;
            width: auto;
        }
        
        .nav-link {
            color: #fff !important;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--primary-color) !important;
        }
        
        /* Main Container */
        .main-container {
            padding: 30px 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(30, 30, 30, 0.95);
            border: 2px solid var(--primary-color);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 77, 0, 0.3);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-family: 'Oswald', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #fff;
        }
        
        .stat-label {
            color: #aaa;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
            margin-top: 5px;
        }
        
        /* Calendar Container */
        .calendar-container {
            background: rgba(30, 30, 30, 0.95);
            border: 2px solid var(--primary-color);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .section-title {
            font-family: 'Oswald', sans-serif;
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .section-title i {
            font-size: 2.2rem;
        }
        
        /* FullCalendar Customization */
        .fc {
            background: transparent !important;
        }
        
        .fc-toolbar-title {
            color: #fff !important;
            font-family: 'Oswald', sans-serif !important;
            font-size: 1.5rem !important;
        }
        
        .fc-button-primary {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        
        .fc-button-primary:hover {
            background: #ff6b35 !important;
            border-color: #ff6b35 !important;
        }
        
        .fc-daygrid-day {
            background: rgba(255, 255, 255, 0.02) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        
        .fc-daygrid-day-number {
            color: #fff !important;
        }
        
        .fc-col-header-cell {
            background: rgba(255, 77, 0, 0.2) !important;
        }
        
        .fc-col-header-cell-cushion {
            color: var(--primary-color) !important;
            font-weight: 600 !important;
        }
        
        .past-event {
            opacity: 0.6;
        }
        
        .upcoming-event {
            opacity: 1;
        }
        
        /* Modal */
        .modal-content {
            background: rgba(30, 30, 30, 0.98);
            border: 2px solid var(--primary-color);
            border-radius: 15px;
            color: #fff;
        }
        
        .modal-header {
            border-bottom: 2px solid var(--primary-color);
            padding: 20px;
        }
        
        .modal-title {
            font-family: 'Oswald', sans-serif;
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .btn-close {
            filter: invert(1);
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-body p {
            margin-bottom: 15px;
            font-size: 1rem;
        }
        
        .modal-body strong {
            color: var(--primary-color);
        }
        
        .modal-footer {
            border-top: 2px solid #444;
            padding: 20px;
        }
        
        .btn-whatsapp {
            background: #25D366;
            border: none;
            color: #fff;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-whatsapp:hover {
            background: #1ebc57;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 211, 102, 0.4);
        }
        
        .btn-secondary-custom {
            background: #6c757d;
            border: none;
            color: #fff;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-secondary-custom:hover {
            background: #5a6268;
        }
        
        /* Logout Button */
        .btn-logout {
            background: rgba(220, 53, 69, 0.2);
            border: 2px solid #dc3545;
            color: #ff6b6b;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: rgba(220, 53, 69, 0.4);
            color: #fff;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
            }
            
            .navbar-brand img {
                height: 50px;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .calendar-container {
                padding: 15px;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 576px) {
            .stats-container {
                grid-template-columns: 1fr;
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
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-user-shield me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="agendar_cita.php">
                            <i class="fas fa-calendar-plus me-2"></i>Nueva Cita
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-logout ms-3" href="admin_citas.php?logout=1">
                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-number"><?php echo $estadisticas['total']; ?></div>
                <div class="stat-label">Total Citas</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo $estadisticas['pendientes']; ?></div>
                <div class="stat-label">Pendientes</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo $estadisticas['confirmadas']; ?></div>
                <div class="stat-label">Confirmadas</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-number"><?php echo $estadisticas['hoy']; ?></div>
                <div class="stat-label">Citas Hoy</div>
            </div>
        </div>

        <!-- Calendar Section -->
        <div class="calendar-container">
            <h2 class="section-title">
                <i class="fas fa-calendar-alt"></i>
                Calendario de Citas
            </h2>
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Modal de Detalles -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">
                        <i class="fas fa-clipboard-list me-2"></i>Detalles de la Cita
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong><i class="fas fa-user me-2"></i>Nombre:</strong> <span id="modalNombre"></span></p>
                    <p><strong><i class="fas fa-phone me-2"></i>Teléfono:</strong> <span id="modalTelefono"></span></p>
                    <p><strong><i class="fas fa-envelope me-2"></i>Email:</strong> <span id="modalEmail"></span></p>
                    <p><strong><i class="fas fa-calendar me-2"></i>Fecha:</strong> <span id="modalFecha"></span></p>
                    <p><strong><i class="fas fa-clock me-2"></i>Hora:</strong> <span id="modalHora"></span></p>
                    <p><strong><i class="fas fa-tools me-2"></i>Servicio:</strong> <span id="modalServicio"></span></p>
                    <p><strong><i class="fas fa-comment me-2"></i>Comentarios:</strong> <span id="modalComentarios"></span></p>
                    <p><strong><i class="fas fa-tag me-2"></i>Estado:</strong> <span id="modalEstado"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cerrar
                    </button>
                    <a class="btn btn-whatsapp" id="whatsappLink" target="_blank" rel="noopener noreferrer">
                        <i class="fab fa-whatsapp me-2"></i>Enviar a WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar calendario
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                },
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes',
                    week: 'Semana',
                    day: 'Día',
                    list: 'Lista'
                },
                events: <?php echo json_encode($citas); ?>,
                eventClick: function(info) {
                    // Llenar modal con datos del evento
                    document.getElementById('modalNombre').innerText = info.event.extendedProps.nombre;
                    document.getElementById('modalTelefono').innerText = info.event.extendedProps.telefono;
                    document.getElementById('modalEmail').innerText = info.event.extendedProps.email || 'No especificado';
                    document.getElementById('modalFecha').innerText = info.event.extendedProps.fecha;
                    document.getElementById('modalHora').innerText = info.event.extendedProps.hora;
                    document.getElementById('modalServicio').innerText = info.event.extendedProps.servicio;
                    document.getElementById('modalComentarios').innerText = info.event.extendedProps.comentarios || 'Sin comentarios';
                    
                    // Formatear estado
                    const estado = info.event.extendedProps.estado;
                    const estadoFormatted = estado.charAt(0).toUpperCase() + estado.slice(1);
                    document.getElementById('modalEstado').innerText = estadoFormatted;
                    
                    // Setear enlace de WhatsApp
                    document.getElementById('whatsappLink').href = info.event.extendedProps.whatsapp_url;
                    
                    // Mostrar modal
                    var modal = new bootstrap.Modal(document.getElementById('eventModal'));
                    modal.show();
                },
                eventDidMount: function(info) {
                    // Tooltip personalizado
                    info.el.setAttribute('title', info.event.title);
                }
            });
            calendar.render();
            
            // Manejar logout
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('logout') === '1') {
                Swal.fire({
                    icon: 'success',
                    title: 'Sesión cerrada',
                    text: 'Ha cerrado sesión correctamente',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.history.replaceState({}, document.title, window.location.pathname);
                });
            }
        });
    </script>
</body>
</html>