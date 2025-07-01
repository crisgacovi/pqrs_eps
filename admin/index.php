<?php
// admin/index.php - Panel de administración
session_start();

// Definir constante para acceso seguro al sidebar
define('IN_ADMIN', true);

// Verificar si el usuario está autenticado
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Incluir archivo de configuración
require_once "../config/config.php";

// Función para verificar si el usuario es administrador
function isAdmin()
{
    if (!isset($_SESSION['admin_role'])) {
        return false;
    }
    return $_SESSION['admin_role'] === 'admin';
}

// Obtener estadísticas con manejo de errores
try {
    $stats = [
        'total' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'resolved' => 0,
        'closed' => 0
    ];

    $result = $conn->query("SELECT COUNT(*) as count FROM pqrss");
    if ($result) {
        $stats['total'] = $result->fetch_assoc()['count'];
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM pqrss WHERE estado = 'Pendiente'");
    if ($result) {
        $stats['pending'] = $result->fetch_assoc()['count'];
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM pqrss WHERE estado = 'En Proceso'");
    if ($result) {
        $stats['in_progress'] = $result->fetch_assoc()['count'];
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM pqrss WHERE estado = 'Resuelto'");
    if ($result) {
        $stats['resolved'] = $result->fetch_assoc()['count'];
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM pqrss WHERE estado = 'Cerrado'");
    if ($result) {
        $stats['closed'] = $result->fetch_assoc()['count'];
    }
} catch (Exception $e) {
    error_log("Error al obtener estadísticas: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Sistema de PQRSs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin-styles.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-5">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Totales</h5>
                                <h2><?php echo $stats['total']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <h5 class="card-title">Pendientes</h5>
                                <h2><?php echo $stats['pending']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">En Proceso</h5>
                                <h2><?php echo $stats['in_progress']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Resueltas</h5>
                                <h2><?php echo $stats['resolved']; ?></h2>
                            </div>
                        </div>
                    </div>
                    
                </div>

                <!-- Últimas pqrss registradas -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-list-ul"></i> Últimas PQRSs registradas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Paciente</th>
                                        <th>EPS</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Consultar las últimas 5 pqrss, filtrando por ciudad si es consultor_ciudad
                                    $sql = "SELECT q.id, q.fecha_creacion, q.nombre_paciente, 
                                                e.nombre as eps_nombre, 
                                                t.nombre as tipo_pqrs_nombre, 
                                                q.estado
                                        FROM pqrss q
                                        LEFT JOIN eps e ON q.eps_id = e.id
                                        LEFT JOIN tipos_pqrs t ON q.tipo_pqrs_id = t.id";
                                    if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'consultor_ciudad') {
                                        $ciudad_id = (int)$_SESSION['ciudad_id'];
                                        $sql .= " WHERE q.ciudad_id = $ciudad_id";
                                    }
                                    $sql .= " ORDER BY q.fecha_creacion DESC
                                        LIMIT 5";

                                    $result = $conn->query($sql);

                                    if ($result && $result->num_rows > 0):
                                        while ($row = $result->fetch_assoc()):
                                            // Determinar la clase del badge según el estado
                                            switch($row['estado']) {
                                                case 'Pendiente':
                                                    $badgeClass = 'bg-warning text-dark';
                                                    break;
                                                case 'En Proceso':
                                                    $badgeClass = 'bg-info text-white';
                                                    break;
                                                case 'Resuelto':
                                                    $badgeClass = 'bg-success text-white';
                                                    break;
                                                case 'Cerrado':
                                                    $badgeClass = 'bg-secondary text-white';
                                                    break;
                                                default:
                                                    $badgeClass = 'bg-secondary text-white';
                                            }
                                    ?>
                                            <tr>
                                                <td><?php echo $row['id']; ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_creacion'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['nombre_paciente']); ?></td>
                                                <td><?php echo htmlspecialchars($row['eps_nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($row['tipo_pqrs_nombre']); ?></td>
                                                <td>
                                                    <span class="badge rounded-pill <?php echo $badgeClass; ?>">
                                                        <?php echo $row['estado']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php
                                        endwhile;
                                    else:
                                        ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                                    <p>No hay pqrss registradas.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <a href="pqrss.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-eye"></i> Ver todas las pqrss
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>