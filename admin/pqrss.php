<?php
/**
 * Gestión de PQRSs - Sistema de PQRSs
 * Última modificación: 2025-05-16 01:50:00 UTC
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

// Configuración de la paginación
$pqrss_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $pqrss_por_pagina;

// Obtener el total de pqrss para la paginación
try {
    $sql_total = "SELECT COUNT(*) as total FROM pqrss q
                  JOIN ciudades c ON q.ciudad_id = c.id
                  JOIN eps e ON q.eps_id = e.id
                  JOIN tipos_pqrs t ON q.tipo_pqrs_id = t.id
                  WHERE 1=1";
    
    // Filtrar por ciudad si es consultor_ciudad
    if ($_SESSION['admin_role'] === 'consultor_ciudad') {
        $sql_total .= " AND q.ciudad_id = " . (int)$_SESSION['ciudad_id'];
    }
    
    if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
        $buscar = '%' . $conn->real_escape_string($_GET['buscar']) . '%';
        $sql_total .= " AND (q.id LIKE ? OR 
                            q.nombre_paciente LIKE ? OR 
                            q.documento_identidad LIKE ? OR
                            q.descripcion LIKE ? OR
                            c.nombre LIKE ? OR
                            e.nombre LIKE ? OR
                            t.nombre LIKE ?)";
    }
    
    $stmt_total = $conn->prepare($sql_total);
    
    if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
        $stmt_total->bind_param("sssssss", $buscar, $buscar, $buscar, $buscar, $buscar, $buscar, $buscar);
    }
    
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    $row_total = $result_total->fetch_assoc();
    $total_pqrss = $row_total['total'];
    $total_paginas = ceil($total_pqrss / $pqrss_por_pagina);
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Procesar la generación del reporte Excel si se solicita
if (isset($_POST['generar_reporte'])) {
    try {
        $fecha_inicio = $_POST['fecha_inicio'];
        $fecha_fin = $_POST['fecha_fin'];
        
        // Validar fechas
        if (empty($fecha_inicio) || empty($fecha_fin)) {
            throw new Exception("Por favor seleccione ambas fechas para generar el reporte.");
        }

        // Consulta para el reporte
        $sql = "SELECT q.id, q.fecha_creacion, q.nombre_paciente, q.documento_identidad, 
                       q.email, q.telefono, c.nombre AS ciudad_nombre, 
                       e.nombre AS eps_nombre, t.nombre AS tipo_pqrs_nombre, 
                       q.descripcion, q.respuesta, q.fecha_respuesta, q.estado, q.email_enviado
                FROM pqrss q
                JOIN ciudades c ON q.ciudad_id = c.id
                JOIN eps e ON q.eps_id = e.id
                JOIN tipos_pqrs t ON q.tipo_pqrs_id = t.id
                WHERE DATE(q.fecha_creacion) BETWEEN ? AND ?";

        // Filtrar por ciudad si es consultor_ciudad
        if ($_SESSION['admin_role'] === 'consultor_ciudad') {
            $sql .= " AND q.ciudad_id = " . (int)$_SESSION['ciudad_id'];
        }

        $sql .= " ORDER BY q.fecha_creacion DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $filename = "reporte_pqrss_" . date('Y-m-d') . ".xls";
            
            header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            
            echo "\xEF\xBB\xBF"; // BOM para UTF-8
            echo "<table border='1'>";
            echo "<tr>";
            echo "<th>ID</th>";
            echo "<th>Fecha Creación</th>";
            echo "<th>Paciente</th>";
            echo "<th>Documento</th>";
            echo "<th>Email</th>";
            echo "<th>Teléfono</th>";
            echo "<th>Ciudad</th>";
            echo "<th>EPS</th>";
            echo "<th>Tipo de PQRS</th>";
            echo "<th>Descripción</th>";
            echo "<th>Respuesta</th>";
            echo "<th>Fecha Respuesta</th>";
            echo "<th>Estado</th>";
            echo "</tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($row['fecha_creacion'])) . "</td>";
                echo "<td>" . $row['nombre_paciente'] . "</td>";
                echo "<td>" . $row['documento_identidad'] . "</td>";
                echo "<td>" . $row['email'] . "</td>";
                echo "<td>" . $row['telefono'] . "</td>";
                echo "<td>" . $row['ciudad_nombre'] . "</td>";
                echo "<td>" . $row['eps_nombre'] . "</td>";
                echo "<td>" . $row['tipo_pqrs_nombre'] . "</td>";
                echo "<td>" . $row['descripcion'] . "</td>";
                echo "<td>" . $row['respuesta'] . "</td>";
                echo "<td>" . ($row['fecha_respuesta'] ? date('d/m/Y', strtotime($row['fecha_respuesta'])) : '') . "</td>";
                echo "<td>" . $row['estado'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de PQRSs - Sistema de PQRSs</title>
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
                    <h1 class="h2">Gestión de PQRSs</h1>
                    <?php if ($_SESSION['admin_role'] === 'consultor_ciudad'): ?>
                        <span class="badge bg-info fs-6">
                            <i class="bi bi-geo-alt"></i> 
                            <?php echo htmlspecialchars($_SESSION['ciudad_nombre']); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Sección de Generación de Reportes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Generar Reporte de PQRSs
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3" onsubmit="return validarFechas()">
                            <div class="col-md-4">
                                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                            </div>
                            <div class="col-md-4">
                                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" name="generar_reporte" class="btn btn-primary">
                                    <i class="bi bi-download"></i> Generar Reporte Excel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sección de búsqueda -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="buscar" name="buscar" 
                                           placeholder="Buscar por ID, paciente, documento..." 
                                           value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de pqrss -->
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                            <?php if ($_SESSION['admin_role'] === 'consultor_ciudad'): ?>
                                <th>ID</th>
                                <th>Fecha pqrs</th>
                                <th>Paciente</th>
                                <th>Documento</th>
                                <th>EPS</th>
                                <th>Tipo pqrs</th>
                                <th>Estado</th>
                                <th>Fecha estado</th>
                                <th>Respuesta</th>
                            <?php else: ?>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Paciente</th>
                                <th>Ciudad</th>
                                <th>EPS</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                if ($_SESSION['admin_role'] === 'consultor_ciudad') {
                                    $sql = "SELECT q.id, q.fecha_creacion, q.nombre_paciente, q.documento_identidad, 
                                            e.nombre AS eps_nombre, t.nombre AS tipo_pqrs_nombre, 
                                            q.estado, q.fecha_respuesta, q.respuesta
                                            FROM pqrss q
                                            JOIN eps e ON q.eps_id = e.id
                                            JOIN tipos_pqrs t ON q.tipo_pqrs_id = t.id
                                            WHERE q.ciudad_id = ?
                                            ";
                                    // Buscar
                                    $params = [];
                                    $types = "i";
                                    $params[] = (int)$_SESSION['ciudad_id'];

                                    if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
                                        $buscar = '%' . $conn->real_escape_string($_GET['buscar']) . '%';
                                        $sql .= " AND (q.id LIKE ? OR 
                                                      q.nombre_paciente LIKE ? OR 
                                                      q.documento_identidad LIKE ? OR
                                                      q.respuesta LIKE ? OR
                                                      e.nombre LIKE ? OR
                                                      t.nombre LIKE ?)";
                                        $types .= "ssssss";
                                        array_push($params, $buscar, $buscar, $buscar, $buscar, $buscar, $buscar);
                                    }

                                    $sql .= " ORDER BY q.fecha_creacion DESC LIMIT ? OFFSET ?";
                                    $types .= "ii";
                                    array_push($params, $pqrss_por_pagina, $offset);

                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param($types, ...$params);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    while ($pqrs = $result->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td><?php echo $pqrs['id']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($pqrs['fecha_creacion'])); ?></td>
                                        <td><?php echo htmlspecialchars($pqrs['nombre_paciente']); ?></td>
                                        <td><?php echo htmlspecialchars($pqrs['documento_identidad']); ?></td>
                                        <td><?php echo htmlspecialchars($pqrs['eps_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($pqrs['tipo_pqrs_nombre']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getBadgeClass($pqrs['estado']); ?>">
                                                <?php echo $pqrs['estado']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $pqrs['fecha_respuesta'] ? date('d/m/Y', strtotime($pqrs['fecha_respuesta'])) : ''; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($pqrs['respuesta']); ?></td>
                                    </tr>
                                <?php
                                    endwhile;
                                } else {
                                    $sql = "SELECT q.*, c.nombre AS ciudad_nombre, e.nombre AS eps_nombre, 
                                                  t.nombre AS tipo_pqrs_nombre,
                                                  CASE 
                                                      WHEN q.estado = 'En Proceso' 
                                                      AND q.respuesta IS NOT NULL 
                                                      AND q.fecha_respuesta IS NOT NULL 
                                                      AND q.email_enviado = 1
                                                      THEN 1 
                                                      ELSE 0 
                                                  END as email_enviado
                                           FROM pqrss q
                                           JOIN ciudades c ON q.ciudad_id = c.id
                                           JOIN eps e ON q.eps_id = e.id
                                           JOIN tipos_pqrs t ON q.tipo_pqrs_id = t.id
                                           WHERE 1=1";
                                    if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
                                        $buscar = '%' . $conn->real_escape_string($_GET['buscar']) . '%';
                                        $sql .= " AND (q.id LIKE ? OR 
                                                      q.nombre_paciente LIKE ? OR 
                                                      q.documento_identidad LIKE ? OR
                                                      q.descripcion LIKE ? OR
                                                      c.nombre LIKE ? OR
                                                      e.nombre LIKE ? OR
                                                      t.nombre LIKE ?)";
                                    }
                                    $sql .= " ORDER BY q.fecha_creacion DESC LIMIT ? OFFSET ?";

                                    if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
                                        $stmt = $conn->prepare($sql);
                                        $stmt->bind_param("sssssssii", $buscar, $buscar, $buscar, $buscar, $buscar, $buscar, $buscar, $pqrss_por_pagina, $offset);
                                    } else {
                                        $stmt = $conn->prepare($sql);
                                        $stmt->bind_param("ii", $pqrss_por_pagina, $offset);
                                    }
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    while ($pqrs = $result->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td><?php echo $pqrs['id']; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($pqrs['fecha_creacion'])); ?></td>
                                        <td><?php echo htmlspecialchars($pqrs['nombre_paciente']); ?></td>
                                        <td><?php echo htmlspecialchars($pqrs['ciudad_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($pqrs['eps_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($pqrs['tipo_pqrs_nombre']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getBadgeClass($pqrs['estado']); ?>">
                                                <?php echo $pqrs['estado']; ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <a href="ver_pqrs.php?id=<?php echo $pqrs['id']; ?>" 
                                                   class="btn btn-primary btn-sm" title="Ver Detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="editar_pqrs.php?id=<?php echo $pqrs['id']; ?>" 
                                                   class="btn btn-warning btn-sm" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($pqrs['estado'] === 'En Proceso' && !empty($pqrs['respuesta'])): ?>
                                                    <button type="button" 
                                                            class="btn <?php echo $pqrs['email_enviado'] ? 'btn-success' : 'btn-info'; ?> btn-sm enviar-email" 
                                                            data-pqrs-id="<?php echo $pqrs['id']; ?>"
                                                            title="<?php echo $pqrs['email_enviado'] ? 'Email enviado' : 'Enviar Email de Respuesta'; ?>"
                                                            <?php echo $pqrs['email_enviado'] ? 'disabled' : ''; ?>>
                                                        <i class="bi <?php echo $pqrs['email_enviado'] ? 'bi-envelope-check-fill' : 'bi-envelope'; ?>"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($_SESSION['admin_role'] === 'admin'): ?>
                                                    <a href="eliminar_pqrs.php?id=<?php echo $pqrs['id']; ?>" 
                                                       class="btn btn-danger btn-sm"
                                                       onclick="return confirm('¿Está seguro de eliminar esta pqrs?')"
                                                       title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                    endwhile;
                                }
                            } catch (Exception $e) {
                                echo '<tr><td colspan="12" class="text-center text-danger">Error al cargar las pqrss</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                <nav aria-label="Navegación de páginas" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo isset($_GET['buscar']) ? '&buscar='.urlencode($_GET['buscar']) : ''; ?>">Anterior</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?php echo $i === $pagina_actual ? 'active' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo isset($_GET['buscar']) ? '&buscar='.urlencode($_GET['buscar']) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo isset($_GET['buscar']) ? '&buscar='.urlencode($_GET['buscar']) : ''; ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal para envío de email -->
<?php if ($_SESSION['admin_role'] !== 'consultor_ciudad'): ?>
<div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailModalLabel">Enviar Email de Respuesta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="emailForm">
                    <input type="hidden" id="pqrs_id" name="pqrs_id">
                    <div class="mb-3">
                        <label for="mensaje" class="form-label">Mensaje Adicional (opcional):</label>
                        <textarea class="form-control" id="mensaje" name="mensaje" rows="4" 
                                placeholder="Escriba un mensaje adicional si lo desea..."></textarea>
                    </div>
                </form>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    Se enviará un email al paciente con la respuesta a su pqrs y otro email a la EPS correspondiente 
                    con los detalles de la pqrs y los plazos legales de respuesta.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="enviarEmailBtn">Enviar Emails</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Incluir SweetAlert2 -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
<?php if ($_SESSION['admin_role'] !== 'consultor_ciudad'): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar clic en botón de email
    document.querySelectorAll('.enviar-email').forEach(button => {
        button.addEventListener('click', function() {
            const pqrsId = this.getAttribute('data-pqrs-id');
            document.getElementById('pqrs_id').value = pqrsId;
            new bootstrap.Modal(document.getElementById('emailModal')).show();
        });
    });

    // Manejar envío de email
    document.getElementById('enviarEmailBtn').addEventListener('click', async function() {
        try {
            // Deshabilitar botón mientras se envía
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';

            const form = document.getElementById('emailForm');
            const formData = new FormData(form);
            const pqrsId = document.getElementById('pqrs_id').value;

            // Primera petición: enviar email
            const emailResponse = await fetch('procesar_email_pqrs.php', {
                method: 'POST',
                body: formData
            });

            // Verificar si la respuesta es JSON válido
            let data;
            try {
                data = await emailResponse.json();
            } catch (e) {
                throw new Error('Error en la respuesta del servidor: No es un JSON válido');
            }

            if (!data.success) {
                throw new Error(data.message || 'Error al enviar el email');
            }

            // Si el email se envió correctamente, actualizar la UI
            const emailButton = document.querySelector(`.enviar-email[data-pqrs-id="${pqrsId}"]`);
            if (emailButton) {
                emailButton.innerHTML = '<i class="bi bi-envelope-check-fill"></i>';
                emailButton.classList.remove('btn-info');
                emailButton.classList.add('btn-success');
                emailButton.disabled = true;
                emailButton.title = 'Emails enviados';

                // Segunda petición: actualizar estado
                const statusResponse = await fetch('actualizar_estado_email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `pqrs_id=${pqrsId}&email_enviado=1`
                });

                const statusData = await statusResponse.json();
                if (!statusData.success) {
                    console.error('Error al actualizar el estado:', statusData.message);
                }
            }

            // Mostrar mensaje de éxito
            await Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Emails enviados exitosamente al paciente y a la EPS',
                confirmButtonColor: '#0d6efd'
            });

            // Cerrar el modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('emailModal'));
            if (modal) {
                modal.hide();
            }

        } catch (error) {
            console.error('Error:', error);
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Error al enviar los emails. Por favor, inténtelo de nuevo.',
                confirmButtonColor: '#dc3545'
            });
        } finally {
            // Restaurar botón del modal
            this.disabled = false;
            this.innerHTML = 'Enviar Emails';
        }
    });
});
<?php endif; ?>

// Validar fechas para el reporte
window.validarFechas = function() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    
    if (!fechaInicio || !fechaFin) {
        alert('Por favor seleccione ambas fechas');
        return false;
    }
    
    if (fechaInicio > fechaFin) {
        alert('La fecha de inicio no puede ser posterior a la fecha final');
        return false;
    }
    
    return true;
};
</script>
</body>
</html>