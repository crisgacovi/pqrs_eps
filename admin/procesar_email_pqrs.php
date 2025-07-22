<?php
// Deshabilitar la salida del búfer
@ob_end_clean();
// Iniciar nuevo búfer
ob_start();

// Configurar cabeceras
header('Content-Type: application/json; charset=utf-8');

session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

try {
    require_once "includes/email_functions.php";

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $pqrs_id = filter_input(INPUT_POST, 'pqrs_id', FILTER_VALIDATE_INT);
    if (!$pqrs_id) {
        throw new Exception('ID de pqrs inválido');
    }

    $mensaje_adicional = filter_input(INPUT_POST, 'mensaje', FILTER_SANITIZE_STRING) ?? '';

    // Intentar enviar el email
    if (enviarEmailRespuestaPQRS($pqrs_id, $mensaje_adicional)) {
        $response = [
            'success' => true,
            'message' => 'Email enviado exitosamente'
        ];
    } else {
        throw new Exception('Error al enviar el email');
    }

} catch (Exception $e) {
    // Asegurar que cualquier salida previa sea limpiada
    ob_clean();
    
    http_response_code(500);
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// Asegurar que no hay salida previa
ob_end_clean();

// Enviar la respuesta JSON
echo json_encode($response);
exit;