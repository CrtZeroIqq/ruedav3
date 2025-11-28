<?php
/**
 * Visor de Rueda de Negocios - Vista Completa
 * Ruta: views/visor-rueda.php
 *
 * Parámetros URL:
 * ?hora=10:30 - Simula que la hora actual es 10:30
 * ?vista=matriz|cronograma - Cambia entre vistas
 * ?fullscreen=1 - Modo pantalla completa
 */
require_once '../config/config.php';

// Capturar parámetros
$hora_simulada = isset($_GET['hora']) ? $_GET['hora'] : null;
$vista_default = isset($_GET['vista']) ? $_GET['vista'] : 'matriz';
$fullscreen = isset($_GET['fullscreen']) ? true : false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Rueda - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-disponible: #10b981;
            --color-ocupado: #6b7280;
            --color-activo: #fbbf24;
            --color-proximo: #f97316;
        }

        .bloque-disponible {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .bloque-ocupado {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }

        .bloque-actual {
            animation: pulse-glow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            border: 4px solid var(--color-activo);
            box-shadow: 0 0 30px rgba(251, 191, 36, 0.7);
            transform: scale(1.03);
            z-index: 10;
            position: relative;
        }

        .bloque-proximo {
            border: 3px solid var(--color-proximo);
            box-shadow: 0 0 20px rgba(249, 115, 22, 0.5);
        }

        @keyframes pulse-glow {
            0%, 100% {
                opacity: 1;
                box-shadow: 0 0 30px rgba(251, 191, 36, 0.7);
            }
            50% {
                opacity: 0.95;
                box-shadow: 0 0 50px rgba(251, 191, 36, 0.9);
            }
        }

        .empresa-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 8px;
            background: white;
            padding: 4px;
        }

        .pyme-logo {
            width: 45px;
            height: 45px;
            object-fit: contain;
            border-radius: 6px;
            background: white;
            padding: 3px;
        }

        .tabla-bloques {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .tabla-bloques td, .tabla-bloques th {
            min-height: 110px;
            vertical-align: middle;
        }

        .empresa-row {
            transition: all 0.3s ease;
        }

        .empresa-row:hover {
            background-color: #f9fafb;
            transform: translateX(2px);
        }

        .horario-header {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            padding: 1.2rem;
            min-width: 220px;
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .empresa-cell {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            color: white;
            font-weight: 600;
            min-width: 220px;
            padding: 1rem;
            position: sticky;
            left: 0;
            z-index: 15;
        }

        .bloque-cell {
            min-width: 220px;
            min-height: 110px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .bloque-cell:hover {
            transform: scale(1.05);
            z-index: 5;
        }

        .slide-transition {
            transition: all 0.8s ease-in-out;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .timeline-item {
            position: relative;
            padding-left: 50px;
            padding-bottom: 30px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, #3b82f6, #8b5cf6);
        }

        .timeline-dot {
            position: absolute;
            left: 11px;
            top: 10px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: white;
            border: 3px solid #3b82f6;
            z-index: 5;
        }

        .timeline-dot.active {
            background: var(--color-activo);
            border-color: var(--color-activo);
            box-shadow: 0 0 20px rgba(251, 191, 36, 0.8);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        .filter-chip {
            transition: all 0.2s ease;
        }

        .filter-chip:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .stat-card {
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            animation: fadeIn 0.3s;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideUp 0.3s;
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        <?php if($fullscreen): ?>
        body {
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 100%;
            padding: 0.5rem;
        }
        <?php endif; ?>
    </style>
</head>
<body class="bg-gray-100">

    <?php if(!$fullscreen): ?>
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-6 shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">🎯 Rueda de Negocios Arica 2025</h1>
                    <p class="mt-2 text-blue-100">Visor Completo en Tiempo Real</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="toggleFullscreen()" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition">
                        <i class="fas fa-expand"></i> Pantalla Completa
                    </button>
                    <button onclick="exportarDatos()" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Header compacto para fullscreen -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 shadow">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <h1 class="text-xl font-bold">🎯 Rueda de Negocios Arica 2025</h1>
            <button onclick="exitFullscreen()" class="bg-white/20 hover:bg-white/30 px-3 py-1 rounded text-sm">
                <i class="fas fa-compress"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="container mx-auto px-4 py-6">
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4 stat-card">
                <div class="text-gray-500 text-sm">Fecha Evento</div>
                <div class="text-xl font-bold text-blue-600" id="fecha-evento">--</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 stat-card">
                <div class="text-gray-500 text-sm">Total Bloques</div>
                <div class="text-xl font-bold text-gray-700" id="total-bloques">0</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 stat-card">
                <div class="text-gray-500 text-sm">Disponibles</div>
                <div class="text-xl font-bold text-green-600" id="bloques-disponibles">0</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 stat-card">
                <div class="text-gray-500 text-sm">Ocupados</div>
                <div class="text-xl font-bold text-gray-600" id="bloques-ocupados">0</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 stat-card">
                <div class="text-gray-500 text-sm">% Ocupación</div>
                <div class="text-xl font-bold text-purple-600" id="porcentaje-ocupacion">0%</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 stat-card">
                <div class="text-gray-500 text-sm">Empresas</div>
                <div class="text-xl font-bold text-indigo-600" id="total-empresas">0</div>
            </div>
        </div>

        <!-- Reloj y controles -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="flex flex-wrap gap-4 justify-between items-center">
                <div class="flex items-center gap-4">
                    <div>
                        <span class="text-gray-600">Hora actual:</span>
                        <span class="text-2xl font-bold text-blue-600 ml-2" id="hora-actual">--:--</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-sm text-gray-600">Actualización automática</span>
                    </div>
                </div>

                <!-- Selector de vista -->
                <div class="flex gap-2">
                    <button onclick="cambiarVista('matriz')" id="btn-vista-matriz" class="px-4 py-2 rounded-lg bg-blue-600 text-white transition">
                        <i class="fas fa-th"></i> Matriz
                    </button>
                    <button onclick="cambiarVista('cronograma')" id="btn-vista-cronograma" class="px-4 py-2 rounded-lg bg-gray-300 text-gray-700 transition">
                        <i class="fas fa-stream"></i> Cronograma
                    </button>
                </div>

                <span class="text-sm text-gray-500" id="ultima-actualizacion">--</span>
            </div>
        </div>

        <!-- Filtros y búsqueda -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-search"></i> Buscar empresa
                    </label>
                    <input
                        type="text"
                        id="search-input"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg search-input focus:outline-none focus:border-blue-500"
                        placeholder="Buscar por nombre de empresa..."
                        oninput="aplicarFiltros()"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-filter"></i> Estado
                    </label>
                    <select id="filter-estado" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" onchange="aplicarFiltros()">
                        <option value="">Todos</option>
                        <option value="disponible">Disponibles</option>
                        <option value="ocupado">Ocupados</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-industry"></i> Rubro
                    </label>
                    <select id="filter-rubro" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" onchange="aplicarFiltros()">
                        <option value="">Todos</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <button onclick="limpiarFiltros()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition text-sm">
                    <i class="fas fa-times"></i> Limpiar filtros
                </button>
                <div id="filtros-activos" class="flex gap-2 flex-wrap"></div>
            </div>
        </div>

        <!-- Leyenda -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex flex-wrap gap-6 justify-center items-center">
                <div class="flex items-center gap-3">
                    <div class="w-14 h-14 bloque-disponible rounded-lg shadow-md flex items-center justify-center">
                        <i class="fas fa-check text-2xl"></i>
                    </div>
                    <span class="font-medium">Disponible</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-14 h-14 bloque-ocupado rounded-lg shadow-md flex items-center justify-center">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <span class="font-medium">Ocupado</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-14 h-14 bg-white border-4 border-yellow-400 rounded-lg shadow-lg flex items-center justify-center">
                        <i class="fas fa-bolt text-yellow-400 text-2xl"></i>
                    </div>
                    <span class="font-medium">⚡ Activo Ahora</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-14 h-14 bg-white border-3 border-orange-500 rounded-lg shadow flex items-center justify-center">
                        <i class="fas fa-clock text-orange-500 text-2xl"></i>
                    </div>
                    <span class="font-medium">Próximo (15 min)</span>
                </div>
            </div>
        </div>

        <!-- Vista Matriz -->
        <div id="vista-matriz" class="bg-white rounded-lg shadow-xl overflow-hidden fade-in">
            <div class="p-6 bg-gradient-to-r from-blue-600 to-purple-600">
                <h2 class="text-2xl font-bold text-white mb-2">
                    <i class="fas fa-th"></i> Vista Matriz - Agenda en Tiempo Real
                </h2>
                <p class="text-blue-100">Visualización completa de bloques por empresa y horario</p>
            </div>
            <div class="overflow-x-auto">
                <div id="loading-matriz" class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600"></div>
                    <p class="mt-4 text-gray-600 text-lg">Cargando bloques...</p>
                </div>
                <div id="tabla-container" class="hidden slide-transition">
                    <table class="tabla-bloques">
                        <thead id="tabla-header"></thead>
                        <tbody id="tabla-body"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Vista Cronograma -->
        <div id="vista-cronograma" class="hidden bg-white rounded-lg shadow-xl overflow-hidden fade-in">
            <div class="p-6 bg-gradient-to-r from-blue-600 to-purple-600">
                <h2 class="text-2xl font-bold text-white mb-2">
                    <i class="fas fa-stream"></i> Vista Cronograma - Timeline del Evento
                </h2>
                <p class="text-blue-100">Visualización cronológica de todos los bloques</p>
            </div>
            <div class="p-6">
                <div id="cronograma-container"></div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center text-gray-500 text-sm">
            <p>Sistema de Rueda de Negocios - <?php echo SITE_NAME; ?></p>
            <p class="mt-1">Actualización automática cada 30 segundos | <span id="contador-actualizacion">30</span>s</p>
        </div>
    </div>

    <!-- Modal de detalle de bloque -->
    <div id="modal-bloque" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-2xl font-bold text-gray-800">Detalle del Bloque</h3>
                <button onclick="cerrarModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modal-body"></div>
        </div>
    </div>

    <script>
        let datosActuales = null;
        let vistaActual = '<?php echo $vista_default; ?>';
        let contadorActualizacion = 30;
        let intervaloContador = null;

        // Hora simulada desde URL
        const horaSimulada = '<?php echo $hora_simulada ? $hora_simulada : ""; ?>';

        // Obtener hora actual (real o simulada)
        function obtenerHoraActual() {
            if (horaSimulada) {
                const [horas, minutos] = horaSimulada.split(':');
                const ahora = new Date();
                ahora.setHours(parseInt(horas), parseInt(minutos), 0, 0);
                return ahora;
            }
            return new Date();
        }

        // Cargar bloques desde la API
        async function cargarBloques() {
            try {
                const response = await fetch('<?php echo BASE_URL; ?>api/get_bloques_visor.php');
                const data = await response.json();

                if (data.success) {
                    datosActuales = data;
                    actualizarInterfaz(data);
                    resetearContador();
                } else {
                    console.error('Error:', data.mensaje);
                    mostrarError('Error al cargar los datos');
                }
            } catch (error) {
                console.error('Error al cargar bloques:', error);
                mostrarError('Error de conexión con el servidor');
            }
        }

        // Actualizar interfaz completa
        function actualizarInterfaz(data) {
            // Estadísticas
            document.getElementById('fecha-evento').textContent = formatearFecha(data.fecha_evento);
            document.getElementById('total-bloques').textContent = data.estadisticas.total_bloques;
            document.getElementById('bloques-disponibles').textContent = data.estadisticas.disponibles;
            document.getElementById('bloques-ocupados').textContent = data.estadisticas.ocupados;
            document.getElementById('porcentaje-ocupacion').textContent = data.estadisticas.porcentaje_ocupacion + '%';
            document.getElementById('total-empresas').textContent = data.empresas.length;

            // Actualizar hora
            actualizarHora();
            document.getElementById('ultima-actualizacion').textContent = 'Última actualización: ' + new Date().toLocaleTimeString();

            // Llenar filtro de rubros
            llenarFiltroRubros(data.empresas);

            // Generar vista correspondiente
            if (vistaActual === 'matriz') {
                generarVistaMatriz(data);
            } else {
                generarVistaCronograma(data);
            }

            // Aplicar filtros si hay
            aplicarFiltros();
        }

        // Generar vista matriz
        function generarVistaMatriz(data) {
            const header = document.getElementById('tabla-header');
            const body = document.getElementById('tabla-body');

            // Generar encabezado
            let headerHTML = '<tr><th class="border border-gray-700 empresa-cell sticky left-0">Empresa</th>';
            data.horarios.forEach(horario => {
                const esActual = esBloqueActualPorHorario(horario);
                const esProximo = esBloqueProximoPorHorario(horario);
                const claseExtra = esActual ? 'border-4 border-yellow-400' : (esProximo ? 'border-3 border-orange-500' : '');

                headerHTML += `
                    <th class="border border-gray-700 horario-header text-center ${claseExtra}">
                        ${formatearHorario(horario)}
                        ${esActual ? '<div class="text-sm mt-1"><i class="fas fa-bolt"></i> AHORA</div>' : ''}
                        ${esProximo ? '<div class="text-sm mt-1"><i class="fas fa-clock"></i> PRÓXIMO</div>' : ''}
                    </th>
                `;
            });
            headerHTML += '</tr>';
            header.innerHTML = headerHTML;

            // Generar cuerpo
            let bodyHTML = '';
            data.empresas.forEach(empresa => {
                bodyHTML += `
                    <tr class="empresa-row" data-empresa-id="${empresa.id}" data-empresa-nombre="${empresa.nombre.toLowerCase()}" data-empresa-rubro="${empresa.rubro || ''}">
                        <td class="border border-gray-700 empresa-cell sticky left-0">
                            <div class="flex items-center gap-3">
                                ${empresa.logo ? `<img src="<?php echo UPLOAD_URL; ?>${empresa.logo}" alt="${empresa.nombre}" class="empresa-logo">` : '<div class="w-14 h-14 bg-gray-300 rounded-lg flex items-center justify-center"><i class="fas fa-building text-gray-500"></i></div>'}
                                <div>
                                    <div class="font-bold text-base">${empresa.nombre}</div>
                                    <div class="text-xs text-gray-300">${empresa.rubro || 'Sin rubro'}</div>
                                </div>
                            </div>
                        </td>
                `;

                data.horarios.forEach(horario => {
                    const celda = data.matriz[horario] && data.matriz[horario][empresa.id];

                    if (celda) {
                        const claseEstado = celda.estado === 'disponible' ? 'bloque-disponible' : 'bloque-ocupado';
                        const esActual = esBloqueActualPorHorario(horario);
                        const esProximo = esBloqueProximoPorHorario(horario);
                        const claseActual = esActual ? 'bloque-actual' : (esProximo ? 'bloque-proximo' : '');

                        bodyHTML += `
                            <td class="border border-gray-700 bloque-cell ${claseEstado} ${claseActual}"
                                data-estado="${celda.estado}"
                                onclick='mostrarDetalleBloque(${JSON.stringify(celda)}, "${empresa.nombre}", "${horario}")'>
                                ${celda.estado === 'ocupado' && celda.pyme_nombre ? `
                                    <div class="flex flex-col items-center justify-center gap-2 h-full p-4">
                                        ${celda.pyme_logo ? `<img src="<?php echo UPLOAD_URL; ?>${celda.pyme_logo}" alt="${celda.pyme_nombre}" class="pyme-logo">` : '<i class="fas fa-user-tie text-3xl"></i>'}
                                        <div class="text-sm font-semibold text-center">${celda.pyme_nombre}</div>
                                    </div>
                                ` : `
                                    <div class="flex flex-col items-center justify-center h-full p-4">
                                        <i class="fas fa-check text-3xl mb-2"></i>
                                        <div class="text-base font-semibold">Disponible</div>
                                    </div>
                                `}
                            </td>
                        `;
                    } else {
                        bodyHTML += '<td class="border border-gray-700 bloque-cell bg-gray-100"></td>';
                    }
                });

                bodyHTML += '</tr>';
            });
            body.innerHTML = bodyHTML;

            document.getElementById('loading-matriz').classList.add('hidden');
            document.getElementById('tabla-container').classList.remove('hidden');
        }

        // Generar vista cronograma
        function generarVistaCronograma(data) {
            const container = document.getElementById('cronograma-container');
            let html = '';

            data.horarios.forEach(horario => {
                const esActual = esBloqueActualPorHorario(horario);
                const esProximo = esBloqueProximoPorHorario(horario);

                html += `
                    <div class="timeline-item">
                        <div class="timeline-dot ${esActual ? 'active' : ''}"></div>
                        <div class="bg-white rounded-lg shadow p-6 ${esActual ? 'border-4 border-yellow-400' : ''} ${esProximo ? 'border-3 border-orange-500' : ''}">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-xl font-bold text-gray-800">
                                    <i class="fas fa-clock text-blue-600"></i> ${formatearHorario(horario)}
                                    ${esActual ? '<span class="ml-2 px-3 py-1 bg-yellow-400 text-yellow-900 rounded-full text-sm"><i class="fas fa-bolt"></i> AHORA</span>' : ''}
                                    ${esProximo ? '<span class="ml-2 px-3 py-1 bg-orange-500 text-white rounded-full text-sm"><i class="fas fa-clock"></i> PRÓXIMO</span>' : ''}
                                </h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                `;

                data.empresas.forEach(empresa => {
                    const celda = data.matriz[horario] && data.matriz[horario][empresa.id];
                    if (celda) {
                        const estadoClass = celda.estado === 'disponible' ? 'bloque-disponible' : 'bloque-ocupado';
                        html += `
                            <div class="p-4 rounded-lg ${estadoClass} cursor-pointer hover:scale-105 transition"
                                 onclick='mostrarDetalleBloque(${JSON.stringify(celda)}, "${empresa.nombre}", "${horario}")'>
                                <div class="flex items-center gap-3 mb-2">
                                    ${empresa.logo ? `<img src="<?php echo UPLOAD_URL; ?>${empresa.logo}" alt="${empresa.nombre}" class="w-12 h-12 rounded">` : '<i class="fas fa-building text-2xl"></i>'}
                                    <div class="flex-1">
                                        <div class="font-bold">${empresa.nombre}</div>
                                        <div class="text-xs opacity-80">${empresa.rubro || 'Sin rubro'}</div>
                                    </div>
                                </div>
                                ${celda.estado === 'ocupado' && celda.pyme_nombre ? `
                                    <div class="mt-2 pt-2 border-t border-white/30">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-user-tie"></i>
                                            <span class="text-sm font-semibold">${celda.pyme_nombre}</span>
                                        </div>
                                    </div>
                                ` : `
                                    <div class="mt-2 text-center">
                                        <i class="fas fa-check text-xl"></i>
                                        <span class="ml-2 font-semibold">Disponible</span>
                                    </div>
                                `}
                            </div>
                        `;
                    }
                });

                html += `
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // Determinar si un horario es el bloque actual
        function esBloqueActualPorHorario(horario) {
            const horaActual = obtenerHoraActual();
            const horaActualStr = horaActual.getHours().toString().padStart(2, '0') + ':' +
                                  horaActual.getMinutes().toString().padStart(2, '0') + ':00';

            const [inicio, fin] = horario.split('-');
            const inicioStr = inicio + ':00';
            const finStr = fin + ':00';

            return horaActualStr >= inicioStr && horaActualStr < finStr;
        }

        // Determinar si un bloque es próximo (dentro de 15 minutos)
        function esBloqueProximoPorHorario(horario) {
            const horaActual = obtenerHoraActual();
            const [inicio] = horario.split('-');
            const [h, m] = inicio.split(':');

            const horaInicio = new Date(horaActual);
            horaInicio.setHours(parseInt(h), parseInt(m), 0);

            const diferencia = (horaInicio - horaActual) / 1000 / 60; // en minutos

            return diferencia > 0 && diferencia <= 15;
        }

        // Cambiar entre vistas
        function cambiarVista(vista) {
            vistaActual = vista;

            if (vista === 'matriz') {
                document.getElementById('vista-matriz').classList.remove('hidden');
                document.getElementById('vista-cronograma').classList.add('hidden');
                document.getElementById('btn-vista-matriz').classList.add('bg-blue-600', 'text-white');
                document.getElementById('btn-vista-matriz').classList.remove('bg-gray-300', 'text-gray-700');
                document.getElementById('btn-vista-cronograma').classList.remove('bg-blue-600', 'text-white');
                document.getElementById('btn-vista-cronograma').classList.add('bg-gray-300', 'text-gray-700');

                if (datosActuales) generarVistaMatriz(datosActuales);
            } else {
                document.getElementById('vista-matriz').classList.add('hidden');
                document.getElementById('vista-cronograma').classList.remove('hidden');
                document.getElementById('btn-vista-cronograma').classList.add('bg-blue-600', 'text-white');
                document.getElementById('btn-vista-cronograma').classList.remove('bg-gray-300', 'text-gray-700');
                document.getElementById('btn-vista-matriz').classList.remove('bg-blue-600', 'text-white');
                document.getElementById('btn-vista-matriz').classList.add('bg-gray-300', 'text-gray-700');

                if (datosActuales) generarVistaCronograma(datosActuales);
            }
        }

        // Llenar filtro de rubros
        function llenarFiltroRubros(empresas) {
            const select = document.getElementById('filter-rubro');
            const rubros = [...new Set(empresas.map(e => e.rubro).filter(r => r))];

            let options = '<option value="">Todos</option>';
            rubros.forEach(rubro => {
                options += `<option value="${rubro}">${rubro}</option>`;
            });
            select.innerHTML = options;
        }

        // Aplicar filtros
        function aplicarFiltros() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const filterEstado = document.getElementById('filter-estado').value;
            const filterRubro = document.getElementById('filter-rubro').value;

            const rows = document.querySelectorAll('.empresa-row');
            let visibles = 0;

            rows.forEach(row => {
                const nombre = row.getAttribute('data-empresa-nombre');
                const rubro = row.getAttribute('data-empresa-rubro');

                let mostrar = true;

                // Filtro de búsqueda
                if (searchTerm && !nombre.includes(searchTerm)) {
                    mostrar = false;
                }

                // Filtro de rubro
                if (filterRubro && rubro !== filterRubro) {
                    mostrar = false;
                }

                // Filtro de estado
                if (filterEstado) {
                    const bloques = row.querySelectorAll(`[data-estado="${filterEstado}"]`);
                    if (bloques.length === 0) {
                        mostrar = false;
                    }
                }

                row.style.display = mostrar ? '' : 'none';
                if (mostrar) visibles++;
            });

            actualizarChipsFiltros(searchTerm, filterEstado, filterRubro, visibles);
        }

        // Actualizar chips de filtros activos
        function actualizarChipsFiltros(search, estado, rubro, visibles) {
            const container = document.getElementById('filtros-activos');
            let html = '';

            if (search) {
                html += `<span class="filter-chip px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">
                    Búsqueda: "${search}" <i class="fas fa-times ml-1 cursor-pointer" onclick="limpiarFiltro('search')"></i>
                </span>`;
            }
            if (estado) {
                html += `<span class="filter-chip px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">
                    Estado: ${estado} <i class="fas fa-times ml-1 cursor-pointer" onclick="limpiarFiltro('estado')"></i>
                </span>`;
            }
            if (rubro) {
                html += `<span class="filter-chip px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm">
                    Rubro: ${rubro} <i class="fas fa-times ml-1 cursor-pointer" onclick="limpiarFiltro('rubro')"></i>
                </span>`;
            }

            html += `<span class="text-sm text-gray-600 px-3 py-1">${visibles} empresa(s) visible(s)</span>`;

            container.innerHTML = html;
        }

        // Limpiar un filtro específico
        function limpiarFiltro(tipo) {
            if (tipo === 'search') document.getElementById('search-input').value = '';
            if (tipo === 'estado') document.getElementById('filter-estado').value = '';
            if (tipo === 'rubro') document.getElementById('filter-rubro').value = '';
            aplicarFiltros();
        }

        // Limpiar todos los filtros
        function limpiarFiltros() {
            document.getElementById('search-input').value = '';
            document.getElementById('filter-estado').value = '';
            document.getElementById('filter-rubro').value = '';
            aplicarFiltros();
        }

        // Mostrar detalle de bloque en modal
        function mostrarDetalleBloque(celda, empresaNombre, horario) {
            const modal = document.getElementById('modal-bloque');
            const body = document.getElementById('modal-body');

            const estado = celda.estado === 'disponible' ?
                '<span class="px-3 py-1 bg-green-500 text-white rounded-full"><i class="fas fa-check"></i> Disponible</span>' :
                '<span class="px-3 py-1 bg-gray-500 text-white rounded-full"><i class="fas fa-users"></i> Ocupado</span>';

            body.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <label class="text-sm text-gray-600">Empresa A:</label>
                        <div class="text-lg font-bold">${empresaNombre}</div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Horario:</label>
                        <div class="text-lg font-bold">${formatearHorario(horario)}</div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Estado:</label>
                        <div class="mt-1">${estado}</div>
                    </div>
                    ${celda.estado === 'ocupado' && celda.pyme_nombre ? `
                        <div class="border-t pt-4">
                            <label class="text-sm text-gray-600">Reunión con:</label>
                            <div class="text-lg font-bold mt-1">${celda.pyme_nombre}</div>
                            ${celda.pyme_logo ? `<img src="<?php echo UPLOAD_URL; ?>${celda.pyme_logo}" alt="${celda.pyme_nombre}" class="mt-3 w-20 h-20 rounded-lg">` : ''}
                        </div>
                    ` : ''}
                    <div class="border-t pt-4">
                        <div class="text-sm text-gray-500">ID del bloque: ${celda.bloque_id}</div>
                    </div>
                </div>
            `;

            modal.classList.add('active');
        }

        // Cerrar modal
        function cerrarModal() {
            document.getElementById('modal-bloque').classList.remove('active');
        }

        // Pantalla completa
        function toggleFullscreen() {
            window.location.href = '<?php echo BASE_URL; ?>views/visor-rueda.php?fullscreen=1&hora=' + horaSimulada;
        }

        function exitFullscreen() {
            window.location.href = '<?php echo BASE_URL; ?>views/visor-rueda.php?hora=' + horaSimulada;
        }

        // Exportar datos
        function exportarDatos() {
            if (!datosActuales) return;

            const csv = generarCSV(datosActuales);
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `rueda-negocios-${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Generar CSV
        function generarCSV(data) {
            let csv = 'Empresa,Rubro,Horario,Estado,PYME\n';

            data.empresas.forEach(empresa => {
                data.horarios.forEach(horario => {
                    const celda = data.matriz[horario] && data.matriz[horario][empresa.id];
                    if (celda) {
                        csv += `"${empresa.nombre}","${empresa.rubro || ''}","${horario}","${celda.estado}","${celda.pyme_nombre || ''}"\n`;
                    }
                });
            });

            return csv;
        }

        // Utilidades de formato
        function formatearFecha(fecha) {
            const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            const d = new Date(fecha + 'T00:00:00');
            return `${d.getDate()} ${meses[d.getMonth()]} ${d.getFullYear()}`;
        }

        function formatearHorario(horario) {
            const [inicio, fin] = horario.split('-');
            return `${inicio} - ${fin}`;
        }

        function actualizarHora() {
            const ahora = obtenerHoraActual();
            const horas = String(ahora.getHours()).padStart(2, '0');
            const minutos = String(ahora.getMinutes()).padStart(2, '0');
            document.getElementById('hora-actual').textContent = `${horas}:${minutos}`;
        }

        function mostrarError(mensaje) {
            // Implementar notificación de error
            console.error(mensaje);
        }

        // Contador de actualización
        function iniciarContador() {
            intervaloContador = setInterval(() => {
                contadorActualizacion--;
                document.getElementById('contador-actualizacion').textContent = contadorActualizacion;

                if (contadorActualizacion <= 0) {
                    resetearContador();
                }
            }, 1000);
        }

        function resetearContador() {
            contadorActualizacion = 30;
            document.getElementById('contador-actualizacion').textContent = contadorActualizacion;
        }

        // Inicialización
        cargarBloques();
        setInterval(actualizarHora, 1000);
        setInterval(cargarBloques, 30000);
        iniciarContador();

        // Cerrar modal con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') cerrarModal();
        });

        // Cerrar modal al hacer clic fuera
        document.getElementById('modal-bloque').addEventListener('click', (e) => {
            if (e.target.id === 'modal-bloque') cerrarModal();
        });
    </script>
</body>
</html>
