<?php
/**
 * Common header for all pages - Sidebar Layout
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/db.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'normal';
$flash = getFlashMessage();

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentPath = $_SERVER['PHP_SELF'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'Muniverse - Connect with your community') ?></title>
    <meta name="description" content="Platform to manage activities, events and connect with people with similar interests">
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fdf4ff',
                            100: '#fae8ff',
                            200: '#f5d0fe',
                            300: '#f0abfc',
                            400: '#e879f9',
                            500: '#d946ef',
                            600: '#c026d3',
                            700: '#a21caf',
                            800: '#86198f',
                            900: '#701a75',
                        },
                        accent: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #d946ef 0%, #a21caf 50%, #86198f 100%);
        }
        
        .gradient-accent {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #d946ef 0%, #c026d3 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -4px rgba(217, 70, 239, 0.5);
        }
        
        .btn-accent {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            transition: all 0.3s ease;
        }
        
        .btn-accent:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -4px rgba(249, 115, 22, 0.5);
        }
        
        .calendar-day {
            min-height: 120px;
        }
        
        .event-pill {
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .availability-block {
            background: repeating-linear-gradient(
                45deg,
                rgba(217, 70, 239, 0.1),
                rgba(217, 70, 239, 0.1) 5px,
                rgba(217, 70, 239, 0.2) 5px,
                rgba(217, 70, 239, 0.2) 10px
            );
            border: 1px dashed #d946ef;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .nav-item {
            transition: all 0.2s ease;
        }
        
        .nav-item:hover {
            background: rgba(217, 70, 239, 0.1);
        }
        
        .nav-item.active {
            background: rgba(217, 70, 239, 0.15);
            color: #c026d3;
            border-left: 3px solid #c026d3;
        }
        
        .sponsored-badge {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        }
        
        /* Sidebar styles */
        .sidebar {
            transition: transform 0.3s ease;
        }
        
        /* Mobile bottom nav */
        .bottom-nav-item {
            transition: all 0.2s ease;
        }
        
        .bottom-nav-item.active {
            color: #c026d3;
        }
        
        .bottom-nav-item.active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 24px;
            height: 3px;
            background: #c026d3;
            border-radius: 0 0 4px 4px;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php if ($isLoggedIn): ?>
    <!-- Desktop Sidebar -->
    <aside class="hidden md:flex fixed left-0 top-0 h-full w-64 bg-white border-r border-gray-100 flex-col z-50 sidebar">
        <!-- Logo -->
        <div class="p-6 border-b border-gray-100">
            <a href="/views/calendar/index.php" class="flex items-center gap-3">
                <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center">
                    <i class="fas fa-globe text-white text-lg"></i>
                </div>
                <span class="text-xl font-bold text-gray-900">Muniverse</span>
            </a>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 py-6">
            <div class="px-3 mb-2">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider px-3">Menu</span>
            </div>
            
            <a href="/views/calendar/index.php" 
               class="nav-item flex items-center gap-3 px-6 py-3 text-gray-700 font-medium <?= strpos($currentPath, 'calendar') !== false ? 'active' : '' ?>">
                <i class="fas fa-calendar-week w-5 text-center"></i>
                <span>Calendar</span>
            </a>
            
            <?php if ($userRole !== 'business'): ?>
            <a href="/views/events/index.php" 
               class="nav-item flex items-center gap-3 px-6 py-3 text-gray-700 font-medium <?= $currentPage === 'index' && strpos($currentPath, 'events') !== false ? 'active' : '' ?>">
                <i class="fas fa-compass w-5 text-center"></i>
                <span>Explore</span>
            </a>
            <?php endif; ?>
            
            <a href="/views/events/my-events.php" 
               class="nav-item flex items-center gap-3 px-6 py-3 text-gray-700 font-medium <?= $currentPage === 'my-events' ? 'active' : '' ?>">
                <i class="fas fa-list w-5 text-center"></i>
                <span>My Activities</span>
            </a>
            
            <div class="px-3 mt-6 mb-2">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider px-3">Actions</span>
            </div>
            
            <?php if ($userRole === 'business'): ?>
            <a href="/views/events/create.php" 
               class="nav-item flex items-center gap-3 px-6 py-3 text-accent-600 font-medium <?= $currentPage === 'create' ? 'active' : '' ?>">
                <i class="fas fa-store w-5 text-center"></i>
                <span>Offer Space</span>
            </a>
            <?php else: ?>
            <a href="/views/events/create.php" 
               class="nav-item flex items-center gap-3 px-6 py-3 text-purple-600 font-medium <?= $currentPage === 'create' ? 'active' : '' ?>">
                <i class="fas fa-plus w-5 text-center"></i>
                <span>New Activity</span>
            </a>
            <?php endif; ?>
        </nav>
        
        <!-- User Profile Section -->
        <div class="border-t border-gray-100 p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 <?= $userRole === 'business' ? 'bg-accent-100' : 'bg-primary-100' ?> rounded-full flex items-center justify-center">
                    <i class="fas <?= $userRole === 'business' ? 'fa-store' : 'fa-user' ?> <?= $userRole === 'business' ? 'text-accent-600' : 'text-primary-600' ?>"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 truncate"><?= h($userName) ?></p>
                    <p class="text-xs text-gray-500"><?= $userRole === 'business' ? 'Business' : 'Personal' ?></p>
                </div>
            </div>
            
            <div class="flex gap-2">
                <a href="/views/auth/profile.php" class="flex-1 text-center py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    <i class="fas fa-cog"></i>
                </a>
                <a href="/controllers/AuthController.php?action=logout" class="flex-1 text-center py-2 text-sm text-red-500 hover:bg-red-50 rounded-lg transition">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </aside>
    
    <!-- Mobile Bottom Navigation -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 z-50 safe-area-bottom">
        <div class="flex items-center justify-around h-16">
            <a href="/views/calendar/index.php" 
               class="bottom-nav-item relative flex flex-col items-center justify-center flex-1 h-full text-gray-500 <?= strpos($currentPath, 'calendar') !== false ? 'active' : '' ?>">
                <i class="fas fa-calendar-week text-lg"></i>
                <span class="text-xs mt-1">Calendar</span>
            </a>
            
            <?php if ($userRole !== 'business'): ?>
            <a href="/views/events/index.php" 
               class="bottom-nav-item relative flex flex-col items-center justify-center flex-1 h-full text-gray-500 <?= $currentPage === 'index' && strpos($currentPath, 'events') !== false ? 'active' : '' ?>">
                <i class="fas fa-compass text-lg"></i>
                <span class="text-xs mt-1">Explore</span>
            </a>
            <?php endif; ?>
            
            <a href="/views/events/create.php" 
               class="bottom-nav-item relative flex flex-col items-center justify-center flex-1 h-full <?= $userRole === 'business' ? 'text-accent-500' : 'text-purple-500' ?>">
                <div class="w-12 h-12 <?= $userRole === 'business' ? 'gradient-accent' : 'gradient-bg' ?> rounded-full flex items-center justify-center -mt-5 shadow-lg">
                    <i class="fas fa-plus text-white text-lg"></i>
                </div>
            </a>
            
            <a href="/views/events/my-events.php" 
               class="bottom-nav-item relative flex flex-col items-center justify-center flex-1 h-full text-gray-500 <?= $currentPage === 'my-events' ? 'active' : '' ?>">
                <i class="fas fa-list text-lg"></i>
                <span class="text-xs mt-1">Activities</span>
            </a>
            
            <a href="/views/auth/profile.php" 
               class="bottom-nav-item relative flex flex-col items-center justify-center flex-1 h-full text-gray-500 <?= $currentPage === 'profile' ? 'active' : '' ?>">
                <i class="fas fa-user text-lg"></i>
                <span class="text-xs mt-1">Profile</span>
            </a>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Main Content Wrapper -->
    <div class="<?= $isLoggedIn ? 'md:ml-64 pb-20 md:pb-0' : '' ?>">
        <!-- Mobile Header (only for non-logged in or minimal info) -->
        <?php if (!$isLoggedIn): ?>
        <nav class="bg-white shadow-sm sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="/" class="flex items-center gap-2">
                            <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center">
                                <i class="fas fa-globe text-white text-lg"></i>
                            </div>
                            <span class="text-xl font-bold text-gray-900">Muniverse</span>
                        </a>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="/views/auth/login.php" class="text-gray-600 hover:text-gray-900 font-medium">Log In</a>
                        <a href="/views/auth/register.php" class="btn-primary text-white px-4 py-2 rounded-lg font-medium text-sm">Sign Up</a>
                    </div>
                </div>
            </div>
        </nav>
        <?php endif; ?>
        
        <!-- Flash Messages -->
        <?php if ($flash): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="rounded-lg p-4 animate-fade-in <?= $flash['type'] === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                <div class="flex items-center gap-3">
                    <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <p><?= $flash['message'] ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <main class="animate-fade-in">
