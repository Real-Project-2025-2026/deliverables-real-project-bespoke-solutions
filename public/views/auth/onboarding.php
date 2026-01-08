<?php
/**
 * Onboarding Page - First Steps
 */
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/User.php';

requireLogin();

// If onboarding is already completed, redirect
$userModel = new User();
$user = $userModel->findById($_SESSION['user_id']);

if ($user['onboarding_completed']) {
    header('Location: /views/calendar/index.php');
    exit;
}

// Get all neighborhoods for selection
$neighborhoods = $userModel->getAllNeighborhoods();

$flash = getFlashMessage();
$dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$hours = range(8, 22); // 08:00 to 22:00 (last block is 22:00-23:00)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up Your Profile - Muniverse</title>
    
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
        .category-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .category-card:hover {
            transform: translateY(-2px);
        }
        .category-card.selected {
            border-color: #d946ef;
            background: #fdf4ff;
        }
        .neighborhood-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .neighborhood-card:hover {
            border-color: #d946ef;
            background: #fdf4ff;
        }
        .neighborhood-card.selected {
            border-color: #d946ef;
            background: #fdf4ff;
            box-shadow: 0 0 0 2px rgba(217, 70, 239, 0.3);
        }
        .step-indicator.active {
            background: linear-gradient(135deg, #d946ef 0%, #c026d3 100%);
            color: white;
        }
        .step-indicator.completed {
            background: #22c55e;
            color: white;
        }
        .time-slot {
            transition: all 0.15s ease;
            cursor: pointer;
            user-select: none;
        }
        .time-slot:hover {
            background: #fae8ff;
        }
        .time-slot.selected {
            background: linear-gradient(135deg, #d946ef 0%, #c026d3 100%);
            color: white;
        }
        .time-slot.selecting {
            background: #e9d5ff;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 py-8 animate-fade-in">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 gradient-bg rounded-2xl mb-4">
                <i class="fas fa-magic text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Welcome to Muniverse!</h1>
            <p class="text-gray-500 mt-1">Set up your preferences to find perfect activities in Munich</p>
        </div>
        
        <!-- Step Indicators -->
        <div class="flex justify-center gap-4 mb-8">
            <div class="flex items-center gap-2">
                <div class="step-indicator active w-8 h-8 rounded-full flex items-center justify-center font-medium" id="step1-indicator">1</div>
                <span class="text-sm text-gray-600">Neighborhood</span>
            </div>
            <div class="w-8 h-0.5 bg-gray-200 self-center"></div>
            <div class="flex items-center gap-2">
                <div class="step-indicator w-8 h-8 rounded-full flex items-center justify-center font-medium bg-gray-200 text-gray-500" id="step2-indicator">2</div>
                <span class="text-sm text-gray-600">Interests</span>
            </div>
            <div class="w-8 h-0.5 bg-gray-200 self-center"></div>
            <div class="flex items-center gap-2">
                <div class="step-indicator w-8 h-8 rounded-full flex items-center justify-center font-medium bg-gray-200 text-gray-500" id="step3-indicator">3</div>
                <span class="text-sm text-gray-600">Availability</span>
            </div>
        </div>
        
        <?php if ($flash): ?>
        <div class="mb-6 rounded-lg p-4 <?= $flash['type'] === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' ?>">
            <div class="flex items-center gap-3">
                <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <p class="text-sm"><?= $flash['message'] ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <form action="/controllers/AuthController.php?action=save-onboarding" method="POST" id="onboardingForm">
            <!-- Hidden inputs -->
            <input type="hidden" name="availability_data" id="availabilityData" value="[]">
            <input type="hidden" name="neighborhood_id" id="neighborhoodId" value="">
            
            <!-- Step 1: Neighborhood Selection -->
            <div id="step1" class="bg-white rounded-2xl shadow-xl p-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-2">
                    <i class="fas fa-map-marker-alt text-purple-500 mr-2"></i>
                    Where in Munich do you live?
                </h2>
                <p class="text-gray-500 mb-6">Select your neighborhood to discover local activities</p>
                
                <!-- Search -->
                <div class="relative mb-6">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-search"></i></span>
                    <input type="text" id="neighborhoodSearch" placeholder="Search neighborhoods..." 
                        class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        oninput="filterNeighborhoods(this.value)">
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-8 max-h-96 overflow-y-auto" id="neighborhoodGrid">
                    <?php foreach ($neighborhoods as $neighborhood): ?>
                    <div class="neighborhood-card p-4 rounded-xl border-2 border-gray-200 text-center"
                         data-id="<?= $neighborhood['id'] ?>"
                         data-name="<?= strtolower($neighborhood['name']) ?>"
                         onclick="selectNeighborhood(<?= $neighborhood['id'] ?>, this)">
                        <i class="fas fa-map-pin text-purple-400 mb-2"></i>
                        <p class="font-medium text-gray-900 text-sm"><?= h($neighborhood['short_name'] ?? $neighborhood['name']) ?></p>
                        <p class="text-xs text-gray-500 truncate" title="<?= h($neighborhood['name']) ?>">
                            <?= strlen($neighborhood['name']) > 20 ? substr(h($neighborhood['name']), 0, 20) . '...' : h($neighborhood['name']) ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" onclick="goToStep(2)" class="w-full btn-primary text-white py-3 rounded-xl font-semibold" id="step1Next" disabled>
                    Continue <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
            
            <!-- Step 2: Categories -->
            <div id="step2" class="bg-white rounded-2xl shadow-xl p-8 hidden">
                <h2 class="text-xl font-semibold text-gray-900 mb-2">What are you interested in?</h2>
                <p class="text-gray-500 mb-6">Select the categories you like most (you can choose several)</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                    <label class="category-card p-4 rounded-xl border-2 border-gray-200">
                        <input type="checkbox" name="categories[]" value="sports" class="hidden" onchange="updateCategory(this)">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-running text-green-600 text-2xl"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Sports</p>
                                <p class="text-sm text-gray-500">Running, football, yoga...</p>
                            </div>
                        </div>
                    </label>
                    
                    <label class="category-card p-4 rounded-xl border-2 border-gray-200">
                        <input type="checkbox" name="categories[]" value="culture" class="hidden" onchange="updateCategory(this)">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-palette text-purple-600 text-2xl"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Culture</p>
                                <p class="text-sm text-gray-500">Museums, theater, art...</p>
                            </div>
                        </div>
                    </label>
                    
                    <label class="category-card p-4 rounded-xl border-2 border-gray-200">
                        <input type="checkbox" name="categories[]" value="food" class="hidden" onchange="updateCategory(this)">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-utensils text-orange-600 text-2xl"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Food</p>
                                <p class="text-sm text-gray-500">Dinners, tastings, brunch...</p>
                            </div>
                        </div>
                    </label>
                    
                    <label class="category-card p-4 rounded-xl border-2 border-gray-200">
                        <input type="checkbox" name="categories[]" value="games" class="hidden" onchange="updateCategory(this)">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-gamepad text-blue-600 text-2xl"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Games</p>
                                <p class="text-sm text-gray-500">Board games, RPG...</p>
                            </div>
                        </div>
                    </label>
                    
                    <label class="category-card p-4 rounded-xl border-2 border-gray-200 md:col-span-2">
                        <input type="checkbox" name="categories[]" value="language" class="hidden" onchange="updateCategory(this)">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-cyan-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-language text-cyan-600 text-2xl"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Languages</p>
                                <p class="text-sm text-gray-500">Exchanges, conversation...</p>
                            </div>
                        </div>
                    </label>
                </div>
                
                <div class="flex gap-4">
                    <button type="button" onclick="goToStep(1)" class="px-6 py-3 border border-gray-200 rounded-xl font-medium text-gray-600 hover:bg-gray-50 transition">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </button>
                    <button type="button" onclick="goToStep(3)" class="flex-1 btn-primary text-white py-3 rounded-xl font-semibold">
                        Continue <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
            
            <!-- Step 3: Weekly Availability Calendar -->
            <div id="step3" class="bg-white rounded-2xl shadow-xl p-6 hidden">
                <h2 class="text-xl font-semibold text-gray-900 mb-2">When are you free?</h2>
                <p class="text-gray-500 mb-4">Click or drag to select time blocks</p>
                
                <div class="text-xs text-gray-500 mb-4 flex items-center gap-4">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-gradient-to-r from-purple-500 to-purple-600"></span> Available</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-gray-200"></span> Not available</span>
                </div>
                
                <!-- Calendar Grid -->
                <div class="overflow-x-auto -mx-2 px-2">
                    <div class="min-w-[600px]">
                        <!-- Header row -->
                        <div class="grid grid-cols-8 gap-1 mb-1">
                            <div class="text-xs text-gray-400 text-right pr-2 py-2">Time</div>
                            <?php foreach ($dayNames as $index => $day): ?>
                            <div class="text-xs font-medium text-gray-600 text-center py-2"><?= $day ?></div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Time slots -->
                        <?php foreach ($hours as $hour): ?>
                        <div class="grid grid-cols-8 gap-1 mb-1">
                            <div class="text-xs text-gray-400 text-right pr-2 py-1"><?= sprintf('%02d:00', $hour) ?></div>
                            <?php for ($day = 0; $day < 7; $day++): ?>
                            <div class="time-slot h-8 bg-gray-100 rounded text-xs flex items-center justify-center border border-transparent hover:border-purple-300"
                                 data-day="<?= $day ?>" 
                                 data-hour="<?= $hour ?>"
                                 onmousedown="startSelection(this, event)"
                                 onmouseenter="continueSelection(this)"
                                 onmouseup="endSelection()">
                            </div>
                            <?php endfor; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="button" onclick="goToStep(2)" class="px-6 py-3 border border-gray-200 rounded-xl font-medium text-gray-600 hover:bg-gray-50 transition">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </button>
                    <button type="submit" class="flex-1 btn-primary text-white py-3 rounded-xl font-semibold">
                        <i class="fas fa-check mr-2"></i>Done! Start exploring Munich
                    </button>
                </div>
            </div>
        </form>
        
        <!-- Skip link -->
        <p class="text-center mt-6">
            <a href="/views/calendar/index.php" class="text-gray-500 hover:text-gray-700 text-sm">
                Skip setup for now
            </a>
        </p>
    </div>
    
    <script>
    // Selection state
    let isSelecting = false;
    let selectionStart = null;
    let selectedSlots = new Set();
    let selectedNeighborhoodId = null;
    
    function selectNeighborhood(id, element) {
        // Remove selected from all
        document.querySelectorAll('.neighborhood-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Add selected to clicked
        element.classList.add('selected');
        selectedNeighborhoodId = id;
        document.getElementById('neighborhoodId').value = id;
        document.getElementById('step1Next').disabled = false;
    }
    
    function filterNeighborhoods(query) {
        const cards = document.querySelectorAll('.neighborhood-card');
        const lowerQuery = query.toLowerCase();
        
        cards.forEach(card => {
            const name = card.dataset.name;
            if (name.includes(lowerQuery)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    function updateCategory(checkbox) {
        const card = checkbox.closest('.category-card');
        if (checkbox.checked) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    }
    
    function startSelection(cell, event) {
        event.preventDefault();
        isSelecting = true;
        selectionStart = cell;
        toggleSlot(cell);
    }
    
    function continueSelection(cell) {
        if (!isSelecting) return;
        
        const startDay = parseInt(selectionStart.dataset.day);
        const startHour = parseInt(selectionStart.dataset.hour);
        const currentDay = parseInt(cell.dataset.day);
        const currentHour = parseInt(cell.dataset.hour);
        
        // Only allow selection in same column (day)
        if (startDay === currentDay) {
            const minHour = Math.min(startHour, currentHour);
            const maxHour = Math.max(startHour, currentHour);
            
            // Select all cells in range
            for (let h = minHour; h <= maxHour; h++) {
                const slotCell = document.querySelector(`.time-slot[data-day="${currentDay}"][data-hour="${h}"]`);
                if (slotCell && !slotCell.classList.contains('selected')) {
                    slotCell.classList.add('selecting');
                }
            }
        }
    }
    
    function endSelection() {
        if (!isSelecting) return;
        
        // Convert selecting to selected
        document.querySelectorAll('.time-slot.selecting').forEach(cell => {
            cell.classList.remove('selecting');
            cell.classList.add('selected');
            const key = `${cell.dataset.day}-${cell.dataset.hour}`;
            selectedSlots.add(key);
        });
        
        isSelecting = false;
        selectionStart = null;
        updateAvailabilityData();
    }
    
    function toggleSlot(cell) {
        const key = `${cell.dataset.day}-${cell.dataset.hour}`;
        
        if (cell.classList.contains('selected')) {
            cell.classList.remove('selected');
            selectedSlots.delete(key);
        } else {
            cell.classList.add('selected');
            selectedSlots.add(key);
        }
        updateAvailabilityData();
    }
    
    function updateAvailabilityData() {
        // Convert selected slots to availability ranges
        const availability = {};
        
        selectedSlots.forEach(key => {
            const [day, hour] = key.split('-').map(Number);
            if (!availability[day]) {
                availability[day] = [];
            }
            availability[day].push(hour);
        });
        
        // Convert hours to ranges
        const ranges = [];
        Object.entries(availability).forEach(([day, hours]) => {
            hours.sort((a, b) => a - b);
            
            let rangeStart = hours[0];
            let rangeEnd = hours[0];
            
            for (let i = 1; i <= hours.length; i++) {
                if (i < hours.length && hours[i] === rangeEnd + 1) {
                    rangeEnd = hours[i];
                } else {
                    ranges.push({
                        day: parseInt(day),
                        start: `${String(rangeStart).padStart(2, '0')}:00`,
                        end: `${String(rangeEnd + 1).padStart(2, '0')}:00`
                    });
                    if (i < hours.length) {
                        rangeStart = hours[i];
                        rangeEnd = hours[i];
                    }
                }
            }
        });
        
        document.getElementById('availabilityData').value = JSON.stringify(ranges);
    }
    
    // Prevent text selection during drag
    document.addEventListener('mouseup', endSelection);
    
    function goToStep(step) {
        document.getElementById('step1').classList.add('hidden');
        document.getElementById('step2').classList.add('hidden');
        document.getElementById('step3').classList.add('hidden');
        document.getElementById('step' + step).classList.remove('hidden');
        
        // Update indicators
        const step1Indicator = document.getElementById('step1-indicator');
        const step2Indicator = document.getElementById('step2-indicator');
        const step3Indicator = document.getElementById('step3-indicator');
        
        // Reset all
        [step1Indicator, step2Indicator, step3Indicator].forEach(el => {
            el.classList.remove('active', 'completed');
            el.classList.add('bg-gray-200', 'text-gray-500');
        });
        
        if (step === 1) {
            step1Indicator.classList.remove('bg-gray-200', 'text-gray-500');
            step1Indicator.classList.add('active');
        } else if (step === 2) {
            step1Indicator.classList.remove('bg-gray-200', 'text-gray-500');
            step1Indicator.classList.add('completed');
            step2Indicator.classList.remove('bg-gray-200', 'text-gray-500');
            step2Indicator.classList.add('active');
        } else if (step === 3) {
            step1Indicator.classList.remove('bg-gray-200', 'text-gray-500');
            step1Indicator.classList.add('completed');
            step2Indicator.classList.remove('bg-gray-200', 'text-gray-500');
            step2Indicator.classList.add('completed');
            step3Indicator.classList.remove('bg-gray-200', 'text-gray-500');
            step3Indicator.classList.add('active');
        }
    }
    </script>
</body>
</html>
