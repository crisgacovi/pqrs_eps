<?php
/**
 * Funciones de Email - Sistema de PQRSs
 * Última modificación: 2025-07-21 22:19:27 UTC
 * @author crisgacovi
 */

require_once __DIR__ . "/../../config/config.php";
require_once __DIR__ . "/../../config/email_config.php";
require_once __DIR__ . "/../../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuración de logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/email_error.log');
error_reporting(E_ALL);

/**
 * Obtiene los detalles de una pqrs desde la base de datos
 * @param int $pqrs_id ID de la pqrs
 * @return array Detalles de la pqrs
 * @throws Exception si la pqrs no existe o hay error en la consulta
 */
function obtenerDetallesPQRS($pqrs_id) {
    global $conn;

    $sql = "SELECT q.*, c.nombre as ciudad_nombre, e.nombre as eps_nombre, e.id as eps_id,
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
 * Función para obtener la ruta física de un archivo
 * @param string $ruta_archivo Ruta del archivo
 * @return string Ruta física normalizada
 * @throws Exception si la ruta no es válida o no se puede resolver
 */
function obtenerRutaFisica($ruta_archivo) {
    try {
        // Verificar si la ruta está vacía
        if (empty($ruta_archivo)) {
            throw new Exception("La ruta del archivo está vacía");
        }

        // Definir las rutas base
        $base_path = dirname(dirname(__DIR__)); // Subir dos niveles desde /admin/includes/
        $uploads_dir = $base_path . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'adjuntos';

        // Normalizar para Windows y quitar http(s):// si llega por error
        $ruta_relativa = preg_replace('#^https?://[^/]+/#', '', $ruta_archivo);
        $ruta_relativa = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($ruta_relativa, '/\\'));

        // Si la ruta no comienza con 'uploads/adjuntos', agregarla
        if (strpos($ruta_relativa, 'uploads' . DIRECTORY_SEPARATOR . 'adjuntos') === false) {
            $ruta_relativa = 'uploads' . DIRECTORY_SEPARATOR . 'adjuntos' . DIRECTORY_SEPARATOR . basename($ruta_relativa);
        }
        
        // Construir rutas posibles
        $rutas_posibles = [
            $base_path . DIRECTORY_SEPARATOR . $ruta_relativa,
            $uploads_dir . DIRECTORY_SEPARATOR . basename($ruta_relativa)
        ];

        error_log("Debug - Rutas posibles:");
        foreach ($rutas_posibles as $ruta) {
            error_log("Intentando ruta: " . $ruta);
            if (file_exists($ruta) && is_file($ruta) && is_readable($ruta)) {
                error_log("Archivo encontrado en: " . $ruta);
                return realpath($ruta);
            }
        }

        // Si llegamos aquí, no se encontró el archivo
        throw new Exception("El archivo no existe o no es accesible en ninguna de las rutas intentadas");

    } catch (Exception $e) {
        error_log("Error al resolver ruta del archivo: " . $e->getMessage());
        error_log("Ruta original: " . $ruta_archivo);
        error_log("Base path: " . $base_path);
        error_log("Uploads dir: " . $uploads_dir);
        throw new Exception("No se puede resolver la ruta del archivo: " . $e->getMessage());
    }
}

/**
 * Función para adjuntar archivos al email
 * @param PHPMailer $mail Instancia de PHPMailer
 * @param string $ruta_archivo Ruta del archivo a adjuntar
 * @throws Exception si hay error con el archivo
 */
function adjuntarArchivo($mail, $ruta_archivo) {
    try {
        if (empty($ruta_archivo)) {
            throw new Exception("Ruta de archivo vacía");
        }

        $ruta_absoluta = realpath($ruta_archivo);
        if (!$ruta_absoluta) {
            throw new Exception("No se puede resolver la ruta del archivo");
        }

        if (!is_file($ruta_absoluta) || !is_readable($ruta_absoluta)) {
            throw new Exception("El archivo no existe o no es accesible");
        }

        if (filesize($ruta_absoluta) > SMTP_MAX_FILE_SIZE) {
            throw new Exception("El archivo excede el tamaño máximo permitido (" . (SMTP_MAX_FILE_SIZE / 1024 / 1024) . "MB)");
        }

        $mail->addAttachment($ruta_absoluta);
        return true;
    } catch (Exception $e) {
        error_log("Error al adjuntar archivo: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Configura los parámetros SMTP del objeto PHPMailer
 * @param PHPMailer $mail Instancia de PHPMailer
 * @throws Exception si hay error en la configuración
 * @return bool
 */
function configurarSMTP($mail) {
    try {
        if (!defined('EMAIL_FROM_ADDRESS') || !filter_var(EMAIL_FROM_ADDRESS, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('La dirección de correo del remitente (' . (defined('EMAIL_FROM_ADDRESS') ? EMAIL_FROM_ADDRESS : 'NO DEFINIDA') . ') no es válida.');
        }

        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = SMTP_TIMEOUT;
        
        $mail->MaxFileSize = SMTP_MAX_FILE_SIZE;
        
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addReplyTo(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);

        if (defined('SMTP_DEBUG') && SMTP_DEBUG === true) {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = 'error_log';
        }

        return true;
    } catch (Exception $e) {
        error_log("Error en configuración SMTP: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Envía el email de respuesta al paciente
 * @param PHPMailer $mail Instancia de PHPMailer
 * @param array $pqrs Datos de la pqrs
 * @param int $pqrs_id ID de la pqrs
 * @param string $mensaje_adicional Mensaje adicional opcional
 * @throws Exception si hay error al enviar el email
 * @return bool
 */
function enviarEmailPaciente($mail, $pqrs, $pqrs_id, $mensaje_adicional) {
    try {
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
                </div>
            </div>
        </body>
        </html>';

        $mail->Body = $cuerpo_email;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $cuerpo_email));

        if (!empty($pqrs['archivo_adjunto'])) {
            $ruta_fisica = obtenerRutaFisica($pqrs['archivo_adjunto']);
            if ($ruta_fisica) {
                adjuntarArchivo($mail, $ruta_fisica);
            }
        }

        if (!$mail->send()) {
            throw new Exception($mail->ErrorInfo);
        }

        return true;
    } catch (Exception $e) {
        error_log("Error al enviar email al paciente: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Envía el email a la EPS
 * @param PHPMailer $mail Instancia de PHPMailer
 * @param array $pqrs Datos de la pqrs
 * @param int $pqrs_id ID de la pqrs
 * @throws Exception si hay error al enviar el email
 * @return bool
 */
function enviarEmailEPS($mail, $pqrs, $pqrs_id) {
    try {
        // Obtener los emails de la EPS de la base de datos
        global $conn;
        $sql = "SELECT email FROM eps_emails WHERE eps_id = ? AND estado = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pqrs['eps_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al obtener los emails de la EPS: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $destinatarios = [];
        
        while ($row = $result->fetch_assoc()) {
            $destinatarios[] = $row['email'];
        }
        
        if (empty($destinatarios)) {
            throw new Exception("La EPS no tiene emails configurados");
        }

        error_log("Intentando enviar email a los siguientes destinatarios:");
        foreach ($destinatarios as $email) {
            error_log("- " . $email);
            $mail->addAddress($email, $pqrs['eps_nombre']);
        }
        
        // Agregar copia al correo de la auditoría si está configurado
        if (defined('AUDITORIA_EMAIL') && !empty(AUDITORIA_EMAIL)) {
            $mail->addCC(AUDITORIA_EMAIL, 'Auditoría');
        }

        $mail->isHTML(true);
        $mail->Subject = "Nueva PQRS Registrada #" . $pqrs_id;

        $fecha_limite = date('Y-m-d', strtotime($pqrs['fecha_creacion'] . ' + 15 weekdays'));

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

        if (!empty($pqrs['archivo_adjunto'])) {
            $ruta_fisica = obtenerRutaFisica($pqrs['archivo_adjunto']);
            if ($ruta_fisica) {
                adjuntarArchivo($mail, $ruta_fisica);
            }
        }

        if (!$mail->send()) {
            throw new Exception($mail->ErrorInfo);
        }

        return true;
    } catch (Exception $e) {
        error_log("Error al enviar email a la EPS: " . $e->getMessage());
        throw $e;
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
    try {
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
        if (!configurarSMTP($mail)) {
            throw new Exception("Error en la configuración SMTP");
        }

        // Enviar email al paciente
        if (!enviarEmailPaciente($mail, $pqrs, $pqrs_id, $mensaje_adicional)) {
            throw new Exception("Error al enviar el email al paciente");
        }

        // Reiniciar el objeto PHPMailer para el segundo email
        $mail->clearAddresses();
        $mail->clearAttachments();

        // Enviar email a la EPS
        if (!enviarEmailEPS($mail, $pqrs, $pqrs_id)) {
            throw new Exception("Error al enviar el email a la EPS");
        }

        // Actualizar el estado del email en la base de datos
        global $conn;
        $sql = "UPDATE pqrss SET email_enviado = 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pqrs_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar el estado del email: " . $stmt->error);
        }

        return true;
    } catch (Exception $e) {
        error_log("Error al enviar email: " . $e->getMessage());
        throw new Exception("Error al enviar el email: " . $e->getMessage());
    } finally {
        if (isset($mail)) {
            $mail->clearAddresses();
            $mail->clearAttachments();
        }
    }
}