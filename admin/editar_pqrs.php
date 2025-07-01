<?php
/**
 * Editar PQRS - Sistema de PQRSs
 * Última modificación: 2025-04-29 03:18:11 UTC
 * @author crisgacovi
 */

session_start();

// Definir constante para acceso seguro al sidebar
define('IN_ADMIN', true);

// Verificar si el usuario está autenticado
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("location: login.php");
    exit;
}

require_once "../config/config.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("location: pqrss.php");
    exit;
}

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $estado = $_POST['estado'];
        $respuesta = trim($_POST['respuesta']);
        $fecha_respuesta = !empty($_POST['fecha_respuesta']) ? $_POST['fecha_respuesta'] : null;
        $archivo_respuesta = null;
        
        // Procesar el archivo de respuesta si se ha subido uno
        if (isset($_FILES['archivo_respuesta']) && $_FILES['archivo_respuesta']['error'] === UPLOAD_ERR_OK) {
            $archivo_info = $_FILES['archivo_respuesta'];
            $archivo_nombre = $archivo_info['name'];
            $archivo_tmp = $archivo_info['tmp_name'];
            $archivo_tipo = $archivo_info['type'];
            $archivo_tamano = $archivo_info['size'];
            
            // Validar el tipo de archivo
            $tipos_permitidos = ['application/pdf', 'image/jpeg', 'image/png'];
            
            if (!in_array($archivo_tipo, $tipos_permitidos)) {
                throw new Exception("Tipo de archivo no permitido. Solo se permiten archivos PDF, JPG y PNG.");
            }
            
            // Validar el tamaño del archivo (máximo 5MB)
            if ($archivo_tamano > 5242880) {
                throw new Exception("El archivo es demasiado grande. El tamaño máximo permitido es 5MB.");
            }
            
            // Generar nombre único para el archivo
            $extension = pathinfo($archivo_nombre, PATHINFO_EXTENSION);
            $nombre_archivo = 'respuesta_pqrs_' . $id . '_' . uniqid() . '.' . $extension;
            $ruta_destino = "../uploads/respuestas/" . $nombre_archivo;
            
            // Crear directorio si no existe
            if (!is_dir("../uploads/respuestas")) {
                mkdir("../uploads/respuestas", 0777, true);
            }
            
            // Mover el archivo
            if (move_uploaded_file($archivo_tmp, $ruta_destino)) {
                $archivo_respuesta = "uploads/respuestas/" . $nombre_archivo;
            } else {
                throw new Exception("Error al guardar el archivo.");
            }
        }
        
        // Actualizar la pqrs incluyendo el archivo de respuesta
        if ($archivo_respuesta) {
            $stmt = $conn->prepare("UPDATE pqrss SET estado = ?, respuesta = ?, fecha_respuesta = ?, archivo_respuesta = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $estado, $respuesta, $fecha_respuesta, $archivo_respuesta, $id);
        } else {
            $stmt = $conn->prepare("UPDATE pqrss SET estado = ?, respuesta = ?, fecha_respuesta = ? WHERE id = ?");
            $stmt->bind_param("sssi", $estado, $respuesta, $fecha_respuesta, $id);
        }
        
        if ($stmt->execute()) {
            $success = true;
            $mensaje = "PQRS actualizada exitosamente.";
        } else {
            throw new Exception("Error al actualizar la pqrs: " . $stmt->error);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener datos de la pqrs
try {
    $sql = "SELECT q.*, c.nombre AS ciudad_nombre, e.nombre AS eps_nombre, 
            t.nombre AS tipo_pqrs_nombre
            FROM pqrss q
            JOIN ciudades c ON q.ciudad_id = c.id
            JOIN eps e ON q.eps_id = e.id
            JOIN tipos_pqrs t ON q.tipo_pqrs_id = t.id
            WHERE q.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("location: pqrss.php");
        exit;
    }
    
    $pqrs = $result->fetch_assoc();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Función para obtener la clase de badge según el estado
function getBadgeClass($estado) {
    $badges = array(
        'Pendiente' => 'warning',
        'En Proceso' => 'info',
        'Resuelto' => 'success',
        'Cerrado' => 'secondary'
    );
    return isset($badges[$estado]) ? $badges[$estado] : 'primary';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar PQRS - Sistema de PQRSs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin-styles.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Editar PQRS #<?php echo $id; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="pqrss.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (isset($success) && $success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill"></i> <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form action="editar_pqrs.php?id=<?php echo $id; ?>" 
                              method="POST" 
                              id="pqrsForm"
                              enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="card-title">Información del Paciente</h5>
                                    <dl class="row">
                                        <dt class="col-sm-4">Nombre:</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($pqrs['nombre_paciente']); ?></dd>
                                        
                                        <dt class="col-sm-4">Documento:</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($pqrs['documento_identidad']); ?></dd>
                                        
                                        <dt class="col-sm-4">Email:</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($pqrs['email']); ?></dd>
                                        
                                        <dt class="col-sm-4">Teléfono:</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($pqrs['telefono']); ?></dd>
                                        
                                        <dt class="col-sm-4">Ciudad:</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($pqrs['ciudad_nombre']); ?></dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="card-title">Detalles de la PQRS</h5>
                                    <dl class="row">
                                        <dt class="col-sm-4">EPS:</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($pqrs['eps_nombre']); ?></dd>
                                        
                                        <dt class="col-sm-4">Tipo de PQRS:</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($pqrs['tipo_pqrs_nombre']); ?></dd>
                                        
                                        <dt class="col-sm-4">Estado:</dt>
                                        <dd class="col-sm-8">
                                            <select name="estado" class="form-select" required>
                                                <option value="Pendiente" <?php echo $pqrs['estado'] === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                                <option value="En Proceso" <?php echo $pqrs['estado'] === 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                                                <option value="Resuelto" <?php echo $pqrs['estado'] === 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                                            </select>
                                        </dd>
                                        
                                        <dt class="col-sm-4">Fecha Creación:</dt>
                                        <dd class="col-sm-8"><?php echo date('d/m/Y H:i', strtotime($pqrs['fecha_creacion'])); ?></dd>
                                    </dl>
                                </div>
                            </div>
                            
                            <h5 class="card-title mt-4">Descripción de la PQRS</h5>
                            <div class="card mb-4">
                                <div class="card-body bg-light">
                                    <?php echo nl2br(htmlspecialchars($pqrs['descripcion'])); ?>
                                </div>
                            </div>

                            <!-- Sección de Respuesta con Fecha -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Respuesta de la PQRS</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label for="fecha_respuesta" class="form-label">Fecha de Respuesta</label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="fecha_respuesta" 
                                                   name="fecha_respuesta"
                                                   value="<?php echo $pqrs['fecha_respuesta'] ?? ''; ?>"
                                                   max="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div class="col-md-9 mb-3">
                                            <label for="respuesta" class="form-label">Respuesta</label>
                                            <textarea class="form-control" 
                                                      id="respuesta"
                                                      name="respuesta" 
                                                      rows="2" 
                                                      placeholder="Escriba aquí la respuesta a la pqrs..."><?php 
                                                echo htmlspecialchars($pqrs['respuesta'] ?? ''); 
                                            ?></textarea>
                                        </div>
                                    </div>

                                    <!-- Campo de archivo -->
                                    <div class="row">
                                        <div class="col-12">
                                            <label for="archivo_respuesta" class="form-label">
                                                Adjuntar archivo de respuesta 
                                                <small class="text-muted">(PDF, JPG, PNG - Máx. 5MB)</small>
                                            </label>
                                            <input type="file" 
                                                   class="form-control" 
                                                   id="archivo_respuesta" 
                                                   name="archivo_respuesta"
                                                   accept=".pdf,.jpg,.jpeg,.png">
                                        </div>
                                    </div>

                                    <!-- Mostrar archivo de respuesta actual si existe -->
                                    <?php if (!empty($pqrs['archivo_respuesta'])): ?>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="alert alert-info">
                                                <a href="../<?php echo htmlspecialchars($pqrs['archivo_respuesta']); ?>" 
                                                class="btn btn-outline-primary" target="_blank">
                                                    <i class="bi bi-file-earmark"></i> Ver archivo respuesta
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const fechaRespuesta = document.getElementById('fecha_respuesta');
        const respuestaTextarea = document.getElementById('respuesta');
        const archivoRespuesta = document.getElementById('archivo_respuesta');
        const today = new Date().toISOString().split('T')[0];
        
        // Establecer fecha máxima como hoy
        fechaRespuesta.max = today;
        
        // Validar archivo
        archivoRespuesta.addEventListener('change', function() {
            if (this.files[0]) {
                // Validar tamaño (5MB)
                if (this.files[0].size > 5242880) {
                    alert('El archivo es demasiado grande. El tamaño máximo permitido es 5MB.');
                    this.value = '';
                    return false;
                }
                
                // Validar tipo
                const tiposPermitidos = ['application/pdf', 'application/msword', 
                                       'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                       'image/jpeg', 'image/png'];
                if (!tiposPermitidos.includes(this.files[0].type)) {
                    alert('Tipo de archivo no permitido. Solo se permiten archivos PDF, DOC, DOCX, JPG y PNG.');
                    this.value = '';
                    return false;
                }
            }
        });
        
        // Validar formulario
        document.getElementById('pqrsForm').addEventListener('submit', function(e) {
            const respuestaTexto = respuestaTextarea.value.trim();
            const fecha = fechaRespuesta.value;
            
            if (respuestaTexto && !fecha) {
                e.preventDefault();
                alert('Si ingresa una respuesta, debe especificar la fecha de respuesta.');
                return false;
            }
            
            if (fecha && !respuestaTexto) {
                e.preventDefault();
                alert('Si especifica una fecha de respuesta, debe ingresar la respuesta.');
                return false;
            }
            
            return true;
        });
    });
    </script>
</body>
</html>