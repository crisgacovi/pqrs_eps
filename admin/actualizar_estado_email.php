<?php
// Deshabilitar la salida del búfer
@ob_end_clean();
// Iniciar nuevo búfer
ob_start();

// Deshabilitar todos los errores de salida
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

try {
    require_once "../config/config.php";

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $pqrs_id = filter_input(INPUT_POST, 'pqrs_id', FILTER_VALIDATE_INT);
    $email_enviado = filter_input(INPUT_POST, 'email_enviado', FILTER_VALIDATE_INT);

    if (!$pqrs_id) {
        throw new Exception('ID de pqrs inválido');
    }

    $sql = "UPDATE pqrss SET email_enviado = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $email_enviado, $pqrs_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar el estado del email");
    }

    $response = ['success' => true];

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// Limpiar cualquier salida anterior
ob_end_clean();

// Enviar la respuesta JSON
echo json_encode($response);
exit;