<?php
/**
 * Login de Administración - Sistema de PQRSs
 * Última modificación: 2025-05-14 03:08:55 UTC
 * @author crisgacovi
 */

// Iniciar sesión
session_start();

// Verificar si el usuario ya está logueado
if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
    header("location: index.php");
    exit;
}

// Incluir archivo de configuración
require_once "../config/config.php";

// Definir variables
$username = $password = "";
$error = "";

// Procesar datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validar campos vacíos
        if (empty(trim($_POST["username"])) || empty(trim($_POST["password"]))) {
            throw new Exception("Por favor, complete todos los campos.");
        }

        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);

        // Preparar la consulta incluyendo información de ciudad para consultores
        $sql = "SELECT u.id, u.username, u.password, u.nombre_completo, u.role, u.estado,
                       uc.ciudad_id, c.nombre as ciudad_nombre 
                FROM usuarios u 
                LEFT JOIN usuario_ciudad uc ON u.id = uc.usuario_id 
                LEFT JOIN ciudades c ON uc.ciudad_id = c.id 
                WHERE u.username = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $username);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                
                if ($result->num_rows == 1) {
                    $row = $result->fetch_assoc();
                    
                    // Verificar estado del usuario
                    if ($row['estado'] == 0) {
                        throw new Exception("Su cuenta está desactivada. Contacte al administrador.");
                    }
                    
                    // Verificar contraseña
                    if (password_verify($password, $row['password'])) {
                        // Almacenar datos en variables de sesión
                        $_SESSION['admin_loggedin'] = true;
                        $_SESSION['admin_id'] = $row['id'];
                        $_SESSION['admin_username'] = $row['username'];
                        $_SESSION['admin_nombre'] = $row['nombre_completo'];
                        $_SESSION['admin_role'] = $row['role'];
                        
                        // Si es consultor_ciudad, guardar datos de la ciudad
                        if ($row['role'] === 'consultor_ciudad') {
                            if (!$row['ciudad_id']) {
                                throw new Exception("No tiene una ciudad asignada. Contacte al administrador.");
                            }
                            $_SESSION['ciudad_id'] = $row['ciudad_id'];
                            $_SESSION['ciudad_nombre'] = $row['ciudad_nombre'];
                        }
                        
                        // Actualizar último acceso
                        $update_sql = "UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?";
                        if ($update_stmt = $conn->prepare($update_sql)) {
                            $update_stmt->bind_param("i", $row['id']);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                        
                        // Redirigir al usuario
                        header("location: index.php");
                        exit;
                    } else {
                        throw new Exception("La contraseña ingresada no es válida.");
                    }
                } else {
                    throw new Exception("No existe una cuenta con ese nombre de usuario.");
                }
            } else {
                throw new Exception("Error en el servidor. Por favor, intente más tarde.");
            }
            
            $stmt->close();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de PQRSs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
            background-color: #f5f5f5;
        }
        .form-signin {
            width: 100%;
            max-width: 330px;
            padding: 15px;
            margin: auto;
        }
        .form-signin .form-floating:focus-within {
            z-index: 2;
        }
        .form-signin input[type="text"] {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }
        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
        .logo {
            width: 100px;
            height: 100px;
            margin-bottom: 1.5rem;
        }
        .error-shake {
            animation: shake 0.82s cubic-bezier(.36,.07,.19,.97) both;
            transform: translate3d(0, 0, 0);
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
    </style>
</head>
<body class="text-center">
    <main class="form-signin">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" 
              class="<?php echo !empty($error) ? 'error-shake' : ''; ?>">
            <img class="logo mb-4" src="../assets/img/logo.png" alt="Logo">
            <h1 class="h3 mb-3 fw-normal">Acceso Administrativo</h1>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="Usuario" value="<?php echo htmlspecialchars($username); ?>" required>
                <label for="username">Usuario</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Contraseña" required>
                <label for="password">Contraseña</label>
            </div>

            <button class="w-100 btn btn-lg btn-primary mb-3" type="submit">
                <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
            </button>
            
            <a href="../index.php" class="btn btn-link">
                <i class="bi bi-arrow-left"></i> Volver al sitio público
            </a>
            
            <p class="mt-5 mb-3 text-muted">&copy; <?php echo date('Y'); ?> Sistema de PQRSs para EPSs</p>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Enfocar el campo de usuario al cargar la página
        document.getElementById('username').focus();

        // Prevenir múltiples envíos del formulario
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton.disabled) {
                e.preventDefault();
            } else {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Iniciando sesión...';
            }
        });
    });
    </script>
</body>
</html>