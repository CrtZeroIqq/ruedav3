<?php
// Incluir configuraciones PRIMERO
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Verificar rol correcto
if ($_SESSION['rol'] !== 'empresa_a') {
    header('Location: ../index.php');
    exit;
}

$empresa_id = $_SESSION['empresa_id'];

// Consultar si la empresa ya configuró disponibilidad
$stmt = $pdo->prepare("SELECT COUNT(*) FROM disponibilidad_empresas WHERE empresa_id = ?");
$stmt->execute([$empresa_id]);
$tieneDisponibilidad = $stmt->fetchColumn();

if ($tieneDisponibilidad == 0) {
    // Mostrar wizard en vez del panel
    include 'wizard-disponibilidad.php';
    exit;
}

// Ahora S� podemos usar las constantes
$pageTitle = "Panel de Demandantes - " . SITE_NAME;

// Verificar acceso
checkAccess([ROL_EMPRESA_A]);

// Obtener datos de la empresa
$empresaId = getEmpresaId();
// DEBUG: Verificar empresa ID y bloques
echo "<!-- DEBUG: Empresa ID = " . $empresaId . " -->";
try {
    $stmtDebug = $pdo->prepare("SELECT COUNT(*) as total FROM bloques_horarios WHERE empresa_id = ?");
    $stmtDebug->execute([$empresaId]);
    $totalBloques = $stmtDebug->fetchColumn();
    echo "<!-- DEBUG: Total bloques encontrados = " . $totalBloques . " -->";
} catch (PDOException $e) {
    echo "<!-- DEBUG ERROR: " . $e->getMessage() . " -->";
}
try {
    $stmt = $pdo->prepare("
        SELECT * FROM empresas 
        WHERE id = ? AND tipo = 'grande'
    ");
    $stmt->execute([$empresaId]);
    $empresa = $stmt->fetch();
    
    if (!$empresa) {
        die("Error: Empresa no encontrada");
    }
        
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

require_once '../includes/header.php';
$empresa_a_id = $_SESSION['empresa_id'];

// =================================================================
// CARGAR DATOS PARA EL PANEL
// =================================================================

// 1. Reuniones PENDIENTES (sin mesa asignada)
$stmt = $pdo->prepare("
    SELECT
        r.id,
        r.notas,
        r.fecha_solicitud,
        r.empresa_b_id,
        b.id as bloque_id,
        b.fecha,
        b.hora_inicio,
        b.hora_fin,
        b.orden,
        eb.nombre AS empresa_b_nombre,
        eb.rubro AS empresa_b_rubro,
        eb.email_contacto AS empresa_b_email,
        u.nombre_completo AS solicitante_nombre
    FROM reuniones r
    JOIN empresas eb ON eb.id = r.empresa_b_id
    JOIN bloques_horarios_globales b ON b.id = r.bloque_global_id
    LEFT JOIN usuarios u ON u.empresa_id = r.empresa_b_id
    WHERE r.empresa_a_id = ?
    AND r.estado = 'pendiente'
    ORDER BY b.orden ASC, r.fecha_solicitud ASC
");
$stmt->execute([$empresa_a_id]);
$reunionesPendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Reuniones CONFIRMADAS (con mesa asignada)
$stmt = $pdo->prepare("
    SELECT
        r.id,
        r.mesa_asignada,
        r.fecha_respuesta,
        b.fecha,
        b.hora_inicio,
        b.hora_fin,
        b.orden,
        eb.nombre AS empresa_b_nombre,
        eb.rubro AS empresa_b_rubro,
        eb.email_contacto AS empresa_b_email,
        eb.logo AS empresa_b_logo
    FROM reuniones r
    JOIN empresas eb ON eb.id = r.empresa_b_id
    JOIN bloques_horarios_globales b ON b.id = r.bloque_global_id
    WHERE r.empresa_a_id = ?
    AND r.estado = 'confirmada'
    ORDER BY b.orden ASC
");
$stmt->execute([$empresa_a_id]);
$reunionesConfirmadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Main Content -->
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Page Header -->
        <div class="mb-8 animate-fade-in">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Panel de Demandantes</h1>
            <p class="text-gray-600"><span class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">Modalidad: Busco Servicios</span></p>
            <p class="text-gray-500 text-sm mt-2">Gestiona tu agenda y reuniones del evento</p>
        </div>
        
        <!-- Layout Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            
            <!-- Sidebar: Perfil de la empresa -->
            <aside class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden sticky top-24 animate-slide-down">
                    <!-- Card Header -->
                    <div class="bg-gradient-to-r from-primary-500 to-purple-500 p-6 text-white">
                        <h2 class="text-lg font-bold flex items-center">
                            <i class="fas fa-building mr-2"></i>Mi Empresa
                        </h2>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="p-6 space-y-4">
                        <!-- Logo -->
                        <div class="flex justify-center">
                            <?php if ($empresa['logo']): ?>
                                <img src="<?php echo UPLOAD_URL . $empresa['logo']; ?>" 
                                     alt="Logo" 
                                     class="w-24 h-24 rounded-lg object-cover border-2 border-gray-200">
                            <?php else: ?>
                                <div class="w-24 h-24 bg-gradient-to-br from-primary-100 to-purple-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-building text-4xl text-primary-500"></i>
                                </div>
                            <?php endif; ?>
                    
                        </div>
 
                        
                        <!-- Info -->
                        <div class="space-y-3">
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Nombre</label>
                                <p class="text-sm font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($empresa['nombre']); ?></p>
                            </div>
                            
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Rubro</label>
                                <p class="text-sm text-gray-700 mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                        <?php echo htmlspecialchars($empresa['rubro'] ?? 'No especificado'); ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Email</label>
                                <p class="text-sm text-gray-700 mt-1 flex items-center">
                                    <i class="fas fa-envelope text-primary-500 mr-2 text-xs"></i>
                                    <?php echo htmlspecialchars($empresa['email_contacto']); ?>
                                </p>
                            </div>
                            
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tel�fono</label>
                                <p class="text-sm text-gray-700 mt-1 flex items-center">
                                    <i class="fas fa-phone text-primary-500 mr-2 text-xs"></i>
                                    <?php echo htmlspecialchars($empresa['telefono'] ?? 'No especificado'); ?>
                                </p>
                            </div>
                            
                            <?php if ($empresa['descripcion']): ?>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Descripci�n</label>
                                <p class="text-sm text-gray-700 mt-1"><?php echo nl2br(htmlspecialchars($empresa['descripcion'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                            <div class="pt-4 border-t border-gray-200">
        <button onclick="abrirModalEditarPerfil()" 
                class="w-full bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 flex items-center justify-center">
            <i class="fas fa-edit mr-2"></i>Editar Perfil
        </button>
    </div>
                    </div>
                </div>
            </aside>
            
            <!-- Main Content: Reuniones -->
            <main class="lg:col-span-3">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    
                    <!-- Tabs -->
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button onclick="switchTab('pendientes')" 
                                    class="tab-btn active flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors duration-200"
                                    data-tab="pendientes">
                                <i class="fas fa-clock mr-2"></i>
                                Solicitudes Pendientes
                                <?php if (count($reunionesPendientes) > 0): ?>
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <?php echo count($reunionesPendientes); ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                            
                            <button onclick="switchTab('confirmadas')" 
                                    class="tab-btn flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors duration-200"
                                    data-tab="confirmadas">
                                <i class="fas fa-check-circle mr-2"></i>
                                Reuniones Confirmadas
                                <?php if (count($reunionesConfirmadas) > 0): ?>
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <?php echo count($reunionesConfirmadas); ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                            
                            <button onclick="switchTab('agenda')" 
                                    class="tab-btn flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors duration-200"
                                    data-tab="agenda">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                Mi Agenda
                            </button>
                        </nav>
                    </div>
                    
                    <!-- Tab Content -->
                    <div class="p-6">
                        
                        <!-- Tab: Solicitudes Pendientes -->
                        <div id="tab-pendientes" class="tab-content">
                            <?php if (empty($reunionesPendientes)): ?>
                                <div class="text-center py-12">
                                    <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg">No tienes solicitudes pendientes</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($reunionesPendientes as $reunion): ?>
                                        <div id="reunion-<?php echo $reunion['id']; ?>" 
                                             class="bg-gradient-to-r from-yellow-50 to-orange-50 border-l-4 border-yellow-400 rounded-lg p-5 hover:shadow-md transition-shadow duration-200">
                                            <div class="flex justify-between items-start">
                                                <div class="flex-1">
                                                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                                                        <?php echo htmlspecialchars($reunion['empresa_b_nombre']); ?>
                                                    </h3>
                                                    <div class="space-y-1 text-sm text-gray-600">
                                                        <p><i class="fas fa-briefcase w-4 mr-2"></i><strong>Rubro:</strong> <?php echo htmlspecialchars($reunion['empresa_b_rubro'] ?? 'No especificado'); ?></p>
                                                        <p><i class="fas fa-user w-4 mr-2"></i><strong>Solicitado por:</strong> <?php echo htmlspecialchars($reunion['solicitante_nombre'] ?? 'Usuario'); ?></p>
                                                        <p><i class="fas fa-calendar w-4 mr-2"></i><strong>Fecha solicitud:</strong> <?php echo date('d/m/Y H:i', strtotime($reunion['fecha_solicitud'])); ?></p>
                                                    </div>
                                                    
                                                    <?php if ($reunion['notas']): ?>
                                                        <div class="mt-3 p-3 bg-white rounded-lg border border-yellow-200">
                                                            <p class="text-sm text-gray-700"><strong>Mensaje:</strong> <?php echo nl2br(htmlspecialchars($reunion['notas'])); ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="ml-4 flex-shrink-0">
                                                    <div class="bg-white px-4 py-2 rounded-lg shadow-sm border border-yellow-200">
                                                        <div class="text-primary-600 font-bold text-center">
                                                            <?php echo date('H:i', strtotime($reunion['hora_inicio'])); ?> - 
                                                            <?php echo date('H:i', strtotime($reunion['hora_fin'])); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500 text-center mt-1">
                                                            <?php echo date('d/m/Y', strtotime($reunion['fecha'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="flex gap-3 mt-4">
                                                <button onclick="responderReunion(<?php echo $reunion['id']; ?>, 'aceptar')" 
                                                        class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                                                    <i class="fas fa-check mr-2"></i>Aceptar
                                                </button>
                                                <button onclick="responderReunion(<?php echo $reunion['id']; ?>, 'rechazar')" 
                                                        class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                                                    <i class="fas fa-times mr-2"></i>Rechazar
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Tab: Reuniones Confirmadas -->
                        <div id="tab-confirmadas" class="tab-content hidden">
                            <?php if (empty($reunionesConfirmadas)): ?>
                                <div class="text-center py-12">
                                    <i class="fas fa-calendar-check text-6xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg">No tienes reuniones confirmadas a�n</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($reunionesConfirmadas as $reunion): ?>
                                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 rounded-lg p-5 hover:shadow-md transition-shadow duration-200">
                                            <div class="flex justify-between items-start">
                                                <div class="flex-1">
                                                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                                                        <?php echo htmlspecialchars($reunion['empresa_b_nombre']); ?>
                                                    </h3>
                                                    <div class="space-y-1 text-sm text-gray-600">
                                                        <p><i class="fas fa-briefcase w-4 mr-2"></i><strong>Rubro:</strong> <?php echo htmlspecialchars($reunion['empresa_b_rubro'] ?? 'No especificado'); ?></p>
                                                        <p><i class="fas fa-envelope w-4 mr-2"></i><strong>Email:</strong> <?php echo htmlspecialchars($reunion['empresa_b_email']); ?></p>
                                                        <p><i class="fas fa-calendar w-4 mr-2"></i><strong>Fecha:</strong> <?php echo formatearFecha($reunion['fecha']); ?></p>
                                                    </div>
                                                </div>
                                                
                                                <div class="ml-4 flex-shrink-0">
                                                    <div class="bg-white px-4 py-2 rounded-lg shadow-sm border border-green-200">
                                                        <div class="text-green-600 font-bold text-center">
                                                            <?php echo date('H:i', strtotime($reunion['hora_inicio'])); ?> - 
                                                            <?php echo date('H:i', strtotime($reunion['hora_fin'])); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500 text-center mt-1">
                                                            <i class="fas fa-check-circle text-green-500"></i> Confirmada
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Tab: Mi Agenda -->
<div id="tab-agenda" class="tab-content hidden">
    <?php
    // Obtener todos los bloques globales con disponibilidad y reuniones
    try {
        $stmt = $pdo->prepare("
            SELECT
                bg.id,
                bg.fecha,
                bg.hora_inicio,
                bg.hora_fin,
                bg.orden,
                de.disponible,
                r.id as reunion_id,
                r.estado as reunion_estado,
                r.mesa_asignada,
                eb.nombre as empresa_b_nombre,
                eb.logo as empresa_b_logo,
                eb.rubro as empresa_b_rubro
            FROM bloques_horarios_globales bg
            LEFT JOIN disponibilidad_empresas de ON de.bloque_id = bg.id AND de.empresa_id = ?
            LEFT JOIN reuniones r ON r.bloque_global_id = bg.id AND r.empresa_a_id = ? AND r.estado IN ('pendiente', 'confirmada')
            LEFT JOIN empresas eb ON r.empresa_b_id = eb.id
            ORDER BY bg.orden ASC
        ");
        $stmt->execute([$empresaId, $empresaId]);
        $bloques = $stmt->fetchAll();

        // Agrupar por fecha
        $bloquesAgrupados = [];
        foreach ($bloques as $bloque) {
            $fecha = $bloque['fecha'];
            if (!isset($bloquesAgrupados[$fecha])) {
                $bloquesAgrupados[$fecha] = [];
            }
            $bloquesAgrupados[$fecha][] = $bloque;
        }

    } catch (PDOException $e) {
        error_log("Error al cargar agenda: " . $e->getMessage());
        $bloquesAgrupados = [];
    }
    ?>
    
    <?php if (empty($bloquesAgrupados)): ?>
        <div class="text-center py-12">
            <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg mb-4">No hay bloques horarios configurados para el evento</p>
            <p class="text-sm text-gray-400">Contacta al administrador</p>
        </div>
    <?php else: ?>
        <!-- Resumen de agenda -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-green-700">
                    <?php
                    $disponibles = array_filter($bloques, function($b) {
                        return $b['disponible'] == 1 && empty($b['reunion_id']);
                    });
                    echo count($disponibles);
                    ?>
                </div>
                <div class="text-xs text-green-600 font-medium mt-1">Bloques Disponibles</div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-blue-700">
                    <?php
                    $confirmadas = array_filter($bloques, function($b) {
                        return $b['reunion_estado'] === 'confirmada';
                    });
                    echo count($confirmadas);
                    ?>
                </div>
                <div class="text-xs text-blue-600 font-medium mt-1">Reuniones Confirmadas</div>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-yellow-700">
                    <?php
                    $pendientes = array_filter($bloques, function($b) {
                        return $b['reunion_estado'] === 'pendiente';
                    });
                    echo count($pendientes);
                    ?>
                </div>
                <div class="text-xs text-yellow-600 font-medium mt-1">Solicitudes Pendientes</div>
            </div>
        </div>
        
        <!-- Agenda por fecha -->
        <div class="space-y-6">
            <?php foreach ($bloquesAgrupados as $fecha => $bloquesDia): ?>
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <!-- Header de fecha -->
                    <div class="bg-gradient-to-r from-primary-50 to-purple-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-primary-600 rounded-lg flex flex-col items-center justify-center text-white">
                                    <div class="text-xs font-semibold"><?php echo strtoupper(date('M', strtotime($fecha))); ?></div>
                                    <div class="text-lg font-bold"><?php echo date('d', strtotime($fecha)); ?></div>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">
                                        <?php 
                                        $dias = ['Domingo', 'Lunes', 'Martes', 'Mi�rcoles', 'Jueves', 'Viernes', 'S�bado'];
                                        echo $dias[date('w', strtotime($fecha))];
                                        ?>
                                    </h3>
                                    <p class="text-sm text-gray-600"><?php echo date('d \d\e F \d\e Y', strtotime($fecha)); ?></p>
                                </div>
                            </div>
                            <div class="text-sm text-gray-600">
                                <span class="font-semibold"><?php echo count($bloquesDia); ?></span> bloques
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bloques del d�a -->
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($bloquesDia as $bloque): ?>
                            <?php
                            // Determinar el estado visual del bloque
                            $estaDisponible = ($bloque['disponible'] == 1);
                            $tieneReunion = !empty($bloque['reunion_id']);
                            $tieneConfirmada = ($bloque['reunion_estado'] === 'confirmada');
                            $tienePendiente = ($bloque['reunion_estado'] === 'pendiente');

                            // Clases CSS según estado
                            if ($tieneConfirmada) {
                                $claseBloque = 'bg-blue-50 hover:bg-blue-100';
                                $iconoEstado = '<i class="fas fa-check-circle text-blue-600"></i>';
                                $textoEstado = '<span class="text-blue-700 font-semibold">Reunión Confirmada - Mesa ' . $bloque['mesa_asignada'] . '</span>';
                            } elseif ($tienePendiente) {
                                $claseBloque = 'bg-yellow-50 hover:bg-yellow-100';
                                $iconoEstado = '<i class="fas fa-clock text-yellow-600"></i>';
                                $textoEstado = '<span class="text-yellow-700 font-semibold">Solicitud Pendiente</span>';
                            } elseif ($estaDisponible && !$tieneReunion) {
                                $claseBloque = 'bg-green-50 hover:bg-green-100';
                                $iconoEstado = '<i class="fas fa-circle text-green-600"></i>';
                                $textoEstado = '<span class="text-green-700 font-semibold">Disponible</span>';
                            } else {
                                $claseBloque = 'bg-gray-50 hover:bg-gray-100';
                                $iconoEstado = '<i class="fas fa-ban text-gray-400"></i>';
                                $textoEstado = '<span class="text-gray-500">No Disponible</span>';
                            }
                            ?>
                            
                            <div class="<?php echo $claseBloque; ?> px-6 py-4 transition-colors duration-150">
                                <div class="flex items-center justify-between">
                                    <!-- Horario -->
                                    <div class="flex items-center space-x-4">
                                        <div class="text-center">
                                            <div class="text-lg font-bold text-gray-900">
                                                <?php echo date('H:i', strtotime($bloque['hora_inicio'])); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">a</div>
                                            <div class="text-lg font-bold text-gray-900">
                                                <?php echo date('H:i', strtotime($bloque['hora_fin'])); ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Divider -->
                                        <div class="w-px h-16 bg-gray-300"></div>
                                        
                                        <!-- Informaci�n de reunión o estado -->
                                        <div class="flex-1">
                                            <?php if (!empty($bloque['reunion_id']) && ($tieneConfirmada || $tienePendiente)): ?>
                                                <div class="flex items-center space-x-3">
                                                    <!-- Logo de la empresa -->
                                                    <?php if (!empty($bloque['empresa_b_logo'])): ?>
                                                        <img src="<?php echo UPLOAD_URL . $bloque['empresa_b_logo']; ?>" 
                                                             alt="<?php echo htmlspecialchars($bloque['empresa_b_nombre']); ?>"
                                                             class="w-12 h-12 rounded-lg object-cover border-2 border-white shadow">
                                                    <?php else: ?>
                                                        <div class="w-12 h-12 bg-gradient-to-br from-green-100 to-emerald-100 rounded-lg flex items-center justify-center">
                                                            <i class="fas fa-store text-green-600"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <div>
                                                        <div class="font-semibold text-gray-900">
                                                            <?php echo htmlspecialchars($bloque['empresa_b_nombre']); ?>
                                                        </div>
                                                        <div class="text-sm text-gray-600">
                                                            <?php echo htmlspecialchars($bloque['empresa_b_rubro'] ?? 'Sin rubro'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="flex items-center space-x-2">
                                                    <?php echo $iconoEstado; ?>
                                                    <?php echo $textoEstado; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Badge de estado -->
                                    <div>
                                        <?php if ($tieneConfirmada): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <i class="fas fa-check-circle mr-1"></i> Confirmada
                                            </span>
                                        <?php elseif ($tienePendiente): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-clock mr-1"></i> Pendiente
                                            </span>
                                        <?php elseif ($estaDisponible && !$tieneReunion): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-circle mr-1"></i> Disponible
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
                        
                    </div>
                </div>
            </main>
            
        </div>
    </div>
</div>

                                <!-- Modal: Editar Perfil -->
<div id="modalEditarPerfil" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
        <div class="bg-gradient-to-r from-primary-600 to-purple-600 p-6 text-white">
            <h2 class="text-2xl font-bold flex items-center">
                <i class="fas fa-user-edit mr-3"></i>Editar Perfil Empresarial
            </h2>
        </div>
        
        <!-- Tabs del Modal -->
        <div class="border-b border-gray-200">
            <nav class="flex">
                <button onclick="switchModalTab('datos')" 
                        class="modal-tab-btn active flex-1 py-3 px-4 text-center border-b-2 font-medium text-sm"
                        data-modal-tab="datos">
                    <i class="fas fa-info-circle mr-2"></i>Datos
                </button>
                <button onclick="switchModalTab('logo')" 
                        class="modal-tab-btn flex-1 py-3 px-4 text-center border-b-2 font-medium text-sm"
                        data-modal-tab="logo">
                    <i class="fas fa-image mr-2"></i>Logo
                </button>
            </nav>
        </div>
        
        <!-- Contenido del Modal -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-180px)]">
            
            <!-- Tab: Datos -->
            <div id="modal-tab-datos" class="modal-tab-content">
                <form id="formActualizarDatos" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Nombre de la Empresa *
                        </label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($empresa['nombre']); ?>" 
                               required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Rubro
                        </label>
                        <input type="text" name="rubro" value="<?php echo htmlspecialchars($empresa['rubro'] ?? ''); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                               placeholder="Ej: Miner�a, Agricultura, Tecnolog�a">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Email de Contacto *
                        </label>
                        <input type="email" name="email_contacto" value="<?php echo htmlspecialchars($empresa['email_contacto']); ?>" 
                               required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Tel�fono
                        </label>
                        <input type="tel" name="telefono" value="<?php echo htmlspecialchars($empresa['telefono'] ?? ''); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                               placeholder="+56 9 1234 5678">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Direccion
                        </label>
                        <input type="text" name="direccion" value="<?php echo htmlspecialchars($empresa['direccion'] ?? ''); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                               placeholder="Calle, Ciudad, Regi�n">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Descripci�n
                        </label>
                        <textarea name="descripcion" rows="4" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
                                  placeholder="Describe tu empresa, productos o servicios..."><?php echo htmlspecialchars($empresa['descripcion'] ?? ''); ?></textarea>
                    </div>
                    <!-- Tags de B�squeda -->
<!-- Tags de B�squeda -->
<div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
        <i class="fas fa-tags mr-1 text-primary-600"></i>
        &iquest;Qu&eacute; buscas en las empresas proveedoras? (M&aacute;ximo 5 etiquetas)
    </label>
    <div id="tags-container" class="flex flex-wrap gap-2 p-3 border border-gray-300 rounded-lg bg-gray-50 min-h-[60px]">
        <!-- Los tags se insertar&aacute;n aqu&iacute; din&aacute;micamente -->
    </div>
    <div class="mt-2 flex gap-2">
        <input 
            type="text" 
            id="tag-input" 
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
            placeholder="Ej: aire acondicionado, proveedor, mantenimiento..."
            maxlength="50"
        >
        <button 
            type="button" 
            onclick="agregarTag()" 
            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors"
        >
            <i class="fas fa-plus mr-1"></i>Agregar
        </button>
    </div>
    <p class="text-xs text-gray-500 mt-2">
        <i class="fas fa-info-circle mr-1"></i>
        Presiona Enter o haz clic en "Agregar" para a&ntilde;adir etiquetas. Estas ayudar&aacute;n a las PYMEs a encontrarte.
    </p>
    <input type="hidden" name="tags_busqueda" id="tags-hidden">
</div>
                    <div class="flex gap-3 pt-4">
                        <button type="submit" 
                                class="flex-1 bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            <i class="fas fa-save mr-2"></i>Guardar Cambios
                        </button>
                        <button type="button" onclick="cerrarModalPerfil()" 
                                class="px-6 py-3 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Tab: Logo -->
            <div id="modal-tab-logo" class="modal-tab-content hidden">
                <div class="text-center mb-6">
                    <div class="inline-block relative">
                        <?php if ($empresa['logo']): ?>
                            <img id="preview-logo" src="<?php echo UPLOAD_URL . $empresa['logo']; ?>" 
                                 alt="Logo" class="w-40 h-40 rounded-lg object-cover border-2 border-gray-200">
                        <?php else: ?>
                            <div id="preview-logo" class="w-40 h-40 bg-gradient-to-br from-primary-100 to-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-building text-6xl text-primary-500"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <p class="text-sm text-gray-500 mt-3">Logo actual</p>
                </div>
                
                <form id="formActualizarLogo" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Subir Nuevo Logo
                        </label>
                        <input type="file" name="logo" accept="image/*" required
                               onchange="previsualizarLogo(event)"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Formatos: JPG, PNG, GIF, WEBP. M�ximo 5MB
                        </p>
                    </div>
                    
                    <div id="nueva-preview" class="hidden text-center">
                        <p class="text-sm font-semibold text-gray-700 mb-2">Vista previa:</p>
                        <img id="nueva-preview-img" class="w-40 h-40 rounded-lg object-cover border-2 border-primary-300 mx-auto">
                    </div>
                    
                    <div class="flex gap-3 pt-4">
                        <button type="submit" 
                                class="flex-1 bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            <i class="fas fa-upload mr-2"></i>Subir Logo
                        </button>
                        <button type="button" onclick="cerrarModalPerfil()" 
                                class="px-6 py-3 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>
</div>
                                
<script>
                        
                        // Funciones para edici�n de perfil
function abrirModalEditarPerfil() {
    const modal = document.getElementById('modalEditarPerfil');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Cargar tags existentes
    cargarTagsExistentes();
}

function cerrarModalPerfil() {
    const modal = document.getElementById('modalEditarPerfil');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    
    // Resetear formularios
    document.getElementById('formActualizarDatos').reset();
    document.getElementById('formActualizarLogo').reset();
    document.getElementById('nueva-preview').classList.add('hidden');
}

function switchModalTab(tabName) {
    // Remover active de todos los botones
    document.querySelectorAll('.modal-tab-btn').forEach(btn => {
        btn.classList.remove('active', 'text-primary-600', 'border-primary-600');
        btn.classList.add('text-gray-500', 'border-transparent');
    });
    
    // Ocultar todos los contenidos
    document.querySelectorAll('.modal-tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Activar tab seleccionado
    const activeBtn = document.querySelector(`[data-modal-tab="${tabName}"]`);
    activeBtn.classList.remove('text-gray-500', 'border-transparent');
    activeBtn.classList.add('active', 'text-primary-600', 'border-primary-600');
    
    // Mostrar contenido seleccionado
    document.getElementById('modal-tab-' + tabName).classList.remove('hidden');
}

function previsualizarLogo(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('nueva-preview-img').src = e.target.result;
            document.getElementById('nueva-preview').classList.remove('hidden');
        }
        reader.readAsDataURL(file);
    }
}

// Formulario actualizar datos
document.getElementById('formActualizarDatos').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('accion', 'actualizar_datos');
    
    fetch('<?php echo BASE_URL; ?>api/actualizar_perfil.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('? ' + data.message);
            location.reload();
        } else {
            alert('? ' + data.message);
        }
    })
    .catch(error => {
        alert('? Error de conexi�n');
        console.error(error);
    });
});

// Formulario actualizar logo
document.getElementById('formActualizarLogo').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('accion', 'actualizar_logo');
    
    fetch('<?php echo BASE_URL; ?>api/actualizar_perfil.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('? ' + data.message);
            location.reload();
        } else {
            alert('? ' + data.message);
        }
    })
    .catch(error => {
        alert('? Error de conexi�n');
        console.error(error);
    });
});

// Cerrar modal al hacer clic fuera
document.getElementById('modalEditarPerfil').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalPerfil();
    }
});
// Gesti�n de tabs
function switchTab(tabName) {
    // Remover active de todos los botones
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active', 'text-primary-600', 'border-primary-600');
        btn.classList.add('text-gray-500', 'border-transparent', 'hover:text-gray-700', 'hover:border-gray-300');
    });
    
    // Ocultar todos los contenidos
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Activar tab seleccionado
    const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
    activeBtn.classList.remove('text-gray-500', 'border-transparent', 'hover:text-gray-700', 'hover:border-gray-300');
    activeBtn.classList.add('active', 'text-primary-600', 'border-primary-600');
    
    // Mostrar contenido seleccionado
    document.getElementById('tab-' + tabName).classList.remove('hidden');
}

// Responder a solicitud de reuni�n
function responderReunion(reunionId, accion) {
    const mensajes = {
        'aceptar': '�Est� seguro que desea aceptar esta solicitud?',
        'rechazar': '�Est� seguro que desea rechazar esta solicitud?'
    };
    
    if (!confirm(mensajes[accion])) {
        return;
    }
    
    const formData = new FormData();
    formData.append('reunion_id', reunionId);
    formData.append('accion', accion);
    
    fetch('<?php echo BASE_URL; ?>api/reuniones.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar mensaje de �xito
            alert('? ' + data.message);
            location.reload();
        } else {
            alert('? Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('? Error de conexi�n');
        console.error(error);
    });
}

// Inicializar primera tab al cargar
document.addEventListener('DOMContentLoaded', function() {
    const firstBtn = document.querySelector('.tab-btn[data-tab="pendientes"]');
    firstBtn.classList.add('text-primary-600', 'border-primary-600');
});
// ========== SISTEMA DE TAGS ==========

// Array para almacenar los tags
let tags = [];

// Cargar tags existentes al abrir el modal
function cargarTagsExistentes() {
    const tagsEmpresa = <?php echo json_encode(json_decode($empresa['tags_busqueda'] ?? '[]')); ?>;
    tags = Array.isArray(tagsEmpresa) ? tagsEmpresa : [];
    renderizarTags();
}

// Renderizar tags en el contenedor
function renderizarTags() {
    const container = document.getElementById('tags-container');
    const hiddenInput = document.getElementById('tags-hidden');
    
    if (!container) return;
    
    if (tags.length === 0) {
        container.innerHTML = '<span class="text-gray-400 text-sm">No has agregado etiquetas a�n</span>';
    } else {
        container.innerHTML = tags.map((tag, index) => `
            <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary-100 text-primary-800 rounded-full text-sm font-medium">
                <i class="fas fa-tag text-xs"></i>
                ${escapeHtml(tag)}
                <button 
                    type="button" 
                    onclick="eliminarTag(${index})" 
                    class="ml-1 hover:text-red-600 transition-colors"
                    title="Eliminar etiqueta"
                >
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
        `).join('');
    }
    
    if (hiddenInput) {
        hiddenInput.value = JSON.stringify(tags);
    }
}

// Agregar tag
function agregarTag() {
    const input = document.getElementById('tag-input');
    const tag = input.value.trim().toLowerCase();
    
    if (!tag) {
        alert('?? Por favor escribe una etiqueta');
        return;
    }
    
    if (tags.length >= 5) {
        alert('?? M�ximo 5 etiquetas permitidas');
        return;
    }
    
    if (tags.includes(tag)) {
        alert('?? Esta etiqueta ya existe');
        input.value = '';
        return;
    }
    
    if (tag.length > 50) {
        alert('?? La etiqueta es demasiado larga (m�ximo 50 caracteres)');
        return;
    }
    
    tags.push(tag);
    renderizarTags();
    input.value = '';
    input.focus();
}

// Eliminar tag
function eliminarTag(index) {
    tags.splice(index, 1);
    renderizarTags();
}

// Funci�n auxiliar para escapar HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Event listener para Enter en el input de tags
document.addEventListener('DOMContentLoaded', function() {
    const tagInput = document.getElementById('tag-input');
    if (tagInput) {
        tagInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                agregarTag();
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>