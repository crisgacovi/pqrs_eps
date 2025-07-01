<?php
// logout.php - Cierra la sesión y redirecciona al login
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la sesión
session_destroy();

// Redirigir al login
header("location: login.php");
exit;
?>
