<?php
require_once '../config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor en Vivo - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-+1QeK6b4Ni3D1ZC2L6N1x2Q17OBp0L0nK0lxuTez7tY+Q8aE1Q4lTnVbJkG0JiG+jD/adJzi10BGSAdoo6+1bQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="bg-slate-950 text-white min-h-screen">
    <header class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 shadow-lg">
        <div class="max-w-7xl mx-auto px-6 py-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm uppercase tracking-wide text-white/70">Rueda de Negocios</p>
                <h1 class="text-3xl font-extrabold flex items-center gap-2">
                    <i class="fas fa-display"></i>
                    Visor de Mesas en Vivo
                </h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="bg-black/20 rounded-lg px-4 py-2 text-sm text-white/80">
                    <span class="font-semibold" id="reloj-actual">--:--:--</span>
                    <span class="ml-2 text-white/60">Hora del servidor</span>
                </div>
                <div class="bg-white/10 border border-white/10 rounded-lg px-4 py-2 text-sm text-white/90" id="estado-bloque">Sin bloque activo</div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8">
        <section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-slate-900 border border-white/5 rounded-xl p-4 shadow-md">
                <p class="text-sm text-white/60">Bloque</p>
                <div class="mt-2 flex items-center gap-2">
                    <span class="text-3xl font-bold" id="bloque-horario">--:-- - --:--</span>
                    <span class="px-3 py-1 text-xs rounded-full bg-indigo-500/20 text-indigo-200 border border-indigo-500/40" id="bloque-orden">#--</span>
                </div>
                <p class="text-sm text-white/60 mt-1" id="bloque-fecha">--</p>
            </div>
            <div class="bg-slate-900 border border-white/5 rounded-xl p-4 shadow-md">
                <p class="text-sm text-white/60">Mesas</p>
                <div class="mt-2 flex items-center gap-4">
                    <div>
                        <p class="text-3xl font-bold" id="mesas-ocupadas">0</p>
                        <p class="text-xs text-white/60">Ocupadas</p>
                    </div>
                    <div>
                        <p class="text-3xl font-bold" id="mesas-libres">0</p>
                        <p class="text-xs text-white/60">Libres</p>
                    </div>
                    <div class="ml-auto text-right">
                        <p class="text-3xl font-bold" id="total-mesas">15</p>
                        <p class="text-xs text-white/60">Total</p>
                    </div>
                </div>
            </div>
            <div class="bg-slate-900 border border-white/5 rounded-xl p-4 shadow-md">
                <p class="text-sm text-white/60">Tiempo</p>
                <div class="mt-2 flex items-center gap-3">
                    <div class="bg-emerald-500/15 border border-emerald-400/30 text-emerald-100 px-3 py-2 rounded-lg" id="contador-tiempo">--</div>
                    <p class="text-white/60 text-sm" id="mensaje-tiempo">Esperando bloque...</p>
                </div>
            </div>
        </section>

        <section class="bg-slate-900 border border-white/5 rounded-2xl shadow-lg overflow-hidden">
            <div class="border-b border-white/5 px-6 py-4 flex items-center justify-between bg-white/5">
                <div>
                    <h2 class="text-xl font-semibold">Mesas en este bloque</h2>
                    <p class="text-sm text-white/60">Actualización automática cada 15 segundos</p>
                </div>
                <div class="flex items-center gap-2 text-sm text-white/70" id="ultima-actualizacion">
                    <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                    <span>--</span>
                </div>
            </div>

            <div id="contenedor-mesas" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
                <div class="col-span-full text-center text-white/60" id="loader">
                    <div class="inline-block h-12 w-12 border-4 border-indigo-400 border-t-transparent rounded-full animate-spin mb-3"></div>
                    <p>Cargando información del bloque...</p>
                </div>
            </div>
        </section>
    </main>

    <script>
        const contenedorMesas = document.getElementById('contenedor-mesas');
        const loader = document.getElementById('loader');

        function badgeEstado(estado) {
            if (estado === 'confirmada') {
                return '<span class="px-2 py-1 text-xs rounded-full bg-emerald-500/20 text-emerald-100 border border-emerald-400/40">Confirmada</span>';
            }
            if (estado === 'pendiente') {
                return '<span class="px-2 py-1 text-xs rounded-full bg-amber-500/20 text-amber-100 border border-amber-400/40">Pendiente</span>';
            }
            return '<span class="px-2 py-1 text-xs rounded-full bg-slate-700 text-white/80">Libre</span>';
        }

        function renderMesas(mesas) {
            contenedorMesas.innerHTML = '';
            mesas.forEach(mesa => {
                const card = document.createElement('div');
                card.className = 'bg-slate-800/60 border border-white/5 rounded-xl p-4 shadow-sm flex flex-col gap-3';

                const header = document.createElement('div');
                header.className = 'flex items-center justify-between';
                header.innerHTML = `
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-lg bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-xl font-bold">${mesa.mesa}</div>
                        <div>
                            <p class="text-sm text-white/60">Mesa</p>
                            <p class="text-lg font-semibold">#${mesa.mesa}</p>
                        </div>
                    </div>
                    ${badgeEstado(mesa.estado)}
                `;

                const cuerpo = document.createElement('div');
                cuerpo.className = 'bg-slate-900/60 border border-white/5 rounded-lg p-3 h-full';

                if (mesa.estado === 'libre') {
                    cuerpo.innerHTML = `
                        <div class="text-center text-white/50 flex flex-col items-center justify-center h-full gap-2">
                            <i class="fas fa-chair text-2xl"></i>
                            <p>Sin reunión asignada</p>
                        </div>
                    `;
                } else {
                    cuerpo.innerHTML = `
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-white/50">Demandante</p>
                                <p class="text-base font-semibold">${mesa.demandante}</p>
                                <p class="text-xs text-white/60">${mesa.rubro_demandante || ''}</p>
                            </div>
                            <div class="border-t border-white/5 pt-3">
                                <p class="text-xs uppercase tracking-wide text-white/50">Oferente</p>
                                <p class="text-base font-semibold">${mesa.oferente}</p>
                                <p class="text-xs text-white/60">${mesa.rubro_oferente || ''}</p>
                            </div>
                        </div>
                    `;
                }

                card.appendChild(header);
                card.appendChild(cuerpo);
                contenedorMesas.appendChild(card);
            });
        }

        function actualizarCabecera(datos) {
            const bloque = datos.bloque;
            document.getElementById('reloj-actual').textContent = datos.ahora || '--:--:--';
            document.getElementById('ultima-actualizacion').querySelector('span:nth-child(2)').textContent = new Date().toLocaleTimeString();

            if (!bloque) {
                document.getElementById('estado-bloque').textContent = 'Sin bloques programados';
                document.getElementById('bloque-horario').textContent = '--:-- - --:--';
                document.getElementById('bloque-orden').textContent = '#--';
                document.getElementById('bloque-fecha').textContent = '--';
                document.getElementById('contador-tiempo').textContent = '--';
                document.getElementById('mensaje-tiempo').textContent = 'Esperando programación...';
                return;
            }

            document.getElementById('bloque-horario').textContent = `${bloque.hora_inicio} - ${bloque.hora_fin}`;
            document.getElementById('bloque-orden').textContent = `#${bloque.orden}`;
            document.getElementById('bloque-fecha').textContent = bloque.fecha;

            if (bloque.estado === 'activo') {
                document.getElementById('estado-bloque').innerHTML = '<span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-100 border border-emerald-400/40">Bloque en curso</span>';
                document.getElementById('contador-tiempo').textContent = `${bloque.minutos_restantes} min restantes`;
                document.getElementById('mensaje-tiempo').textContent = 'Supervisar tiempos para cierre de reuniones';
            } else {
                document.getElementById('estado-bloque').innerHTML = '<span class="px-3 py-1 rounded-full bg-amber-500/20 text-amber-100 border border-amber-400/40">Próximo bloque</span>';
                document.getElementById('contador-tiempo').textContent = `${bloque.minutos_para_inicio} min para iniciar`;
                document.getElementById('mensaje-tiempo').textContent = 'Preparar mesas para el siguiente bloque';
            }

            document.getElementById('mesas-ocupadas').textContent = datos.estadisticas.mesas_ocupadas;
            document.getElementById('mesas-libres').textContent = datos.estadisticas.mesas_libres;
            document.getElementById('total-mesas').textContent = datos.estadisticas.total_mesas;
        }

        async function cargarDatos() {
            try {
                const respuesta = await fetch('../api/visor_rueda.php');
                const datos = await respuesta.json();

                if (!datos.success) {
                    loader.innerHTML = '<p class="text-red-300">No se pudo cargar la información.</p>';
                    return;
                }

                loader.classList.add('hidden');
                renderMesas(datos.mesas);
                actualizarCabecera(datos);
            } catch (error) {
                loader.innerHTML = '<p class="text-red-300">Error de conexión con el visor.</p>';
                console.error(error);
            }
        }

        cargarDatos();
        setInterval(cargarDatos, 15000);
    </script>
</body>
</html>
