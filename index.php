<?php
require_once 'config/config.php';
require_once 'config/session.php';

// Si ya está logueado, redirigir a su panel
if (isLoggedIn()) {
    redirectToDashboard(getUserRole());
} else {
    // Si no está logueado, mostrar login
    header('Location: ' . BASE_URL . 'views/login.php');
    exit;
}
?>