<?php
/**
 * Login Page
 */
session_start();
require_once __DIR__ . '/../../config/db.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: /views/events/index.php');
    exit;
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Muniverse</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fdf4ff',
                            100: '#fae8ff',
                            500: '#d946ef',
                            600: '#c026d3',
                            700: '#a21caf',
                        }
                    }
                }
            }
        }
    </script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #d946ef 0%, #a21caf 50%, #86198f 100%); }
        .btn-primary {
            background: linear-gradient(135deg, #d946ef 0%, #c026d3 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -4px rgba(217, 70, 239, 0.5);
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md animate-fade-in">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 gradient-bg rounded-2xl mb-4">
                <i class="fas fa-globe text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Muniverse</h1>
            <p class="text-gray-500 mt-1">Connect with your community</p>
        </div>
        
        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Sign In</h2>
            
            <?php if ($flash): ?>
            <div class="mb-6 rounded-lg p-4 <?= $flash['type'] === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' ?>">
                <div class="flex items-center gap-3">
                    <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <p class="text-sm"><?= $flash['message'] ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <form action="/controllers/AuthController.php?action=login" method="POST" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required
                            class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                            placeholder="you@email.com"
                        >
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                            placeholder="••••••••"
                        >
                    </div>
                </div>
                
                <button type="submit" class="w-full btn-primary text-white py-3 rounded-xl font-semibold">
                    Sign In
                </button>
            </form>
            
            <p class="mt-6 text-center text-gray-500 text-sm">
                Don't have an account? 
                <a href="/views/auth/register.php" class="text-primary-600 hover:text-primary-700 font-medium">
                    Sign up here
                </a>
            </p>
        </div>
        
        <!-- Demo credentials -->
        <!--<div class="mt-6 bg-primary-50 rounded-xl p-4 text-center space-y-2">
            <p class="text-sm text-primary-800">
                <i class="fas fa-user mr-1"></i>
                <strong>Personal Demo:</strong> demo@muniverse.com / demo123
            </p>
            <p class="text-sm text-primary-800">
                <i class="fas fa-store mr-1"></i>
                <strong>Business Demo:</strong> cafeteria@business.com / demo123
            </p>
        </div>-->

    </div>
</body>
</html>
