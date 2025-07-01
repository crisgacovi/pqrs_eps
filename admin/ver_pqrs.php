<?php
/**
 * Ver pqrs - Sistema de PQRSs
 * Última modificación: 2025-04-26 03:22:25 UTC
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver pqrs - Sistema de PQRSs</title>
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
                    <h1 class="h2">Detalles de la PQRS #<?php echo $id; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="pqrss.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                            <a href="editar_pqrs.php?id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
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
                                            <span class="badge bg-<?php echo getBadgeClass($pqrs['estado']); ?>">
                                                <?php echo $pqrs['estado']; ?>
                                            </span>
                                        </dd>
                                        
                                        <dt class="col-sm-4">Fecha:</dt>
                                        <dd class="col-sm-8"><?php echo date('d/m/Y H:i', strtotime($pqrs['fecha_creacion'])); ?></dd>
                                    </dl>
                                </div>
                            </div>
                            
                            <h5 class="card-title mt-4">Descripción de la PQRS</h5>
                            <div class="card">
                                <div class="card-body bg-light">
                                    <?php echo nl2br(htmlspecialchars($pqrs['descripcion'])); ?>
                                </div>
                            </div>
                            
                            <!-- Archivo adjunto -->
                            <?php if (!empty($pqrs['archivo_adjunto'])): ?>
                                <h5 class="card-title mt-4">Archivo Adjunto</h5>
                                <div class="card">
                                    <div class="alert alert-info">
                                        <?php
                                        $archivo_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $pqrs['archivo_adjunto'];
                                        if (file_exists($archivo_path)): ?>
                                            <a href="../<?php echo htmlspecialchars($pqrs['archivo_adjunto']); ?>" 
                                            class="btn btn-outline-primary" target="_blank">
                                                <i class="bi bi-file-earmark"></i> Ver archivo adjunto
                                            </a>
                                        <?php else: ?>
                                            <div class="alert alert-warning">
                                                <i class="bi bi-exclamation-triangle"></i> El archivo no se encuentra disponible.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>