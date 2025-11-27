<?php
require_once '../config/config.php';
require_once '../config/session.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    redirectToDashboard(getUserRole());
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
        
        .tipo-empresa-card {
            transition: all 0.3s ease;
        }
        
        .tipo-empresa-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .tipo-empresa-card.selected {
            border-color: #2563eb;
            background: linear-gradient(to bottom right, #eff6ff, #dbeafe);
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
    <div class="relative w-full max-w-4xl">
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
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">¡Únete al evento!</h2>
                    <p class="text-gray-600 text-sm">Completa el formulario para registrar tu empresa</p>
                </div>
                
                <!-- Alert Box -->
                <div id="alertBox" class="hidden mb-6 p-4 rounded-lg"></div>
                
                <!-- Form -->
                <form id="registroForm" method="POST" class="space-y-6">
                    <!-- Tipo de Empresa -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">
                            Selecciona si tu empresa es: Gran Empresa o MiPyme
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Empresa Grande -->
                            <div class="tipo-empresa-card border-2 border-gray-200 rounded-xl p-6 cursor-pointer"
                                 onclick="seleccionarTipo('grande')">
                                <input type="radio" name="tipo_empresa" value="grande" id="tipo_grande" class="hidden" required>
                                <div class="text-center">
                                    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3" style="background: linear-gradient(to bottom right, #dbeafe, #bfdbfe);">
                                        <i class="fas fa-building text-3xl text-blue-600"></i>
                                    </div>
                                    <h3 class="font-bold text-gray-900 mb-1">Empresa Grande</h3>
                                    <p class="text-sm text-gray-600">Para empresas consolidadas que buscan proveedores</p>
                                </div>
                            </div>
                            
                            <!-- Pyme -->
                            <div class="tipo-empresa-card border-2 border-gray-200 rounded-xl p-6 cursor-pointer"
                                 onclick="seleccionarTipo('pyme')">
                                <input type="radio" name="tipo_empresa" value="pyme" id="tipo_pyme" class="hidden" required>
                                <div class="text-center">
                                    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3" style="background: linear-gradient(to bottom right, #d1fae5, #a7f3d0);">
                                        <i class="fas fa-store text-3xl" style="color: #059669;"></i>
                                    </div>
                                    <h3 class="font-bold text-gray-900 mb-1">Pyme</h3>
                                    <p class="text-sm text-gray-600">Para pequeñas y medianas empresas proveedoras</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Datos Básicos -->
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
                                <i class="fas fa-briefcase mr-2 text-blue-600"></i>Rubro
                            </label>
                            <input 
                                type="text" 
                                id="rubro" 
                                name="rubro" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg transition-all focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Ej: Tecnología, Agricultura, Minería"
                            >
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-2 text-blue-600"></i>Email (sera usado como usuario para ingresar)
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg transition-all focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="contacto@empresa.cl"
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="telefono" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-phone mr-2 text-blue-600"></i>Teléfono
                            </label>
                            <input 
                                type="tel" 
                                id="telefono" 
                                name="telefono" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg transition-all focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="+56 9 1234 5678"
                            >
                        </div>
                    </div>
                    
                    <!-- Contraseña -->
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
                    
                    <!-- Información adicional -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Nota:</strong> Después del registro podrás completar tu perfil con logo, descripción y más detalles.
                        </p>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="pt-3">
                        <button 
                            type="submit" 
                            class="btn-primary w-full py-4 rounded-lg font-semibold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                        >
                            <i class="fas fa-check-circle mr-2"></i>Registrar Empresa
                        </button>
                    </div>
                    
                    <!-- Loading -->
                    <div id="loadingMsg" class="hidden text-center text-gray-600 text-sm">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Procesando registro...
                    </div>
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
            
            <!-- Footer con logo del desarrollador -->
            <div class="bg-gray-50 border-t border-gray-200 py-4 px-8">
                <div class="flex items-center justify-center gap-2 text-gray-500 text-xs">
                    <span>Desarrollado por</span>
                    <img src="https://www.bioceanicocentral.cl/wp-content/uploads/2025/11/wallRecurso-10iphone-1024x341-1.png" alt="SEID Development" class="h-6 w-auto opacity-80 hover:opacity-100 transition-opacity">
                </div>
            </div>
        </div>
    </div>

    <script>
        // Seleccionar tipo de empresa
        function seleccionarTipo(tipo) {
            // Remover selección anterior
            document.querySelectorAll('.tipo-empresa-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Seleccionar nuevo
            const card = document.querySelector(`#tipo_${tipo}`).closest('.tipo-empresa-card');
            card.classList.add('selected');
            document.getElementById(`tipo_${tipo}`).checked = true;
        }
        
        // Submit del formulario
        document.getElementById('registroForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const alertBox = document.getElementById('alertBox');
            const loadingMsg = document.getElementById('loadingMsg');
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Validar que se seleccionó tipo de empresa
            const tipoSeleccionado = document.querySelector('input[name="tipo_empresa"]:checked');
            if (!tipoSeleccionado) {
                alertBox.className = 'mb-6 p-4 rounded-lg bg-red-100 border border-red-300 text-red-800 flex items-center';
                alertBox.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Debes seleccionar un tipo de empresa';
                alertBox.classList.remove('hidden');
                return;
            }
            
            // Validar que las contraseñas coincidan
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            
            if (password !== passwordConfirm) {
                alertBox.className = 'mb-6 p-4 rounded-lg bg-red-100 border border-red-300 text-red-800 flex items-center';
                alertBox.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Las contraseñas no coinciden';
                alertBox.classList.remove('hidden');
                return;
            }
            
            // Limpiar alertas previas
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
                    
                    // Redirigir después de 2 segundos
                    setTimeout(() => {
                        window.location.href = data.data.redirect;
                    }, 2000);
                } else {
                    alertBox.className = 'mb-6 p-4 rounded-lg bg-red-100 border border-red-300 text-red-800 flex items-center';
                    alertBox.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + data.message;
                    alertBox.classList.remove('hidden');
                }
            })
            .catch(error => {
                loadingMsg.classList.add('hidden');
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                alertBox.className = 'mb-6 p-4 rounded-lg bg-red-100 border border-red-300 text-red-800 flex items-center';
                alertBox.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Error de conexión. Intente nuevamente.';
                alertBox.classList.remove('hidden');
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>