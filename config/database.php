<?php
// Configuración de conexión a MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'rueda_negocios_arica');
define('DB_USER', 'seidev'); // Cambiar según tu configuración Webmin
define('DB_PASS', 'Tz9!qE7#Xr2@Lm5$Vp8^');     // Cambiar según tu configuración Webmin
define('DB_CHARSET', 'utf8mb4');

// Conexión PDO con manejo de errores
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // En producción, registrar en log en vez de mostrar
    die("Error de conexión: " . $e->getMessage());
}
?>