<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: admin_citas.php");
    exit();
}

include 'db.php';

$sql = "SELECT * FROM citas";
$result = $conn->query($sql);

$citas = [];
while ($row = $result->fetch_assoc()) {
    $mensaje = "Detalles de la Cita:\n"
        . "Nombre: " . $row['nombre'] . "\n"
        . "Teléfono: " . $row['telefono'] . "\n"
        . "Fecha: " . $row['fecha'] . "\n"
        . "Hora: " . $row['hora'] . "\n"
        . "Servicio: " . $row['servicio'];
    $mensaje_codificado = urlencode($mensaje);
    $whatsapp_url = "https://api.whatsapp.com/send?phone=34699883683&text=$mensaje_codificado";

    $citas[] = [
        'title' => $row['nombre'] . ' - ' . $row['servicio'],
        'start' => $row['fecha'] . 'T' . $row['hora'],
        'className' => (new DateTime($row['fecha'] . ' ' . $row['hora']) < new DateTime()) ? 'past-event' : 'upcoming-event',
        'extendedProps' => [
            'telefono' => $row['telefono'],
            'fecha' => $row['fecha'],
            'hora' => $row['hora'],
            'servicio' => $row['servicio'],
            'whatsapp_url' => $whatsapp_url
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Citas</title>
    <link href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    <style>
        .past-event {
            background-color: rgba(0, 0, 255, 0.3) !important;
        }
        .upcoming-event {
            background-color: rgba(255, 0, 0, 0.3) !important;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="title is-2 has-text-centered my-4">Citas Agendadas</h1>
    <div id="calendar"></div>
</div>

<!-- Modal -->
<div class="modal" id="eventModal">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">Detalles de la Cita</p>
            <button class="delete" aria-label="close"></button>
        </header>
        <section class="modal-card-body">
            <p><strong>Nombre:</strong> <span id="modalNombre"></span></p>
            <p><strong>Teléfono:</strong> <span id="modalTelefono"></span></p>
            <p><strong>Fecha:</strong> <span id="modalFecha"></span></p>
            <p><strong>Hora:</strong> <span id="modalHora"></span></p>
            <p><strong>Servicio:</strong> <span id="modalServicio"></span></p>
        </section>
        <footer class="modal-card-foot">
            <button class="button" id="closeModal">Cerrar</button>
            <a class="button is-link" id="whatsappLink" target="_blank">Enviar a WhatsApp</a>
        </footer>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: <?php echo json_encode($citas); ?>,
            eventClick: function(info) {
                document.getElementById('modalNombre').innerText = info.event.title.split(' - ')[0];
                document.getElementById('modalTelefono').innerText = info.event.extendedProps.telefono;
                document.getElementById('modalFecha').innerText = info.event.extendedProps.fecha;
                document.getElementById('modalHora').innerText = info.event.extendedProps.hora;
                document.getElementById('modalServicio').innerText = info.event.extendedProps.servicio;
                document.getElementById('whatsappLink').href = info.event.extendedProps.whatsapp_url;
                document.getElementById('eventModal').classList.add('is-active');
            }
        });
        calendar.render();

        document.querySelectorAll('.delete, #closeModal').forEach(function(element) {
            element.addEventListener('click', function() {
                document.getElementById('eventModal').classList.remove('is-active');
            });
        });
    });
</script>
</body>
</html>