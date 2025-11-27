<?php
/**
 * Visor público de bloques en tiempo real
 * Ruta: views/visor-bloques.php
 * 
 * Parámetro URL para simulación:
 * ?hora=10:30 - Simula que la hora actual es 10:30
 */
require_once '../config/config.php';

// Capturar parámetro de simulación
$hora_simulada = isset($_GET['hora']) ? $_GET['hora'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Bloques - Rueda de Negocios Arica</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bloque-disponible {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        .bloque-ocupado {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }
        .bloque-actual {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            border: 4px solid #fbbf24;
            box-shadow: 0 0 30px rgba(251, 191, 36, 0.6);
            transform: scale(1.02);
        }
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                box-shadow: 0 0 30px rgba(251, 191, 36, 0.6);
            }
            50% {
                opacity: 0.9;
                box-shadow: 0 0 50px rgba(251, 191, 36, 0.8);
            }
        }
        .empresa-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        .pyme-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }
        .tabla-bloques {
            width: 100%;
        }
        .tabla-bloques td, .tabla-bloques th {
            min-height: 100px;
            vertical-align: middle;
        }
        .empresa-row {
            transition: all 0.3s ease;
        }
        .empresa-row:hover {
            background-color: #f9fafb;
        }
        .horario-header {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%);
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            padding: 1rem;
            min-width: 200px;
        }
        .empresa-cell {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            color: white;
            font-weight: 600;
            min-width: 200px;
            padding: 1rem;
        }
        .bloque-cell {
            min-width: 200px;
            min-height: 100px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        .slide-transition {
            transition: all 0.8s ease-in-out;
        }
    </style>
</head>
<body class="bg-gray-100">
    
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-6 shadow-lg">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold text-center">🎯 Rueda de Negocios Arica 2025</h1>
            <p class="text-center mt-2 text-blue-100">Visor de Bloques en Tiempo Real</p>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="container mx-auto px-4 py-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-gray-500 text-sm">Fecha Evento</div>
                <div class="text-2xl font-bold text-blue-600" id="fecha-evento">--</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-gray-500 text-sm">Total Bloques</div>
                <div class="text-2xl font-bold text-gray-700" id="total-bloques">0</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-gray-500 text-sm">Disponibles</div>
                <div class="text-2xl font-bold text-green-600" id="bloques-disponibles">0</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-gray-500 text-sm">Ocupados</div>
                <div class="text-2xl font-bold text-gray-600" id="bloques-ocupados">0</div>
            </div>
        </div>

        <!-- Reloj y actualización -->
        <div class="bg-white rounded-lg shadow p-4 mb-6 flex justify-between items-center">
            <div>
                <span class="text-gray-600">Hora actual:</span>
                <span class="text-xl font-bold text-blue-600 ml-2" id="hora-actual">--:--</span>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-sm text-gray-600">Actualización automática</span>
                </div>
                <span class="text-sm text-gray-500" id="ultima-actualizacion">--</span>
            </div>
        </div>

        <!-- Leyenda -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex flex-wrap gap-8 justify-center items-center">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bloque-disponible rounded-lg shadow-md"></div>
                    <span class="text-base font-medium">Disponible</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bloque-ocupado rounded-lg shadow-md"></div>
                    <span class="text-base font-medium">Ocupado</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white border-4 border-yellow-400 rounded-lg shadow-lg"></div>
                    <span class="text-base font-medium">⚡ Bloque Activo Ahora</span>
                </div>
            </div>
        </div>

        <!-- Tabla de Bloques -->
        <div class="bg-white rounded-lg shadow-xl overflow-hidden">
            <div class="p-6 bg-gradient-to-r from-blue-600 to-purple-600">
                <h2 class="text-2xl font-bold text-white mb-2">📅 Agenda en Tiempo Real</h2>
                <p class="text-blue-100">Mostrando 3 bloques centrados en el horario actual</p>
            </div>
            <div class="overflow-x-auto">
                <div id="loading" class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600"></div>
                    <p class="mt-4 text-gray-600 text-lg">Cargando bloques...</p>
                </div>
                <div id="tabla-container" class="hidden slide-transition">
                    <table class="tabla-bloques border-collapse">
                        <thead id="tabla-header">
                            <!-- Se genera dinámicamente -->
                        </thead>
                        <tbody id="tabla-body">
                            <!-- Se genera dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer con info -->
        <div class="mt-6 text-center text-gray-500 text-sm">
            <p>Sistema de Rueda de Negocios - Arica 2025</p>
            <p class="mt-1">Actualización automática cada 30 segundos</p>
        </div>
    </div>

    <script>
        let datosActuales = null;
        
        // Hora simulada desde URL (para presentaciones)
        const horaSimulada = '<?php echo $hora_simulada ? $hora_simulada : ""; ?>';
        
        // Función para obtener la hora actual (real o simulada)
        function obtenerHoraActual() {
            if (horaSimulada) {
                const [horas, minutos] = horaSimulada.split(':');
                const ahora = new Date();
                ahora.setHours(parseInt(horas), parseInt(minutos), 0, 0);
                return ahora;
            }
            return new Date();
        }

        // Función para cargar los bloques
        async function cargarBloques() {
            try {
                const response = await fetch('<?php echo BASE_URL; ?>api/get_bloques_visor.php');
                const data = await response.json();
                
                if (data.success) {
                    datosActuales = data;
                    actualizarInterfaz(data);
                } else {
                    console.error('Error:', data.mensaje);
                }
            } catch (error) {
                console.error('Error al cargar bloques:', error);
            }
        }

        // Función para actualizar la interfaz
        function actualizarInterfaz(data) {
            // Actualizar estadísticas
            document.getElementById('fecha-evento').textContent = formatearFecha(data.fecha_evento);
            document.getElementById('total-bloques').textContent = data.estadisticas.total_bloques;
            document.getElementById('bloques-disponibles').textContent = data.estadisticas.disponibles;
            document.getElementById('bloques-ocupados').textContent = data.estadisticas.ocupados;
            
            // Actualizar hora
            actualizarHora();
            document.getElementById('ultima-actualizacion').textContent = 'Última actualización: ' + new Date().toLocaleTimeString();
            
            // Generar tabla
            generarTabla(data);
            
            // Mostrar tabla y ocultar loading
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('tabla-container').classList.remove('hidden');
        }

        // Función para determinar si un horario es el bloque activo
        function esBloqueActual(horario) {
            const horaActual = obtenerHoraActual();
            const horaActualStr = horaActual.getHours().toString().padStart(2, '0') + ':' + 
                                  horaActual.getMinutes().toString().padStart(2, '0') + ':00';
            
            const [inicio, fin] = horario.split('-');
            const inicioStr = inicio + ':00';
            const finStr = fin + ':00';
            
            return horaActualStr >= inicioStr && horaActualStr < finStr;
        }
        
        // Función para generar la tabla
        function generarTabla(data) {
            const header = document.getElementById('tabla-header');
            const body = document.getElementById('tabla-body');
            
            // Determinar qué 3 bloques mostrar
            const bloquesMostrar = seleccionarBloquesMostrar(data.horarios);
            
            // Generar encabezado (horarios en horizontal)
            let headerHTML = '<tr><th class="border border-gray-700 empresa-cell sticky left-0">Empresa</th>';
            bloquesMostrar.forEach(horario => {
                headerHTML += `
                    <th class="border border-gray-700 horario-header text-center">
                        ${formatearHorario(horario)}
                    </th>
                `;
            });
            headerHTML += '</tr>';
            header.innerHTML = headerHTML;
            
            // Generar cuerpo (una fila por empresa)
            let bodyHTML = '';
            data.empresas.forEach(empresa => {
                bodyHTML += `
                    <tr class="empresa-row">
                        <td class="border border-gray-700 empresa-cell sticky left-0">
                            <div class="flex items-center gap-3">
                                ${empresa.logo ? `<img src="<?php echo UPLOAD_URL; ?>${empresa.logo}" alt="${empresa.nombre}" class="empresa-logo">` : ''}
                                <div>
                                    <div class="font-bold text-base">${empresa.nombre}</div>
                                    <div class="text-xs text-gray-300">${empresa.rubro || ''}</div>
                                </div>
                            </div>
                        </td>
                `;
                
                // Agregar celdas de bloques
                bloquesMostrar.forEach(horario => {
                    const celda = data.matriz[horario] && data.matriz[horario][empresa.id];
                    
                    if (celda) {
                        const claseEstado = celda.estado === 'disponible' ? 'bloque-disponible' : 'bloque-ocupado';
                        
                        // Determinar si este bloque está activo usando la hora simulada o actual
                        const esActual = esBloqueActual(horario);
                        const claseActual = esActual ? 'bloque-actual' : '';
                        
                        bodyHTML += `
                            <td class="border border-gray-700 bloque-cell ${claseEstado} ${claseActual}">
                                ${celda.estado === 'ocupado' && celda.pyme_nombre ? `
                                    <div class="flex flex-col items-center justify-center gap-2 h-full p-4">
                                        ${celda.pyme_logo ? `<img src="<?php echo UPLOAD_URL; ?>${celda.pyme_logo}" alt="${celda.pyme_nombre}" class="pyme-logo">` : ''}
                                        <div class="text-sm font-semibold">${celda.pyme_nombre}</div>
                                    </div>
                                ` : `
                                    <div class="flex items-center justify-center h-full p-4">
                                        <div class="text-base font-semibold">✓ Disponible</div>
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
        }
        
        // Función para seleccionar qué 3 bloques mostrar (centrados en el actual)
        function seleccionarBloquesMostrar(horarios) {
            if (horarios.length <= 3) {
                return horarios; // Si hay 3 o menos, mostrar todos
            }
            
            // Buscar el índice del bloque actual (usando hora simulada si existe)
            let indiceActual = -1;
            const horaActual = obtenerHoraActual();
            const horaActualStr = horaActual.getHours().toString().padStart(2, '0') + ':' + 
                                  horaActual.getMinutes().toString().padStart(2, '0') + ':00';
            
            for (let i = 0; i < horarios.length; i++) {
                const [inicio, fin] = horarios[i].split('-');
                const inicioStr = inicio + ':00';
                const finStr = fin + ':00';
                
                if (horaActualStr >= inicioStr && horaActualStr < finStr) {
                    indiceActual = i;
                    break;
                }
            }
            
            // Si no hay bloque actual (antes o después del evento)
            if (indiceActual === -1) {
                // Si la hora actual es antes del primer bloque, mostrar los primeros 3
                const primerBloque = horarios[0].split('-')[0] + ':00';
                if (horaActualStr < primerBloque) {
                    return horarios.slice(0, 3);
                }
                // Si es después del último bloque, mostrar los últimos 3
                return horarios.slice(-3);
            }
            
            // Centrar en el bloque actual
            let inicio = indiceActual - 1;
            let fin = indiceActual + 2;
            
            // Ajustar si estamos al principio
            if (inicio < 0) {
                inicio = 0;
                fin = 3;
            }
            
            // Ajustar si estamos al final
            if (fin > horarios.length) {
                fin = horarios.length;
                inicio = Math.max(0, fin - 3);
            }
            
            return horarios.slice(inicio, fin);
        }

        // Función para formatear fecha
        function formatearFecha(fecha) {
            const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            const d = new Date(fecha + 'T00:00:00');
            return `${d.getDate()} ${meses[d.getMonth()]} ${d.getFullYear()}`;
        }

        // Función para formatear horario
        function formatearHorario(horario) {
            const [inicio, fin] = horario.split('-');
            return `${inicio} - ${fin}`;
        }

        // Actualizar hora actual
        function actualizarHora() {
            const ahora = obtenerHoraActual();
            const horas = String(ahora.getHours()).padStart(2, '0');
            const minutos = String(ahora.getMinutes()).padStart(2, '0');
            document.getElementById('hora-actual').textContent = `${horas}:${minutos}`;
        }

        // Inicializar
        cargarBloques();
        
        // Actualizar hora cada segundo
        setInterval(actualizarHora, 1000);
        
        // Recargar bloques cada 30 segundos
        setInterval(cargarBloques, 30000);
    </script>
</body>
</html>