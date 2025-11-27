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
    
    // Total de empresas
    $stmt = $pdo->query("SELECT COUNT(*) FROM empresas WHERE activo = 1");
    $stats['total_empresas'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM empresas WHERE tipo = 'grande' AND activo = 1");
    $stats['empresas_grandes'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM empresas WHERE tipo = 'pyme' AND activo = 1");
    $stats['empresas_pymes'] = $stmt->fetchColumn();
    
    // Reuniones por estado
    $stmt = $pdo->query("SELECT estado, COUNT(*) as total FROM reuniones GROUP BY estado");
    $reunionesPorEstado = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $stats['reuniones_pendientes'] = $reunionesPorEstado['pendiente'] ?? 0;
    $stats['reuniones_confirmadas'] = $reunionesPorEstado['confirmada'] ?? 0;
    $stats['reuniones_rechazadas'] = $reunionesPorEstado['rechazada'] ?? 0;
    $stats['reuniones_canceladas'] = $reunionesPorEstado['cancelada'] ?? 0;
    $stats['total_reuniones'] = array_sum($reunionesPorEstado);
    
    // Bloques disponibles
    $stmt = $pdo->query("SELECT COUNT(*) FROM bloques_horarios WHERE estado = 'disponible'");
    $stats['bloques_disponibles'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM bloques_horarios WHERE estado = 'ocupado'");
    $stats['bloques_ocupados'] = $stmt->fetchColumn();
    
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
            eb.nombre as empresa_b_nombre,
            eb.rubro as empresa_b_rubro,
            bh.fecha,
            bh.hora_inicio,
            bh.hora_fin,
            u.nombre_completo as solicitante
        FROM reuniones r
        INNER JOIN empresas ea ON r.empresa_a_id = ea.id
        INNER JOIN empresas eb ON r.empresa_b_id = eb.id
        INNER JOIN bloques_horarios bh ON r.bloque_id = bh.id
        LEFT JOIN usuarios u ON r.solicitado_por = u.id
        ORDER BY bh.fecha DESC, bh.hora_inicio DESC, r.created_at DESC
    ");
    $reuniones = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

require_once '../includes/header.php';
?>

<style>
    .admin-container {
        padding: 30px;
        max-width: 1600px;
        margin: 0 auto;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        text-align: center;
    }
    
    .stat-card.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .stat-card.success {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        color: white;
    }
    
    .stat-card.warning {
        background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%);
        color: white;
    }
    
    .stat-card.danger {
        background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
        color: white;
    }
    
    .stat-number {
        font-size: 36px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 14px;
        opacity: 0.9;
    }
    
    .card {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }
    
    .card h2 {
        font-size: 18px;
        color: #333;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #667eea;
    }
    
    .tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 2px solid #eee;
    }
    
    .tab {
        padding: 10px 20px;
        cursor: pointer;
        border: none;
        background: none;
        font-size: 14px;
        color: #666;
        position: relative;
        transition: all 0.3s;
    }
    
    .tab.active {
        color: #667eea;
        font-weight: 600;
    }
    
    .tab.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: #667eea;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    table th {
        background: #f8f9fa;
        padding: 12px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #666;
        border-bottom: 2px solid #dee2e6;
    }
    
    table td {
        padding: 12px;
        font-size: 13px;
        color: #333;
        border-bottom: 1px solid #dee2e6;
    }
    
    table tr:hover {
        background: #f8f9fa;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .badge-warning {
        background: #fff3cd;
        color: #856404;
    }
    
    .badge-success {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-danger {
        background: #f8d7da;
        color: #721c24;
    }
    
    .badge-secondary {
        background: #e2e3e5;
        color: #383d41;
    }
    
    .badge-info {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .btn-actions {
        display: flex;
        gap: 5px;
    }
    
    .btn-sm {
        padding: 5px 10px;
        font-size: 11px;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-danger-sm {
        background: #dc3545;
        color: white;
    }
    
    .btn-danger-sm:hover {
        background: #c82333;
    }
    
    .btn-export {
        background: #28a745;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        font-size: 14px;
        margin-bottom: 15px;
    }
    
    .btn-export:hover {
        background: #218838;
    }
    
    .empresa-tag {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: 600;
        margin-left: 8px;
    }
    
    .empresa-tag.grande {
        background: #667eea;
        color: white;
    }
    
    .empresa-tag.pyme {
        background: #28a745;
        color: white;
    }
</style>

<div class="admin-container">
    <div class="page-header">
        <h1>Panel de Administración</h1>
        <p>Control y gestión del evento Rueda de Negocios</p>
    </div>
    
    <!-- Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-number"><?php echo $stats['total_empresas']; ?></div>
            <div class="stat-label">Empresas Totales</div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-number"><?php echo $stats['reuniones_pendientes']; ?></div>
            <div class="stat-label">Solicitudes Pendientes</div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-number"><?php echo $stats['reuniones_confirmadas']; ?></div>
            <div class="stat-label">Reuniones Confirmadas</div>
        </div>
        
        <div class="stat-card danger">
            <div class="stat-number"><?php echo $stats['reuniones_rechazadas']; ?></div>
            <div class="stat-label">Reuniones Rechazadas</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number" style="color: #667eea;"><?php echo $stats['bloques_disponibles']; ?></div>
            <div class="stat-label" style="color: #666;">Bloques Disponibles</div>
        </div>
    </div>
    
    <!-- Tabs principales -->
    <div class="card">
        <div class="tabs">
            <button class="tab active" onclick="switchTab('reuniones')">
                Todas las Reuniones
            </button>
            <button class="tab" onclick="switchTab('empresas')">
                Empresas Participantes
            </button>
            <button class="tab" onclick="switchTab('exportar')">
                Exportar Datos
            </button>
        </div>
        
        <!-- Tab: Todas las Reuniones -->
        <div id="tab-reuniones" class="tab-content active">
            <h2>Gestión de Reuniones</h2>
            
            <?php if (empty($reuniones)): ?>
                <p style="text-align: center; color: #999; padding: 40px;">No hay reuniones registradas</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Empresa Grande</th>
                                <th>Pyme</th>
                                <th>Fecha</th>
                                <th>Horario</th>
                                <th>Estado</th>
                                <th>Solicitante</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reuniones as $reunion): ?>
                                <tr id="reunion-row-<?php echo $reunion['id']; ?>">
                                    <td>#<?php echo $reunion['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($reunion['empresa_a_nombre']); ?>
                                        <br><small style="color: #999;"><?php echo htmlspecialchars($reunion['empresa_a_rubro'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($reunion['empresa_b_nombre']); ?>
                                        <br><small style="color: #999;"><?php echo htmlspecialchars($reunion['empresa_b_rubro'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($reunion['fecha'])); ?></td>
                                    <td>
                                        <?php echo date('H:i', strtotime($reunion['hora_inicio'])); ?> - 
                                        <?php echo date('H:i', strtotime($reunion['hora_fin'])); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'pendiente' => '<span class="badge badge-warning">Pendiente</span>',
                                            'confirmada' => '<span class="badge badge-success">Confirmada</span>',
                                            'rechazada' => '<span class="badge badge-danger">Rechazada</span>',
                                            'cancelada' => '<span class="badge badge-secondary">Cancelada</span>'
                                        ];
                                        echo $badges[$reunion['estado']] ?? $reunion['estado'];
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($reunion['solicitante'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($reunion['estado'] === 'pendiente' || $reunion['estado'] === 'confirmada'): ?>
                                            <div class="btn-actions">
                                                <button class="btn-sm btn-danger-sm" onclick="cancelarReunion(<?php echo $reunion['id']; ?>)">
                                                    Cancelar
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <small style="color: #999;">-</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Tab: Empresas Participantes -->
        <div id="tab-empresas" class="tab-content">
            <h2>Empresas Participantes</h2>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Rubro</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empresas as $empresa): ?>
                            <tr>
                                <td>#<?php echo $empresa['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($empresa['nombre']); ?>
                                    <span class="empresa-tag <?php echo $empresa['tipo']; ?>">
                                        <?php echo $empresa['tipo'] === 'grande' ? 'GRANDE' : 'PYME'; ?>
                                    </span>
                                </td>
                                <td><?php echo ucfirst($empresa['tipo']); ?></td>
                                <td><?php echo htmlspecialchars($empresa['rubro'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($empresa['email_contacto']); ?></td>
                                <td><?php echo htmlspecialchars($empresa['telefono'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($empresa['activo']): ?>
                                        <span class="badge badge-success">Activa</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Tab: Exportar -->
        <div id="tab-exportar" class="tab-content">
            <h2>Exportar Datos del Evento</h2>
            
            <p style="margin-bottom: 20px; color: #666;">
                Descarga los datos del evento en formato CSV para análisis o respaldo.
            </p>
            
            <div style="display: grid; gap: 15px; max-width: 500px;">
                <button class="btn-export" onclick="exportarCSV('reuniones')">
                    📊 Exportar Todas las Reuniones (CSV)
                </button>
                
                <button class="btn-export" onclick="exportarCSV('confirmadas')">
                    ✅ Exportar Solo Reuniones Confirmadas (CSV)
                </button>
                
                <button class="btn-export" onclick="exportarCSV('empresas')">
                    🏢 Exportar Listado de Empresas (CSV)
                </button>
            </div>
            
            <hr style="margin: 30px 0;">
            
            <h3 style="margin-bottom: 15px;">Estadísticas Generales</h3>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <p><strong>Total de empresas grandes:</strong> <?php echo $stats['empresas_grandes']; ?></p>
                <p><strong>Total de Pymes:</strong> <?php echo $stats['empresas_pymes']; ?></p>
                <p><strong>Total de reuniones solicitadas:</strong> <?php echo $stats['total_reuniones']; ?></p>
                <p><strong>Reuniones confirmadas:</strong> <?php echo $stats['reuniones_confirmadas']; ?></p>
                <p><strong>Reuniones pendientes:</strong> <?php echo $stats['reuniones_pendientes']; ?></p>
                <p><strong>Reuniones rechazadas:</strong> <?php echo $stats['reuniones_rechazadas']; ?></p>
                <p><strong>Bloques horarios disponibles:</strong> <?php echo $stats['bloques_disponibles']; ?></p>
                <p><strong>Bloques horarios ocupados:</strong> <?php echo $stats['bloques_ocupados']; ?></p>
                <?php if ($stats['reuniones_confirmadas'] > 0): ?>
                    <p><strong>Tasa de confirmación:</strong> 
                        <?php echo round(($stats['reuniones_confirmadas'] / $stats['total_reuniones']) * 100, 1); ?>%
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Cambiar entre tabs
function switchTab(tabName) {
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById('tab-' + tabName).classList.add('active');
}

// Cancelar reunión
function cancelarReunion(reunionId) {
    if (!confirm('¿Está seguro que desea cancelar esta reunión?')) {
        return;
    }
    
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
        alert('Error de conexión');
        console.error(error);
    });
}

// Exportar CSV
function exportarCSV(tipo) {
    window.location.href = '<?php echo BASE_URL; ?>api/exportar.php?tipo=' + tipo;
}
</script>

<?php require_once '../includes/footer.php'; ?>