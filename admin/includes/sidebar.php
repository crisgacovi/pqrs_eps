<?php
if (!defined('IN_ADMIN')) {
    exit;
}
?>

<!-- Botón del menú hamburguesa -->
<button class="navbar-toggler position-fixed d-md-none collapsed" 
        type="button" 
        data-bs-toggle="collapse" 
        data-bs-target="#sidebarMenu" 
        aria-controls="sidebarMenu" 
        aria-expanded="false" 
        aria-label="Toggle navigation"
        style="top: 10px; left: 10px; z-index: 1031; background-color: #ffffff;">
    <span class="navbar-toggler-icon"></span>
</button>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <!-- Logo y nombre del sistema -->
        <div class="text-center mb-4">
            <img src="../assets/img/logo.png" alt="Logo" class="img-fluid mb-3" style="max-width: 100px;">
            <h6 class="sidebar-heading px-3 mb-1">Sistema de PQRSs</h6>
        </div>

        <!-- Usuario actual -->
        <div class="px-3 mb-3">
            <div class="d-flex align-items-center text-dark">
                <i class="bi bi-person-circle me-2"></i>
                <small>
                    <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                    <?php if ($_SESSION['admin_role'] === 'consultor_ciudad'): ?>
                        <br>
                        <span class="badge bg-info">
                            <i class="bi bi-geo-alt"></i> 
                            <?php echo htmlspecialchars($_SESSION['ciudad_nombre']); ?>
                        </span>
                    <?php endif; ?>
                </small>
            </div>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" 
                   href="index.php">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pqrss.php' ? 'active' : ''; ?>" 
                   href="pqrss.php">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Gestión de PQRSs
                </a>
            </li>
            <?php if ($_SESSION['admin_role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'eps.php' ? 'active' : ''; ?>" 
                       href="eps.php">
                        <i class="bi bi-building me-2"></i>
                        Gestión de EPS
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ciudades.php' ? 'active' : ''; ?>" 
                       href="ciudades.php">
                        <i class="bi bi-geo-alt me-2"></i>
                        Gestión de Ciudades
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tipos_pqrs.php' ? 'active' : ''; ?>" 
                       href="tipos_pqrs.php">
                        <i class="bi bi-tags me-2"></i>
                        Tipos de PQRS
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>" 
                       href="usuarios.php">
                        <i class="bi bi-people me-2"></i>
                        Usuarios
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Divider -->
        <hr class="my-3">

        <!-- Cerrar sesión -->
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="../index.php" target="_blank">
                    <i class="bi bi-box-arrow-up-right"></i>
                    Ver sitio público
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    Cerrar sesión
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 1030;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
    background-color: #f8f9fa;
}

.sidebar .nav-link {
    font-weight: 500;
    color: #333;
    padding: .5rem 1rem;
    transition: all 0.3s;
}

.sidebar .nav-link:hover {
    color: #0d6efd;
    background: rgba(13, 110, 253, .1);
}

.sidebar .nav-link.active {
    color: #0d6efd;
    background: rgba(13, 110, 253, .15);
}

.sidebar-heading {
    font-size: .75rem;
    text-transform: uppercase;
    color: #6c757d;
}

.navbar-toggler {
    padding: .5rem;
    border: 1px solid rgba(0, 0, 0, .1);
    border-radius: .25rem;
    transition: all 0.3s;
}

.navbar-toggler:hover {
    background-color: #f8f9fa;
}

@media (max-width: 767.98px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease-in-out;
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
    
    .sidebar-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1029;
        display: none;
    }
    
    .sidebar.show ~ .sidebar-backdrop {
        display: block;
    }
    
    main {
        margin-left: 0 !important;
        padding-top: 60px !important;
    }
}

@media (min-width: 768px) {
    .navbar-toggler {
        display: none;
    }
    
    .sidebar {
        transform: none !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Crear el backdrop para el sidebar
    const backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);
    
    // Obtener referencias
    const sidebarMenu = document.getElementById('sidebarMenu');
    const navbarToggler = document.querySelector('.navbar-toggler');
    
    // Función para cerrar el sidebar
    function closeSidebar() {
        sidebarMenu.classList.remove('show');
        navbarToggler.classList.add('collapsed');
        navbarToggler.setAttribute('aria-expanded', 'false');
    }
    
    // Cerrar sidebar al hacer clic en el backdrop
    backdrop.addEventListener('click', closeSidebar);
    
    // Cerrar sidebar al hacer clic en un enlace (en móviles)
    const navLinks = sidebarMenu.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 768) {
                closeSidebar();
            }
        });
    });
    
    // Ajustar cuando se redimensiona la ventana
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            closeSidebar();
        }
    });
});
</script>