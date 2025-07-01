<?php
/**
 * Funciones de Email - Sistema de PQRSs
 * Última modificación: 2025-05-22
 * @author crisgacovi
 */

require_once __DIR__ . "/../../config/config.php";
require_once __DIR__ . "/../../config/email_config.php";
require_once __DIR__ . "/../../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Obtiene los detalles de una pqrs desde la base de datos
 * @param int $pqrs_id ID de la pqrs
 * @return array Detalles de la pqrs
 * @throws Exception si la pqrs no existe o hay error en la consulta
 */
function obtenerDetallesPQRS($pqrs_id) {
    global $conn;

    $sql = "SELECT q.*, c.nombre as ciudad_nombre, e.nombre as eps_nombre, e.email as eps_email, 
                   t.nombre as tipo_pqrs_nombre 
            FROM pqrss q 
            JOIN ciudades c ON q.ciudad_id = c.id 
            JOIN eps e ON q.eps_id = e.id 
            JOIN tipos_pqrs t ON q.tipo_pqrs_id = t.id 
            WHERE q.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pqrs_id);

    if (!$stmt->execute()) {
        throw new Exception("Error al obtener los detalles de la pqrs: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("No se encontró la pqrs especificada.");
    }

    return $result->fetch_assoc();
}

/**
 * Configura los parámetros SMTP del objeto PHPMailer
 * @param PHPMailer $mail Instancia de PHPMailer
 * @throws Exception si hay error en la configuración
 */
function configurarSMTP($mail) {
    if (!defined('EMAIL_FROM_ADDRESS') || !filter_var(EMAIL_FROM_ADDRESS, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('La dirección de correo del remitente (' . (defined('EMAIL_FROM_ADDRESS') ? EMAIL_FROM_ADDRESS : 'NO DEFINIDA') . ') no es válida.');
    }
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
    $mail->addReplyTo(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);

    if (defined('SMTP_DEBUG') && SMTP_DEBUG === true) {
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = 'error_log';
    }
}

/**
 * Envía el email de respuesta al paciente
 * @param PHPMailer $mail Instancia de PHPMailer
 * @param array $pqrs Datos de la pqrs
 * @param int $pqrs_id ID de la pqrs
 * @param string $mensaje_adicional Mensaje adicional opcional
 * @throws Exception si hay error al enviar el email
 */
function enviarEmailPaciente($mail, $pqrs, $pqrs_id, $mensaje_adicional) {
    $mail->addAddress($pqrs['email'], $pqrs['nombre_paciente']);
    $mail->isHTML(true);
    $mail->Subject = "Respuesta a su PQRS #" . $pqrs_id;

    $cuerpo_email = '
    <html>
    <head>
        <title>Respuesta a su PQRS</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { padding: 20px; }
            .header { background-color: #f8f9fa; padding: 10px; }
            .content { padding: 15px 0; }
            .footer { color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Respuesta a su PQRS #' . $pqrs_id . '</h2>
            </div>
            <div class="content">
                <p>Estimado/a ' . htmlspecialchars($pqrs['nombre_paciente']) . ',</p>
                <p>Su pqrs ha sido trasladada a su EPS, quien deberá resolverla dentro de los términos de ley.</p>
                <hr>
                <p><strong>Respuesta:</strong></p>
                ' . nl2br(htmlspecialchars($pqrs['respuesta'])) . '
                ' . (!empty($mensaje_adicional) ? '<p><strong>Mensaje adicional:</strong><br>' . nl2br(htmlspecialchars($mensaje_adicional)) . '</p>' : '') . '
                <hr>
                <p><strong>Detalles de la pqrs:</strong></p>
                <ul>
                    <li>Fecha de creación: ' . date('Y-m-d', strtotime($pqrs['fecha_creacion'])) . '</li>
                    <li>Ciudad: ' . htmlspecialchars($pqrs['ciudad_nombre']) . '</li>
                    <li>EPS: ' . htmlspecialchars($pqrs['eps_nombre']) . '</li>
                    <li>Tipo de pqrs: ' . htmlspecialchars($pqrs['tipo_pqrs_nombre']) . '</li>
                </ul>
            </div>
            <div class="footer">
                <p>Este es un mensaje generado en el Sistema de PQRSs de Auditorías Escamilla.</p>
                <p>Por favor no responda a este correo. Pronto su EPS se comunicará con usted.</p>
            </div>
        </div>
    </body>
    </html>';

    $mail->Body = $cuerpo_email;
    $mail->AltBody = strip_tags(str_replace('<br>', "\n", $cuerpo_email));

    if (!$mail->send()) {
        throw new Exception($mail->ErrorInfo);
    }
}

/**
 * Envía el email de notificación a la EPS con datos del usuario y adjunto si existe
 * @param PHPMailer $mail Instancia de PHPMailer
 * @param array $pqrs Datos de la pqrs
 * @param int $pqrs_id ID de la pqrs
 * @throws Exception si hay error al enviar el email o si la EPS no tiene email registrado
 */
function enviarEmailEPS($mail, $pqrs, $pqrs_id) {
    if (empty($pqrs['eps_email'])) {
        throw new Exception("La EPS no tiene un correo electrónico registrado.");
    }

    $mail->addAddress($pqrs['eps_email'], $pqrs['eps_nombre']);
    $mail->isHTML(true);
    $mail->Subject = "Nueva PQRS Registrada #" . $pqrs_id;

    $fecha_limite = date('Y-m-d', strtotime($pqrs['fecha_creacion'] . ' + 15 weekdays'));

    // ==== ADJUNTAR ARCHIVO SI EXISTE Y ES ACCESIBLE ====
    if (!empty($pqrs['archivo_adjunto'])) {
        // Normalizar para Windows y quitar http(s):// si llega por error
        $ruta_relativa = preg_replace('#^https?://[^/]+/#', '', $pqrs['archivo_adjunto']);
        $ruta_relativa = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($ruta_relativa, '/\\'));
        // Construir la ruta absoluta en Windows
        $ruta_fisica = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . DIRECTORY_SEPARATOR . $ruta_relativa;
        // error_log("Buscando adjunto: " . $ruta_fisica);
        if (file_exists($ruta_fisica) && is_file($ruta_fisica)) {
            $mail->addAttachment($ruta_fisica);
        } else {
            // error_log("No se encontró el archivo adjunto para EPS: " . $ruta_fisica);
        }
    }

    $cuerpo_email = '
    <html>
    <head>
        <title>Nueva PQRS Registrada</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { padding: 20px; }
            .header { background-color: #f8f9fa; padding: 10px; }
            .content { padding: 15px 0; }
            .footer { color: #666; font-size: 12px; }
            .important { color: #dc3545; font-weight: bold; }
            .plazos { background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Nueva PQRS Registrada #' . $pqrs_id . '</h2>
            </div>
            <div class="content">
                <p>Se ha registrado una nueva pqrs contra su EPS con los siguientes detalles:</p>
                <hr>
                <p><strong>Datos del usuario que interpuso la pqrs:</strong></p>
                <ul>
                    <li><strong>Nombre:</strong> ' . htmlspecialchars($pqrs['nombre_paciente']) . '</li>
                    <li><strong>Documento:</strong> ' . htmlspecialchars($pqrs['documento_identidad']) . '</li>
                    <li><strong>Email:</strong> ' . htmlspecialchars($pqrs['email']) . '</li>
                    <li><strong>Teléfono:</strong> ' . htmlspecialchars($pqrs['telefono']) . '</li>
                </ul>
                <p><strong>Detalles de la pqrs:</strong></p>
                <ul>
                    <li>Fecha de registro: ' . date('Y-m-d', strtotime($pqrs['fecha_creacion'])) . '</li>
                    <li>Ciudad: ' . htmlspecialchars($pqrs['ciudad_nombre']) . '</li>
                    <li>Tipo de pqrs: ' . htmlspecialchars($pqrs['tipo_pqrs_nombre']) . '</li>
                </ul>
                <p><strong>Descripción de la pqrs:</strong></p>
                ' . nl2br(htmlspecialchars($pqrs['descripcion'])) . '
                <hr>
                <div class="plazos">
                    <p class="important">Plazos de respuesta según la ley:</p>
                    <ul>
                        <li>Reclamos de riesgo simple: 72 horas _ Consultas médicas, generación de autorizaciones, etc</li>
                        <li>Reclamos de riesgo priorizado: 48 horas Entrega de medicamentos</li>
                        <li>Reclamos de riesgo vital: 24 horas según Remisiones, referencia, traslados, etc</li>
                        <li>Solicitudes de información: diez (10) días hábiles</li>
                        <li>Copias: dentro de los tres (3) días hábiles - Si las copias son de historias clínicas o de exámenes y se requieran para una consulta o urgencia, serán catalogados como reclamos</li>
                    </ul>
                    <p class="important">Fecha límite de respuesta: ' . $fecha_limite . '</p>
                    <p>Por favor, asegúrese de responder dentro del plazo establecido para evitar sanciones legales.</p>
                </div>
                ' . (!empty($pqrs['archivo_adjunto']) ? '<p><strong>Se adjunta archivo enviado por el usuario.</strong></p>' : '') . '
            </div>
            <div class="footer">
                <p>Este es un mensaje generado en el Sistema de PQRSs de Auditorías Escamilla.</p>
                <p>Responder al correo registrado por el usuario con copia al correo de la auditoria dentro de los términos de ley.</p>
            </div>
        </div>
    </body>
    </html>';

    $mail->Body = $cuerpo_email;
    $mail->AltBody = strip_tags(str_replace('<br>', "\n", $cuerpo_email));

    if (!$mail->send()) {
        throw new Exception($mail->ErrorInfo);
    }
}

/**
 * Función principal para enviar emails de respuesta a pqrss
 * @param int $pqrs_id ID de la pqrs
 * @param string $mensaje_adicional Mensaje adicional opcional
 * @return bool true si el envío fue exitoso
 * @throws Exception si hay error en el proceso
 */
function enviarEmailRespuestaPQRS($pqrs_id, $mensaje_adicional = '') {
    if (!$pqrs_id) {
        throw new Exception("ID de pqrs no válido.");
    }

    $pqrs = obtenerDetallesPQRS($pqrs_id);

    // Verificar que exista email y respuesta
    if (empty($pqrs['email'])) {
        throw new Exception("La pqrs no tiene email asociado.");
    }
    if (empty($pqrs['respuesta'])) {
        throw new Exception("La pqrs no tiene una respuesta registrada.");
    }

    // Crear instancia de PHPMailer
    $mail = new PHPMailer(true);

    // Configuración del servidor
    configurarSMTP($mail);

    try {
        // Enviar email al paciente
        enviarEmailPaciente($mail, $pqrs, $pqrs_id, $mensaje_adicional);

        // Reiniciar el objeto PHPMailer para el segundo email
        $mail->clearAddresses();
        $mail->clearAttachments();

        // Enviar email a la EPS (con adjunto y datos usuario)
        enviarEmailEPS($mail, $pqrs, $pqrs_id);

        return true;
    } catch (Exception $e) {
        error_log("Error al enviar email: " . $e->getMessage());
        throw new Exception("Error al enviar el email: " . $e->getMessage());
    }
}