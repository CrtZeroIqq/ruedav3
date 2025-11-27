<?php
// Configuración global del sistema
define('SITE_NAME', 'Rueda de Negocios Arica');
define('BASE_URL', 'https://www.bioceanicocentral.cl/rueda-negocios-arica/'); // Ajustar según tu entorno
define('UPLOAD_PATH', __DIR__ . '/../uploads/logos/');
define('UPLOAD_URL', BASE_URL . 'uploads/logos/');

// Zona horaria
date_default_timezone_set('America/Santiago');

// Configuración de sesiones
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Email para notificaciones
define('MAIL_FROM', 'noreply@ruedanegocios.cl');
define('MAIL_FROM_NAME', 'Rueda de Negocios Arica');

// Estados de reunión
define('ESTADO_PENDIENTE', 'pendiente');
define('ESTADO_CONFIRMADA', 'confirmada');
define('ESTADO_RECHAZADA', 'rechazada');
define('ESTADO_CANCELADA', 'cancelada');

// Roles de usuario
define('ROL_EMPRESA_A', 'empresa_a');
define('ROL_EMPRESA_B', 'empresa_b');
define('ROL_ADMIN', 'admin');
?>