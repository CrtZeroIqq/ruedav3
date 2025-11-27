<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Verificar autenticación
if (!isLoggedIn()) {
    jsonResponse(false, 'No autorizado');
}

// Solo aceptar GET para consultas
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Método no permitido');
}

$empresaId = intval($_GET['empresa_id'] ?? 0);

if ($empresaId <= 0) {
    jsonResponse(false, 'ID de empresa inválido');
}

try {
    // Obtener disponibilidad de la empresa con mesa_numero
    $stmt = $pdo->prepare("
        SELECT
            de.mesa_numero,
            de.bloque_id,
            bg.fecha,
            bg.hora_inicio,
            bg.hora_fin,
            bg.orden,
            r.id as reunion_id,
            r.estado as reunion_estado,
            r.empresa_b_id,
            eb.nombre as empresa_b_nombre,
            CASE
                WHEN r.id IS NULL THEN 'disponible'
                WHEN r.estado = 'pendiente' THEN 'pendiente'
                WHEN r.estado = 'confirmada' THEN 'ocupado'
                ELSE 'no_disponible'
            END as estado
        FROM disponibilidad_empresas de
        INNER JOIN bloques_horarios_globales bg ON bg.id = de.bloque_id
        LEFT JOIN reuniones r ON r.bloque_global_id = de.bloque_id
            AND r.empresa_a_id = ?
            AND r.estado IN ('pendiente', 'confirmada')
        LEFT JOIN empresas eb ON r.empresa_b_id = eb.id
        WHERE de.empresa_id = ? AND de.disponible = 1
        ORDER BY de.mesa_numero, bg.orden
    ");
    $stmt->execute([$empresaId, $empresaId]);
    $bloques = $stmt->fetchAll();

    jsonResponse(true, 'Bloques obtenidos', ['bloques' => $bloques]);

} catch (PDOException $e) {
    error_log("Error en bloques.php: " . $e->getMessage());
    jsonResponse(false, 'Error al obtener bloques');
}
?>