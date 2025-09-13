<?php
// logout.php
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la sesión
session_destroy();

// Redirigir al formulario de login
header('Location: newpicture.php');
exit();
?>