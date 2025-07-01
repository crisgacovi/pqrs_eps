<?php
/**
 * Gestión de Tipos de PQRS - Sistema de PQRSs
 * Última modificación: 2025-04-23 19:37:57 UTC
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

// Procesar formulario de creación/edición
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['action'])) {
            $nombre = trim($_POST['nombre']);
            $descripcion = trim($_POST['descripcion']);
            $estado = isset($_POST['estado']) ? 1 : 0;

            if (empty($nombre)) {
                throw new Exception("El nombre del tipo de pqrs es requerido.");
            }

            if ($_POST['action'] == 'create') {
                $stmt = $conn->prepare("INSERT INTO tipos_pqrs (nombre, descripcion, estado) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $nombre, $descripcion, $estado);
                $mensaje = "Tipo de pqrs creado exitosamente.";
            } else if ($_POST['action'] == 'edit' && isset($_POST['id'])) {
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("UPDATE tipos_pqrs SET nombre = ?, descripcion = ?, estado = ? WHERE id = ?");
                $stmt->bind_param("ssii", $nombre, $descripcion, $estado, $id);
                $mensaje = "Tipo de pqrs actualizado exitosamente.";
            }

            if ($stmt->execute()) {
                $success = true;
            } else {
                throw new Exception("Error al procesar la operación: " . $stmt->error);
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Eliminar tipo de pqrs
if (isset($_GET['delete']) && $_SESSION['admin_role'] === 'admin') {
    try {
        $id = (int)$_GET['delete'];
        
        // Verificar si el tipo de pqrs está siendo utilizado
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pqrss WHERE tipo_pqrs_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['total'];

        if ($count > 0) {
            throw new Exception("No se puede eliminar el tipo de pqrs porque está siendo utilizado en pqrss existentes.");
        }

        $stmt = $conn->prepare("DELETE FROM tipos_pqrs WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = true;
            $mensaje = "Tipo de pqrs eliminado exitosamente.";
        } else {
            throw new Exception("Error al eliminar el tipo de pqrs: " . $stmt->error);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener lista de tipos de pqrs
try {
    $result = $conn->query("SELECT * FROM tipos_pqrs ORDER BY nombre");
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tipos de PQRS - Sistema de PQRSs</title>
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
                    <h1 class="h2">Gestión de Tipos de PQRS</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tipoPQRSModal">
                        <i class="bi bi-plus-circle"></i> Nuevo Tipo de PQRS
                    </button>
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
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['id']; ?></td>
                                                <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $row['estado'] ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo $row['estado'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" 
                                                                class="btn btn-sm btn-primary"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#tipoPQRSModal"
                                                                data-id="<?php echo $row['id']; ?>"
                                                                data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                                                data-descripcion="<?php echo htmlspecialchars($row['descripcion']); ?>"
                                                                data-estado="<?php echo $row['estado']; ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <?php if ($_SESSION['admin_role'] === 'admin'): ?>
                                                            <a href="tipos_pqrs.php?delete=<?php echo $row['id']; ?>" 
                                                               class="btn btn-sm btn-danger"
                                                               onclick="return confirm('¿Está seguro de que desea eliminar este tipo de pqrs?');">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                                    <p>No hay tipos de pqrs registrados.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para crear/editar tipo de pqrs -->
    <div class="modal fade" id="tipoPQRSModal" tabindex="-1" aria-labelledby="tipoPQRSModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tipoPQRSModalLabel">Nuevo Tipo de PQRS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="tipoPQRSForm" action="tipos_pqrs.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="id" id="tipo_pqrs_id">
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Tipo de PQRS</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="estado" name="estado" checked>
                                <label class="form-check-label" for="estado">
                                    Activo
                                </label>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipoPQRSModal = document.getElementById('tipoPQRSModal');
        if (tipoPQRSModal) {
            tipoPQRSModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const modal = this;
                const form = modal.querySelector('form');
                const modalTitle = modal.querySelector('.modal-title');
                
                if (button.hasAttribute('data-id')) {
                    // Modo edición
                    const id = button.getAttribute('data-id');
                    const nombre = button.getAttribute('data-nombre');
                    const descripcion = button.getAttribute('data-descripcion');
                    const estado = button.getAttribute('data-estado') === '1';
                    
                    modalTitle.textContent = 'Editar Tipo de PQRS';
                    form.querySelector('input[name="action"]').value = 'edit';
                    form.querySelector('#tipo_pqrs_id').value = id;
                    form.querySelector('#nombre').value = nombre;
                    form.querySelector('#descripcion').value = descripcion;
                    form.querySelector('#estado').checked = estado;
                } else {
                    // Modo creación
                    modalTitle.textContent = 'Nuevo Tipo de PQRS';
                    form.querySelector('input[name="action"]').value = 'create';
                    form.querySelector('#tipo_pqrs_id').value = '';
                    form.querySelector('#nombre').value = '';
                    form.querySelector('#descripcion').value = '';
                    form.querySelector('#estado').checked = true;
                }
            });
        }
    });
    </script>
</body>
</html>