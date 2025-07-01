<?php
// index.php - Formulario de registro de PQRSs
require_once "config/config.php";

session_start();

// Obtener lista de ciudades
$sql = "SELECT id, nombre FROM ciudades ORDER BY nombre";
$ciudades = $conn->query($sql);

// Obtener lista de EPS
$sql = "SELECT id, nombre FROM eps ORDER BY nombre";
$eps = $conn->query($sql);

// Obtener tipos de pqrs
$sql = "SELECT id, nombre FROM tipos_pqrs ORDER BY nombre";
$tipos_pqrs = $conn->query($sql);

// Recuperar datos del formulario si hubo errores
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_data']);

// Recuperar errores si existen
$errores = isset($_SESSION['errores']) ? $_SESSION['errores'] : [];
unset($_SESSION['errores']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de PQRSs para EPSs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" href="assets/img/logo.png">
    
</head>
<body>
    <div class="container">
        <header class="text-center py-4">
            <h1>Sistema de PQRSs para EPSs - Auditorías Escamilla</h1>
            <h2>Registro de Nueva PQRS</h2>
        </header>

        <main>
            <div class="form-section">
                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errores as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="procesar_pqrs.php" method="POST" enctype="multipart/form-data" onsubmit="return validarFormulario()">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">Nombre completo *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required
                                   value="<?php echo isset($form_data['nombre']) ? htmlspecialchars($form_data['nombre']) : ''; ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="documento" class="form-label">Documento de identidad *</label>
                            <input type="text" class="form-control" id="documento" name="documento" required
                                   value="<?php echo isset($form_data['documento']) ? htmlspecialchars($form_data['documento']) : ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Correo electrónico *</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?php echo isset($form_data['email']) ? htmlspecialchars($form_data['email']) : ''; ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono"
                                   value="<?php echo isset($form_data['telefono']) ? htmlspecialchars($form_data['telefono']) : ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="ciudad" class="form-label">Ciudad *</label>
                            <select class="form-select" id="ciudad" name="ciudad" required>
                                <option value="">Seleccione una ciudad</option>
                                <?php while ($ciudad = $ciudades->fetch_assoc()): ?>
                                    <option value="<?php echo $ciudad['id']; ?>"
                                            <?php echo (isset($form_data['ciudad']) && $form_data['ciudad'] == $ciudad['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ciudad['nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="eps" class="form-label">EPS *</label>
                            <select class="form-select" id="eps" name="eps" required>
                                <option value="">Seleccione una EPS</option>
                                <?php while ($ep = $eps->fetch_assoc()): ?>
                                    <option value="<?php echo $ep['id']; ?>"
                                            <?php echo (isset($form_data['eps']) && $form_data['eps'] == $ep['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ep['nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="tipo_pqrs" class="form-label">Motivo de la pqrs *</label>
                            <select class="form-select" id="tipo_pqrs" name="tipo_pqrs" required>
                                <option value="">Seleccione un motivo</option>
                                <?php while ($tipo = $tipos_pqrs->fetch_assoc()): ?>
                                    <option value="<?php echo $tipo['id']; ?>"
                                            <?php echo (isset($form_data['tipo_pqrs']) && $form_data['tipo_pqrs'] == $tipo['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tipo['nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción detallada de la pqrs *</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required><?php echo isset($form_data['descripcion']) ? htmlspecialchars($form_data['descripcion']) : ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="archivo" class="form-label">Adjuntar archivo (PDF o JPG, máximo 5MB)</label>
                        <input type="file" class="form-control" id="archivo" name="archivo" accept=".pdf,.jpg,.jpeg"
                               onchange="validarArchivo(this)">
                        <div id="archivoHelp" class="form-text">Formatos permitidos: PDF, JPG. Tamaño máximo: 5MB</div>
                        <div id="previewContainer" class="mt-2" style="display: none;">
                            <img id="preview" src="" alt="Vista previa" style="max-width: 200px; max-height: 200px;">
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary">Enviar PQRS</button>
                    </div>
                </form>
            </div>
        </main>

        <footer class="text-center py-4">
            <p>&copy; <?php echo date('Y'); ?> Sistema de PQRSs para EPSs</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validarArchivo(input) {
            const archivo = input.files[0];
            const previewContainer = document.getElementById('previewContainer');
            const preview = document.getElementById('preview');
            const archivoHelp = document.getElementById('archivoHelp');
            
            // Ocultar vista previa anterior
            previewContainer.style.display = 'none';
            
            if (archivo) {
                // Validar tamaño (5MB = 5242880 bytes)
                if (archivo.size > 5242880) {
                    alert('El archivo es demasiado grande. El tamaño máximo permitido es 5MB.');
                    input.value = '';
                    return;
                }

                // Validar tipo de archivo
                const tiposPermitidos = ['application/pdf', 'image/jpeg', 'image/jpg'];
                if (!tiposPermitidos.includes(archivo.type)) {
                    alert('Tipo de archivo no permitido. Solo se permiten archivos PDF y JPG.');
                    input.value = '';
                    return;
                }

                // Mostrar vista previa para imágenes
                if (archivo.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        previewContainer.style.display = 'block';
                    }
                    reader.readAsDataURL(archivo);
                }

                archivoHelp.textContent = `Archivo seleccionado: ${archivo.name} (${(archivo.size/1024/1024).toFixed(2)}MB)`;
            }
        }

        function validarFormulario() {
            const campos = ['nombre', 'documento', 'email', 'ciudad', 'eps', 'tipo_pqrs', 'descripcion'];
            let valido = true;

            campos.forEach(campo => {
                const elemento = document.getElementById(campo);
                if (!elemento.value.trim()) {
                    elemento.classList.add('is-invalid');
                    valido = false;
                } else {
                    elemento.classList.remove('is-invalid');
                }
            });

            if (!valido) {
                alert('Por favor complete todos los campos obligatorios.');
                return false;
            }

            return true;
        }
    </script>
</body>
</html>