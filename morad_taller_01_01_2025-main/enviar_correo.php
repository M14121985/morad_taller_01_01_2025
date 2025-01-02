<?php
// Configuración de la zona horaria
date_default_timezone_set('Europe/Madrid');

// Verificamos si los datos fueron enviados por el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibimos los datos del formulario y los validamos
    $nombre = trim(htmlspecialchars($_POST['nombre']));
    $email = trim(htmlspecialchars($_POST['email']));
    $mensaje = trim(htmlspecialchars($_POST['mensaje']));

    // Validación de correo electrónico
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Dirección de correo electrónico no válida.'); window.location = 'contacto.html';</script>";
        exit;
    }

    // Destinatario
    $to = "talleresmoradmelilla@hotmail.com";

    // Asunto del correo para el destinatario
    $subject = "Nuevo mensaje de contacto de $nombre";

    // Cuerpo del mensaje para el destinatario
    $body = "
    <html>
    <head>
      <title>Nuevo mensaje de contacto</title>
    </head>
    <body>
      <p><strong>Nombre:</strong> $nombre</p>
      <p><strong>Correo electrónico:</strong> $email</p>
      <p><strong>Mensaje:</strong><br>$mensaje</p>
    </body>
    </html>
    ";

    // Encabezados
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: $email" . "\r\n"; // Desde el correo del usuario

    // Enviamos el correo al destinatario
    $mail_sent_to_company = mail($to, $subject, $body, $headers);

    // Asunto del correo para el usuario
    $user_subject = "Confirmación de recepción de su consulta";

    // Cuerpo del mensaje para el usuario
    $user_body = "
    <html>
    <head>
      <title>Confirmación de consulta</title>
    </head>
    <body>
      <h2>Estimado/a $nombre,</h2>
      <p>Hemos recibido su consulta. Nos pondremos en contacto con usted a la brevedad.</p>
      <p><strong>Datos de contacto:</strong></p>
      <p><strong>Teléfono:</strong> +34 699883683</p>
      <p><strong>Email:</strong> talleresmoradmelilla@hotmail.com</p>
      <p>¡Gracias por contactarnos!</p>
    </body>
    </html>
    ";

    // Encabezados para el correo del usuario
    $user_headers = "MIME-Version: 1.0" . "\r\n";
    $user_headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $user_headers .= "From: talleresmoradmelilla@hotmail.com" . "\r\n"; // Desde la dirección de la empresa

    // Enviamos el correo al usuario
    $mail_sent_to_user = mail($email, $user_subject, $user_body, $user_headers);

    // Verificamos si ambos correos fueron enviados exitosamente
    if ($mail_sent_to_company && $mail_sent_to_user) {
        echo "<script>alert('Mensaje enviado exitosamente. Nos pondremos en contacto contigo pronto.'); window.location = 'contacto.html';</script>";
    } else {
        echo "<script>alert('Error al enviar el mensaje. Inténtalo de nuevo más tarde.'); window.location = 'contacto.html';</script>";
    }
} else {
    // Si alguien intenta acceder directamente al archivo PHP sin el formulario
    echo "<script>alert('Por favor, rellena el formulario.'); window.location = 'contacto.html';</script>";
}

