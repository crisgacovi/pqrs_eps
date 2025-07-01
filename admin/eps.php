<?php
/**
 * Gestión de EPS - Sistema de PQRSs
 * Última modificación: 2025-05-14 04:26:05 UTC
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
            $email = trim($_POST['email']);
            $estado = isset($_POST['estado']) ? 1 : 0;

            if (empty($nombre)) {
                throw new Exception("El nombre de la EPS es requerido.");
            }

            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("El formato del email no es válido.");
            }

            if ($_POST['action'] == 'create') {
                // Verificar si ya existe
                $stmt = $conn->prepare("SELECT id FROM eps WHERE nombre = ?");
                $stmt->bind_param("s", $nombre);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("Ya existe una EPS con este nombre.");
                }

                $stmt = $conn->prepare("INSERT INTO eps (nombre, email, estado) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $nombre, $email, $estado);
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

                $stmt = $conn->prepare("UPDATE eps SET nombre = ?, email = ?, estado = ? WHERE id = ?");
                $stmt->bind_param("ssii", $nombre, $email, $estado, $id);
                $mensaje = "EPS actualizada exitosamente.";
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

// Eliminar EPS
if (isset($_GET['delete'])) {
    try {
        $id = (int)$_GET['delete'];

        // Verificar si hay pqrss asociadas
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pqrss WHERE eps_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['total'];

        if ($count > 0) {
            throw new Exception("No se puede eliminar la EPS porque tiene pqrss asociadas.");
        }

        $stmt = $conn->prepare("DELETE FROM eps WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = true;
            $mensaje = "EPS eliminada exitosamente.";
        } else {
            throw new Exception("Error al eliminar la EPS: " . $stmt->error);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener lista de EPS
try {
    $result = $conn->query("SELECT * FROM eps ORDER BY nombre");
} catch (Exception $e) {
    $error = $e->getMessage();
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
                        <i class="bi bi-plus-circle"></i> Nueva EPS
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
                                        <th>Email</th>
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
                                                <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
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
                                                                data-bs-target="#epsModal"
                                                                data-id="<?php echo $row['id']; ?>"
                                                                data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                                                data-email="<?php echo htmlspecialchars($row['email'] ?? ''); ?>"
                                                                data-estado="<?php echo $row['estado']; ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <a href="eps.php?delete=<?php echo $row['id']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('¿Está seguro de eliminar esta EPS?');">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                                    <p>No hay EPS registradas.</p>
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

    <!-- Modal para crear/editar EPS -->
    <div class="modal fade" id="epsModal" tabindex="-1" aria-labelledby="epsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="epsModalLabel">Nueva EPS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="epsForm" action="eps.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="id" id="eps_id">
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la EPS</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email de la EPS</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="ejemplo@eps.com">
                            <div class="form-text">Email para notificaciones de PQRSs.</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="estado" name="estado" value="1" checked>
                                <label class="form-check-label" for="estado">
                                    EPS activa
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
        const epsModal = document.getElementById('epsModal');
        if (epsModal) {
            epsModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const modal = this;
                const form = modal.querySelector('form');
                const modalTitle = modal.querySelector('.modal-title');
                
                if (button.hasAttribute('data-id')) {
                    // Modo edición
                    const id = button.getAttribute('data-id');
                    const nombre = button.getAttribute('data-nombre');
                    const email = button.getAttribute('data-email');
                    const estado = button.getAttribute('data-estado');
                    
                    modalTitle.textContent = 'Editar EPS';
                    form.querySelector('input[name="action"]').value = 'edit';
                    form.querySelector('#eps_id').value = id;
                    form.querySelector('#nombre').value = nombre;
                    form.querySelector('#email').value = email;
                    form.querySelector('#estado').checked = estado === '1';
                } else {
                    // Modo creación
                    modalTitle.textContent = 'Nueva EPS';
                    form.reset();
                    form.querySelector('input[name="action"]').value = 'create';
                    form.querySelector('#eps_id').value = '';
                    form.querySelector('#estado').checked = true;
                }
            });
        }
    });
    </script>
</body>
</html>