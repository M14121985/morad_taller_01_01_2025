<?php
/**
 * Sistema de Envío de Correos - Talleres MORAD
 * Versión moderna con validaciones mejoradas y seguridad
 */

// Configuración de la zona horaria
date_default_timezone_set('Europe/Madrid');

// Función para sanitizar inputs
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Función para validar email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Verificamos si los datos fueron enviados por el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibimos los datos del formulario y los validamos
    $nombre = sanitize_input($_POST['nombre'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $telefono = sanitize_input($_POST['telefono'] ?? '');
    $asunto = sanitize_input($_POST['asunto'] ?? 'Consulta desde formulario web');
    $mensaje = sanitize_input($_POST['mensaje'] ?? '');
    
    // Validaciones mejoradas
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio.";
    } elseif (strlen($nombre) < 3) {
        $errores[] = "El nombre debe contener al menos 3 caracteres.";
    }
    
    if (empty($email)) {
        $errores[] = "El correo electrónico es obligatorio.";
    } elseif (!validate_email($email)) {
        $errores[] = "Dirección de correo electrónico no válida.";
    }
    
    if (!empty($telefono) && !preg_match('/^[0-9]{9}$/', $telefono)) {
        $errores[] = "El teléfono debe contener 9 dígitos.";
    }
    
    if (empty($mensaje)) {
        $errores[] = "El mensaje es obligatorio.";
    } elseif (strlen($mensaje) < 10) {
        $errores[] = "El mensaje debe contener al menos 10 caracteres.";
    }
    
    // Si hay errores, mostrar y salir
    if (!empty($errores)) {
        $mensaje_error = implode("\\n", $errores);
        echo "<script>alert('$mensaje_error'); window.location = 'contacto.html';</script>";
        exit;
    }
    
    // Destinatario
    $to = "talleresmoradmelilla@hotmail.com";
    
    // Asunto del correo para el destinatario
    $subject = "🔧 Nuevo mensaje de contacto de $nombre - Talleres MORAD";
    
    // Cuerpo del mensaje para el destinatario (HTML mejorado)
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #ff4d00, #ff6b35); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #ff4d00; }
            .footer { background: #333; color: white; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔧 Talleres MORAD</h1>
                <p>Nuevo Mensaje de Contacto</p>
            </div>
            <div class='content'>
                <div class='field'>
                    <span class='label'>👤 Nombre:</span> $nombre
                </div>
                <div class='field'>
                    <span class='label'>📧 Email:</span> $email
                </div>
                " . ($telefono ? "<div class='field'><span class='label'>📱 Teléfono:</span> $telefono</div>" : "") . "
                <div class='field'>
                    <span class='label'>📋 Asunto:</span> $asunto
                </div>
                <div class='field'>
                    <span class='label'>💬 Mensaje:</span><br>
                    <p style='background: white; padding: 15px; border-left: 3px solid #ff4d00; margin-top: 10px;'>$mensaje</p>
                </div>
                <div class='field'>
                    <span class='label'>🕐 Fecha de envío:</span> " . date('d/m/Y H:i:s') . "
                </div>
            </div>
            <div class='footer'>
                <p>Talleres MORAD - Tu taller de confianza en Melilla</p>
                <p>📍 Calle Real, 52 | 📞 699883683</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Encabezados mejorados
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: Talleres MORAD Web <no-reply@talleresmorad.com>\r\n";
    $headers .= "Reply-To: $nombre <$email>\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Intentar enviar correo al destinatario
    $mail_sent_to_company = @mail($to, $subject, $body, $headers);
    
    // Preparar correo de confirmación para el usuario
    $user_subject = "✅ Confirmación de recepción - Talleres MORAD";
    
    // Cuerpo del mensaje para el usuario (HTML mejorado)
    $user_body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            .info-box { background: white; padding: 20px; border-left: 4px solid #28a745; margin: 20px 0; }
            .contact-info { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px; }
            .footer { background: #333; color: white; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; }
            .btn { display: inline-block; padding: 12px 30px; background: #ff4d00; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 15px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✅ ¡Mensaje Recibido!</h1>
                <p>Gracias por contactar con Talleres MORAD</p>
            </div>
            <div class='content'>
                <h2>Hola $nombre,</h2>
                <p>Hemos recibido su consulta correctamente. Nos pondremos en contacto con usted a la brevedad posible.</p>
                
                <div class='info-box'>
                    <h3>📋 Resumen de su consulta:</h3>
                    <p><strong>Asunto:</strong> $asunto</p>
                    <p><strong>Fecha de envío:</strong> " . date('d/m/Y H:i:s') . "</p>
                    <p><strong>Número de referencia:</strong> #" . strtoupper(uniqid('MORAD')) . "</p>
                </div>
                
                <p>Nuestro equipo técnico revisará su solicitud y le responderemos en un plazo máximo de 24-48 horas laborables.</p>
                
                <div class='contact-info'>
                    <h3>📞 Datos de contacto:</h3>
                    <p><strong>📍 Dirección:</strong> Calle Real, 52, Melilla</p>
                    <p><strong>📱 Teléfono:</strong> +34 699883683</p>
                    <p><strong>📧 Email:</strong> talleresmoradmelilla@hotmail.com</p>
                    <p><strong>⏰ Horario:</strong> Lunes a Viernes: 8:00 - 20:00</p>
                </div>
                
                <div style='text-align: center;'>
                    <a href='tel:+34699883683' class='btn'>📞 Llamar Ahora</a>
                </div>
                
                <p style='margin-top: 20px;'>¡Gracias por confiar en Talleres MORAD!</p>
            </div>
            <div class='footer'>
                <p>Talleres MORAD © " . date('Y') . " - Todos los derechos reservados</p>
                <p>Especialistas en mecánica automotriz integral</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Encabezados para el correo del usuario
    $user_headers = "MIME-Version: 1.0\r\n";
    $user_headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $user_headers .= "From: Talleres MORAD <talleresmoradmelilla@hotmail.com>\r\n";
    $user_headers .= "Reply-To: talleresmoradmelilla@hotmail.com\r\n";
    $user_headers .= "X-Mailer: PHP/" . phpversion();
    
    // Enviamos el correo al usuario
    $mail_sent_to_user = @mail($email, $user_subject, $user_body, $user_headers);
    
    // Verificamos si ambos correos fueron enviados exitosamente
    if ($mail_sent_to_company && $mail_sent_to_user) {
        // Registrar el envío en log (opcional)
        error_log("Email enviado exitosamente: $nombre ($email) - " . date('Y-m-d H:i:s'));
        
        echo "<script>
            alert('✅ Mensaje enviado exitosamente.\\n\\nNos pondremos en contacto contigo pronto.\\n\\n¡Gracias por contactar con Talleres MORAD!');
            window.location = 'contacto.html';
        </script>";
    } else {
        // Registrar error en log
        error_log("Error al enviar email: $nombre ($email) - " . date('Y-m-d H:i:s'));
        
        echo "<script>
            alert('⚠️ Error al enviar el mensaje.\\n\\nPor favor, inténtelo de nuevo más tarde o llámenos directamente.\\n\\n📞 699883683');
            window.location = 'contacto.html';
        </script>";
    }
} else {
    // Si alguien intenta acceder directamente al archivo PHP sin el formulario
    echo "<script>
        alert('⚠️ Por favor, rellena el formulario de contacto.');
        window.location = 'contacto.html';
    </script>";
}
?>