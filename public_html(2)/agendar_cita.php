<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = htmlspecialchars(trim($_POST['nombre']));
    $telefono = htmlspecialchars(trim($_POST['telefono']));
    $fecha = htmlspecialchars(trim($_POST['fecha']));
    $hora = htmlspecialchars(trim($_POST['hora']));
    $servicio = htmlspecialchars(trim($_POST['servicio']));

    // Validaciones
    if (empty($nombre) || empty($telefono) || empty($fecha) || empty($hora) || empty($servicio)) {
        echo "Todos los campos son obligatorios.";
        exit();
    }

    if (!preg_match("/^[a-zA-Z\s]+$/", $nombre)) {
        echo "El nombre solo puede contener letras y espacios.";
        exit();
    }

    if (!preg_match("/^[0-9]{9}$/", $telefono)) {
        echo "El teléfono debe contener 9 dígitos.";
        exit();
    }

    $sql = "INSERT INTO citas (nombre, telefono, fecha, hora, servicio) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nombre, $telefono, $fecha, $hora, $servicio);

    if ($stmt->execute()) {
        $mensaje = "NUEVA CITA AGENDADA\n"
            . "-----------------\n"
            . "Nombre: " . $nombre . "\n"
            . "Teléfono: " . $telefono . "\n"
            . "Fecha: " . $fecha . "\n"
            . "Hora: " . $hora . "\n"
            . "Servicio: " . $servicio;

        $mensaje_codificado = urlencode($mensaje);
        $whatsapp_url = "https://api.whatsapp.com/send?phone=34699883683&text=$mensaje_codificado";

        echo <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Procesando...</title>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>
        <body>
            <script>
                Swal.fire({
                    title: 'Cita Agendada',
                    text: 'Su cita ha sido agendada con éxito.',
                    icon: 'success',
                    confirmButtonText: 'Continuar a WhatsApp'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '$whatsapp_url';
                    }
                });
            </script>
        </body>
        </html>
HTML;
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Cita - Talleres MORAD</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Estilos generales del cuerpo de la página */
        html, body {
            height: 100%;
            margin: 0;
            overflow-y: auto;
            scroll-behavior: smooth;
        }

        .page-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        body {
            padding-top: 100px;
            background-color: #000;
            color: #fff;
            background-image: url('imagenes/fondo.jpg');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            font-family: Arial, sans-serif;
        }

        footer {
            margin-top: auto;
            position: relative;
            bottom: 0;
            width: 100%;
            padding: 40px;
            height: 150px;
            background-color: #222;
            background-image: url('imagenes/fibra-carbono.jpeg');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
        }

        .contact-info {
            text-align: left;
        }

        .email-info {
            text-align: right;
        }

        .marca {
            width: 100%;
            text-align: center;
            margin-top: 20px;
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
        }

        .section {
            height: auto;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 50px 0;
        }

        .navbar {
            background-color: transparent !important;
            backdrop-filter: blur(5px);
            margin-top: auto;
            left: 0;
            width: 100%;
            padding: 20px;
            z-index: 1000;
        }

        .navbar-brand {
            margin-left: 0;
        }

        .navbar-brand img {
            height: 200px;
            width: auto;
            object-fit: contain;
            margin-top: -90px;
        }

        .navbar-nav {
            margin-left: 100px;
            margin-right: auto;
        }

        .navbar-nav .nav-link {
            transition: all 0.3s ease-in-out;
            padding: 0.5rem 1rem;
            perspective: 1000px;
            position: relative;
        }

        .navbar-nav .nav-link:hover {
            transform: scale(1.05) rotateY(10deg) rotateX(10deg);
            color: #ff4d00;
            text-shadow: 0 0 80px rgb(255, 77, 0), 0 0 60px rgb(255, 77, 0), 0 0 30px rgb(255, 77, 0);
            box-shadow: 0 0 30px rgb(255, 77, 0);
        }

        .nav-link {
            color: white !important;
            font-weight: bold;
        }

        .header-container {
            padding-bottom: 60px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            text-align: center;
            color: white;
            margin-top: 0px;
            flex-grow: 1;
        }

        .content {
            flex-grow: 0;
        }

        .welcome-message {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            background-color: transparent;
            padding: 20px;
            z-index: 1;
        }

        /* Estilos para hacer los campos de entrada más anchos */
        .form-control {
            width: 100%;
            max-width: 1000px; /* Adjust this value as needed */
            margin: 0 auto; /* Center the fields */
        }

        .custom-width {
            width: 100%;
            max-width: 1000px; /* Adjust this value as needed */
            margin: 0 auto; /* Center the fields */
        }

        /* Estilos del formulario de contacto */
        .contact-form {
            background-color: rgba(0, 0, 0, 0.7);
            border: 2px solid #fff;
            padding: 30px;
            border-radius: 10px;
            color: #fff;
            margin-top: -2cm; /* Añade esta línea para subir el formulario */
        }

        .contact-form h2 {
            color: #fff;
        }

        .contact-form label {
            color: #fff;
        }

        .contact-form .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .contact-form .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
    </style>
</head>
<body>
<div class="page-container">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <a class="navbar-brand" href="index.html">
            <img src="imagenes/logo morad 2.jpg" alt="Talleres MORAD" width="100" height="100">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.html">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="servicios.html">Servicios</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="sobre-nosotros.html">Sobre Nosotros</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contacto.html">Contacto</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="terminos-y-condiciones.html">Términos y Privacidad</a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="agendar_cita.php">Agendar Cita</a>
                </li>
            </ul>
        </div>
    </nav>

    <main class="content">
        <section class="container my-5 section">
            <div class="contact-form">
                <h2 class="text-center">Agendar Cita</h2>
                <form action="agendar_cita.php" method="POST">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" class="form-control custom-width" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="text" class="form-control custom-width" id="telefono" name="telefono" required>
                    </div>
                    <div class="form-group">
                        <label for="fecha">Fecha</label>
                        <input type="date" class="form-control custom-width" id="fecha" name="fecha" required>
                    </div>
                    <div class="form-group">
                        <label for="hora">Hora</label>
                        <input type="time" class="form-control custom-width" id="hora" name="hora" required>
                    </div>
                    <div class="form-group">
                        <label for="servicio">Servicio</label>
                        <select class="form-control custom-width" id="servicio" name="servicio" required>
                            <option value="Diagnóstico">Diagnóstico</option>
                            <option value="Cambio de Aceite">Cambio de Aceite</option>
                            <option value="Distribución">Distribución</option>
                            <option value="Frenos">Frenos</option>
                            <option value="Mecánica General">Mecánica General</option>
                            <option value="Chapa y Pintura">Chapa y Pintura</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Agendar Cita</button>
                </form>
            </div>
        </section>
    </main>

    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row text-center text-md-left align-items-center">
                <div class="col-md-4 mb-3 mb-md-0">
                    <p class="mb-1">Dirección: Calle de la Dalia, 39 - 52006 Melilla</p>
                    <p class="mb-0">Teléfono: +34 699883683</p>
                </div>
                <div class="col-md-4 text-center mb-3 mb-md-0">
                    <a href="https://api.whatsapp.com/send?phone=34699883683&text=Hola!%20Bienvenido%20a%20Talleres%20Morad..." target="_self">
                        <img src="imagenes/whatsapp-logo.png" alt="WhatsApp" class="img-fluid" style="width: 50px; height: 50px;">
                    </a>
                </div>
                <div class="col-md-4 text-md-right">
                    <p class="mb-0">Correo electrónico: talleresmoradmelilla@hotmail.com</p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col text-center">
                    <p class="mb-0">2024 Talleres MORAD. Todos los derechos reservados. | Diseño web by zubimendi</p>
                </div>
            </div>
        </div>
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    function showSuccessMessage() {
        Swal.fire({
            title: 'Cita Agendada',
            text: 'Su cita ha sido agendada con éxito.',
            icon: 'success',
            confirmButtonText: 'OK'
        });
    }
</script>
</body>
</html>