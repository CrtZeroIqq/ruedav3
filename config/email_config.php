<?php
// Configuración de correo electrónico
define('SMTP_HOST', '10.24.254.104');
define('SMTP_PORT', 587);
define('SMTP_USER', 'contacto@bioceanicocentral.cl');
define('SMTP_PASS', '@@Bio2025');
define('SMTP_FROM_EMAIL', 'contacto@bioceanicocentral.cl');
define('SMTP_FROM_NAME', 'Rueda de Negocios Arica 2025');
define('SMTP_ENCRYPTION', 'tls'); // o 'ssl'

// Opciones de seguridad
define('SMTP_OPTIONS', [
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
]);
?>