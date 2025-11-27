<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

try {
    $ahora = new DateTime();
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Buscar bloque activo en la fecha actual
    $stmtActivo = $pdo->prepare(
        "SELECT * FROM bloques_horarios_globales
         WHERE fecha = CURDATE()
         AND hora_inicio <= CURTIME()
         AND hora_fin > CURTIME()
         ORDER BY orden
         LIMIT 1"
    );
    $stmtActivo->execute();
    $bloqueObjetivo = $stmtActivo->fetch();
    $estadoBloque = 'sin_bloque';
    $infoBloque = null;

    if ($bloqueObjetivo) {
        $estadoBloque = 'activo';
    } else {
        // Buscar siguiente bloque programado
        $stmtProximo = $pdo->prepare(
            "SELECT * FROM bloques_horarios_globales
             WHERE fecha >= CURDATE()
             AND (fecha > CURDATE() OR hora_inicio > CURTIME())
             ORDER BY fecha ASC, hora_inicio ASC
             LIMIT 1"
        );
        $stmtProximo->execute();
        $bloqueObjetivo = $stmtProximo->fetch();
        if ($bloqueObjetivo) {
            $estadoBloque = 'proximo';
        }
    }

    if ($bloqueObjetivo) {
        $inicio = new DateTime($bloqueObjetivo['fecha'] . ' ' . $bloqueObjetivo['hora_inicio']);
        $fin = new DateTime($bloqueObjetivo['fecha'] . ' ' . $bloqueObjetivo['hora_fin']);

        $infoBloque = [
            'id' => (int) $bloqueObjetivo['id'],
            'fecha' => $bloqueObjetivo['fecha'],
            'hora_inicio' => substr($bloqueObjetivo['hora_inicio'], 0, 5),
            'hora_fin' => substr($bloqueObjetivo['hora_fin'], 0, 5),
            'orden' => (int) $bloqueObjetivo['orden'],
            'estado' => $estadoBloque,
            'minutos_restantes' => $estadoBloque === 'activo'
                ? max(0, floor(($fin->getTimestamp() - $ahora->getTimestamp()) / 60))
                : null,
            'minutos_para_inicio' => $estadoBloque === 'proximo'
                ? max(0, floor(($inicio->getTimestamp() - $ahora->getTimestamp()) / 60))
                : null,
        ];
    }

    $mesas = [];
    for ($mesa = 1; $mesa <= 15; $mesa++) {
        $mesas[$mesa] = [
            'mesa' => $mesa,
            'estado' => 'libre',
            'reunion_id' => null,
            'demandante' => null,
            'oferente' => null,
            'rubro_demandante' => null,
            'rubro_oferente' => null,
        ];
    }

    $ocupadas = 0;

    if ($bloqueObjetivo) {
        $stmtReuniones = $pdo->prepare(
            "SELECT
                r.id,
                r.estado,
                r.mesa_asignada,
                ea.nombre AS demandante,
                ea.rubro AS rubro_demandante,
                ea.logo AS logo_demandante,
                eb.nombre AS oferente,
                eb.rubro AS rubro_oferente,
                eb.logo AS logo_oferente
            FROM reuniones r
            INNER JOIN empresas ea ON r.empresa_a_id = ea.id
            INNER JOIN empresas eb ON r.empresa_b_id = eb.id
            WHERE r.bloque_global_id = ?
              AND r.estado IN ('confirmada', 'pendiente')
            ORDER BY r.mesa_asignada"
        );
        $stmtReuniones->execute([$bloqueObjetivo['id']]);
        $reuniones = $stmtReuniones->fetchAll();

        foreach ($reuniones as $reunion) {
            $mesaNum = (int) $reunion['mesa_asignada'];
            if ($mesaNum < 1 || $mesaNum > 15) {
                continue;
            }

            $mesas[$mesaNum] = [
                'mesa' => $mesaNum,
                'estado' => $reunion['estado'],
                'reunion_id' => (int) $reunion['id'],
                'demandante' => $reunion['demandante'],
                'oferente' => $reunion['oferente'],
                'rubro_demandante' => $reunion['rubro_demandante'],
                'rubro_oferente' => $reunion['rubro_oferente'],
                'logo_demandante' => $reunion['logo_demandante'],
                'logo_oferente' => $reunion['logo_oferente'],
            ];
            $ocupadas++;
        }
    }

    echo json_encode([
        'success' => true,
        'bloque' => $infoBloque,
        'mesas' => array_values($mesas),
        'estadisticas' => [
            'mesas_ocupadas' => $ocupadas,
            'mesas_libres' => 15 - $ocupadas,
            'total_mesas' => 15,
        ],
        'ahora' => $ahora->format('H:i:s'),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo obtener la información del visor',
        'mensaje' => $e->getMessage(),
    ]);
}
