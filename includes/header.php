<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}
if (!isset($_SESSION)) {
    require_once __DIR__ . '/../config/session.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? SITE_NAME; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f5f7ff',
                            100: '#ebefff',
                            200: '#d6deff',
                            300: '#b3c1ff',
                            400: '#8c9eff',
                            500: '#667eea',
                            600: '#5568d3',
                            700: '#4553b8',
                            800: '#384497',
                            900: '#2d3675',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        /* Animaciones personalizadas */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .animate-slide-down {
            animation: slideDown 0.3s ease-out;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Smooth transitions */
        * {
            transition: all 0.2s ease;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #5568d3;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-gradient-to-r from-primary-600 to-purple-600 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo y nombre -->
                <div class="flex items-center space-x-3">
                    <img src="https://www.bioceanicocentral.cl/wp-content/uploads/2025/11/logonuevo.png" 
                         alt="Nodo Bioceánico Central" 
                         class="h-12 w-auto">
                    <div class="text-white">
                        <div class="font-bold text-lg"><?php echo SITE_NAME; ?></div>
                        <div class="text-xs opacity-90">28 de Noviembre, 2025</div>
                    </div>
                </div>
                
                <!-- Usuario y logout -->
                <div class="flex items-center space-x-4">
                    <div class="hidden md:block text-right">
                        <div class="text-white font-semibold text-sm">
                            <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?>
                        </div>
                        <div class="text-white text-xs opacity-90">
                            <?php
                            $rolNames = [
                                'empresa_a' => 'Modalidad: Busco Servicios',
                                'empresa_b' => 'Modalidad: Ofrezco Servicios',
                                'admin' => 'Administrador'
                            ];
                            echo $rolNames[$_SESSION['rol'] ?? ''] ?? 'Usuario';
                            ?>
                        </div>
                    </div>
                    
                    <a href="<?php echo BASE_URL; ?>api/logout.php" 
                       class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hidden sm:inline">Cerrar Sesión</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>