<?php
// Conexión a la base de datos del formulario de registro externo
// Usa las mismas credenciales que el sistema principal a menos que se definan otras
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/database.php';
}

$registroDsn = 'mysql:host=' . DB_HOST . ';dbname=registro_evento;charset=' . DB_CHARSET;
$registroOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdoRegistro = new PDO($registroDsn, DB_USER, DB_PASS, $registroOptions);
} catch (PDOException $e) {
    error_log('Error de conexión a registro_evento: ' . $e->getMessage());
    $pdoRegistro = null;
}