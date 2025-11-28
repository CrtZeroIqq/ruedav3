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

    // Si la empresa no tiene disponibilidad configurada, ofrecer todos los bloques con una mesa sugerida
    if (empty($bloques)) {
        // Determinar una mesa preferida basada en reuniones existentes
        $stmtMesa = $pdo->prepare("SELECT mesa_asignada FROM reuniones WHERE empresa_a_id = ? AND mesa_asignada IS NOT NULL ORDER BY id DESC LIMIT 1");
        $stmtMesa->execute([$empresaId]);
        $mesaPreferida = intval($stmtMesa->fetchColumn()) ?: 1;

        // Obtener todos los bloques y marcar ocupación en la mesa preferida
        $stmt = $pdo->prepare("
            SELECT
                :mesa_preferida as mesa_numero,
                bg.id as bloque_id,
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
            FROM bloques_horarios_globales bg
            LEFT JOIN reuniones r ON r.bloque_global_id = bg.id
                AND r.empresa_a_id = :empresa_id
                AND r.mesa_asignada = :mesa_preferida
                AND r.estado IN ('pendiente', 'confirmada')
            LEFT JOIN empresas eb ON r.empresa_b_id = eb.id
            ORDER BY bg.orden
        ");

        $stmt->execute([
            ':mesa_preferida' => $mesaPreferida,
            ':empresa_id' => $empresaId
        ]);

        $bloques = $stmt->fetchAll();
    }

    jsonResponse(true, 'Bloques obtenidos', ['bloques' => $bloques]);

} catch (PDOException $e) {
    error_log("Error en bloques.php: " . $e->getMessage());
    jsonResponse(false, 'Error al obtener bloques');
}
?>