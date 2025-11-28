<?php
/**
 * Visor de Rueda de Negocios - Vista por Mesas
 * Ruta: views/visor-rueda.php
 *
 * Eje X: 15 Mesas
 * Eje Y: Empresas Grandes (Demandantes)
 */
require_once '../config/config.php';

$fullscreen = isset($_GET['fullscreen']) ? true : false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Mesas - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        :root {
            --color-disponible: #10b981;
            --color-ocupado: #3b82f6;
            --color-pendiente: #f59e0b;
            --bg-primary: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 14px;
            background: var(--bg-primary);
        }

        .tabla-mesas {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
        }

        .tabla-mesas th,
        .tabla-mesas td {
            border: none;
            padding: 12px 8px;
            text-align: center;
            vertical-align: middle;
        }

        .mesa-header {
            background: linear-gradient(180deg, #ffffff 0%, #f1f5f9 100%);
            color: #475569;
            font-weight: 600;
            font-size: 12px;
            padding: 16px 8px;
            min-width: 70px;
            position: sticky;
            top: 0;
            z-index: 20;
            border-bottom: 2px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .mesa-header i {
            display: block;
            margin-bottom: 4px;
            color: #3b82f6;
            font-size: 14px;
        }

        .empresa-cell {
            background: linear-gradient(90deg, #334155 0%, #475569 100%);
            color: white;
            font-weight: 600;
            font-size: 13px;
            padding: 14px 12px;
            min-width: 200px;
            max-width: 220px;
            position: sticky;
            left: 0;
            z-index: 15;
            text-align: left;
            border-right: 3px solid #1e293b;
            box-shadow: 2px 0 4px rgba(0,0,0,0.1);
        }

        .celda-disponible {
            background: #ecfdf5;
            color: #047857;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 70px;
            height: 75px;
            font-weight: 600;
            border: 2px solid #d1fae5;
            border-radius: 6px;
        }

        .celda-disponible:hover {
            background: #d1fae5;
            transform: translateY(-2px);
            z-index: 5;
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.15);
            border-color: #10b981;
        }

        .celda-ocupada {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            color: #1e40af;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 70px;
            height: 75px;
            font-weight: 600;
            border: 2px solid #bfdbfe;
            border-radius: 6px;
        }

        .celda-ocupada:hover {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            transform: translateY(-2px);
            z-index: 5;
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.2);
            border-color: #3b82f6;
        }

        .celda-pendiente {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            color: #92400e;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            border: 2px solid #fde68a;
            border-radius: 6px;
            min-width: 70px;
            height: 75px;
        }

        .celda-pendiente:hover {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            transform: translateY(-2px);
            z-index: 5;
            box-shadow: 0 8px 16px rgba(245, 158, 11, 0.2);
            border-color: #f59e0b;
        }

        .celda-no-disponible {
            background: #fafafa;
            color: #e5e7eb;
            min-width: 70px;
            height: 75px;
            border: 1px solid #f3f4f6;
            border-radius: 6px;
        }

        .pyme-mini-logo {
            width: 36px;
            height: 36px;
            object-fit: contain;
            background: white;
            border-radius: 6px;
            padding: 4px;
            margin: 0 auto 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .empresa-logo-mini {
            width: 40px;
            height: 40px;
            object-fit: contain;
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
            padding: 4px;
            margin-right: 10px;
            display: inline-block;
            vertical-align: middle;
            border: 2px solid rgba(255,255,255,0.2);
        }

        .empresa-row {
            transition: all 0.2s ease;
        }

        .empresa-row:hover {
            background: rgba(0,0,0,0.02);
        }

        .empresa-row:nth-child(even) {
            background: rgba(0,0,0,0.01);
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
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .stat-card {
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
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
        .tabla-mesas th,
        .tabla-mesas td {
            padding: 6px 3px;
            font-size: 11px;
        }
        <?php endif; ?>

        @media (max-width: 1400px) {
            .tabla-mesas {
                font-size: 11px;
            }
            .mesa-header {
                min-width: 55px;
                font-size: 11px;
                padding: 10px 3px;
            }
            .celda-disponible,
            .celda-ocupada,
            .celda-no-disponible {
                min-width: 55px;
                height: 60px;
            }
        }
    </style>
</head>
<body class="bg-gray-100">

    <?php if(!$fullscreen): ?>
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-4 shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">🎯 Visor de Mesas - Rueda de Negocios</h1>
                    <p class="mt-1 text-blue-100 text-sm">Vista por Empresas Demandantes y Mesas</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="toggleFullscreen()" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition text-sm">
                        <i class="fas fa-expand"></i> Pantalla Completa
                    </button>
                    <button onclick="exportarDatos()" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition text-sm">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Header compacto -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-2 shadow">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <h1 class="text-lg font-bold">🎯 Visor de Mesas - Rueda de Negocios</h1>
            <button onclick="exitFullscreen()" class="bg-white/20 hover:bg-white/30 px-3 py-1 rounded text-sm">
                <i class="fas fa-compress"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <div class="container mx-auto px-4 py-4">

        <!-- Estadísticas y controles -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
            <div class="bg-white rounded-lg shadow p-3 stat-card">
                <div class="text-gray-500 text-xs">Total Mesas</div>
                <div class="text-xl font-bold text-blue-600" id="total-mesas">15</div>
            </div>
            <div class="bg-white rounded-lg shadow p-3 stat-card">
                <div class="text-gray-500 text-xs">Empresas Activas</div>
                <div class="text-xl font-bold text-gray-700" id="total-empresas">0</div>
            </div>
            <div class="bg-white rounded-lg shadow p-3 stat-card">
                <div class="text-gray-500 text-xs">Disponibles</div>
                <div class="text-xl font-bold text-green-600" id="slots-disponibles">0</div>
            </div>
            <div class="bg-white rounded-lg shadow p-3 stat-card">
                <div class="text-gray-500 text-xs">Ocupadas</div>
                <div class="text-xl font-bold text-blue-600" id="slots-ocupados">0</div>
            </div>
            <div class="bg-white rounded-lg shadow p-3 stat-card">
                <div class="text-gray-500 text-xs">% Ocupación</div>
                <div class="text-xl font-bold text-purple-600" id="porcentaje-ocupacion">0%</div>
            </div>
        </div>

        <!-- Selector de bloque y reloj -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <div class="flex flex-wrap gap-4 justify-between items-center">
                <div class="flex items-center gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-1">
                            <i class="fas fa-clock text-purple-600"></i> Bloque Horario
                        </label>
                        <select id="selector-bloque" onchange="cambiarBloque(this.value)"
                                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <!-- Se llena dinámicamente -->
                        </select>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Hora actual:</span>
                        <span class="text-lg font-bold text-blue-600 ml-2" id="hora-actual">--:--</span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-sm text-gray-600">Actualización automática</span>
                    </div>
                    <button onclick="recargarDatos()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Leyenda -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <div class="flex flex-wrap gap-6 justify-center items-center text-sm">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 celda-disponible rounded flex items-center justify-center">
                        <i class="fas fa-check"></i>
                    </div>
                    <span class="font-medium">Disponible</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 celda-ocupada rounded flex items-center justify-center">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="font-medium">Reunión Confirmada</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 celda-pendiente rounded flex items-center justify-center">
                        <i class="fas fa-clock"></i>
                    </div>
                    <span class="font-medium">Reunión Pendiente</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 celda-no-disponible rounded border border-gray-200 flex items-center justify-center">
                        <span class="text-gray-300 text-xs">—</span>
                    </div>
                    <span class="font-medium text-gray-500">No Disponible</span>
                </div>
            </div>
        </div>

        <!-- Tabla de Mesas -->
        <div class="bg-white rounded-lg shadow-xl overflow-x-auto">
            <div id="loading" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600"></div>
                <p class="mt-4 text-gray-600 text-lg">Cargando disposición de mesas...</p>
            </div>

            <div id="tabla-container" class="hidden">
                <table class="tabla-mesas">
                    <thead id="tabla-header">
                        <!-- Generado dinámicamente -->
                    </thead>
                    <tbody id="tabla-body">
                        <!-- Generado dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-4 text-center text-gray-500 text-xs">
            <p>Sistema de Rueda de Negocios - <?php echo SITE_NAME; ?></p>
            <p class="mt-1">Actualización automática cada 30 segundos</p>
        </div>
    </div>

    <!-- Modal de detalle -->
    <div id="modal-detalle" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-xl font-bold text-gray-800">Detalle de la Mesa</h3>
                <button onclick="cerrarModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modal-body"></div>
        </div>
    </div>

    <script>
        let datosActuales = null;
        let bloqueActualId = null;

        // Cargar datos desde la API
        async function cargarDatos(bloqueId = null) {
            try {
                const url = bloqueId
                    ? '<?php echo BASE_URL; ?>api/get_mesas_visor.php?bloque_id=' + bloqueId
                    : '<?php echo BASE_URL; ?>api/get_mesas_visor.php';

                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    datosActuales = data;
                    bloqueActualId = data.bloque_actual.id;
                    actualizarInterfaz(data);
                } else {
                    console.error('Error API:', data.mensaje);
                    mostrarError(data.mensaje || 'Error al cargar los datos');
                }
            } catch (error) {
                console.error('Error al cargar datos:', error);
                mostrarError('Error de conexión: ' + error.message + '\n\nVerifique que:\n- La base de datos esté configurada\n- Existan bloques horarios\n- Existan empresas registradas');
            }
        }

        // Actualizar toda la interfaz
        function actualizarInterfaz(data) {
            // Calcular empresas activas (con disponibilidad o reuniones)
            const empresasActivas = data.empresas.filter(empresa => {
                const filaEmpresa = data.matriz[empresa.id];
                for (let mesa = 1; mesa <= 15; mesa++) {
                    const celda = filaEmpresa[mesa];
                    if (celda.estado === 'disponible' || celda.estado === 'confirmada' || celda.estado === 'pendiente') {
                        return true;
                    }
                }
                return false;
            });

            // Estadísticas
            document.getElementById('total-mesas').textContent = data.estadisticas.total_mesas;
            document.getElementById('total-empresas').textContent = empresasActivas.length + ' / ' + data.estadisticas.total_empresas;
            document.getElementById('slots-disponibles').textContent = data.estadisticas.slots_disponibles;
            document.getElementById('slots-ocupados').textContent = data.estadisticas.slots_ocupados;
            document.getElementById('porcentaje-ocupacion').textContent = data.estadisticas.porcentaje_ocupacion + '%';

            // Selector de bloques
            const selector = document.getElementById('selector-bloque');
            selector.innerHTML = '';
            data.bloques.forEach(bloque => {
                const option = document.createElement('option');
                option.value = bloque.id;
                option.textContent = `${bloque.hora_inicio.substring(0,5)} - ${bloque.hora_fin.substring(0,5)} (Bloque ${bloque.orden})`;
                if (bloque.id == data.bloque_actual.id) {
                    option.selected = true;
                }
                selector.appendChild(option);
            });

            // Hora actual
            actualizarHora();

            // Generar tabla
            generarTabla(data);

            // Mostrar tabla
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('tabla-container').classList.remove('hidden');
        }

        // Generar tabla de mesas
        function generarTabla(data) {
            const header = document.getElementById('tabla-header');
            const body = document.getElementById('tabla-body');

            // Encabezado: Empresa + 15 mesas
            let headerHTML = '<tr>';
            headerHTML += '<th class="empresa-cell sticky left-0">Empresa Demandante</th>';
            for (let mesa = 1; mesa <= 15; mesa++) {
                headerHTML += `<th class="mesa-header"><i class="fas fa-table-cells mb-1 block"></i>Mesa ${mesa}</th>`;
            }
            headerHTML += '</tr>';
            header.innerHTML = headerHTML;

            // Filtrar empresas que tienen actividad (disponibilidad o reuniones)
            const empresasActivas = data.empresas.filter(empresa => {
                const filaEmpresa = data.matriz[empresa.id];
                // Verificar si tiene al menos una mesa disponible o con reunión
                for (let mesa = 1; mesa <= 15; mesa++) {
                    const celda = filaEmpresa[mesa];
                    if (celda.estado === 'disponible' || celda.estado === 'confirmada' || celda.estado === 'pendiente') {
                        return true; // Esta empresa tiene actividad
                    }
                }
                return false; // No tiene ninguna actividad
            });

            // Cuerpo: Una fila por empresa (solo las activas)
            let bodyHTML = '';
            empresasActivas.forEach(empresa => {
                bodyHTML += '<tr class="empresa-row">';

                // Columna de empresa
                const logoHTML = empresa.logo
                    ? `<img src="<?php echo UPLOAD_URL; ?>${empresa.logo}" alt="${empresa.nombre}" class="empresa-logo-mini">`
                    : '<i class="fas fa-building empresa-logo-mini text-white text-xl"></i>';

                bodyHTML += `
                    <td class="empresa-cell sticky left-0">
                        ${logoHTML}
                        <div class="inline-block align-middle">
                            <div class="font-bold text-sm leading-tight mb-1">${empresa.nombre}</div>
                            <div class="text-xs opacity-75">${empresa.rubro || 'Sin rubro'}</div>
                        </div>
                    </td>
                `;

                // 15 celdas de mesas
                for (let mesa = 1; mesa <= 15; mesa++) {
                    const celda = data.matriz[empresa.id][mesa];
                    let celdaHTML = '';
                    let claseEstado = 'celda-no-disponible';

                    if (celda.estado === 'disponible') {
                        claseEstado = 'celda-disponible';
                        celdaHTML = `
                            <div onclick='mostrarDetalle(${JSON.stringify(celda)}, "${empresa.nombre}", ${mesa}, "${data.bloque_actual.hora_inicio}", "${data.bloque_actual.hora_fin}")' class="h-full flex flex-col items-center justify-center p-2">
                                <i class="fas fa-check-circle text-2xl mb-1"></i>
                                <div class="text-xs font-semibold">Disponible</div>
                            </div>
                        `;
                    } else if (celda.estado === 'confirmada' || celda.estado === 'pendiente') {
                        claseEstado = celda.estado === 'confirmada' ? 'celda-ocupada' : 'celda-pendiente';
                        const pyme = celda.reunion;
                        const logoImg = pyme.pyme_logo
                            ? `<img src="<?php echo UPLOAD_URL; ?>${pyme.pyme_logo}" alt="${pyme.pyme_nombre}" class="pyme-mini-logo">`
                            : '<i class="fas fa-user-tie text-xl mb-1"></i>';

                        celdaHTML = `
                            <div onclick='mostrarDetalle(${JSON.stringify(celda)}, "${empresa.nombre}", ${mesa}, "${data.bloque_actual.hora_inicio}", "${data.bloque_actual.hora_fin}")' class="h-full flex flex-col items-center justify-center p-2">
                                ${logoImg}
                                <div class="text-xs font-semibold leading-tight text-center">${truncate(pyme.pyme_nombre, 18)}</div>
                            </div>
                        `;
                    } else {
                        celdaHTML = '<span class="text-gray-300 text-xs">—</span>';
                    }

                    bodyHTML += `<td class="${claseEstado}">${celdaHTML}</td>`;
                }

                bodyHTML += '</tr>';
            });

            body.innerHTML = bodyHTML;
        }

        // Cambiar bloque horario
        function cambiarBloque(bloqueId) {
            cargarDatos(bloqueId);
        }

        // Recargar datos
        function recargarDatos() {
            cargarDatos(bloqueActualId);
        }

        // Mostrar detalle en modal
        function mostrarDetalle(celda, empresaNombre, mesa, horaInicio, horaFin) {
            const modal = document.getElementById('modal-detalle');
            const body = document.getElementById('modal-body');

            let contenido = `
                <div class="space-y-4">
                    <div>
                        <label class="text-sm text-gray-600">Empresa Demandante:</label>
                        <div class="text-lg font-bold">${empresaNombre}</div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Mesa:</label>
                        <div class="text-lg font-bold">Mesa ${mesa}</div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Horario:</label>
                        <div class="text-lg font-bold">${horaInicio.substring(0,5)} - ${horaFin.substring(0,5)}</div>
                    </div>
            `;

            if (celda.reunion) {
                const pyme = celda.reunion;
                const estadoColor = celda.estado === 'confirmada' ? 'bg-blue-500' : 'bg-yellow-500';

                contenido += `
                    <div class="border-t pt-4">
                        <span class="px-3 py-1 ${estadoColor} text-white rounded-full text-sm font-semibold">
                            <i class="fas fa-${celda.estado === 'confirmada' ? 'check-circle' : 'clock'}"></i>
                            ${celda.estado.toUpperCase()}
                        </span>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Reunión con:</label>
                        <div class="text-lg font-bold mt-1">${pyme.pyme_nombre}</div>
                        ${pyme.pyme_rubro ? `<div class="text-sm text-gray-600">${pyme.pyme_rubro}</div>` : ''}
                        ${pyme.pyme_logo ? `<img src="<?php echo UPLOAD_URL; ?>${pyme.pyme_logo}" alt="${pyme.pyme_nombre}" class="mt-3 w-20 h-20 rounded-lg">` : ''}
                    </div>
                    ${pyme.notas ? `
                        <div>
                            <label class="text-sm text-gray-600">Notas:</label>
                            <div class="text-sm mt-1 bg-gray-50 p-3 rounded">${pyme.notas}</div>
                        </div>
                    ` : ''}
                `;
            } else {
                contenido += `
                    <div class="border-t pt-4">
                        <span class="px-3 py-1 bg-green-500 text-white rounded-full text-sm font-semibold">
                            <i class="fas fa-check"></i> DISPONIBLE
                        </span>
                    </div>
                `;
            }

            contenido += '</div>';
            body.innerHTML = contenido;
            modal.classList.add('active');
        }

        // Cerrar modal
        function cerrarModal() {
            document.getElementById('modal-detalle').classList.remove('active');
        }

        // Actualizar hora
        function actualizarHora() {
            const ahora = new Date();
            const horas = String(ahora.getHours()).padStart(2, '0');
            const minutos = String(ahora.getMinutes()).padStart(2, '0');
            document.getElementById('hora-actual').textContent = `${horas}:${minutos}`;
        }

        // Truncar texto
        function truncate(str, n) {
            return (str.length > n) ? str.substr(0, n-1) + '...' : str;
        }

        // Fullscreen
        function toggleFullscreen() {
            window.location.href = '<?php echo BASE_URL; ?>views/visor-rueda.php?fullscreen=1';
        }

        function exitFullscreen() {
            window.location.href = '<?php echo BASE_URL; ?>views/visor-rueda.php';
        }

        // Exportar a CSV
        function exportarDatos() {
            if (!datosActuales) return;

            let csv = 'Empresa,Mesa,Estado,PYME,Horario\n';
            const bloque = datosActuales.bloque_actual;
            const horario = `${bloque.hora_inicio.substring(0,5)}-${bloque.hora_fin.substring(0,5)}`;

            datosActuales.empresas.forEach(empresa => {
                for (let mesa = 1; mesa <= 15; mesa++) {
                    const celda = datosActuales.matriz[empresa.id][mesa];
                    const pyme = celda.reunion ? celda.reunion.pyme_nombre : '';
                    csv += `"${empresa.nombre}",${mesa},${celda.estado},"${pyme}","${horario}"\n`;
                }
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `mesas-rueda-negocios-${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        function mostrarError(mensaje) {
            console.error(mensaje);

            // Mostrar error en el contenedor de loading
            const loadingDiv = document.getElementById('loading');
            loadingDiv.innerHTML = `
                <div class="py-12">
                    <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Error al cargar datos</h3>
                    <p class="text-gray-600 mb-4">${mensaje}</p>
                    <button onclick="location.reload()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        <i class="fas fa-sync-alt mr-2"></i>Reintentar
                    </button>
                </div>
            `;
            loadingDiv.classList.remove('hidden');
            document.getElementById('tabla-container').classList.add('hidden');
        }

        // Inicialización
        cargarDatos();
        setInterval(actualizarHora, 1000);
        setInterval(recargarDatos, 30000);

        // Cerrar modal con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') cerrarModal();
        });

        // Cerrar modal al hacer clic fuera
        document.getElementById('modal-detalle').addEventListener('click', (e) => {
            if (e.target.id === 'modal-detalle') cerrarModal();
        });
    </script>
</body>
</html>
