<?php
/**
 * Visor de Rueda de Negocios - Vista por Mesas (Diseño Premium 2025)
 */
require_once '../config/config.php';

$fullscreen = isset($_GET['fullscreen']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Mesas - <?php echo SITE_NAME; ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- ===========================
         ESTILO PREMIUM 2025
         =========================== -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        :root {
            --color-disponible: #0ea5e9;
            --color-ocupado: #6366f1;
            --color-pendiente: #f59e0b;

            --empresa-bg: #0f172a;
            --empresa-bg-2: #1e293b;

            --bg-body: #f3f4f6;
            --bg-white: #ffffff;

            --border: #e5e7eb;
            --border-dark: #cbd5e1;

            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        body {
            background: var(--bg-body);
            font-family: "Inter", sans-serif;
        }

        /* HEADER */
        .header-main {
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            padding: 1.3rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            border-bottom: 3px solid rgba(255, 255, 255, 0.15);
        }

        .header-title {
            font-size: 1.9rem;
            font-weight: 700;
        }

        .btn-header {
            padding: .55rem 1.2rem;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            transition: .25s;
            font-size: .9rem;
        }

        .btn-header:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* CARDS */
        .stat-card {
            background: var(--bg-white);
            border-radius: 14px;
            border: 1px solid var(--border);
            padding: 1rem;
            transition: .25s;
        }

        .stat-card:hover {
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.08);
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: 1.4rem;
            font-weight: 700;
        }

        /* BLOQUE */
        .box-selector {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.2rem;
        }

        /* LEYENDA */
        .legend-box {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.2rem;
        }

        .legend-pill {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* TABLA */
        .table-wrapper {
            background: var(--bg-white);
            border-radius: 14px;
            border: 1px solid var(--border);
            overflow-x: auto;
        }

        .tabla-mesas th {
            background: #eef2ff;
            padding: 16px 6px;
            text-transform: uppercase;
            font-size: 11px;
            border-bottom: 2px solid var(--border-dark);
            color: #475569;
            font-weight: 600;
        }

        .empresa-cell {
            background: linear-gradient(90deg, var(--empresa-bg), var(--empresa-bg-2));
            color: white;
            padding: 15px;
            border-right: 4px solid #0f172a;
            min-width: 250px;
            border-radius: 8px;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .empresa-logo-mini {
            width: 46px;
            height: 46px;
            background: rgba(255, 255, 255, 0.22);
            padding: 4px;
            border-radius: 10px;
        }

        /* CELDAS */
        .celda-disponible,
        .celda-ocupada,
        .celda-pendiente,
        .celda-no-disponible {
            height: 78px;
            border-radius: 12px;
            transition: .25s;
            padding: 5px;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .celda-disponible {
            background: #e0f2fe;
            border-color: #bae6fd;
        }

        .celda-disponible:hover {
            background: #bae6fd;
            box-shadow: 0 5px 15px rgba(14, 165, 233, 0.25);
        }

        .celda-ocupada {
            background: #ede9fe;
            border-color: #ddd6fe;
        }

        .celda-ocupada:hover {
            background: #ddd6fe;
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.25);
        }

        .celda-pendiente {
            background: #fef3c7;
            border-color: #fde68a;
        }

        .celda-pendiente:hover {
            background: #fde68a;
            box-shadow: 0 5px 15px rgba(245, 158, 11, 0.25);
        }

        .celda-no-disponible {
            background: #f3f4f6;
            color: #cbd5e1;
        }

        .pyme-mini-logo {
            width: 42px;
            height: 42px;
            padding: 4px;
            border-radius: 8px;
            background: white;
        }

        /* MODAL */
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            z-index: 999;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            border: 2px solid var(--border);
            max-width: 480px;
            width: 92%;
        }

        .tag-confirmada {
            background: var(--color-ocupado);
            color: white;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: .75rem;
        }

        .tag-pendiente {
            background: var(--color-pendiente);
            color: white;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: .75rem;
        }

        .tag-disponible {
            background: var(--color-disponible);
            color: white;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: .75rem;
        }
    </style>
</head>

<body>

    <!-- ===========================
         HEADER SUPERIOR
         =========================== -->
    <?php if (!$fullscreen): ?>
    <div class="header-main text-white">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <div>
                <h1 class="header-title">🎯 Visor de Mesas</h1>
                <p class="opacity-80 text-sm">Rueda de Negocios • Vista por Demandantes</p>
            </div>

            <div class="flex gap-3">
                <button onclick="toggleFullscreen()" class="btn-header">
                    <i class="fas fa-expand"></i> Completa
                </button>
                <button onclick="exportarDatos()" class="btn-header">
                    <i class="fas fa-download"></i> Exportar
                </button>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="header-main text-white py-2">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <span class="font-bold text-lg">Visor Mesas</span>
            <button onclick="exitFullscreen()" class="btn-header px-3 py-1 text-sm">
                <i class="fas fa-compress"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===========================
         CONTENIDO
         =========================== -->
    <div class="container mx-auto px-4 py-4">

        <!-- === TARJETAS === -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
            <div class="stat-card">
                <div class="text-muted text-xs">Total Mesas</div>
                <div class="stat-number text-sky-600" id="total-mesas">15</div>
            </div>
            <div class="stat-card">
                <div class="text-muted text-xs">Empresas Activas</div>
                <div class="stat-number" id="total-empresas">0</div>
            </div>
            <div class="stat-card">
                <div class="text-muted text-xs">Disponibles</div>
                <div class="stat-number text-emerald-600" id="slots-disponibles">0</div>
            </div>
            <div class="stat-card">
                <div class="text-muted text-xs">Ocupadas</div>
                <div class="stat-number text-indigo-600" id="slots-ocupados">0</div>
            </div>
            <div class="stat-card">
                <div class="text-muted text-xs">% Ocupación</div>
                <div class="stat-number text-purple-600" id="porcentaje-ocupacion">0%</div>
            </div>
        </div>

        <!-- === BLOQUE & HORA === -->
        <div class="box-selector mb-4">
            <div class="flex justify-between items-center flex-wrap gap-4">

                <div class="flex gap-4 items-center">
                    <div>
                        <label class="text-sm text-muted block mb-1">
                            <i class="fas fa-clock text-indigo-500"></i> Bloque Horario
                        </label>
                        <select id="selector-bloque" onchange="cambiarBloque(this.value)" class="px-4 py-2 border border-gray-300 rounded-lg">
                        </select>
                    </div>

                    <div>
                        <span class="text-sm text-muted">Hora actual:</span>
                        <span id="hora-actual" class="text-lg font-bold text-indigo-600 ml-1">--:--</span>
                    </div>
                </div>

                <button onclick="recargarDatos()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    <i class="fas fa-sync-alt"></i>
                </button>

            </div>
        </div>

        <!-- === LEYENDA === -->
        <div class="legend-box mb-4">
            <div class="flex flex-wrap gap-6 justify-center text-sm">

                <div class="flex items-center gap-2">
                    <div class="legend-pill bg-sky-200 text-sky-700">
                        <i class="fas fa-check"></i>
                    </div>
                    <span>Disponible</span>
                </div>

                <div class="flex items-center gap-2">
                    <div class="legend-pill bg-indigo-200 text-indigo-700">
                        <i class="fas fa-users"></i>
                    </div>
                    <span>Confirmada</span>
                </div>

                <div class="flex items-center gap-2">
                    <div class="legend-pill bg-amber-200 text-amber-700">
                        <i class="fas fa-clock"></i>
                    </div>
                    <span>Pendiente</span>
                </div>

                <div class="flex items-center gap-2">
                    <div class="legend-pill bg-gray-200 text-gray-400">
                        <i class="fas fa-minus"></i>
                    </div>
                    <span>No Disponible</span>
                </div>

            </div>
        </div>

        <!-- === TABLA === -->
        <div class="table-wrapper">

            <div id="loading" class="text-center py-10">
                <div class="animate-spin h-14 w-14 border-b-4 border-indigo-500 border-solid rounded-full mx-auto"></div>
                <p class="text-muted mt-4 text-lg">Cargando datos...</p>
            </div>

            <div id="tabla-container" class="hidden">
                <table class="tabla-mesas w-full">
                    <thead id="tabla-header"></thead>
                    <tbody id="tabla-body"></tbody>
                </table>
            </div>

        </div>

        <!-- === FOOTER === -->
        <div class="mt-6 text-center text-muted text-xs">
            <p>Rueda de Negocios — <?php echo SITE_NAME; ?></p>
            <p>Actualización automática cada 30 segundos</p>
        </div>

    </div>

    <!-- === MODAL === -->
    <div id="modal-detalle" class="modal">
        <div class="modal-content">
            <div class="flex justify-between mb-4">
                <h3 class="text-xl font-bold">Detalle de la Mesa</h3>
                <button onclick="cerrarModal()">
                    <i class="fas fa-times text-xl text-muted hover:text-gray-800"></i>
                </button>
            </div>
            <div id="modal-body"></div>
        </div>
    </div>

    <!-- ===========================
         JAVASCRIPT ORIGINAL
         =========================== -->
    <script>

        let datosActuales = null;
        let bloqueActualId = null;

        async function cargarDatos(bloqueId = null) {
            try {
                const url = bloqueId
                    ? '<?php echo BASE_URL; ?>api/get_mesas_visor.php?bloque_id=' + bloqueId
                    : '<?php echo BASE_URL; ?>api/get_mesas_visor.php';

                const response = await fetch(url);
                if (!response.ok) throw new Error("HTTP error " + response.status);

                const data = await response.json();
                if (!data.success) return mostrarError(data.mensaje);

                datosActuales = data;
                bloqueActualId = data.bloque_actual.id;

                actualizarInterfaz(data);

            } catch (e) {
                mostrarError("Error: " + e.message);
            }
        }

        function actualizarInterfaz(data) {

            const empresasActivas = data.empresas.filter(empresa => {
                const row = data.matriz[empresa.id];
                return Object.values(row).some(c => c.estado !== "no_disponible");
            });

            document.getElementById("total-empresas").textContent =
                empresasActivas.length + " / " + data.estadisticas.total_empresas;

            document.getElementById("total-mesas").textContent = data.estadisticas.total_mesas;
            document.getElementById("slots-disponibles").textContent = data.estadisticas.slots_disponibles;
            document.getElementById("slots-ocupados").textContent = data.estadisticas.slots_ocupados;
            document.getElementById("porcentaje-ocupacion").textContent =
                data.estadisticas.porcentaje_ocupacion + "%";

            // SELECTOR BLOQUES
            const selector = document.getElementById("selector-bloque");
            selector.innerHTML = "";
            data.bloques.forEach(b => {
                selector.innerHTML += `<option value="${b.id}" ${b.id == data.bloque_actual.id ? "selected" : ""}>
                    ${b.hora_inicio.substring(0, 5)} - ${b.hora_fin.substring(0, 5)}
                </option>`;
            });

            actualizarHora();
            generarTabla(data);

            document.getElementById("loading").classList.add("hidden");
            document.getElementById("tabla-container").classList.remove("hidden");
        }

        function generarTabla(data) {

            const header = document.getElementById("tabla-header");
            const body = document.getElementById("tabla-body");

            header.innerHTML = `
                <tr>
                    <th class="empresa-cell">Empresa</th>
                    ${Array.from({ length: 15 }, (_, i) => `<th>Mesa ${i + 1}</th>`).join("")}
                </tr>
            `;

            let html = "";

            data.empresas.forEach(empresa => {

                const fila = data.matriz[empresa.id];
                const activa = Object.values(fila).some(c => c.estado !== "no_disponible");

                if (!activa) return;

                html += `<tr class="empresa-row">
                    <td class="empresa-cell">
                        ${(empresa.logo)
                        ? `<img src="<?php echo UPLOAD_URL; ?>${empresa.logo}" class="empresa-logo-mini">`
                        : `<i class='fas fa-building empresa-logo-mini text-white'></i>`}
                        <div>
                            <div class="font-bold">${empresa.nombre}</div>
                            <div class="text-xs opacity-75">${empresa.rubro || "Sin rubro"}</div>
                        </div>
                    </td>
                `;

                for (let mesa = 1; mesa <= 15; mesa++) {
                    const c = fila[mesa];

                    let estadoClass = "celda-no-disponible";
                    let contenido = `<span class="opacity-40">—</span>`;

                    if (c.estado === "disponible") {
                        estadoClass = "celda-disponible";
                        contenido = `
                        <div onclick='mostrarDetalle(${JSON.stringify(c)}, "${empresa.nombre}", ${mesa}, "${data.bloque_actual.hora_inicio}", "${data.bloque_actual.hora_fin}")'
                            class="flex flex-col items-center justify-center h-full">
                            <i class="fas fa-check-circle text-xl mb-1"></i>
                            <div class="text-xs font-semibold">Disponible</div>
                        </div>`;
                    }

                    if (c.estado === "confirmada" || c.estado === "pendiente") {
                        estadoClass = c.estado === "confirmada" ? "celda-ocupada" : "celda-pendiente";

                        const pyme = c.reunion;

                        contenido = `
                        <div onclick='mostrarDetalle(${JSON.stringify(c)}, "${empresa.nombre}", ${mesa}, "${data.bloque_actual.hora_inicio}", "${data.bloque_actual.hora_fin}")'
                            class="flex flex-col items-center justify-center h-full">
                            ${pyme.pyme_logo
                                ? `<img src="<?php echo UPLOAD_URL; ?>${pyme.pyme_logo}" class="pyme-mini-logo mb-1">`
                                : `<i class="fas fa-user-tie text-xl mb-1"></i>`}
                            <div class="text-xs font-semibold text-center">${truncate(pyme.pyme_nombre, 18)}</div>
                        </div>`;
                    }

                    html += `<td class="${estadoClass}">${contenido}</td>`;
                }

                html += "</tr>";
            });

            body.innerHTML = html;
        }

        function cambiarBloque(id) {
            cargarDatos(id);
        }

        function recargarDatos() {
            cargarDatos(bloqueActualId);
        }

        function mostrarDetalle(celda, empresaNombre, mesa, h1, h2) {

            const modal = document.getElementById("modal-detalle");
            const body = document.getElementById("modal-body");

            let html = `
                <div class="mb-3">
                    <label class="text-muted text-sm">Empresa:</label>
                    <div class="text-lg font-bold">${empresaNombre}</div>
                </div>

                <div class="mb-3">
                    <label class="text-muted text-sm">Mesa:</label>
                    <div class="font-bold">Mesa ${mesa}</div>
                </div>

                <div class="mb-3">
                    <label class="text-muted text-sm">Horario:</label>
                    <div class="font-bold">${h1.substring(0,5)} - ${h2.substring(0,5)}</div>
                </div>
            `;

            if (celda.reunion) {
                const py = celda.reunion;

                html += `
                <div class="border-t pt-3 mt-3">
                    <span class="tag-${celda.estado.toLowerCase()}">
                        ${celda.estado.toUpperCase()}
                    </span>
                </div>

                <div class="mt-3">
                    <label class="text-muted text-sm">Reunión con:</label>
                    <div class="text-lg font-bold">${py.pyme_nombre}</div>
                    <div class="text-muted text-sm">${py.pyme_rubro || ""}</div>
                    ${py.pyme_logo ? `<img class="mt-3 w-20 h-20 rounded-md" src="<?php echo UPLOAD_URL; ?>${py.pyme_logo}">` : ""}
                </div>

                ${py.notas
                    ? `<div class='mt-3'>
                        <label class="text-muted text-sm">Notas:</label>
                        <div class="bg-gray-50 p-3 rounded text-sm">${py.notas}</div>
                    </div>`
                    : ""
                }
                `;
            } else {
                html += `
                <div class="border-t pt-3 mt-3">
                    <span class="tag-disponible">DISPONIBLE</span>
                </div>
                `;
            }

            body.innerHTML = html;
            modal.classList.add("active");
        }

        function cerrarModal() {
            document.getElementById("modal-detalle").classList.remove("active");
        }

        function actualizarHora() {
            const d = new Date();
            document.getElementById("hora-actual").textContent =
                String(d.getHours()).padStart(2, "0") + ":" + String(d.getMinutes()).padStart(2, "0");
        }

        function truncate(str, n) {
            return str.length > n ? str.substring(0, n - 1) + "…" : str;
        }

        function toggleFullscreen() {
            window.location.href = "?fullscreen=1";
        }

        function exitFullscreen() {
            window.location.href = "visor-rueda.php";
        }

        function exportarDatos() {
            if (!datosActuales) return;

            let csv = "Empresa,Mesa,Estado,PYME,Horario\n";
            const bloque = datosActuales.bloque_actual;
            const horario = `${bloque.hora_inicio.substring(0, 5)}-${bloque.hora_fin.substring(0, 5)}`;

            datosActuales.empresas.forEach(empresa => {
                for (let mesa = 1; mesa <= 15; mesa++) {
                    const celda = datosActuales.matriz[empresa.id][mesa];
                    const pyme = celda.reunion ? celda.reunion.pyme_nombre : "";
                    csv += `"${empresa.nombre}",${mesa},${celda.estado},"${pyme}","${horario}"\n`;
                }
            });

            const blob = new Blob([csv], { type: "text/csv" });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `mesas-rueda-negocios-${new Date().toISOString().split("T")[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        function mostrarError(msg) {
            document.getElementById("loading").innerHTML = `
                <div class="text-center py-10">
                    <i class="fas fa-exclamation-triangle text-red-600 text-5xl"></i>
                    <p class="mt-3 text-lg font-semibold text-red-700">Error</p>
                    <p class="text-muted">${msg}</p>
                    <button onclick="location.reload()"
                        class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg">Reintentar</button>
                </div>
            `;
        }

        // INIT
        cargarDatos();
        setInterval(actualizarHora, 1000);
        setInterval(recargarDatos, 30000);

        // MODAL ESCAPE
        document.addEventListener("keydown", e => {
            if (e.key === "Escape") cerrarModal();
        });

        // CERRAR MODAL CLICK FUERA
        document.getElementById("modal-detalle").addEventListener("click", e => {
            if (e.target.id === "modal-detalle") cerrarModal();
        });

    </script>

</body>
</html>
