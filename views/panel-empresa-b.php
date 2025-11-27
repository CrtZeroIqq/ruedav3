<?php
// Incluir configuraciones PRIMERO
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Ahora SÍ podemos usar las constantes
$pageTitle = "Panel de Oferentes - " . SITE_NAME;

// Verificar acceso
checkAccess([ROL_EMPRESA_B]);

// Obtener datos de la empresa (Oferente)
$empresaId = getEmpresaId();
try {
    $stmt = $pdo->prepare("
        SELECT * FROM empresas
        WHERE id = ? AND tipo = 'pyme'
    ");
    $stmt->execute([$empresaId]);
    $empresa = $stmt->fetch();
    
    if (!$empresa) {
        die("Error: Empresa no encontrada");
    }
    
    // Obtener empresas grandes disponibles con sus tags
    $stmt = $pdo->query("
        SELECT * FROM empresas 
        WHERE tipo = 'grande' AND activo = 1
        ORDER BY nombre
    ");
    $empresasGrandes = $stmt->fetchAll();
    
    // Obtener mis solicitudes
    $stmt = $pdo->prepare("
        SELECT
            r.*,
            ea.nombre as empresa_a_nombre,
            ea.rubro as empresa_a_rubro,
            bg.fecha,
            bg.hora_inicio,
            bg.hora_fin
        FROM reuniones r
        INNER JOIN empresas ea ON r.empresa_a_id = ea.id
        INNER JOIN bloques_horarios_globales bg ON r.bloque_global_id = bg.id
        WHERE r.empresa_b_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$empresaId]);
    $misSolicitudes = $stmt->fetchAll();
    
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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Panel de Oferentes</h1>
            <p class="text-gray-600"><span class="inline-block bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">Modalidad: Ofrezco Servicios</span></p>
            <p class="text-gray-500 text-sm mt-2">Explora empresas y solicita reuniones</p>
        </div>
        
        <!-- Layout Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            
            <!-- Sidebar: Perfil de la pyme -->
            <!-- Sidebar: Perfil de la pyme -->
<aside class="lg:col-span-1">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden sticky top-24 animate-slide-down">
        <!-- Card Header -->
        <div class="bg-gradient-to-r from-green-500 to-emerald-500 p-6 text-white">
            <h2 class="text-lg font-bold flex items-center">
                <i class="fas fa-store mr-2"></i>Mi Empresa
            </h2>
        </div>
        
        <!-- Card Body -->
        <div class="p-6 space-y-4">
            <!-- Logo -->
            <div class="flex justify-center">
                <?php if (!empty($empresa['logo'])): ?>
                    <img src="<?php echo UPLOAD_URL . $empresa['logo']; ?>" 
                         alt="Logo" 
                         class="w-24 h-24 rounded-lg object-cover border-2 border-gray-200">
                <?php else: ?>
                    <div class="w-24 h-24 bg-gradient-to-br from-green-100 to-emerald-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-store text-4xl text-green-600"></i>
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
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <?php echo htmlspecialchars($empresa['rubro'] ?? 'No especificado'); ?>
                        </span>
                    </p>
                </div>
                
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Email</label>
                    <p class="text-sm text-gray-700 mt-1 flex items-center">
                        <i class="fas fa-envelope text-green-500 mr-2 text-xs"></i>
                        <?php echo htmlspecialchars($empresa['email_contacto']); ?>
                    </p>
                </div>
                
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Teléfono</label>
                    <p class="text-sm text-gray-700 mt-1 flex items-center">
                        <i class="fas fa-phone text-green-500 mr-2 text-xs"></i>
                        <?php echo htmlspecialchars($empresa['telefono'] ?? 'No especificado'); ?>
                    </p>
                </div>
                
                <?php if ($empresa['descripcion']): ?>
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Descripción</label>
                    <p class="text-sm text-gray-700 mt-1"><?php echo nl2br(htmlspecialchars($empresa['descripcion'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Botón Editar Perfil -->
            <div class="pt-4 border-t border-gray-200">
                <button onclick="abrirModalEditarPerfil()" 
                        class="w-full bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 flex items-center justify-center">
                    <i class="fas fa-edit mr-2"></i>Editar Perfil
                </button>
            </div>
            
        </div>
    </div>
</aside>
            
            <!-- Main Content -->
            <main class="lg:col-span-3">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    
                    <!-- Tabs -->
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button onclick="switchTab('empresas')" 
                                    class="tab-btn active flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors duration-200"
                                    data-tab="empresas">
                                <i class="fas fa-building mr-2"></i>
                                Empresas Disponibles
                            </button>
                            
                            <button onclick="switchTab('solicitudes')" 
                                    class="tab-btn flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors duration-200"
                                    data-tab="solicitudes">
                                <i class="fas fa-clipboard-list mr-2"></i>
                                Mis Solicitudes
                                <?php if (count($misSolicitudes) > 0): ?>
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo count($misSolicitudes); ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                                <button onclick="switchTab('agenda')" 
        class="tab-btn flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors duration-200"
        data-tab="agenda">
    <i class="fas fa-calendar-check mr-2"></i>
    Mi Agenda
</button>
                        </nav>
                    </div>
                    
                    <!-- Tab Content -->
                    <div class="p-6">
                        
                        <!-- Tab: Empresas Disponibles -->
                        <div id="tab-empresas" class="tab-content">
                            <?php if (empty($empresasGrandes)): ?>
                                <div class="text-center py-12">
                                    <i class="fas fa-building text-6xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg">No hay empresas disponibles</p>
                                </div>
                            <?php else: ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <?php foreach ($empresasGrandes as $empresaGrande): ?>
    <div class="bg-gradient-to-br from-white to-gray-50 border border-gray-200 rounded-xl p-6 hover:shadow-lg transition-all duration-200 cursor-pointer transform hover:-translate-y-1"
         onclick="verAgenda(<?php echo $empresaGrande['id']; ?>, '<?php echo htmlspecialchars($empresaGrande['nombre']); ?>')">
        
        <div class="flex items-start space-x-4 mb-4">
            <?php if (!empty($empresaGrande['logo'])): ?>
                <img src="<?php echo UPLOAD_URL . $empresaGrande['logo']; ?>" 
                     alt="<?php echo htmlspecialchars($empresaGrande['nombre']); ?>"
                     class="w-16 h-16 rounded-lg object-cover border-2 border-gray-200 flex-shrink-0">
            <?php else: ?>
                <div class="w-16 h-16 bg-gradient-to-br from-primary-100 to-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-building text-2xl text-primary-600"></i>
                </div>
            <?php endif; ?>
                                                <div class="flex-1 min-w-0">
                                                    <h3 class="text-lg font-bold text-gray-900 truncate">
                                                        <?php echo htmlspecialchars($empresaGrande['nombre']); ?>
                                                    </h3>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 mt-1">
                                                        <?php echo htmlspecialchars($empresaGrande['rubro'] ?? 'General'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                                                <?php echo htmlspecialchars(substr($empresaGrande['descripcion'] ?? 'Sin descripción disponible', 0, 120)) . '...'; ?>
                                            </p>
                                            
                                            <!-- Tags de búsqueda -->
                                            <?php 
                                            $tags = json_decode($empresaGrande['tags_busqueda'] ?? '[]', true);
                                            if (!empty($tags) && is_array($tags)): 
                                            ?>
                                                <div class="mb-4">
                                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                                        <i class="fas fa-search mr-1"></i>Buscan:
                                                    </p>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        <?php foreach ($tags as $tag): ?>
                                                            <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">
                                                                <i class="fas fa-tag mr-1 text-[10px]"></i>
                                                                <?php echo htmlspecialchars($tag); ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <button onclick="event.stopPropagation(); verAgenda(<?php echo $empresaGrande['id']; ?>, '<?php echo htmlspecialchars($empresaGrande['nombre']); ?>')" 
                                                    class="w-full bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 flex items-center justify-center">
                                                <i class="fas fa-calendar-alt mr-2"></i>Ver Agenda
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Tab: Mis Solicitudes -->
                        <div id="tab-solicitudes" class="tab-content hidden">
                            <?php if (empty($misSolicitudes)): ?>
                                <div class="text-center py-12">
                                    <i class="fas fa-clipboard-list text-6xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg">No has solicitado reuniones aún</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($misSolicitudes as $solicitud): ?>
                                        <?php
                                        $estadoColors = [
                                            'pendiente' => 'bg-gradient-to-r from-yellow-50 to-orange-50 border-yellow-400',
                                            'confirmada' => 'bg-gradient-to-r from-green-50 to-emerald-50 border-green-500',
                                            'rechazada' => 'bg-gradient-to-r from-red-50 to-pink-50 border-red-400',
                                            'cancelada' => 'bg-gradient-to-r from-gray-50 to-slate-50 border-gray-400'
                                        ];
                                        $estadoBadges = [
                                            'pendiente' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"><i class="fas fa-clock mr-1"></i>Pendiente</span>',
                                            'confirmada' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i>Confirmada</span>',
                                            'rechazada' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"><i class="fas fa-times-circle mr-1"></i>Rechazada</span>',
                                            'cancelada' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"><i class="fas fa-ban mr-1"></i>Cancelada</span>'
                                        ];
                                        ?>
                                        
                                        <div class="<?php echo $estadoColors[$solicitud['estado']]; ?> border-l-4 rounded-lg p-5 hover:shadow-md transition-shadow duration-200">
                                            <div class="flex justify-between items-start">
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <h3 class="text-lg font-bold text-gray-900">
                                                            <?php echo htmlspecialchars($solicitud['empresa_a_nombre']); ?>
                                                        </h3>
                                                        <?php echo $estadoBadges[$solicitud['estado']]; ?>
                                                    </div>
                                                    <div class="space-y-1 text-sm text-gray-600">
                                                        <p><i class="fas fa-briefcase w-4 mr-2"></i><strong>Rubro:</strong> <?php echo htmlspecialchars($solicitud['empresa_a_rubro'] ?? 'No especificado'); ?></p>
                                                        <p><i class="fas fa-calendar w-4 mr-2"></i><strong>Fecha solicitud:</strong> <?php echo date('d/m/Y H:i', strtotime($solicitud['created_at'])); ?></p>
                                                    </div>
                                                    
                                                    <?php if ($solicitud['notas']): ?>
                                                        <div class="mt-3 p-3 bg-white rounded-lg border border-gray-200">
                                                            <p class="text-sm text-gray-700"><strong>Tu mensaje:</strong> <?php echo nl2br(htmlspecialchars($solicitud['notas'])); ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="ml-4 flex-shrink-0">
                                                    <div class="bg-white px-4 py-2 rounded-lg shadow-sm border border-gray-200">
                                                        <div class="text-primary-600 font-bold text-center">
                                                            <?php echo date('H:i', strtotime($solicitud['hora_inicio'])); ?> - 
                                                            <?php echo date('H:i', strtotime($solicitud['hora_fin'])); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500 text-center mt-1">
                                                            <?php echo date('d/m/Y', strtotime($solicitud['fecha'])); ?>
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
    // Obtener todas las reuniones de esta pyme (confirmadas y pendientes)
    try {
        $stmt = $pdo->prepare("
            SELECT
                r.*,
                bg.fecha,
                bg.hora_inicio,
                bg.hora_fin,
                ea.nombre as empresa_a_nombre,
                ea.logo as empresa_a_logo,
                ea.rubro as empresa_a_rubro
            FROM reuniones r
            INNER JOIN bloques_horarios_globales bg ON r.bloque_global_id = bg.id
            INNER JOIN empresas ea ON r.empresa_a_id = ea.id
            WHERE r.empresa_b_id = ?
            AND r.estado IN ('pendiente', 'confirmada')
            ORDER BY bg.fecha, bg.hora_inicio
        ");
        $stmt->execute([$empresaId]);
        $reuniones = $stmt->fetchAll();
        
        // Agrupar por fecha
        $reunionesAgrupadas = [];
        foreach ($reuniones as $reunion) {
            $fecha = $reunion['fecha'];
            if (!isset($reunionesAgrupadas[$fecha])) {
                $reunionesAgrupadas[$fecha] = [];
            }
            $reunionesAgrupadas[$fecha][] = $reunion;
        }
        
    } catch (PDOException $e) {
        error_log("Error al cargar agenda pyme: " . $e->getMessage());
        $reunionesAgrupadas = [];
    }
    ?>
    
    <?php if (empty($reunionesAgrupadas)): ?>
        <div class="text-center py-12">
            <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg mb-4">No tienes reuniones programadas</p>
            <p class="text-sm text-gray-400">Solicita reuniones con empresas grandes para que aparezcan aquí</p>
        </div>
    <?php else: ?>
        <!-- Resumen -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-blue-700">
                    <?php 
                    $confirmadas = array_filter($reuniones, function($r) { 
                        return $r['estado'] === 'confirmada'; 
                    });
                    echo count($confirmadas);
                    ?>
                </div>
                <div class="text-xs text-blue-600 font-medium mt-1">Reuniones Confirmadas</div>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-yellow-700">
                    <?php 
                    $pendientes = array_filter($reuniones, function($r) { 
                        return $r['estado'] === 'pendiente'; 
                    });
                    echo count($pendientes);
                    ?>
                </div>
                <div class="text-xs text-yellow-600 font-medium mt-1">En Espera de Confirmación</div>
            </div>
        </div>
        
        <!-- Reuniones por fecha -->
        <div class="space-y-6">
            <?php foreach ($reunionesAgrupadas as $fecha => $reunionesDia): ?>
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <!-- Header de fecha -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-green-600 rounded-lg flex flex-col items-center justify-center text-white">
                                    <div class="text-xs font-semibold"><?php echo strtoupper(date('M', strtotime($fecha))); ?></div>
                                    <div class="text-lg font-bold"><?php echo date('d', strtotime($fecha)); ?></div>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">
                                        <?php 
                                        $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                                        echo $dias[date('w', strtotime($fecha))];
                                        ?>
                                    </h3>
                                    <p class="text-sm text-gray-600"><?php echo date('d \d\e F \d\e Y', strtotime($fecha)); ?></p>
                                </div>
                            </div>
                            <div class="text-sm text-gray-600">
                                <span class="font-semibold"><?php echo count($reunionesDia); ?></span> reuniones
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reuniones del día -->
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($reunionesDia as $reunion): ?>
                            <?php
                            $esConfirmada = $reunion['estado'] === 'confirmada';
                            $esPendiente = $reunion['estado'] === 'pendiente';
                            
                            if ($esConfirmada) {
                                $claseReunion = 'bg-blue-50 hover:bg-blue-100';
                                $iconoEstado = '<i class="fas fa-check-circle text-blue-600"></i>';
                                $textoEstado = '<span class="text-blue-700 font-semibold">Confirmada</span>';
                                $badgeClase = 'bg-blue-100 text-blue-800';
                            } else {
                                $claseReunion = 'bg-yellow-50 hover:bg-yellow-100';
                                $iconoEstado = '<i class="fas fa-clock text-yellow-600"></i>';
                                $textoEstado = '<span class="text-yellow-700 font-semibold">Pendiente</span>';
                                $badgeClase = 'bg-yellow-100 text-yellow-800';
                            }
                            ?>
                            
                            <div class="<?php echo $claseReunion; ?> px-6 py-4 transition-colors duration-150">
                                <div class="flex items-center justify-between">
                                    <!-- Horario -->
                                    <div class="flex items-center space-x-4">
                                        <div class="text-center">
                                            <div class="text-lg font-bold text-gray-900">
                                                <?php echo date('H:i', strtotime($reunion['hora_inicio'])); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">a</div>
                                            <div class="text-lg font-bold text-gray-900">
                                                <?php echo date('H:i', strtotime($reunion['hora_fin'])); ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Divider -->
                                        <div class="w-px h-16 bg-gray-300"></div>
                                        
                                        <!-- Información de la empresa -->
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3">
                                                <!-- Logo de la empresa grande -->
                                                <?php if (!empty($reunion['empresa_a_logo'])): ?>
                                                    <img src="<?php echo UPLOAD_URL . $reunion['empresa_a_logo']; ?>" 
                                                         alt="<?php echo htmlspecialchars($reunion['empresa_a_nombre']); ?>"
                                                         class="w-12 h-12 rounded-lg object-cover border-2 border-white shadow">
                                                <?php else: ?>
                                                    <div class="w-12 h-12 bg-gradient-to-br from-primary-100 to-purple-100 rounded-lg flex items-center justify-center">
                                                        <i class="fas fa-building text-primary-600"></i>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div>
                                                    <div class="font-semibold text-gray-900">
                                                        <?php echo htmlspecialchars($reunion['empresa_a_nombre']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-600">
                                                        <?php echo htmlspecialchars($reunion['empresa_a_rubro'] ?? 'Sin rubro'); ?>
                                                    </div>
                                                    <?php if (!empty($reunion['notas'])): ?>
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            <i class="fas fa-comment-dots mr-1"></i>
                                                            <?php echo htmlspecialchars(substr($reunion['notas'], 0, 50)) . (strlen($reunion['notas']) > 50 ? '...' : ''); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Badge de estado -->
                                    <div>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?php echo $badgeClase; ?>">
                                            <?php echo $iconoEstado; ?>
                                            <span class="ml-1"><?php echo $esConfirmada ? 'Confirmada' : 'Pendiente'; ?></span>
                                        </span>
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
<!-- Fin Tab: Mi Agenda -->
                    </div>
                </div>
            </main>
            
        </div>
    </div>
</div>
<!-- Modal: Editar Perfil -->
<div id="modalEditarPerfil" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-emerald-600 p-6 text-white">
            <h2 class="text-2xl font-bold flex items-center">
                <i class="fas fa-user-edit mr-3"></i>Editar Perfil Empresarial
            </h2>
        </div>
        
        <!-- Tabs del Modal -->
        <div class="border-b border-gray-200">
            <nav class="flex">
                <button onclick="switchModalTab('datos')" 
                        class="modal-tab-btn active flex-1 py-3 px-4 text-center border-b-2 font-medium text-sm text-green-600 border-green-600"
                        data-modal-tab="datos">
                    <i class="fas fa-info-circle mr-2"></i>Datos
                </button>
                <button onclick="switchModalTab('logo')" 
                        class="modal-tab-btn flex-1 py-3 px-4 text-center border-b-2 font-medium text-sm text-gray-500 border-transparent"
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
                               required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Rubro
                        </label>
                        <input type="text" name="rubro" value="<?php echo htmlspecialchars($empresa['rubro'] ?? ''); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                               placeholder="Ej: Tecnología, Servicios, Retail">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Email de Contacto *
                        </label>
                        <input type="email" name="email_contacto" value="<?php echo htmlspecialchars($empresa['email_contacto']); ?>" 
                               required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Teléfono
                        </label>
                        <input type="tel" name="telefono" value="<?php echo htmlspecialchars($empresa['telefono'] ?? ''); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                               placeholder="+56 9 1234 5678">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Dirección
                        </label>
                        <input type="text" name="direccion" value="<?php echo htmlspecialchars($empresa['direccion'] ?? ''); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                               placeholder="Calle, Ciudad, Región">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Descripción
                        </label>
                        <textarea name="descripcion" rows="4" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                  placeholder="Describe tu empresa, productos o servicios..."><?php echo htmlspecialchars($empresa['descripcion'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="flex gap-3 pt-4">
                        <button type="submit" 
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
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
                        <?php if (!empty($empresa['logo'])): ?>
                            <img id="preview-logo" src="<?php echo UPLOAD_URL . $empresa['logo']; ?>" 
                                 alt="Logo" class="w-40 h-40 rounded-lg object-cover border-2 border-gray-200">
                        <?php else: ?>
                            <div id="preview-logo" class="w-40 h-40 bg-gradient-to-br from-green-100 to-emerald-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-store text-6xl text-green-600"></i>
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
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Formatos: JPG, PNG, GIF, WEBP. Máximo 5MB
                        </p>
                    </div>
                    
                    <div id="nueva-preview" class="hidden text-center">
                        <p class="text-sm font-semibold text-gray-700 mb-2">Vista previa:</p>
                        <img id="nueva-preview-img" class="w-40 h-40 rounded-lg object-cover border-2 border-green-300 mx-auto">
                    </div>
                    
                    <div class="flex gap-3 pt-4">
                        <button type="submit" 
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
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
<!-- Modal para ver agenda -->
<div id="agendaModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden animate-slide-down">
        <div class="bg-gradient-to-r from-primary-600 to-purple-600 p-6 text-white flex justify-between items-center">
            <div>
                <h2 id="modalEmpresaNombre" class="text-2xl font-bold">Agenda</h2>
                <p class="text-sm opacity-90">Selecciona un horario disponible</p>
            </div>
            <button onclick="cerrarModal()" class="text-white hover:bg-white/20 rounded-lg p-2 transition-colors duration-200">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div id="modalAgendaContent" class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-4xl text-primary-600 mb-4"></i>
                <p class="text-gray-600">Cargando agenda...</p>
            </div>
        </div>
    </div>
</div>
                        
<!-- Modal para mensaje de solicitud -->
<div id="modalMensajeSolicitud" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[60] p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden animate-scale-in">
        <div class="bg-gradient-to-r from-green-500 to-emerald-500 p-6 text-white">
            <h2 class="text-xl font-bold flex items-center">
                <i class="fas fa-envelope mr-3"></i>Mensaje para la Empresa
            </h2>
            <p class="text-sm opacity-90 mt-1">Opcional: Cuéntales por qué quieres reunirte</p>
        </div>
        
        <div class="p-6">
            <form id="formMensajeSolicitud" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-comment-dots mr-1 text-green-600"></i>
                        Tu mensaje (opcional)
                    </label>
                    <textarea 
                        id="inputMensajeSolicitud" 
                        rows="4" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                        placeholder="Ej: Nos interesa conocer más sobre sus servicios de logística y explorar posibles colaboraciones..."
                        maxlength="500"
                    ></textarea>
                    <div class="flex justify-between items-center mt-2">
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Máximo 500 caracteres
                        </p>
                        <p class="text-xs text-gray-600 font-medium">
                            <span id="contadorCaracteres">0</span>/500
                        </p>
                    </div>
                </div>
                
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-lightbulb mr-2"></i>
                        <strong>Tip:</strong> Un mensaje personalizado aumenta las probabilidades de que acepten tu solicitud.
                    </p>
                </div>
                
                <div class="flex gap-3 pt-2">
                    <button 
                        type="submit" 
                        class="flex-1 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-200 flex items-center justify-center shadow-lg hover:shadow-xl"
                    >
                        <i class="fas fa-paper-plane mr-2"></i>Enviar Solicitud
                    </button>
                    <button 
                        type="button" 
                        onclick="cerrarModalMensaje()" 
                        class="px-6 py-3 border-2 border-gray-300 rounded-lg font-semibold hover:bg-gray-50 transition-colors"
                    >
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>                        

<script>
// ========================================
// FUNCIONES DE TABS PRINCIPALES
// ========================================
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

// ========================================
// FUNCIONES DE AGENDA
// ========================================
function verAgenda(empresaId, nombreEmpresa) {
    document.getElementById('modalEmpresaNombre').textContent = nombreEmpresa;
    const modal = document.getElementById('agendaModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    fetch('<?php echo BASE_URL; ?>api/bloques.php?empresa_id=' + empresaId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.bloques) {
                mostrarAgenda(data.data.bloques, empresaId);
            } else {
                document.getElementById('modalAgendaContent').innerHTML = 
                    '<div class="text-center py-8"><p class="text-gray-500">No hay bloques disponibles</p></div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalAgendaContent').innerHTML = 
                '<div class="text-center py-8"><p class="text-red-500">Error al cargar agenda</p></div>';
        });
}

function mostrarAgenda(bloques, empresaId) {
    const container = document.getElementById('modalAgendaContent');

    if (bloques.length === 0) {
        container.innerHTML = '<div class="text-center py-8"><p class="text-gray-500">No hay bloques horarios configurados</p></div>';
        return;
    }

    let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">';

    bloques.forEach(bloque => {
        const horaInicio = bloque.hora_inicio.substring(0, 5);
        const horaFin = bloque.hora_fin.substring(0, 5);
        const mesaNumero = bloque.mesa_numero;
        const disponible = bloque.estado === 'disponible' && !bloque.reunion_id;

        let claseEstado = '';
        let contenidoBoton = '';

        if (disponible) {
            claseEstado = 'bg-green-50 border-green-300 hover:bg-green-100';
            contenidoBoton = `<button onclick="solicitarReunion(${bloque.bloque_id}, ${empresaId})"
                                     class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                                <i class="fas fa-plus-circle mr-1"></i>Solicitar
                              </button>`;
        } else if (bloque.reunion_estado === 'pendiente') {
            claseEstado = 'bg-yellow-50 border-yellow-300';
            contenidoBoton = '<span class="text-xs text-yellow-700 font-medium"><i class="fas fa-clock mr-1"></i>Pendiente</span>';
        } else {
            claseEstado = 'bg-gray-50 border-gray-300 opacity-60';
            contenidoBoton = '<span class="text-xs text-gray-500"><i class="fas fa-lock mr-1"></i>Ocupado</span>';
        }

        html += `
            <div class="${claseEstado} border rounded-lg p-4 transition-all duration-200">
                <div class="flex justify-between items-center mb-2">
                    <div class="font-semibold text-gray-900">${horaInicio} - ${horaFin}</div>
                    <div class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs font-bold">
                        <i class="fas fa-table-cells mr-1"></i>Mesa ${mesaNumero}
                    </div>
                </div>
                <div class="text-center">
                    ${contenidoBoton}
                </div>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
}

// Variables globales para el modal de mensaje
let bloqueIdSeleccionado = null;
let empresaIdSeleccionada = null;

function solicitarReunion(bloqueId, empresaId) {
    // Guardar IDs para usar después
    bloqueIdSeleccionado = bloqueId;
    empresaIdSeleccionada = empresaId;
    
    // Abrir modal de mensaje
    const modal = document.getElementById('modalMensajeSolicitud');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Resetear formulario y contador
    document.getElementById('inputMensajeSolicitud').value = '';
    document.getElementById('contadorCaracteres').textContent = '0';
    
    // Focus en el textarea
    setTimeout(() => {
        document.getElementById('inputMensajeSolicitud').focus();
    }, 300);
}

function cerrarModalMensaje() {
    const modal = document.getElementById('modalMensajeSolicitud');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    
    // Reset variables
    bloqueIdSeleccionado = null;
    empresaIdSeleccionada = null;
}

function enviarSolicitudReunion(notas) {
    const formData = new FormData();
    formData.append('accion', 'solicitar');
    formData.append('bloque_global_id', bloqueIdSeleccionado);
    formData.append('empresa_a_id', empresaIdSeleccionada);
    formData.append('mensaje', notas);
    
    // Mostrar indicador de carga
    const btnEnviar = document.querySelector('#formMensajeSolicitud button[type="submit"]');
    const textoOriginal = btnEnviar.innerHTML;
    btnEnviar.disabled = true;
    btnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enviando...';
    
    fetch('<?php echo BASE_URL; ?>api/solicitar_reunion.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btnEnviar.disabled = false;
        btnEnviar.innerHTML = textoOriginal;
        
        if (data.success) {
            // Cerrar modales
            cerrarModalMensaje();
            cerrarModal();
            
            // Mostrar notificación de éxito
            mostrarNotificacion('success', data.message);
            
            // Recargar página después de 1.5 segundos
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            mostrarNotificacion('error', data.message);
        }
    })
    .catch(error => {
        btnEnviar.disabled = false;
        btnEnviar.innerHTML = textoOriginal;
        mostrarNotificacion('error', 'Error de conexión');
        console.error(error);
    });
}

function mostrarNotificacion(tipo, mensaje) {
    const colores = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    const iconos = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        info: 'fa-info-circle'
    };
    
    const notif = document.createElement('div');
    notif.className = `fixed top-4 right-4 ${colores[tipo]} text-white px-6 py-4 rounded-lg shadow-2xl flex items-center space-x-3 animate-slide-down z-[70]`;
    notif.innerHTML = `
        <i class="fas ${iconos[tipo]} text-2xl"></i>
        <span class="font-medium">${mensaje}</span>
    `;
    
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.style.opacity = '0';
        notif.style.transition = 'opacity 0.3s';
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}
function cerrarModal() {
    const modal = document.getElementById('agendaModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// ========================================
// FUNCIONES DE EDICIÓN DE PERFIL
// ========================================
function abrirModalEditarPerfil() {
    const modal = document.getElementById('modalEditarPerfil');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        // Activar primera pestaña por defecto
        switchModalTab('datos');
    } else {
        console.error('Modal no encontrado');
    }
}

function cerrarModalPerfil() {
    const modal = document.getElementById('modalEditarPerfil');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    
    // Resetear formularios si existen
    const formDatos = document.getElementById('formActualizarDatos');
    const formLogo = document.getElementById('formActualizarLogo');
    const preview = document.getElementById('nueva-preview');
    
    if (formDatos) formDatos.reset();
    if (formLogo) formLogo.reset();
    if (preview) preview.classList.add('hidden');
}

function switchModalTab(tabName) {
    // Remover active de todos los botones
    document.querySelectorAll('.modal-tab-btn').forEach(btn => {
        btn.classList.remove('active', 'text-green-600', 'border-green-600');
        btn.classList.add('text-gray-500', 'border-transparent');
    });
    
    // Ocultar todos los contenidos
    document.querySelectorAll('.modal-tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Activar tab seleccionado
    const activeBtn = document.querySelector(`[data-modal-tab="${tabName}"]`);
    if (activeBtn) {
        activeBtn.classList.remove('text-gray-500', 'border-transparent');
        activeBtn.classList.add('active', 'text-green-600', 'border-green-600');
    }
    
    // Mostrar contenido seleccionado
    const content = document.getElementById('modal-tab-' + tabName);
    if (content) {
        content.classList.remove('hidden');
    }
}

function previsualizarLogo(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('nueva-preview-img');
            const preview = document.getElementById('nueva-preview');
            if (img && preview) {
                img.src = e.target.result;
                preview.classList.remove('hidden');
            }
        }
        reader.readAsDataURL(file);
    }
}

// ========================================
// INICIALIZACIÓN Y EVENT LISTENERS
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar primera tab principal
    const firstBtn = document.querySelector('.tab-btn[data-tab="empresas"]');
    if (firstBtn) {
        firstBtn.classList.add('text-primary-600', 'border-primary-600');
    }
    
    // Formulario actualizar datos
    const formDatos = document.getElementById('formActualizarDatos');
    if (formDatos) {
        formDatos.addEventListener('submit', function(e) {
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
        });
    }
    
    // Formulario actualizar logo
    const formLogo = document.getElementById('formActualizarLogo');
    if (formLogo) {
        formLogo.addEventListener('submit', function(e) {
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
        });
    }
    
    // Cerrar modal agenda al hacer clic fuera
    const agendaModal = document.getElementById('agendaModal');
    if (agendaModal) {
        agendaModal.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
    }
    
    // Cerrar modal perfil al hacer clic fuera
    const perfilModal = document.getElementById('modalEditarPerfil');
    if (perfilModal) {
        perfilModal.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalPerfil();
            }
        });
    }
	
	// Contador de caracteres para el textarea
    const inputMensaje = document.getElementById('inputMensajeSolicitud');
    if (inputMensaje) {
        inputMensaje.addEventListener('input', function() {
            const contador = document.getElementById('contadorCaracteres');
            contador.textContent = this.value.length;
            
            // Cambiar color si se acerca al límite
            if (this.value.length > 450) {
                contador.classList.add('text-red-600', 'font-bold');
            } else {
                contador.classList.remove('text-red-600', 'font-bold');
            }
        });
    }
    
    // Formulario de mensaje de solicitud
    const formMensaje = document.getElementById('formMensajeSolicitud');
    if (formMensaje) {
        formMensaje.addEventListener('submit', function(e) {
            e.preventDefault();
            const notas = document.getElementById('inputMensajeSolicitud').value.trim();
            enviarSolicitudReunion(notas);
        });
    }
    
    // Cerrar modal de mensaje al hacer clic fuera
    const modalMensaje = document.getElementById('modalMensajeSolicitud');
    if (modalMensaje) {
        modalMensaje.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalMensaje();
            }
        });
    }
    
    // Atajo de teclado ESC para cerrar modales
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalMensaje();
            cerrarModal();
        }
    });

});
</script>

<?php require_once '../includes/footer.php'; ?>