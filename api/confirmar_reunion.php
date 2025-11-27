<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Solo empresa A puede confirmar o rechazar
if ($_SESSION['rol'] !== 'empresa_a') {
    jsonResponse(false, 'Acceso denegado');
}

// Solo método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

$empresa_a_id = $_SESSION['empresa_id'];
$reunion_id   = intval($_POST['reunion_id'] ?? 0);
$accion       = sanitize($_POST['accion'] ?? ''); // confirmar | rechazar

if ($reunion_id <= 0 || !in_array($accion, ['confirmar', 'rechazar'])) {
    jsonResponse(false, 'Datos inválidos');
}

try {
    // Obtener la reunión
    $stmt = $pdo->prepare("
        SELECT * FROM reuniones 
        WHERE id = ? AND empresa_a_id = ?
    ");
    $stmt->execute([$reunion_id, $empresa_a_id]);
    $reunion = $stmt->fetch();

    if (!$reunion) {
        jsonResponse(false, 'Reunión no encontrada');
    }

    $bloque_id = $reunion['bloque_global_id'];

    // Si la acción es RECHAZAR
    if ($accion === 'rechazar') {
        $update = $pdo->prepare("
            UPDATE reuniones SET estado = 'rechazada', fecha_respuesta = NOW()
            WHERE id = ?
        ");
        $update->execute([$reunion_id]);

        jsonResponse(true, 'Reunión rechazada');
    }

    // ----------------------------------------------
    //   CONFIRMAR REUNIÓN  →  ASIGNAR MESA DINÁMICA
    // ----------------------------------------------

    // Buscar primera mesa libre (1 a 15)
    $mesaLibre = null;

    for ($mesa = 1; $mesa <= 15; $mesa++) {
        $check = $pdo->prepare("
            SELECT COUNT(*) FROM reuniones
            WHERE bloque_global_id = ? 
            AND mesa_asignada = ? 
            AND estado = 'confirmada'
        ");
        $check->execute([$bloque_id, $mesa]);

        if ($check->fetchColumn() == 0) {
            $mesaLibre = $mesa;
            break;
        }
    }

    if (!$mesaLibre) {
        jsonResponse(false, 'No quedan mesas disponibles en este bloque');
    }

    // Asignar mesa y confirmar reunión
    $update = $pdo->prepare("
        UPDATE reuniones 
        SET mesa_asignada = ?, estado = 'confirmada', fecha_respuesta = NOW()
        WHERE id = ?
    ");
    $update->execute([$mesaLibre, $reunion_id]);

    jsonResponse(true, 'Reunión confirmada', [
        'mesa' => $mesaLibre
    ]);

} catch (Exception $e) {
    error_log("Error confirmar_reunion.php: " . $e->getMessage());
    jsonResponse(false, 'Error en el servidor');
}
?>
