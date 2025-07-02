<?php
/**
 * Gestión de EPS - Sistema de PQRSs
 * Última modificación: 2025-07-02 05:54:10 UTC
 * @author crisgacovi
 */

session_start();

// Definir constante para acceso seguro al sidebar
define('IN_ADMIN', true);

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['admin_role'] !== 'admin') {
    header("location: index.php");
    exit;
}

require_once "../config/config.php";

// Procesar formulario de creación/edición
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['action'])) {
            $nombre = trim($_POST['nombre']);
            $emails = isset($_POST['emails']) ? array_filter($_POST['emails']) : [];
            $estado = isset($_POST['estado']) ? 1 : 0;

            if (empty($nombre)) {
                throw new Exception("El nombre de la EPS es requerido.");
            }

            if (empty($emails)) {
                throw new Exception("Debe ingresar al menos un correo electrónico.");
            }

            // Validar emails
            foreach ($emails as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("El formato del email '$email' no es válido.");
                }
            }

            $conn->begin_transaction();

            if ($_POST['action'] == 'create') {
                // Verificar si ya existe
                $stmt = $conn->prepare("SELECT id FROM eps WHERE nombre = ?");
                $stmt->bind_param("s", $nombre);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("Ya existe una EPS con este nombre.");
                }

                // Insertar EPS
                $stmt = $conn->prepare("INSERT INTO eps (nombre, estado) VALUES (?, ?)");
                $stmt->bind_param("si", $nombre, $estado);
                $stmt->execute();
                $eps_id = $conn->insert_id;

                // Insertar emails
                $stmt = $conn->prepare("INSERT INTO eps_emails (eps_id, email, estado) VALUES (?, ?, 1)");
                foreach ($emails as $email) {
                    $stmt->bind_param("is", $eps_id, $email);
                    $stmt->execute();
                }
                
                $mensaje = "EPS creada exitosamente.";
            } else if ($_POST['action'] == 'edit' && isset($_POST['id'])) {
                $id = (int)$_POST['id'];

                // Verificar duplicados excepto para la EPS actual
                $stmt = $conn->prepare("SELECT id FROM eps WHERE nombre = ? AND id != ?");
                $stmt->bind_param("si", $nombre, $id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("Ya existe otra EPS con este nombre.");
                }

                // Actualizar EPS
                $stmt = $conn->prepare("UPDATE eps SET nombre = ?, estado = ? WHERE id = ?");
                $stmt->bind_param("sii", $nombre, $estado, $id);
                $stmt->execute();

                // Desactivar emails anteriores
                $stmt = $conn->prepare("UPDATE eps_emails SET estado = 0 WHERE eps_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();

                // Insertar nuevos emails
                $stmt = $conn->prepare("INSERT INTO eps_emails (eps_id, email, estado) VALUES (?, ?, 1)");
                foreach ($emails as $email) {
                    $stmt->bind_param("is", $id, $email);
                    $stmt->execute();
                }

                $mensaje = "EPS actualizada exitosamente.";
            }

            $conn->commit();
            $_SESSION['mensaje'] = $mensaje;
            $_SESSION['tipo_mensaje'] = 'success';
        } else if (isset($_POST['delete']) && isset($_POST['id'])) {
            $id = (int)$_POST['id'];

            // Verificar si hay PQRSs asociadas
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pqrss WHERE eps_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['total'] > 0) {
                throw new Exception("No se puede eliminar la EPS porque tiene PQRSs asociadas.");
            }

            $conn->begin_transaction();

            // Eliminar emails de la EPS
            $stmt = $conn->prepare("DELETE FROM eps_emails WHERE eps_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Eliminar la EPS
            $stmt = $conn->prepare("DELETE FROM eps WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $conn->commit();

            $_SESSION['mensaje'] = "EPS eliminada exitosamente.";
            $_SESSION['tipo_mensaje'] = 'success';
        }
    } catch (Exception $e) {
        if (isset($conn) && $conn->connect_errno == 0) {
            $conn->rollback();
        }
        $_SESSION['mensaje'] = "Error: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'danger';
    }
    
    header("location: eps.php");
    exit;
}

// Obtener lista de EPS
$sql = "SELECT e.*, 
        GROUP_CONCAT(DISTINCT ee.email ORDER BY ee.id SEPARATOR '|') as emails
        FROM eps e 
        LEFT JOIN eps_emails ee ON e.id = ee.eps_id AND ee.estado = 1
        GROUP BY e.id 
        ORDER BY e.nombre";
$result = $conn->query($sql);

if (!$result) {
    error_log("Error en consulta SQL: " . $conn->error);
    $_SESSION['mensaje'] = "Error al cargar las EPS.";
    $_SESSION['tipo_mensaje'] = 'danger';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de EPS - Sistema de PQRSs</title>
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
                    <h1 class="h2">Gestión de EPS</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#epsModal">
                        <i class="bi bi-plus-lg"></i> Nueva EPS
                    </button>
                </div>

                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['mensaje'];
                        unset($_SESSION['mensaje']);
                        unset($_SESSION['tipo_mensaje']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Correos Electrónicos</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                        <td>
                                            <?php 
                                            if ($row['emails']) {
                                                $emails = explode('|', $row['emails']);
                                                foreach ($emails as $email) {
                                                    echo '<div>' . htmlspecialchars($email) . '</div>';
                                                }
                                            } else {
                                                echo '<span class="text-muted">Sin correos registrados</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['estado'] ? 'success' : 'danger'; ?>">
                                                <?php echo $row['estado'] ? 'Activa' : 'Inactiva'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-eps" 
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#epsModal"
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                                    data-emails="<?php echo htmlspecialchars($row['emails'] ?? ''); ?>"
                                                    data-estado="<?php echo $row['estado']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-eps" 
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No hay EPS registradas</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para crear/editar EPS -->
    <div class="modal fade" id="epsModal" tabindex="-1" aria-labelledby="epsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="epsModalLabel">Nueva EPS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="epsForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="id" value="">
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la EPS</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Correos Electrónicos</label>
                            <div id="emailContainer">
                                <!-- Los campos de email se agregarán dinámicamente -->
                            </div>
                            <div class="form-text">Emails para notificaciones de PQRSs. Debe ingresar al menos uno.</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="estado" name="estado" value="1" checked>
                                <label class="form-check-label" for="estado">EPS activa</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para confirmar eliminación -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Está seguro que desea eliminar la EPS <span id="deleteEpsName"></span>?
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="delete" value="1">
                        <input type="hidden" name="id" id="deleteEpsId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Función para agregar nuevo campo de email
        function addEmailField(email = '', isFirst = false) {
            return `
                <div class="input-group mb-2">
                    <input type="email" class="form-control" name="emails[]" value="${email.trim()}" placeholder="ejemplo@eps.com">
                    <button type="button" class="btn ${isFirst ? 'btn-success add-email' : 'btn-danger remove-email'}">
                        <i class="bi ${isFirst ? 'bi-plus' : 'bi-trash'}"></i>
                    </button>
                </div>
            `;
        }

        // Agregar campo de email
        $(document).on('click', '.add-email', function() {
            $(this).removeClass('btn-success add-email')
                   .addClass('btn-danger remove-email')
                   .find('i')
                   .removeClass('bi-plus')
                   .addClass('bi-trash');
            
            $('#emailContainer').append(addEmailField('', true));
        });

        // Remover campo de email
        $(document).on('click', '.remove-email', function() {
            const container = $('#emailContainer');
            const emailGroups = container.find('.input-group');
            
            // Si es el último campo, no lo eliminamos
            if (emailGroups.length === 1) {
                $(this).closest('.input-group').find('input').val('');
                return;
            }
            
            $(this).closest('.input-group').remove();
            
            // Asegurarse que el primer campo tenga el botón de agregar
            const firstGroup = container.find('.input-group:first');
            firstGroup.find('button')
                     .removeClass('btn-danger remove-email')
                     .addClass('btn-success add-email')
                     .find('i')
                     .removeClass('bi-trash')
                     .addClass('bi-plus');
        });

        // Manejo del modal
        $('#epsModal').on('show.bs.modal', function(e) {
            const button = $(e.relatedTarget);
            
            if (button.hasClass('edit-eps')) {
                // Modo edición
                const id = button.data('id');
                const nombre = button.data('nombre');
                const emailsStr = button.data('emails');
                const estado = button.data('estado');

                console.log('Editando EPS:', { id, nombre, emailsStr, estado }); // Debug

                $('#epsModalLabel').text('Editar EPS');
                $('#epsForm input[name="action"]').val('edit');
                $('#epsForm input[name="id"]').val(id);
                $('#epsForm input[name="nombre"]').val(nombre);
                $('#epsForm input[name="estado"]').prop('checked', estado == 1);

                // Limpiar contenedor de emails
                $('#emailContainer').empty();

                // Agregar campos de email
                if (emailsStr) {
                    const emails = emailsStr.split('|').filter(email => email.trim());
                    if (emails.length > 0) {
                        emails.forEach((email, index) => {
                            $('#emailContainer').append(addEmailField(email, index === 0));
                        });
                    } else {
                        $('#emailContainer').append(addEmailField('', true));
                    }
                } else {
                    $('#emailContainer').append(addEmailField('', true));
                }
            } else {
                // Modo creación
                $('#epsModalLabel').text('Nueva EPS');
                $('#epsForm')[0].reset();
                $('#epsForm input[name="action"]').val('create');
                $('#epsForm input[name="id"]').val('');
                $('#emailContainer').empty().append(addEmailField('', true));
            }
        });

        // Remover el evento click anterior de edit-eps
        $('.edit-eps').off('click');

        // Manejo del modal de eliminación
        $('.delete-eps').click(function() {
            const id = $(this).data('id');
            const nombre = $(this).data('nombre');
            
            $('#deleteEpsId').val(id);
            $('#deleteEpsName').text(nombre);
            $('#deleteModal').modal('show');
        });

        // Validación antes de enviar el formulario
        $('#epsForm').on('submit', function(e) {
            const emails = $(this).find('input[name="emails[]"]')
                                .map(function() {
                                    return $(this).val().trim();
                                })
                                .get()
                                .filter(email => email);

            if (emails.length === 0) {
                e.preventDefault();
                alert('Debe ingresar al menos un correo electrónico.');
                return false;
            }
        });
    });
    </script>
</body>
</html>