<?php
/**
 * Gestión de Ciudades - Sistema de PQRSs
 * Última modificación: 2025-04-23 19:36:37 UTC
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
            $departamento = trim($_POST['departamento']);
            $estado = isset($_POST['estado']) ? 1 : 0;

            if (empty($nombre)) {
                throw new Exception("El nombre de la ciudad es requerido.");
            }

            if (empty($departamento)) {
                throw new Exception("El departamento es requerido.");
            }

            if ($_POST['action'] == 'create') {
                $stmt = $conn->prepare("INSERT INTO ciudades (nombre, departamento, estado) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $nombre, $departamento, $estado);
                $mensaje = "Ciudad creada exitosamente.";
            } else if ($_POST['action'] == 'edit' && isset($_POST['id'])) {
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("UPDATE ciudades SET nombre = ?, departamento = ?, estado = ? WHERE id = ?");
                $stmt->bind_param("ssii", $nombre, $departamento, $estado, $id);
                $mensaje = "Ciudad actualizada exitosamente.";
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

// Eliminar ciudad
if (isset($_GET['delete']) && $_SESSION['admin_role'] === 'admin') {
    try {
        $id = (int)$_GET['delete'];
        
        // Verificar si la ciudad está siendo utilizada
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pqrss WHERE ciudad_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['total'];

        if ($count > 0) {
            throw new Exception("No se puede eliminar la ciudad porque está siendo utilizada en pqrss existentes.");
        }

        $stmt = $conn->prepare("DELETE FROM ciudades WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = true;
            $mensaje = "Ciudad eliminada exitosamente.";
        } else {
            throw new Exception("Error al eliminar la ciudad: " . $stmt->error);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener lista de ciudades
try {
    $result = $conn->query("SELECT * FROM ciudades ORDER BY departamento, nombre");
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ciudades - Sistema de PQRSs</title>
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
                    <h1 class="h2">Gestión de Ciudades</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ciudadModal">
                        <i class="bi bi-plus-circle"></i> Nueva Ciudad
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
                                        <th>Departamento</th>
                                        <th>Ciudad</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['id']; ?></td>
                                                <td><?php echo htmlspecialchars($row['departamento']); ?></td>
                                                <td><?php echo htmlspecialchars($row['nombre']); ?></td>
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
                                                                data-bs-target="#ciudadModal"
                                                                data-id="<?php echo $row['id']; ?>"
                                                                data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                                                data-departamento="<?php echo htmlspecialchars($row['departamento']); ?>"
                                                                data-estado="<?php echo $row['estado']; ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <?php if ($_SESSION['admin_role'] === 'admin'): ?>
                                                            <a href="ciudades.php?delete=<?php echo $row['id']; ?>" 
                                                               class="btn btn-sm btn-danger"
                                                               onclick="return confirm('¿Está seguro de que desea eliminar esta ciudad?');">
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
                                                    <p>No hay ciudades registradas.</p>
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

    <!-- Modal para crear/editar ciudad -->
    <div class="modal fade" id="ciudadModal" tabindex="-1" aria-labelledby="ciudadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ciudadModalLabel">Nueva Ciudad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="ciudadForm" action="ciudades.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="id" id="ciudad_id">
                        
                        <div class="mb-3">
                            <label for="departamento" class="form-label">Departamento</label>
                            <input type="text" class="form-control" id="departamento" name="departamento" required>
                        </div>

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la Ciudad</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
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
        const ciudadModal = document.getElementById('ciudadModal');
        if (ciudadModal) {
            ciudadModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const modal = this;
                const form = modal.querySelector('form');
                const modalTitle = modal.querySelector('.modal-title');
                
                if (button.hasAttribute('data-id')) {
                    // Modo edición
                    const id = button.getAttribute('data-id');
                    const nombre = button.getAttribute('data-nombre');
                    const departamento = button.getAttribute('data-departamento');
                    const estado = button.getAttribute('data-estado') === '1';
                    
                    modalTitle.textContent = 'Editar Ciudad';
                    form.querySelector('input[name="action"]').value = 'edit';
                    form.querySelector('#ciudad_id').value = id;
                    form.querySelector('#nombre').value = nombre;
                    form.querySelector('#departamento').value = departamento;
                    form.querySelector('#estado').checked = estado;
                } else {
                    // Modo creación
                    modalTitle.textContent = 'Nueva Ciudad';
                    form.querySelector('input[name="action"]').value = 'create';
                    form.querySelector('#ciudad_id').value = '';
                    form.querySelector('#nombre').value = '';
                    form.querySelector('#departamento').value = '';
                    form.querySelector('#estado').checked = true;
                }
            });
        }
    });
    </script>
</body>
</html>