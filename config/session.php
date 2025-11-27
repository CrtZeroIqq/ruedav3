<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['rol']);
}

// Obtener rol del usuario actual
function getUserRole() {
    return $_SESSION['rol'] ?? null;
}

// Obtener ID del usuario actual
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Obtener ID de empresa del usuario actual
function getEmpresaId() {
    return $_SESSION['empresa_id'] ?? null;
}

// Redirigir según rol
function redirectToDashboard($rol) {
    switch ($rol) {
        case ROL_EMPRESA_A:
            header('Location: ' . BASE_URL . 'views/panel-empresa-a.php');
            break;
        case ROL_EMPRESA_B:
            header('Location: ' . BASE_URL . 'views/panel-empresa-b.php');
            break;
        case ROL_ADMIN:
            header('Location: ' . BASE_URL . 'views/panel-admin.php');
            break;
        default:
            header('Location: ' . BASE_URL . 'views/login.php');
    }
    exit;
}

// Destruir sesión
function logout() {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . 'views/login.php');
    exit;
}
?>