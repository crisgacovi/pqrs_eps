<?php
// procesar_pqrs.php - Procesa el envío del formulario

// Incluir archivo de configuración
require_once "config/config.php";

// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger y validar los datos del formulario
    $nombre = trim($_POST['nombre']);
    $documento = trim($_POST['documento']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $ciudad_id = (int)$_POST['ciudad'];
    $eps_id = (int)$_POST['eps'];
    $tipo_pqrs_id = (int)$_POST['tipo_pqrs'];
    $descripcion = trim($_POST['descripcion']);
    
    // Validación básica
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    }
    
    if (empty($documento)) {
        $errores[] = "El documento de identidad es obligatorio";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico es inválido";
    }
    
    if ($ciudad_id <= 0) {
        $errores[] = "Debe seleccionar una ciudad";
    }
    
    if ($eps_id <= 0) {
        $errores[] = "Debe seleccionar una EPS";
    }
    
    if ($tipo_pqrs_id <= 0) {
        $errores[] = "Debe seleccionar un motivo de pqrs";
    }
    
    if (empty($descripcion)) {
        $errores[] = "La descripción es obligatoria";
    }

    // Procesar archivo adjunto si existe
    $archivo_path = null;
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] == 0) {
        $archivo = $_FILES['archivo'];
        $archivo_nombre = $archivo['name'];
        $archivo_tipo = $archivo['type'];
        $archivo_temp = $archivo['tmp_name'];
        $archivo_tamano = $archivo['size'];
        
        // Validar tipo de archivo
        $tipos_permitidos = ['application/pdf', 'image/jpeg', 'image/jpg'];
        if (!in_array($archivo_tipo, $tipos_permitidos)) {
            $errores[] = "Tipo de archivo no permitido. Solo se permiten archivos PDF y JPG.";
        }
        
        // Validar tamaño (5MB)
        if ($archivo_tamano > 5242880) {
            $errores[] = "El archivo es demasiado grande. El tamaño máximo permitido es 5MB.";
        }
        
        if (empty($errores)) {
            // Generar nombre único para el archivo
            $extension = pathinfo($archivo_nombre, PATHINFO_EXTENSION);
            $archivo_nuevo_nombre = 'adjunto_pqrs_' . $documento . '_' . uniqid() . '.' . $extension;
            $directorio_destino = 'uploads/adjuntos/';
            
            // Crear directorio si no existe
            if (!file_exists($directorio_destino)) {
                mkdir($directorio_destino, 0777, true);
            }
            
            $archivo_path = $directorio_destino . $archivo_nuevo_nombre;
            
            // Intentar mover el archivo
            if (!move_uploaded_file($archivo_temp, $archivo_path)) {
                $errores[] = "Error al guardar el archivo adjunto.";
                $archivo_path = null;
            }
        }
    }
    
    // Si no hay errores, guardar en la base de datos
    if (empty($errores)) {
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Preparar la consulta SQL
            $sql = "INSERT INTO pqrss (nombre_paciente, documento_identidad, email, telefono, 
                    ciudad_id, eps_id, tipo_pqrs_id, descripcion, archivo_adjunto) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssssiiiss", $nombre, $documento, $email, $telefono, 
                                $ciudad_id, $eps_id, $tipo_pqrs_id, $descripcion, $archivo_path);
                
                if ($stmt->execute()) {
                    $conn->commit();
                    // Redirigir a página de éxito
                    header("location: confirmacion.php?id=" . $conn->insert_id);
                    exit();
                } else {
                    throw new Exception("Error al registrar la pqrs: " . $stmt->error);
                }
                
                $stmt->close();
            } else {
                throw new Exception("Error en la preparación de la consulta: " . $conn->error);
            }
        } catch (Exception $e) {
            // Revertir cambios y eliminar archivo si existe
            $conn->rollback();
            if ($archivo_path && file_exists($archivo_path)) {
                unlink($archivo_path);
            }
            $errores[] = $e->getMessage();
        }
    }
    
    // Si hay errores, volver al formulario y mostrarlos
    if (!empty($errores)) {
        session_start();
        $_SESSION['errores'] = $errores;
        $_SESSION['form_data'] = $_POST;
        header("location: index.php");
        exit();
    }
}

// Si se accede directamente a este script, redirigir al formulario
header("location: index.php");
exit();
?>