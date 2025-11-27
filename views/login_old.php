<?php
require_once '../config/config.php';
require_once '../config/session.php';

// Si ya está logueado, redirigir a su panel
if (isLoggedIn()) {
    redirectToDashboard(getUserRole());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    
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
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        
        .btn-primary {
            background: linear-gradient(to right, #667eea, #764ba2);
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, #5568d3, #6a3f8f);
        }
    </style>
</head>
<body class="gradient-animation min-h-screen flex items-center justify-center p-4">
    <!-- Decorative elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-10 w-72 h-72 bg-white/10 rounded-full blur-3xl float-animation"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-white/10 rounded-full blur-3xl float-animation" style="animation-delay: 2s;"></div>
    </div>
    
    <!-- Login Container -->
    <div class="relative w-full max-w-md">
        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden backdrop-blur-sm">
            <!-- Header -->
            <div style="background: linear-gradient(to right, #667eea, #764ba2);" class="p-8 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-lg mb-4">
                    <span style="color: #667eea;" class="font-bold text-3xl">RN</span>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2"><?php echo SITE_NAME; ?></h1>
                <p class="text-white/90 text-sm">28 de Noviembre, 2025</p>
            </div>
            
            <!-- Body -->
            <div class="p-8">
                <div class="mb-6 text-center">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Bienvenido</h2>
                    <p class="text-gray-600 text-sm">Ingrese sus credenciales para continuar</p>
                </div>
                
                <!-- Mensaje de Registro Exitoso -->
                <?php if (isset($_GET['registro']) && $_GET['registro'] === 'exitoso'): ?>
                    <div class="mb-6 p-4 rounded-lg bg-green-100 border border-green-300 text-green-800 flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <div>
                            <strong>¡Registro exitoso!</strong><br>
                            <span class="text-sm">Ya puedes iniciar sesión con tus credenciales.</span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Alert Box -->
                <div id="alertBox" class="hidden mb-6 p-4 rounded-lg"></div>
                
                <!-- Form -->
                <form id="loginForm" method="POST" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2" style="color: #667eea;"></i>Correo Electrónico
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent transition-all"
                            style="outline: none;"
                            placeholder="usuario@empresa.cl"
                            required 
                            autocomplete="email"
                        >
                    </div>
                    
                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2" style="color: #667eea;"></i>Contraseña
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent transition-all"
                            style="outline: none;"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="pt-3">
                        <button 
                            type="submit" 
                            class="btn-primary w-full text-white py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                        >
                            <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
                        </button>
                    </div>
                    
                    <!-- Loading -->
                    <div id="loadingMsg" class="hidden text-center text-gray-600 text-sm pt-2">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Verificando credenciales...
                    </div>
                </form>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 px-8 py-4 text-center border-t">
                <p class="text-xs text-gray-500 mb-3">
                    Sistema de Gestión de Reuniones Empresariales
                </p>
                <p class="text-sm text-gray-700">
                    ¿No tienes cuenta? 
                    <a href="registro.php" style="color: #667eea;" class="font-semibold hover:underline transition-colors">
                        <i class="fas fa-user-plus mr-1"></i>Registrar mi empresa
                    </a>
                </p>
            </div>
        </div>
        
        <!-- Info cards -->
        <div class="mt-6 grid grid-cols-3 gap-4 text-white text-center">
            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-3">
                <i class="fas fa-building text-2xl mb-1"></i>
                <p class="text-xs font-semibold">Empresas</p>
            </div>
            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-3">
                <i class="fas fa-handshake text-2xl mb-1"></i>
                <p class="text-xs font-semibold">Reuniones</p>
            </div>
            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-3">
                <i class="fas fa-calendar-check text-2xl mb-1"></i>
                <p class="text-xs font-semibold">Agenda</p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const alertBox = document.getElementById('alertBox');
            const loadingMsg = document.getElementById('loadingMsg');
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Limpiar alertas previas
            alertBox.classList.add('hidden');
            alertBox.className = 'hidden mb-6 p-4 rounded-lg';
            
            // Mostrar loading
            loadingMsg.classList.remove('hidden');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            
            // Obtener datos del formulario
            const formData = new FormData(this);
            
            // Enviar petición AJAX
            fetch('../api/login.php', {
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
                    
                    // La URL viene en data.data.redirect o data.redirect
                    const redirectUrl = data.data?.redirect || data.redirect;
                    
                    if (redirectUrl) {
                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, 500);
                    }
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