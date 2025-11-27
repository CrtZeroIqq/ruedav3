<?php
// Incluir configuraciones PRIMERO
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Ahora SÍ podemos usar las constantes
$pageTitle = "Panel Administrador - " . SITE_NAME;

// Verificar acceso
checkAccess([ROL_ADMIN]);

try {
    // Obtener estadísticas generales
    $stats = [];
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM empresas WHERE activo = 1");
    $stats['total_empresas'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM empresas WHERE tipo = 'grande' AND activo = 1");
    $stats['empresas_grandes'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM empresas WHERE tipo = 'pyme' AND activo = 1");
    $stats['empresas_pymes'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT estado, COUNT(*) as total FROM reuniones GROUP BY estado");
    $reunionesPorEstado = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $stats['reuniones_pendientes'] = $reunionesPorEstado['pendiente'] ?? 0;
    $stats['reuniones_confirmadas'] = $reunionesPorEstado['confirmada'] ?? 0;
    $stats['reuniones_rechazadas'] = $reunionesPorEstado['rechazada'] ?? 0;
    $stats['reuniones_canceladas'] = $reunionesPorEstado['cancelada'] ?? 0;
    $stats['total_reuniones'] = array_sum($reunionesPorEstado);
    
    // Calcular slots totales y disponibles en el sistema de mesas
    $stmt = $pdo->query("SELECT COUNT(*) FROM bloques_horarios_globales");
    $totalBloques = $stmt->fetchColumn();
    $totalSlots = $totalBloques * 15; // 15 mesas por cada bloque

    $stmt = $pdo->query("SELECT COUNT(*) FROM disponibilidad_empresas WHERE disponible = 1");
    $slotsReservados = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM reuniones WHERE estado = 'confirmada'");
    $slotsOcupados = $stmt->fetchColumn();

    $stats['bloques_disponibles'] = $slotsReservados - $slotsOcupados;
    $stats['bloques_ocupados'] = $slotsOcupados;
    $stats['total_slots'] = $totalSlots;
    $stats['slots_reservados'] = $slotsReservados;
    
    // Obtener todas las empresas
    $stmt = $pdo->query("
        SELECT * FROM empresas 
        WHERE activo = 1
        ORDER BY tipo DESC, nombre
    ");
    $empresas = $stmt->fetchAll();
    
    // Obtener todas las reuniones
    $stmt = $pdo->query("
        SELECT
            r.*,
            ea.nombre as empresa_a_nombre,
            ea.rubro as empresa_a_rubro,
            ea.email_contacto as empresa_a_email,
            eb.nombre as empresa_b_nombre,
            eb.rubro as empresa_b_rubro,
            eb.email_contacto as empresa_b_email,
            bg.fecha,
            bg.hora_inicio,
            bg.hora_fin,
            bg.orden,
            r.mesa_asignada
        FROM reuniones r
        INNER JOIN empresas ea ON r.empresa_a_id = ea.id
        INNER JOIN empresas eb ON r.empresa_b_id = eb.id
        INNER JOIN bloques_horarios_globales bg ON r.bloque_global_id = bg.id
        ORDER BY bg.fecha DESC, bg.orden ASC, r.mesa_asignada ASC
    ");
    $reuniones = $stmt->fetchAll();

    // Obtener todos los bloques globales
    $stmt = $pdo->query("SELECT * FROM bloques_horarios_globales ORDER BY orden");
    $bloquesGlobales = $stmt->fetchAll();

    // Obtener mapa de disponibilidad (mesa x bloque)
    $stmt = $pdo->query("
        SELECT
            de.mesa_numero,
            de.bloque_id,
            e.id as empresa_id,
            e.nombre as empresa_nombre,
            e.tipo as empresa_tipo,
            COUNT(r.id) as reuniones_confirmadas
        FROM disponibilidad_empresas de
        INNER JOIN empresas e ON e.id = de.empresa_id
        LEFT JOIN reuniones r ON r.empresa_a_id = e.id
            AND r.bloque_global_id = de.bloque_id
            AND r.estado = 'confirmada'
        WHERE de.disponible = 1
        GROUP BY de.mesa_numero, de.bloque_id, e.id
        ORDER BY de.mesa_numero, de.bloque_id
    ");
    $mapaDisponibilidad = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

require_once '../includes/header.php';
?>

<!-- Main Content -->
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Page Header -->
        <div class="mb-8 animate-fade-in">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Panel de Administración</h1>
            <p class="text-gray-600">Control y gestión del evento Rueda de Negocios</p>
        </div>
        
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <!-- Stat Card 1 -->
            <div class="bg-gradient-to-br from-primary-500 to-purple-600 rounded-xl p-6 text-white shadow-lg transform hover:scale-105 transition-transform duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90 mb-1">Empresas Totales</p>
                        <p class="text-3xl font-bold"><?php echo $stats['total_empresas']; ?></p>
                    </div>
                    <i class="fas fa-building text-4xl opacity-80"></i>
                </div>
            </div>
            
            <!-- Stat Card 2 -->
            <div class="bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl p-6 text-white shadow-lg transform hover:scale-105 transition-transform duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90 mb-1">Solicitudes Pendientes</p>
                        <p class="text-3xl font-bold"><?php echo $stats['reuniones_pendientes']; ?></p>
                    </div>
                    <i class="fas fa-clock text-4xl opacity-80"></i>
                </div>
            </div>
            
            <!-- Stat Card 3 -->
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-6 text-white shadow-lg transform hover:scale-105 transition-transform duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90 mb-1">Reuniones Confirmadas</p>
                        <p class="text-3xl font-bold"><?php echo $stats['reuniones_confirmadas']; ?></p>
                    </div>
                    <i class="fas fa-check-circle text-4xl opacity-80"></i>
                </div>
            </div>
            
            <!-- Stat Card 4 -->
            <div class="bg-gradient-to-br from-red-500 to-pink-600 rounded-xl p-6 text-white shadow-lg transform hover:scale-105 transition-transform duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90 mb-1">Reuniones Rechazadas</p>
                        <p class="text-3xl font-bold"><?php echo $stats['reuniones_rechazadas']; ?></p>
                    </div>
                    <i class="fas fa-times-circle text-4xl opacity-80"></i>
                </div>
            </div>
            
            <!-- Stat Card 5 -->
            <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl p-6 text-white shadow-lg transform hover:scale-105 transition-transform duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90 mb-1">Bloques Disponibles</p>
                        <p class="text-3xl font-bold"><?php echo $stats['bloques_disponibles']; ?></p>
                    </div>
                    <i class="fas fa-calendar-check text-4xl opacity-80"></i>
                </div>
            </div>
        </div>
        
        <!-- Main Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            
            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px flex-wrap">
                    <button onclick="switchTab('reuniones')"
                            class="tab-btn active flex-1 py-4 px-4 text-center border-b-2 font-medium text-sm transition-colors duration-200"
                            data-tab="reuniones">
                        <i class="fas fa-handshake mr-1"></i>Reuniones
                    </button>

                    <button onclick="switchTab('mapa')"
                            class="tab-btn flex-1 py-4 px-4 text-center border-b-2 font-medium text-sm transition-colors duration-200"
                            data-tab="mapa">
                        <i class="fas fa-table-cells mr-1"></i>Mapa Mesas
                    </button>

                    <button onclick="switchTab('empresas')"
                            class="tab-btn flex-1 py-4 px-4 text-center border-b-2 font-medium text-sm transition-colors duration-200"
                            data-tab="empresas">
                        <i class="fas fa-building mr-1"></i>Empresas
                    </button>

                    <button onclick="switchTab('crear')"
                            class="tab-btn flex-1 py-4 px-4 text-center border-b-2 font-medium text-sm transition-colors duration-200"
                            data-tab="crear">
                        <i class="fas fa-plus-circle mr-1"></i>Crear Reunión
                    </button>

                    <button onclick="switchTab('exportar')"
                            class="tab-btn flex-1 py-4 px-4 text-center border-b-2 font-medium text-sm transition-colors duration-200"
                            data-tab="exportar">
                        <i class="fas fa-download mr-1"></i>Exportar
                    </button>
                </nav>
            </div>
            
            <!-- Tab Content -->
            <div class="p-6">
                
                <!-- Tab: Todas las Reuniones -->
<div id="tab-reuniones" class="tab-content">
    <?php if (empty($reuniones)): ?>
        <div class="text-center py-12">
            <i class="fas fa-handshake text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg">No hay reuniones registradas</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Demandante</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Oferente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mesa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($reuniones as $reunion): ?>
                        <tr id="reunion-row-<?php echo $reunion['id']; ?>" class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                #<?php echo $reunion['id']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($reunion['empresa_a_nombre']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($reunion['empresa_a_rubro'] ?? ''); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($reunion['empresa_b_nombre']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($reunion['empresa_b_rubro'] ?? ''); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-purple-100 text-purple-800">
                                    <i class="fas fa-table-cells mr-1"></i><?php echo $reunion['mesa_asignada'] ?? 'N/A'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d/m/Y', strtotime($reunion['fecha'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('H:i', strtotime($reunion['hora_inicio'])); ?> - 
                                <?php echo date('H:i', strtotime($reunion['hora_fin'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $estadoBadges = [
                                    'pendiente' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"><i class="fas fa-clock mr-1"></i>Pendiente</span>',
                                    'confirmada' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i>Confirmada</span>',
                                    'rechazada' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"><i class="fas fa-times-circle mr-1"></i>Rechazada</span>',
                                    'cancelada' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"><i class="fas fa-ban mr-1"></i>Cancelada</span>'
                                ];
                                echo $estadoBadges[$reunion['estado']] ?? $reunion['estado'];
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center space-x-2">
                                    <?php if ($reunion['estado'] === 'pendiente' || $reunion['estado'] === 'confirmada'): ?>
                                        <!-- Botón Editar -->
                                        <button onclick="abrirModalEditar(<?php echo htmlspecialchars(json_encode($reunion)); ?>)" 
                                                class="text-blue-600 hover:text-blue-900 font-medium" title="Editar reunión">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <!-- Botón Reprogramar -->
                                        <button onclick="abrirModalReprogramar(<?php echo $reunion['id']; ?>, <?php echo $reunion['empresa_a_id']; ?>)" 
                                                class="text-purple-600 hover:text-purple-900 font-medium" title="Cambiar horario">
                                            <i class="fas fa-clock"></i>
                                        </button>
                                        
                                        <!-- Botón Reasignar -->
                                        <button onclick="abrirModalReasignar(<?php echo $reunion['id']; ?>, '<?php echo $reunion['empresa_b_nombre']; ?>')" 
                                                class="text-green-600 hover:text-green-900 font-medium" title="Reasignar pyme">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                        
                                        <!-- Botón Cancelar -->
                                        <button onclick="cancelarReunion(<?php echo $reunion['id']; ?>)" 
                                                class="text-red-600 hover:text-red-900 font-medium" title="Cancelar reunión">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

                <!-- Tab: Mapa de Mesas -->
                <div id="tab-mapa" class="tab-content hidden">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Mapa de Ocupación de Mesas</h3>
                        <p class="text-gray-600">Vista en tiempo real de las 15 mesas y 4 bloques horarios</p>
                    </div>

                    <?php
                    // Crear matriz de disponibilidad
                    $matriz = [];
                    foreach ($mapaDisponibilidad as $slot) {
                        $key = $slot['mesa_numero'] . '_' . $slot['bloque_id'];
                        $matriz[$key] = $slot;
                    }
                    ?>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gradient-to-r from-primary-500 to-purple-600 text-white">
                                    <th class="border border-gray-300 px-3 py-3 text-center font-bold">
                                        <i class="fas fa-table-cells mr-2"></i>MESA
                                    </th>
                                    <?php foreach ($bloquesGlobales as $bloque): ?>
                                        <th class="border border-gray-300 px-3 py-3 text-center min-w-[180px]">
                                            <div class="font-bold"><?php echo date('H:i', strtotime($bloque['hora_inicio'])); ?> - <?php echo date('H:i', strtotime($bloque['hora_fin'])); ?></div>
                                            <div class="text-xs opacity-90">Bloque <?php echo $bloque['orden']; ?></div>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($mesa = 1; $mesa <= 15; $mesa++): ?>
                                    <tr class="<?php echo ($mesa % 2 == 0) ? 'bg-gray-50' : 'bg-white'; ?>">
                                        <td class="border border-gray-300 px-4 py-3 text-center font-bold text-lg bg-gray-100">
                                            Mesa <?php echo $mesa; ?>
                                        </td>
                                        <?php foreach ($bloquesGlobales as $bloque): ?>
                                            <?php
                                            $key = $mesa . '_' . $bloque['id'];
                                            $ocupada = isset($matriz[$key]);
                                            ?>
                                            <td class="border border-gray-300 p-2 text-center <?php echo $ocupada ? 'bg-blue-50' : 'bg-gray-50'; ?>">
                                                <?php if ($ocupada): ?>
                                                    <div class="bg-blue-100 border-2 border-blue-400 rounded-lg p-2">
                                                        <div class="font-semibold text-sm text-blue-900">
                                                            <i class="fas fa-building mr-1"></i><?php echo htmlspecialchars(substr($matriz[$key]['empresa_nombre'], 0, 20)); ?>
                                                        </div>
                                                        <div class="text-xs text-blue-700 mt-1">
                                                            <?php echo $matriz[$key]['empresa_tipo'] === 'grande' ? 'Demandante' : 'Oferente'; ?>
                                                        </div>
                                                        <?php if ($matriz[$key]['reuniones_confirmadas'] > 0): ?>
                                                            <div class="mt-1">
                                                                <span class="bg-green-500 text-white text-xs px-2 py-0.5 rounded-full">
                                                                    <i class="fas fa-check-circle"></i> <?php echo $matriz[$key]['reuniones_confirmadas']; ?> reunión(es)
                                                                </span>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="mt-2 flex gap-1 justify-center">
                                                            <button onclick="verDetalleSlot(<?php echo $mesa; ?>, <?php echo $bloque['id']; ?>, <?php echo $matriz[$key]['empresa_id']; ?>)"
                                                                    class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-2 py-1 rounded"
                                                                    title="Ver detalles">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button onclick="liberarSlot(<?php echo $mesa; ?>, <?php echo $bloque['id']; ?>, <?php echo $matriz[$key]['empresa_id']; ?>)"
                                                                    class="bg-red-600 hover:bg-red-700 text-white text-xs px-2 py-1 rounded"
                                                                    title="Liberar slot">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-gray-400 py-4">
                                                        <i class="fas fa-minus"></i>
                                                        <div class="text-xs mt-1">Disponible</div>
                                                        <button onclick="asignarSlot(<?php echo $mesa; ?>, <?php echo $bloque['id']; ?>)"
                                                                class="mt-2 bg-green-600 hover:bg-green-700 text-white text-xs px-2 py-1 rounded"
                                                                title="Asignar empresa">
                                                            <i class="fas fa-plus"></i> Asignar
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 bg-gray-100 rounded-lg p-4">
                        <h4 class="font-bold text-gray-900 mb-3">Leyenda</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                            <div class="flex items-center">
                                <div class="w-4 h-4 bg-gray-50 border border-gray-300 mr-2"></div>
                                <span>Disponible</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-4 h-4 bg-blue-100 border-2 border-blue-400 mr-2"></div>
                                <span>Reservado</span>
                            </div>
                            <div class="flex items-center">
                                <span class="bg-green-500 text-white text-xs px-2 py-0.5 rounded-full mr-2">✓ N</span>
                                <span>Con reuniones confirmadas</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-eye text-blue-600 mr-2"></i>
                                <span>Ver detalles</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Empresas Participantes -->
                <div id="tab-empresas" class="tab-content hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rubro</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($empresas as $empresa): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #<?php echo $empresa['id']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($empresa['nombre']); ?></div>
                                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $empresa['tipo'] === 'grande' ? 'bg-primary-100 text-primary-800' : 'bg-green-100 text-green-800'; ?>">
                                                    <?php echo $empresa['tipo'] === 'grande' ? 'GRANDE' : 'PYME'; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo ucfirst($empresa['tipo']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($empresa['rubro'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($empresa['email_contacto']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($empresa['telefono'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($empresa['activo']): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-check mr-1"></i>Activa
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <i class="fas fa-times mr-1"></i>Inactiva
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Crear Reunión Manual -->
                <div id="tab-crear" class="tab-content hidden">
                    <div class="max-w-3xl mx-auto">
                        <div class="mb-6">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">
                                <i class="fas fa-plus-circle mr-2 text-primary-600"></i>Crear Reunión Manual
                            </h3>
                            <p class="text-gray-600">Como administrador, puedes crear reuniones directamente sin restricciones de bloques</p>
                        </div>

                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                            <div class="flex">
                                <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-3"></i>
                                <div>
                                    <p class="font-bold text-yellow-800">Privilegios de Administrador</p>
                                    <p class="text-sm text-yellow-700">Puedes asignar más de 2 bloques a una empresa demandante y crear reuniones en cualquier mesa/horario disponible.</p>
                                </div>
                            </div>
                        </div>

                        <form id="formCrearReunion" class="space-y-6 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                            <!-- Empresa Demandante (A) -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    <i class="fas fa-building mr-2 text-blue-600"></i>Empresa Demandante (Busco Servicios)
                                </label>
                                <select name="empresa_a_id" id="crear_empresa_a" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                        onchange="cargarDisponibilidadAdmin(this.value)">
                                    <option value="">Seleccione una empresa...</option>
                                    <?php
                                    $stmtGrandes = $pdo->query("SELECT id, nombre, rubro FROM empresas WHERE tipo = 'grande' AND activo = 1 ORDER BY nombre");
                                    while ($grande = $stmtGrandes->fetch()): ?>
                                        <option value="<?php echo $grande['id']; ?>">
                                            <?php echo htmlspecialchars($grande['nombre']); ?>
                                            <?php if ($grande['rubro']): ?>
                                                - <?php echo htmlspecialchars($grande['rubro']); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Mesa y Bloque -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">
                                        <i class="fas fa-table-cells mr-2 text-purple-600"></i>Mesa
                                    </label>
                                    <select name="mesa_numero" id="crear_mesa" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        <option value="">Seleccione mesa...</option>
                                        <?php for ($i = 1; $i <= 15; $i++): ?>
                                            <option value="<?php echo $i; ?>">Mesa <?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">
                                        <i class="fas fa-clock mr-2 text-green-600"></i>Bloque Horario
                                    </label>
                                    <select name="bloque_id" id="crear_bloque" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        <option value="">Seleccione bloque...</option>
                                        <?php foreach ($bloquesGlobales as $bloque): ?>
                                            <option value="<?php echo $bloque['id']; ?>">
                                                <?php echo date('H:i', strtotime($bloque['hora_inicio'])); ?> -
                                                <?php echo date('H:i', strtotime($bloque['hora_fin'])); ?>
                                                (Bloque <?php echo $bloque['orden']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Empresa Oferente (B) -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    <i class="fas fa-handshake mr-2 text-green-600"></i>Empresa Oferente (Ofrezco Servicios)
                                </label>
                                <select name="empresa_b_id" id="crear_empresa_b" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">Seleccione una empresa...</option>
                                    <?php
                                    $stmtPymes2 = $pdo->query("SELECT id, nombre, rubro FROM empresas WHERE tipo = 'pyme' AND activo = 1 ORDER BY nombre");
                                    while ($pyme = $stmtPymes2->fetch()): ?>
                                        <option value="<?php echo $pyme['id']; ?>">
                                            <?php echo htmlspecialchars($pyme['nombre']); ?>
                                            <?php if ($pyme['rubro']): ?>
                                                - <?php echo htmlspecialchars($pyme['rubro']); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Estado -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600"></i>Estado Inicial
                                </label>
                                <select name="estado" id="crear_estado" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <option value="pendiente">Pendiente (requiere confirmación)</option>
                                    <option value="confirmada">Confirmada (directa)</option>
                                </select>
                            </div>

                            <!-- Notas -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    <i class="fas fa-sticky-note mr-2 text-gray-600"></i>Notas / Observaciones
                                </label>
                                <textarea name="notas" rows="3"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                          placeholder="Información adicional sobre esta reunión..."></textarea>
                            </div>

                            <div class="flex gap-3 pt-4">
                                <button type="submit"
                                        class="flex-1 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white px-6 py-3 rounded-lg font-bold transition-all duration-200 transform hover:scale-105">
                                    <i class="fas fa-save mr-2"></i>Crear Reunión
                                </button>
                                <button type="reset"
                                        class="px-6 py-3 border-2 border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                                    <i class="fas fa-undo mr-2"></i>Limpiar
                                </button>
                            </div>
                        </form>

                        <div id="disponibilidadInfo" class="mt-6 hidden">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="font-bold text-blue-900 mb-2">
                                    <i class="fas fa-info-circle mr-2"></i>Disponibilidad Actual
                                </h4>
                                <div id="disponibilidadContent" class="text-sm text-blue-800"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Exportar -->
                <div id="tab-exportar" class="tab-content hidden">
                    <div class="max-w-3xl mx-auto">
                        <div class="text-center mb-8">
                            <i class="fas fa-download text-6xl text-primary-500 mb-4"></i>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">Exportar Datos del Evento</h3>
                            <p class="text-gray-600">Descarga los datos en formato CSV para análisis o respaldo</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                            <button onclick="exportarCSV('reuniones')" 
                                    class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white p-6 rounded-xl shadow-lg transform hover:scale-105 transition-all duration-200">
                                <i class="fas fa-file-csv text-3xl mb-3"></i>
                                <div class="font-bold text-lg">Todas las Reuniones</div>
                                <div class="text-sm opacity-90">Exportar CSV</div>
                            </button>
                            
                            <button onclick="exportarCSV('confirmadas')" 
                                    class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white p-6 rounded-xl shadow-lg transform hover:scale-105 transition-all duration-200">
                                <i class="fas fa-check-circle text-3xl mb-3"></i>
                                <div class="font-bold text-lg">Solo Confirmadas</div>
                                <div class="text-sm opacity-90">Exportar CSV</div>
                            </button>
                            
                            <button onclick="exportarCSV('empresas')" 
                                    class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white p-6 rounded-xl shadow-lg transform hover:scale-105 transition-all duration-200">
                                <i class="fas fa-building text-3xl mb-3"></i>
                                <div class="font-bold text-lg">Listado de Empresas</div>
                                <div class="text-sm opacity-90">Exportar CSV</div>
                            </button>
                        </div>
                        
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200">
                            <h4 class="font-bold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-chart-bar mr-2 text-primary-600"></i>
                                Estadísticas Generales
                            </h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div><strong>Empresas grandes:</strong> <?php echo $stats['empresas_grandes']; ?></div>
                                <div><strong>Pymes:</strong> <?php echo $stats['empresas_pymes']; ?></div>
                                <div><strong>Total reuniones:</strong> <?php echo $stats['total_reuniones']; ?></div>
                                <div><strong>Confirmadas:</strong> <?php echo $stats['reuniones_confirmadas']; ?></div>
                                <div><strong>Pendientes:</strong> <?php echo $stats['reuniones_pendientes']; ?></div>
                                <div><strong>Rechazadas:</strong> <?php echo $stats['reuniones_rechazadas']; ?></div>
                                <div><strong>Bloques disponibles:</strong> <?php echo $stats['bloques_disponibles']; ?></div>
                                <div><strong>Bloques ocupados:</strong> <?php echo $stats['bloques_ocupados']; ?></div>
                                <?php if ($stats['reuniones_confirmadas'] > 0 && $stats['total_reuniones'] > 0): ?>
                                    <div class="col-span-2">
                                        <strong>Tasa de confirmación:</strong> 
                                        <span class="text-green-600 font-bold">
                                            <?php echo round(($stats['reuniones_confirmadas'] / $stats['total_reuniones']) * 100, 1); ?>%
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>
<!-- Modal: Editar Reunión -->
<div id="modalEditar" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-cyan-600 p-6 text-white">
            <h2 class="text-2xl font-bold flex items-center">
                <i class="fas fa-edit mr-3"></i>Editar Reunión
            </h2>
        </div>
        <form id="formEditar" class="p-6 space-y-4">
            <input type="hidden" id="edit_reunion_id" name="reunion_id">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Empresa Grande</label>
                    <input type="text" id="edit_empresa_a" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Pyme</label>
                    <input type="text" id="edit_empresa_b" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                </div>
            </div>
            
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Fecha</label>
                    <input type="text" id="edit_fecha" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Hora Inicio</label>
                    <input type="text" id="edit_hora_inicio" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Hora Fin</label>
                    <input type="text" id="edit_hora_fin" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Estado</label>
                <select name="estado" id="edit_estado" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="pendiente">Pendiente</option>
                    <option value="confirmada">Confirmada</option>
                    <option value="rechazada">Rechazada</option>
                    <option value="cancelada">Cancelada</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Notas Administrativas</label>
                <textarea name="notas_admin" id="edit_notas" rows="3" 
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="Agregar notas internas..."></textarea>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    <i class="fas fa-save mr-2"></i>Guardar Cambios
                </button>
                <button type="button" onclick="cerrarModal('modalEditar')" 
                        class="px-6 py-3 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reprogramar Reunión -->
<div id="modalReprogramar" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden">
        <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-6 text-white">
            <h2 class="text-2xl font-bold flex items-center">
                <i class="fas fa-clock mr-3"></i>Reprogramar Reunión
            </h2>
            <p class="text-sm opacity-90 mt-1">Selecciona un nuevo horario disponible</p>
        </div>
        <div id="reprogramarContent" class="p-6">
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-4xl text-purple-600 mb-4"></i>
                <p class="text-gray-600">Cargando horarios disponibles...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Reasignar Pyme -->
<div id="modalReasignar" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-emerald-600 p-6 text-white">
            <h2 class="text-2xl font-bold flex items-center">
                <i class="fas fa-exchange-alt mr-3"></i>Reasignar Pyme
            </h2>
            <p class="text-sm opacity-90 mt-1">Cambiar la empresa pyme de esta reunión</p>
        </div>
        <form id="formReasignar" class="p-6 space-y-4">
            <input type="hidden" id="reasignar_reunion_id" name="reunion_id">
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Atención:</strong> La pyme actual será reemplazada por la que selecciones.
                </p>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Pyme Actual</label>
                <input type="text" id="reasignar_actual" readonly 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 font-medium">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nueva Pyme</label>
                <select name="nueva_empresa_b_id" id="reasignar_nueva" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="">Seleccione una pyme...</option>
                    <?php
                    $stmtPymes = $pdo->query("SELECT id, nombre, rubro FROM empresas WHERE tipo = 'pyme' AND activo = 1 ORDER BY nombre");
                    while ($pyme = $stmtPymes->fetch()): ?>
                        <option value="<?php echo $pyme['id']; ?>">
                            <?php echo htmlspecialchars($pyme['nombre']); ?> 
                            <?php if ($pyme['rubro']): ?>
                                - <?php echo htmlspecialchars($pyme['rubro']); ?>
                            <?php endif; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Motivo del cambio</label>
                <textarea name="motivo" rows="3" required
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                          placeholder="Explica por qué se reasigna esta reunión..."></textarea>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    <i class="fas fa-check mr-2"></i>Reasignar
                </button>
                <button type="button" onclick="cerrarModal('modalReasignar')" 
                        class="px-6 py-3 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>
<script>
// Gestión de tabs
function switchTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active', 'text-primary-600', 'border-primary-600');
        btn.classList.add('text-gray-500', 'border-transparent', 'hover:text-gray-700', 'hover:border-gray-300');
    });
    
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
    activeBtn.classList.remove('text-gray-500', 'border-transparent', 'hover:text-gray-700', 'hover:border-gray-300');
    activeBtn.classList.add('active', 'text-primary-600', 'border-primary-600');
    
    document.getElementById('tab-' + tabName).classList.remove('hidden');
}

// Abrir modal de edición
function abrirModalEditar(reunion) {
    document.getElementById('edit_reunion_id').value = reunion.id;
    document.getElementById('edit_empresa_a').value = reunion.empresa_a_nombre;
    document.getElementById('edit_empresa_b').value = reunion.empresa_b_nombre;
    document.getElementById('edit_fecha').value = new Date(reunion.fecha).toLocaleDateString('es-CL');
    document.getElementById('edit_hora_inicio').value = reunion.hora_inicio.substring(0, 5);
    document.getElementById('edit_hora_fin').value = reunion.hora_fin.substring(0, 5);
    document.getElementById('edit_estado').value = reunion.estado;
    document.getElementById('edit_notas').value = reunion.notas || '';
    
    const modal = document.getElementById('modalEditar');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// Abrir modal de reprogramación
function abrirModalReprogramar(reunionId, empresaAId) {
    const modal = document.getElementById('modalReprogramar');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Cargar bloques disponibles
    fetch(`<?php echo BASE_URL; ?>api/bloques.php?empresa_id=${empresaAId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.bloques) {
                mostrarBloquesDisponibles(data.data.bloques, reunionId);
            } else {
                document.getElementById('reprogramarContent').innerHTML = 
                    '<div class="text-center py-8"><p class="text-gray-500">No hay bloques disponibles</p></div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('reprogramarContent').innerHTML = 
                '<div class="text-center py-8"><p class="text-red-500">Error al cargar horarios</p></div>';
        });
}

// Mostrar bloques disponibles para reprogramar
function mostrarBloquesDisponibles(bloques, reunionId) {
    const disponibles = bloques.filter(b => b.estado === 'disponible' && !b.reunion_id);
    
    if (disponibles.length === 0) {
        document.getElementById('reprogramarContent').innerHTML = 
            '<div class="text-center py-8"><p class="text-gray-500">No hay horarios disponibles</p><button onclick="cerrarModal(\'modalReprogramar\')" class="mt-4 px-6 py-2 bg-gray-500 text-white rounded-lg">Cerrar</button></div>';
        return;
    }
    
    let html = '<div class="grid grid-cols-2 md:grid-cols-3 gap-3">';
    
    disponibles.forEach(bloque => {
        const horaInicio = bloque.hora_inicio.substring(0, 5);
        const horaFin = bloque.hora_fin.substring(0, 5);
        
        html += `
            <button onclick="confirmarReprogramacion(${reunionId}, ${bloque.id})" 
                    class="bg-green-50 hover:bg-green-100 border-2 border-green-300 rounded-lg p-4 text-center transition-all">
                <div class="font-bold text-gray-900">${horaInicio} - ${horaFin}</div>
                <div class="text-xs text-gray-600 mt-1">${new Date(bloque.fecha).toLocaleDateString('es-CL')}</div>
            </button>
        `;
    });
    
    html += '</div>';
    html += '<div class="mt-6 text-center"><button onclick="cerrarModal(\'modalReprogramar\')" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button></div>';
    
    document.getElementById('reprogramarContent').innerHTML = html;
}

// Confirmar reprogramación (CORREGIDO: sin espacio en el parámetro)
function confirmarReprogramacion(reunionId, nuevoBloqueId) {
    if (!confirm('¿Confirmar el cambio de horario?')) return;
    
    const formData = new FormData();
    formData.append('accion', 'reprogramar');
    formData.append('reunion_id', reunionId);
    formData.append('nuevo_bloque_id', nuevoBloqueId);
    
    fetch('<?php echo BASE_URL; ?>api/admin_reuniones.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ ' + data.message);
            location.reload();
        } else {
            alert('✗ ' + data.message);
        }
    })
    .catch(error => {
        alert('✗ Error de conexión');
        console.error(error);
    });
}

// Abrir modal de reasignación
function abrirModalReasignar(reunionId, empresaActual) {
    document.getElementById('reasignar_reunion_id').value = reunionId;
    document.getElementById('reasignar_actual').value = empresaActual;
    document.getElementById('reasignar_nueva').value = '';
    document.getElementById('formReasignar').querySelector('textarea[name="motivo"]').value = '';
    
    const modal = document.getElementById('modalReasignar');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// Cerrar modal
function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Cancelar reunión
function cancelarReunion(reunionId) {
    if (!confirm('¿Está seguro que desea cancelar esta reunión?')) return;
    
    const formData = new FormData();
    formData.append('accion', 'cancelar');
    formData.append('reunion_id', reunionId);
    
    fetch('<?php echo BASE_URL; ?>api/reuniones.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ ' + data.message);
            location.reload();
        } else {
            alert('✗ ' + data.message);
        }
    })
    .catch(error => {
        alert('✗ Error de conexión');
        console.error(error);
    });
}

// Exportar CSV
function exportarCSV(tipo) {
    window.location.href = '<?php echo BASE_URL; ?>api/exportar.php?tipo=' + tipo;
}

// Formulario de edición
document.getElementById('formEditar').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('accion', 'editar');
    
    fetch('<?php echo BASE_URL; ?>api/admin_reuniones.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ ' + data.message);
            cerrarModal('modalEditar');
            location.reload();
        } else {
            alert('✗ ' + data.message);
        }
    })
    .catch(error => {
        alert('✗ Error de conexión');
        console.error(error);
    });
});

// Formulario de reasignación
document.getElementById('formReasignar').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!confirm('¿Está seguro de reasignar esta reunión a otra pyme?')) {
        return;
    }
    
    const formData = new FormData(this);
    formData.append('accion', 'reasignar');
    
    fetch('<?php echo BASE_URL; ?>api/admin_reuniones.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ ' + data.message);
            cerrarModal('modalReasignar');
            location.reload();
        } else {
            alert('✗ ' + data.message);
        }
    })
    .catch(error => {
        alert('✗ Error de conexión');
        console.error(error);
    });
});

// Cerrar modales al hacer clic fuera
['modalEditar', 'modalReprogramar', 'modalReasignar'].forEach(modalId => {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal(modalId);
            }
        });
    }
});

// ============ NUEVAS FUNCIONES DE SUPERADMIN ============

// Ver detalle de slot
function verDetalleSlot(mesa, bloqueId, empresaId) {
    fetch(`<?php echo BASE_URL; ?>api/admin_reuniones.php?accion=detalle_slot&mesa=${mesa}&bloque_id=${bloqueId}&empresa_id=${empresaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const info = data.data;
                let html = '<div class="space-y-2">';
                html += `<p><strong>Mesa:</strong> ${mesa}</p>`;
                html += `<p><strong>Empresa:</strong> ${info.empresa_nombre}</p>`;
                html += `<p><strong>Bloque:</strong> ${info.hora_inicio} - ${info.hora_fin}</p>`;
                if (info.reuniones && info.reuniones.length > 0) {
                    html += '<p><strong>Reuniones:</strong></p><ul class="list-disc ml-4">';
                    info.reuniones.forEach(r => {
                        html += `<li>${r.empresa_b_nombre} - ${r.estado}</li>`;
                    });
                    html += '</ul>';
                } else {
                    html += '<p class="text-gray-500">Sin reuniones confirmadas</p>';
                }
                html += '</div>';
                alert(html.replace(/<[^>]*>/g, '\n'));
            } else {
                alert('Error al cargar detalles');
            }
        });
}

// Liberar slot (eliminar disponibilidad)
function liberarSlot(mesa, bloqueId, empresaId) {
    if (!confirm('¿Está seguro de liberar este slot? Se eliminarán las reuniones pendientes asociadas.')) return;

    const formData = new FormData();
    formData.append('accion', 'liberar_slot');
    formData.append('mesa_numero', mesa);
    formData.append('bloque_id', bloqueId);
    formData.append('empresa_id', empresaId);

    fetch('<?php echo BASE_URL; ?>api/admin_reuniones.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert(data.success ? '✓ ' + data.message : '✗ ' + data.message);
        if (data.success) location.reload();
    })
    .catch(error => {
        alert('✗ Error de conexión');
        console.error(error);
    });
}

// Asignar slot a una empresa
function asignarSlot(mesa, bloqueId) {
    const empresaId = prompt('Ingrese el ID de la empresa demandante:');
    if (!empresaId || isNaN(empresaId)) {
        alert('ID de empresa inválido');
        return;
    }

    const formData = new FormData();
    formData.append('accion', 'asignar_slot');
    formData.append('mesa_numero', mesa);
    formData.append('bloque_id', bloqueId);
    formData.append('empresa_id', parseInt(empresaId));

    fetch('<?php echo BASE_URL; ?>api/admin_reuniones.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert(data.success ? '✓ ' + data.message : '✗ ' + data.message);
        if (data.success) location.reload();
    })
    .catch(error => {
        alert('✗ Error de conexión');
        console.error(error);
    });
}

// Cargar disponibilidad de una empresa para el admin
function cargarDisponibilidadAdmin(empresaId) {
    if (!empresaId) {
        document.getElementById('disponibilidadInfo').classList.add('hidden');
        return;
    }

    fetch(`<?php echo BASE_URL; ?>api/admin_reuniones.php?accion=disponibilidad_empresa&empresa_id=${empresaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                let html = '<p class="font-semibold mb-2">Disponibilidad actual:</p><ul class="list-disc ml-4">';
                data.data.forEach(slot => {
                    html += `<li>Mesa ${slot.mesa_numero} - ${slot.hora_inicio} a ${slot.hora_fin}</li>`;
                });
                html += '</ul>';
                document.getElementById('disponibilidadContent').innerHTML = html;
                document.getElementById('disponibilidadInfo').classList.remove('hidden');
            } else {
                document.getElementById('disponibilidadContent').innerHTML = '<p class="text-gray-600">Esta empresa no tiene disponibilidad configurada aún</p>';
                document.getElementById('disponibilidadInfo').classList.remove('hidden');
            }
        });
}

// Form crear reunión manual
document.getElementById('formCrearReunion')?.addEventListener('submit', function(e) {
    e.preventDefault();

    if (!confirm('¿Confirmar la creación de esta reunión?')) return;

    const formData = new FormData(this);
    formData.append('accion', 'crear_reunion_admin');

    fetch('<?php echo BASE_URL; ?>api/admin_reuniones.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ ' + data.message);
            this.reset();
            document.getElementById('disponibilidadInfo').classList.add('hidden');
            // Opcional: redirigir al tab de reuniones
            switchTab('reuniones');
            setTimeout(() => location.reload(), 500);
        } else {
            alert('✗ ' + data.message);
        }
    })
    .catch(error => {
        alert('✗ Error de conexión');
        console.error(error);
    });
});

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    const firstBtn = document.querySelector('.tab-btn[data-tab="reuniones"]');
    if (firstBtn) {
        firstBtn.classList.add('text-primary-600', 'border-primary-600');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>