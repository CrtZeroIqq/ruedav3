<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Solo método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'empresa_a') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$empresa_id = $_SESSION['empresa_id'];
$selecciones = $_POST['selecciones'] ?? [];

if (empty($selecciones)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Debes seleccionar al menos un bloque']);
    exit;
}

// Validar máximo 2 selecciones
if (count($selecciones) > 2) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Máximo 2 bloques permitidos']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Primero, eliminar disponibilidad previa (si existía)
    $del = $pdo->prepare("DELETE FROM disponibilidad_empresas WHERE empresa_id = ?");
    $del->execute([$empresa_id]);

    // Insertar nuevas selecciones con mesa_numero
    $insert = $pdo->prepare("
        INSERT INTO disponibilidad_empresas (empresa_id, bloque_id, mesa_numero, disponible)
        VALUES (?, ?, ?, 1)
    ");

    foreach ($selecciones as $selJson) {
        $sel = json_decode($selJson, true);
        $mesa = intval($sel['mesa']);
        $bloque = intval($sel['bloque']);

        $insert->execute([$empresa_id, $bloque, $mesa]);
    }

    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Disponibilidad guardada exitosamente',
        'bloques_guardados' => count($selecciones)
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Error al guardar disponibilidad: " . $e->getMessage());

    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar: ' . $e->getMessage()
    ]);
    exit;
}
