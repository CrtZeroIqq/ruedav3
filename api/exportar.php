<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Verificar autenticación y permisos
if (!isLoggedIn() || getUserRole() !== ROL_ADMIN) {
    die('Acceso denegado');
}

$tipo = $_GET['tipo'] ?? '';

if (!in_array($tipo, ['reuniones', 'confirmadas', 'empresas'])) {
    die('Tipo de exportación inválido');
}

try {
    switch ($tipo) {
        case 'reuniones':
            exportarReuniones($pdo, false);
            break;
        case 'confirmadas':
            exportarReuniones($pdo, true);
            break;
        case 'empresas':
            exportarEmpresas($pdo);
            break;
    }
} catch (PDOException $e) {
    die('Error al exportar: ' . $e->getMessage());
}

function exportarReuniones($pdo, $soloConfirmadas = false) {
    $whereClause = $soloConfirmadas ? "WHERE r.estado = 'confirmada'" : "";
    
    $stmt = $pdo->query("
        SELECT 
            r.id,
            ea.nombre as empresa_grande,
            ea.rubro as rubro_grande,
            eb.nombre as pyme,
            eb.rubro as rubro_pyme,
            eb.email_contacto as email_pyme,
            eb.telefono as telefono_pyme,
            bh.fecha,
            bh.hora_inicio,
            bh.hora_fin,
            r.estado,
            r.created_at as fecha_solicitud,
            r.fecha_respuesta,
            r.notas
        FROM reuniones r
        INNER JOIN empresas ea ON r.empresa_a_id = ea.id
        INNER JOIN empresas eb ON r.empresa_b_id = eb.id
        INNER JOIN bloques_horarios bh ON r.bloque_id = bh.id
        $whereClause
        ORDER BY bh.fecha, bh.hora_inicio
    ");
    
    $filename = $soloConfirmadas ? 'reuniones_confirmadas' : 'todas_reuniones';
    $filename .= '_' . date('Y-m-d_His') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados
    fputcsv($output, [
        'ID',
        'Empresa Grande',
        'Rubro Grande',
        'Pyme',
        'Rubro Pyme',
        'Email Pyme',
        'Teléfono Pyme',
        'Fecha',
        'Hora Inicio',
        'Hora Fin',
        'Estado',
        'Fecha Solicitud',
        'Fecha Respuesta',
        'Notas'
    ], ';');
    
    // Datos
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    exit;
}

function exportarEmpresas($pdo) {
    $stmt = $pdo->query("
        SELECT 
            id,
            nombre,
            tipo,
            rubro,
            email_contacto,
            telefono,
            direccion,
            activo,
            created_at
        FROM empresas
        ORDER BY tipo DESC, nombre
    ");
    
    $filename = 'empresas_' . date('Y-m-d_His') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados
    fputcsv($output, [
        'ID',
        'Nombre',
        'Tipo',
        'Rubro',
        'Email',
        'Teléfono',
        'Dirección',
        'Activo',
        'Fecha Registro'
    ], ';');
    
    // Datos
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    exit;
}
?>