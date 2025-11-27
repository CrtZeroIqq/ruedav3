<?php
require_once '../config/config.php';

$metricas = [
    [
        'title' => 'Empresas activas',
        'value' => '128',
        'change' => '+12% vs semana pasada',
        'icon' => 'fa-building',
        'color' => 'from-blue-500 to-indigo-500'
    ],
    [
        'title' => 'Reuniones confirmadas',
        'value' => '342',
        'change' => '+68 nuevas en las últimas 24h',
        'icon' => 'fa-handshake',
        'color' => 'from-green-500 to-emerald-500'
    ],
    [
        'title' => 'Bloques disponibles',
        'value' => '210',
        'change' => '70% de capacidad utilizada',
        'icon' => 'fa-table-cells-large',
        'color' => 'from-amber-400 to-orange-500'
    ],
    [
        'title' => 'Tasa de respuesta',
        'value' => '94%',
        'change' => 'Confirmaciones en menos de 12h',
        'icon' => 'fa-bolt',
        'color' => 'from-purple-500 to-fuchsia-500'
    ],
];

$reuniones = [
    [
        'empresa' => 'Agro Andes S.A.',
        'con' => 'Logística Pacífico',
        'hora' => '09:30 - 09:45',
        'mesa' => 'Mesa 04',
        'estado' => 'Confirmada',
        'tag' => 'Exportación'
    ],
    [
        'empresa' => 'TechMar Chile',
        'con' => 'Ocean Freight',
        'hora' => '10:15 - 10:30',
        'mesa' => 'Mesa 12',
        'estado' => 'Pendiente',
        'tag' => 'Innovación'
    ],
    [
        'empresa' => 'Altiplano Foods',
        'con' => 'Puerto Norte',
        'hora' => '11:00 - 11:15',
        'mesa' => 'Mesa 02',
        'estado' => 'Confirmada',
        'tag' => 'Alimentos'
    ],
];

$empresas = [
    ['nombre' => 'Agro Andes S.A.', 'tipo' => 'Empresa A', 'intereses' => 'Logística, Exportación', 'icon' => 'fa-leaf'],
    ['nombre' => 'Logística Pacífico', 'tipo' => 'Empresa B', 'intereses' => 'Transporte, Puertos', 'icon' => 'fa-ship'],
    ['nombre' => 'TechMar Chile', 'tipo' => 'Empresa A', 'intereses' => 'Innovación, IoT', 'icon' => 'fa-microchip'],
    ['nombre' => 'Puerto Norte', 'tipo' => 'Empresa B', 'intereses' => 'Carga, Infraestructura', 'icon' => 'fa-anchor'],
];

$bloques = [
    ['hora' => '09:00 - 09:15', 'ocupado' => 11, 'total' => 15],
    ['hora' => '10:00 - 10:15', 'ocupado' => 9, 'total' => 15],
    ['hora' => '11:00 - 11:15', 'ocupado' => 13, 'total' => 15],
    ['hora' => '12:00 - 12:15', 'ocupado' => 8, 'total' => 15],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo de Dashboard - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .gradient-bg { background: radial-gradient(circle at 20% 20%, rgba(37,99,235,0.12), transparent 25%),
                      radial-gradient(circle at 80% 10%, rgba(236,72,153,0.12), transparent 25%),
                      radial-gradient(circle at 30% 70%, rgba(16,185,129,0.12), transparent 25%),
                      #0f172a; }
    </style>
</head>
<body class="gradient-bg min-h-screen text-slate-100">
    <div class="max-w-7xl mx-auto px-4 py-10 space-y-8">
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white/5 backdrop-blur rounded-2xl p-6 border border-white/10 shadow-xl">
            <div>
                <p class="text-sm text-slate-300 uppercase tracking-[0.2em]">Vista previa</p>
                <h1 class="text-3xl font-extrabold text-white mt-1">Dashboard en modo demo</h1>
                <p class="text-slate-300 mt-2 max-w-2xl">Explora una versión ilustrativa del panel sin necesidad de credenciales. Los datos son ficticios y sirven solo para visualizar la experiencia del usuario.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="<?php echo BASE_URL; ?>views/login.php" class="inline-flex items-center gap-2 px-4 py-3 rounded-xl bg-white text-slate-900 font-semibold shadow-lg hover:-translate-y-0.5 transition-transform">
                    <i class="fa-solid fa-right-to-bracket"></i> Ir al login real
                </a>
                <span class="inline-flex items-center gap-2 px-4 py-3 rounded-xl border border-white/20 text-white/90 bg-white/10">
                    <i class="fa-solid fa-circle-info"></i> Datos de ejemplo
                </span>
            </div>
        </header>

        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($metricas as $card): ?>
                <div class="relative overflow-hidden rounded-2xl p-5 bg-white/5 border border-white/10 shadow-xl">
                    <div class="absolute inset-0 bg-gradient-to-br <?php echo $card['color']; ?> opacity-10"></div>
                    <div class="relative flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-300"><?php echo $card['title']; ?></p>
                            <p class="text-3xl font-bold text-white mt-1"><?php echo $card['value']; ?></p>
                            <p class="text-xs text-emerald-300 mt-2 flex items-center gap-1"><i class="fa-solid fa-arrow-trend-up"></i> <?php echo $card['change']; ?></p>
                        </div>
                        <div class="h-12 w-12 rounded-2xl bg-white/10 flex items-center justify-center text-2xl text-white">
                            <i class="fa-solid <?php echo $card['icon']; ?>"></i>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 bg-white/5 border border-white/10 rounded-2xl p-6 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-slate-300">Agenda</p>
                        <h2 class="text-xl font-semibold text-white">Próximas reuniones</h2>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs bg-emerald-500/20 text-emerald-200">Vista demo</span>
                </div>
                <div class="space-y-3">
                    <?php foreach ($reuniones as $item): ?>
                        <div class="flex items-center justify-between p-4 rounded-xl bg-white/5 border border-white/10">
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-500 flex items-center justify-center text-white text-lg font-bold">
                                    <?php echo substr($item['empresa'], 0, 1); ?>
                                </div>
                                <div>
                                    <p class="text-white font-semibold"><?php echo $item['empresa']; ?></p>
                                    <p class="text-slate-300 text-sm">con <?php echo $item['con']; ?> · <?php echo $item['tag']; ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-slate-200 font-semibold"><?php echo $item['hora']; ?></p>
                                <p class="text-xs text-slate-400"><?php echo $item['mesa']; ?> · <?php echo $item['estado']; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="space-y-4">
                <div class="bg-white/5 border border-white/10 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-lg font-semibold text-white mb-3">Empresas en vitrina</h3>
                    <div class="space-y-3">
                        <?php foreach ($empresas as $empresa): ?>
                            <div class="flex items-center justify-between p-3 rounded-xl bg-white/5 border border-white/10">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-xl bg-white/10 flex items-center justify-center text-white">
                                        <i class="fa-solid <?php echo $empresa['icon']; ?>"></i>
                                    </div>
                                    <div>
                                        <p class="text-white font-semibold"><?php echo $empresa['nombre']; ?></p>
                                        <p class="text-xs text-slate-300"><?php echo $empresa['intereses']; ?></p>
                                    </div>
                                </div>
                                <span class="text-xs px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-100 border border-indigo-400/30"><?php echo $empresa['tipo']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-white/5 border border-white/10 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-lg font-semibold text-white mb-3">Disponibilidad por bloque</h3>
                    <div class="space-y-3">
                        <?php foreach ($bloques as $bloque):
                            $percent = round(($bloque['ocupado'] / $bloque['total']) * 100);
                        ?>
                            <div>
                                <div class="flex items-center justify-between text-sm text-slate-200">
                                    <span class="font-semibold"><?php echo $bloque['hora']; ?></span>
                                    <span><?php echo $bloque['ocupado']; ?>/<?php echo $bloque['total']; ?> mesas</span>
                                </div>
                                <div class="w-full h-2.5 bg-white/10 rounded-full mt-2 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-emerald-400 to-green-500" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-slate-300">Estado general</p>
                        <h3 class="text-lg font-semibold text-white">Mapa de avance</h3>
                    </div>
                    <span class="text-xs px-3 py-1 rounded-full bg-sky-500/20 text-sky-100 border border-sky-400/30">Prototipo</span>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm text-slate-200">
                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                        <p class="text-slate-400 text-xs">Confirmaciones</p>
                        <p class="text-2xl font-semibold text-white">86%</p>
                        <p class="text-emerald-300 text-xs mt-1">Objetivo: 90%</p>
                    </div>
                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                        <p class="text-slate-400 text-xs">Participación</p>
                        <p class="text-2xl font-semibold text-white">432 empresas</p>
                        <p class="text-emerald-300 text-xs mt-1">Crecimiento constante</p>
                    </div>
                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                        <p class="text-slate-400 text-xs">Respuestas pendientes</p>
                        <p class="text-2xl font-semibold text-white">28</p>
                        <p class="text-amber-300 text-xs mt-1">Seguimiento sugerido</p>
                    </div>
                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                        <p class="text-slate-400 text-xs">Satisfacción (demo)</p>
                        <p class="text-2xl font-semibold text-white">4.7/5</p>
                        <p class="text-emerald-300 text-xs mt-1">Basado en pruebas internas</p>
                    </div>
                </div>
            </div>

            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-slate-300">Recorrido rápido</p>
                        <h3 class="text-lg font-semibold text-white">Cómo navegar el panel</h3>
                    </div>
                </div>
                <ol class="space-y-3 text-slate-200 text-sm">
                    <li class="flex gap-3">
                        <span class="h-8 w-8 rounded-full bg-indigo-500 text-white flex items-center justify-center font-semibold">1</span>
                        <div>
                            <p class="font-semibold">Resumen inicial</p>
                            <p class="text-slate-300">Visualiza los indicadores clave y el estado general de la rueda.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="h-8 w-8 rounded-full bg-indigo-500 text-white flex items-center justify-center font-semibold">2</span>
                        <div>
                            <p class="font-semibold">Gestión de reuniones</p>
                            <p class="text-slate-300">Revisa la agenda, confirma mesas y revisa la disponibilidad por bloque.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="h-8 w-8 rounded-full bg-indigo-500 text-white flex items-center justify-center font-semibold">3</span>
                        <div>
                            <p class="font-semibold">Seguimiento de empresas</p>
                            <p class="text-slate-300">Accede al detalle de empresas A y B para ver intereses y coincidencias.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="h-8 w-8 rounded-full bg-indigo-500 text-white flex items-center justify-center font-semibold">4</span>
                        <div>
                            <p class="font-semibold">Pasar al entorno real</p>
                            <p class="text-slate-300">Cuando quieras probar con datos reales, ingresa desde el botón "Ir al login real".</p>
                        </div>
                    </li>
                </ol>
            </div>
        </section>
    </div>
</body>
</html>
