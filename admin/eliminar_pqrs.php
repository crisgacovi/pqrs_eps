<?php
/**
 * Eliminar PQRS - Sistema de PQRSs
 * Última modificación: 2025-04-26 03:06:21 UTC
 * @author crisgacovi
 */

session_start();

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['admin_role'] !== 'admin') {
    header("location: pqrss.php");
    exit;
}

require_once "../config/config.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("location: pqrss.php");
    exit;
}

try {
    // Iniciar transacción
    $conn->begin_transaction();
    
    // Obtener información del archivo adjunto antes de eliminar
    $stmt = $conn->prepare("SELECT archivo_adjunto FROM pqrss WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pqrs = $result->fetch_assoc();
    
    // Eliminar la pqrs
    $stmt = $conn->prepare("DELETE FROM pqrss WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Si hay un archivo adjunto, eliminarlo
        if ($pqrs && $pqrs['archivo_adjunto']) {
            $archivo_path = "../uploads/" . $pqrs['archivo_adjunto'];
            if (file_exists($archivo_path)) {
                unlink($archivo_path);
            }
        }
        
        $conn->commit();
        $_SESSION['success'] = true;
        $_SESSION['mensaje'] = "PQRS eliminada exitosamente.";
    } else {
        throw new Exception("Error al eliminar la pqrs: " . $stmt->error);
    }
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
}

header("location: pqrss.php");
exit;