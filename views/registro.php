<?php
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../config/database.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    redirectToDashboard(getUserRole());
}

// Contar empresas tipo 'grande' (Busco Servicios) - Límite 30
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM empresas WHERE tipo = 'grande'");
    $empresasGrandes = $stmt->fetchColumn();
    $cuposDisponibles = 30 - $empresasGrandes;
    $modalidadBuscoLlena = ($cuposDisponibles <= 0);
} catch (PDOException $e) {
    $empresasGrandes = 0;
    $cuposDisponibles = 30;
    $modalidadBuscoLlena = false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - <?php echo SITE_NAME; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .float-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .gradient-animation {
            background: linear-gradient(-45deg, #1e40af, #2563eb, #3b82f6, #60a5fa);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        
        .rol-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .rol-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .rol-card.selected {
            border-color: #2563eb;
            background: linear-gradient(to bottom right, #eff6ff, #dbeafe);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }
        
        .btn-primary {
            background: linear-gradient(to right, #1e40af, #2563eb);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, #1e3a8a, #1d4ed8);
        }
    </style>
</head>
<body class="gradient-animation min-h-screen flex items-center justify-center p-4">
    <!-- Decorative elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-10 w-72 h-72 bg-white/10 rounded-full blur-3xl float-animation"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-white/10 rounded-full blur-3xl float-animation" style="animation-delay: 2s;"></div>
    </div>
    
    <!-- Registro Container -->
    <div class="relative w-full max-w-5xl my-8">
        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden backdrop-blur-sm">
            <!-- Header -->
            <div style="background: linear-gradient(to right, #1e40af, #2563eb);" class="p-8 text-center">
                <!-- Logo Nodo Bioceánico -->
                <div class="inline-flex items-center justify-center mb-4">
                    <img src="https://www.bioceanicocentral.cl/wp-content/uploads/2025/11/logonuevo.png" alt="Nodo Bioceánico Central" class="h-24 w-auto">
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Registro de Empresas</h1>
                <p class="text-white/90 text-sm"><?php echo SITE_NAME; ?> - 28 de Noviembre, 2025 11:00 AM / 12:30 PM</p>
            </div>
            
            <!-- Body -->
            <div class="p-8">
                <div class="mb-8 text-center">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">¡Únete a la rueda de negocios!</h2>
                    <p class="text-gray-600 text-sm">Completa el formulario para maximizar tus oportunidades de conexión</p>
                </div>
                
                <!-- Alert Box -->
                <div id="alertBox" class="hidden mb-6 p-4 rounded-lg"></div>
                
                <!-- Form -->
                <form id="registroForm" method="POST" class="space-y-6">
                    
                    <!-- PASO 1: ROL PRINCIPAL EN EL EVENTO -->
                    <div class="border-2 border-purple-100 rounded-xl p-6 bg-gradient-to-br from-purple-50/50 to-blue-50/50">
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold mr-3">1</div>
                            <h3 class="text-lg font-bold text-gray-900">¿Cuál es tu objetivo principal en este evento?</h3>
                        </div>
                        
                        <p class="text-sm text-gray-600 mb-5">
                            <i class="fas fa-info-circle text-purple-600 mr-2"></i>
                            Selecciona tu rol principal en la rueda de negocios
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- ROL: BUSCAR SERVICIOS (empresa_a) -->
                            <div class="rol-card border-2 border-gray-200 rounded-xl p-6 bg-white relative <?php echo $modalidadBuscoLlena ? 'opacity-60 cursor-not-allowed' : ''; ?>"
                                 <?php echo !$modalidadBuscoLlena ? 'onclick="seleccionarRol(\'grande\')"' : ''; ?>>
                                <input type="radio" name="tipo_empresa" value="grande" id="tipo_grande" class="hidden" <?php echo $modalidadBuscoLlena ? 'disabled' : 'required'; ?>>

                                <?php if ($modalidadBuscoLlena): ?>
                                    <div class="absolute top-3 right-3">
                                        <span class="inline-block bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold">
                                            <i class="fas fa-lock mr-1"></i>CUPOS AGOTADOS
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="absolute top-3 right-3">
                                        <span class="inline-block bg-green-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                                            <?php echo $cuposDisponibles; ?>/30 disponibles
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <div class="text-center">
                                    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3" style="background: linear-gradient(to bottom right, #dbeafe, #bfdbfe);">
                                        <i class="fas fa-search text-3xl text-blue-600"></i>
                                    </div>
                                    <h3 class="font-bold text-gray-900 mb-1 text-lg">Busco Servicios/Productos</h3>
                                    <p class="text-sm text-gray-600 mb-3">Vengo a encontrar proveedores y soluciones para mi empresa</p>

                                    <?php if ($modalidadBuscoLlena): ?>
                                        <div class="bg-red-50 border-2 border-red-200 rounded-lg p-3 text-center text-xs mb-3">
                                            <p class="font-semibold text-red-900">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>Modalidad completa
                                            </p>
                                            <p class="text-red-700 mt-1">30/30 mesas ocupadas</p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="bg-blue-50 rounded-lg p-3 text-left text-xs">
                                        <div class="font-semibold text-blue-900 mb-1">Ejemplos:</div>
                                        <ul class="text-blue-700 space-y-1">
                                            <li>• Busco proveedores de tecnología</li>
                                            <li>• Necesito servicios logísticos</li>
                                            <li>• Requiero asesoría especializada</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ROL: OFRECER SERVICIOS (empresa_b) -->
                            <div class="rol-card border-2 border-gray-200 rounded-xl p-6 bg-white"
                                 onclick="seleccionarRol('pyme')">
                                <input type="radio" name="tipo_empresa" value="pyme" id="tipo_pyme" class="hidden" required>
                                <div class="text-center">
                                    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3" style="background: linear-gradient(to bottom right, #d1fae5, #a7f3d0);">
                                        <i class="fas fa-handshake text-3xl" style="color: #059669;"></i>
                                    </div>
                                    <h3 class="font-bold text-gray-900 mb-1 text-lg">Ofrezco Servicios/Productos</h3>
                                    <p class="text-sm text-gray-600 mb-3">Vengo a ofrecer mis servicios y conseguir clientes</p>
                                    
                                    <div class="bg-green-50 rounded-lg p-3 text-left text-xs">
                                        <div class="font-semibold text-green-900 mb-1">Ejemplos:</div>
                                        <ul class="text-green-700 space-y-1">
                                            <li>• Ofrezco servicios de transporte</li>
                                            <li>• Vendo productos o soluciones</li>
                                            <li>• Busco clientes para mi empresa</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PASO 2: Datos Básicos -->
                    <div class="border-2 border-gray-100 rounded-xl p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold mr-3">2</div>
                            <h3 class="text-lg font-bold text-gray-900">Información de tu empresa</h3>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="nombre_empresa" class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-building mr-2 text-blue-600"></i>Nombre de la Empresa *
                                    </label>
                                    <input 
                                        type="text" 
                                        id="nombre_empresa" 
                                        name="nombre_empresa" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg transition-all focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Ej: Tech Solutions SpA"
                                        required
                                    >
                                </div>
                                
                                <div>
                                    <label for="rubro" class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-briefcase mr-2 text-blue-600"></i>Rubro *
                                    </label>
                                    <input 
                                        type="text" 
                                        id="rubro" 
                                        name="rubro" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg transition-all focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Ej: Tecnología, Logística, Retail"
                                        required
                                    >
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-envelope mr-2 text-blue-600"></i>Email Corporativo *
                                    </label>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg transition-all focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="contacto@empresa.com"
                                        required
                                    >
                                    <p class="text-xs text-gray-500 mt-1">Será usado como usuario para ingresar</p>
                                </div>
                                
                                <div>
                                    <label for="telefono" class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-phone mr-2 text-blue-600"></i>Teléfono *
                                    </label>
                                    <input 
                                        type="tel" 
                                        id="telefono" 
                                        name="telefono" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg transition-all focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="+56 9 XXXX XXXX"
                                        required
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PASO 3: Detalle según el rol -->
                    <div id="campo_detalle" class="hidden border-2 border-green-100 rounded-xl p-6 bg-gradient-to-br from-green-50/50 to-blue-50/50">
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 rounded-full bg-green-600 text-white flex items-center justify-center font-bold mr-3">3</div>
                            <h3 class="text-lg font-bold text-gray-900">Detalla tu interés específico</h3>
                        </div>
                        
                        <p class="text-sm text-gray-600 mb-6">
                            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                            Esta información ayudará a conectarte con las empresas más relevantes
                        </p>
                        
                        <!-- Campo dinámico según rol seleccionado -->
                        <div id="contenido_campo"></div>
                    </div>
                    
                    <!-- PASO 4: Contraseña -->
                    <div class="border-2 border-gray-100 rounded-xl p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold mr-3">4</div>
                            <h3 class="text-lg font-bold text-gray-900">Crea tu contraseña</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-2 text-blue-600"></i>Contraseña *
                                </label>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg transition-all focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Mínimo 6 caracteres"
                                    required
                                    minlength="6"
                                >
                            </div>
                            
                            <div>
                                <label for="password_confirm" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-2 text-blue-600"></i>Confirmar Contraseña *
                                </label>
                                <input 
                                    type="password" 
                                    id="password_confirm" 
                                    name="password_confirm" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg transition-all focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Repite la contraseña"
                                    required
                                    minlength="6"
                                >
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información adicional -->
                    <div class="bg-gradient-to-r from-blue-50 to-green-50 border-2 border-blue-200 rounded-lg p-4">
                        <p class="text-sm text-gray-800 flex items-start">
                            <i class="fas fa-star text-yellow-500 mr-3 mt-1 flex-shrink-0"></i>
                            <span>
                                <strong>¡Perfil completo = Más conexiones!</strong><br>
                                Empresas con información detallada reciben hasta 3x más solicitudes de reunión.
                            </span>
                        </p>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="pt-3">
                        <button 
                            type="submit" 
                            class="btn-primary w-full py-4 rounded-lg font-semibold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                        >
                            <i class="fas fa-check-circle mr-2"></i>Completar Registro
                        </button>
                    </div>
                    
                    <!-- Loading -->
                    <div id="loadingMsg" class="hidden text-center text-gray-600 text-sm">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Procesando registro...
                    </div>
                    
                    <!-- Hidden fields para los servicios -->
                    <input type="hidden" id="servicios_ofrecidos" name="servicios_ofrecidos">
                    <input type="hidden" id="servicios_necesitados" name="servicios_necesitados">
                </form>
                
                <!-- Link a Login -->
                <div class="mt-6 text-center">
                    <p class="text-gray-600 text-sm">
                        ¿Ya tienes una cuenta? 
                        <a href="login.php" class="font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                            Iniciar Sesión
                        </a>
                    </p>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 border-t border-gray-200 py-4 px-8">
                <div class="flex items-center justify-center gap-2 text-gray-500 text-xs">
                    <span>Desarrollado por</span>
                    <img src="https://www.bioceanicocentral.cl/wp-content/uploads/2025/11/wallRecurso-10iphone-1024x341-1.png" alt="SEID Development" class="h-6 w-auto opacity-80 hover:opacity-100 transition-opacity">
                </div>
            </div>
        </div>
    </div>

    <script>
        // Seleccionar rol
        function seleccionarRol(tipo) {
            // Remover selección anterior
            document.querySelectorAll('.rol-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Seleccionar nuevo
            const card = document.querySelector(`#tipo_${tipo}`).closest('.rol-card');
            card.classList.add('selected');
            document.getElementById(`tipo_${tipo}`).checked = true;
            
            // Mostrar campo según el rol
            mostrarCampoSegunRol(tipo);
        }
        
        // Mostrar campo dinámico según el rol
        function mostrarCampoSegunRol(tipo) {
            const campoDetalle = document.getElementById('campo_detalle');
            const contenidoCampo = document.getElementById('contenido_campo');
            
            if (tipo === 'grande') {
                // ROL: BUSCA SERVICIOS (empresa_a - DEMANDANTE)
                contenidoCampo.innerHTML = `
                    <div class="bg-white border-2 border-blue-200 rounded-lg p-5">
                        <div class="flex items-start mb-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-search text-blue-600"></i>
                            </div>
                            <div class="ml-3 flex-1">
                                <label for="detalle_textarea" class="block font-semibold text-gray-900 mb-1">
                                    ¿Qué servicios o productos necesitas? *
                                </label>
                                <p class="text-sm text-gray-600 mb-3">Describe claramente lo que estás buscando en este evento</p>
                            </div>
                        </div>
                        
                        <textarea 
                            id="detalle_textarea" 
                            rows="4"
                            class="w-full px-4 py-3 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                            placeholder="Ejemplo: Necesito proveedores de embalaje sustentable, servicios de tecnología para tracking de productos en tiempo real, asesoría especializada en comercio exterior para mercados asiáticos..."
                            required
                        ></textarea>
                        
                        <div class="mt-2 flex items-center text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1 text-blue-600"></i>
                            <span>Sé específico: menciona tecnologías, certificaciones, o características que buscas</span>
                        </div>
                    </div>
                `;
                
            } else if (tipo === 'pyme') {
                // ROL: OFRECE SERVICIOS (empresa_b - OFERENTE)
                contenidoCampo.innerHTML = `
                    <div class="bg-white border-2 border-emerald-200 rounded-lg p-5">
                        <div class="flex items-start mb-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-handshake text-emerald-600"></i>
                            </div>
                            <div class="ml-3 flex-1">
                                <label for="detalle_textarea" class="block font-semibold text-gray-900 mb-1">
                                    ¿Qué servicios o productos ofreces? *
                                </label>
                                <p class="text-sm text-gray-600 mb-3">Describe claramente lo que tu empresa puede aportar</p>
                            </div>
                        </div>
                        
                        <textarea 
                            id="detalle_textarea" 
                            rows="4"
                            class="w-full px-4 py-3 border border-emerald-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                            placeholder="Ejemplo: Ofrezco servicios de logística internacional con cobertura en 5 países, transporte terrestre y marítimo con certificación ISO 9001, almacenamiento en zonas francas, distribución de productos perecederos con cadena de frío..."
                            required
                        ></textarea>
                        
                        <div class="mt-2 flex items-center text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1 text-emerald-600"></i>
                            <span>Sé específico: menciona certificaciones, capacidades, cobertura geográfica o tecnologías</span>
                        </div>
                    </div>
                `;
            }
            
            campoDetalle.classList.remove('hidden');
        }
        
        // Submit del formulario
        document.getElementById('registroForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const alertBox = document.getElementById('alertBox');
            const loadingMsg = document.getElementById('loadingMsg');
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Validar que se seleccionó rol
            const rolSeleccionado = document.querySelector('input[name="tipo_empresa"]:checked');
            if (!rolSeleccionado) {
                alertBox.className = 'mb-6 p-4 rounded-lg bg-red-100 border border-red-300 text-red-800 flex items-center';
                alertBox.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Debes seleccionar tu objetivo principal en el evento';
                alertBox.classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            
            // Obtener el valor del textarea y asignar al campo correcto
            const detalleTxt = document.getElementById('detalle_textarea') ? document.getElementById('detalle_textarea').value : '';
            const tipoEmpresa = rolSeleccionado.value;
            
            if (tipoEmpresa === 'grande') {
                // Empresa que BUSCA → servicios_necesitados
                document.getElementById('servicios_necesitados').value = detalleTxt;
                document.getElementById('servicios_ofrecidos').value = '';
            } else if (tipoEmpresa === 'pyme') {
                // Empresa que OFRECE → servicios_ofrecidos
                document.getElementById('servicios_ofrecidos').value = detalleTxt;
                document.getElementById('servicios_necesitados').value = '';
            }
            
            // Validar contraseñas
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            
            if (password !== passwordConfirm) {
                alertBox.className = 'mb-6 p-4 rounded-lg bg-red-100 border border-red-300 text-red-800 flex items-center';
                alertBox.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Las contraseñas no coinciden';
                alertBox.classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            
            // Limpiar alertas
            alertBox.classList.add('hidden');
            
            // Mostrar loading
            loadingMsg.classList.remove('hidden');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            
            // Obtener datos del formulario
            const formData = new FormData(this);
            formData.append('accion', 'registrar');
            
            // Enviar petición AJAX
            fetch('../api/registro.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loadingMsg.classList.add('hidden');
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                
                if (data.success) {
                    alertBox.className = 'mb-6 p-4 rounded-lg bg-green-100 border border-green-300 text-green-800 flex items-center';
                    alertBox.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
                    alertBox.classList.remove('hidden');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    
                    setTimeout(() => {
                        window.location.href = data.data.redirect;
                    }, 2000);
                } else {
                    alertBox.className = 'mb-6 p-4 rounded-lg bg-red-100 border border-red-300 text-red-800 flex items-center';
                    alertBox.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + data.message;
                    alertBox.classList.remove('hidden');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            })
            .catch(error => {
                loadingMsg.classList.add('hidden');
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                alertBox.className = 'mb-6 p-4 rounded-lg bg-red-100 border border-red-300 text-red-800 flex items-center';
                alertBox.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Error de conexión. Intente nuevamente.';
                alertBox.classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>