<?php
/**
 * User Profile Page
 */
$pageTitle = 'My Profile - Muniverse';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/User.php';

session_start();
requireLogin();

$userModel = new User();
$user = $userModel->findById($_SESSION['user_id']);
$preferences = $userModel->getPreferences($_SESSION['user_id']);
$availability = $userModel->getAvailability($_SESSION['user_id']);
$neighborhoods = $userModel->getAllNeighborhoods();
$userNeighborhood = $userModel->getNeighborhood($_SESSION['user_id']);

$userRole = $_SESSION['user_role'] ?? 'normal';
$isBusiness = $userRole === 'business';

// Available categories
$categories = [
    'culture' => ['icon' => 'fa-theater-masks', 'label' => 'Culture', 'color' => 'purple'],
    'food' => ['icon' => 'fa-utensils', 'label' => 'Food', 'color' => 'orange'],
    'games' => ['icon' => 'fa-gamepad', 'label' => 'Games', 'color' => 'pink'],
    'language' => ['icon' => 'fa-language', 'label' => 'Languages', 'color' => 'blue'],
    'sports' => ['icon' => 'fa-running', 'label' => 'Sports', 'color' => 'green'],
];

$dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$dayNamesShort = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

// Convert preferences to simple array
$userPrefs = [];
foreach ($preferences as $pref) {
    $userPrefs[] = is_array($pref) ? $pref['category'] : $pref;
}

// Convert availability to calendar format
$availabilityMap = [];
foreach ($availability as $slot) {
    $day = $slot['day_of_week'];
    $startHour = (int)date('G', strtotime($slot['start_time']));
    $endHour = (int)date('G', strtotime($slot['end_time']));
    for ($h = $startHour; $h < $endHour; $h++) {
        $availabilityMap[$day . '-' . $h] = true;
    }
}

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-lg mx-auto px-4 py-6">
    <!-- Profile Header -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">
        <div class="bg-gradient-to-r from-purple-600 to-orange-500 px-6 py-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white/20 rounded-full mb-3">
                <i class="fas <?= $isBusiness ? 'fa-building' : 'fa-user' ?> text-white text-2xl"></i>
            </div>
            <h2 class="text-xl font-bold text-white"><?= h($user['name']) ?></h2>
            <p class="text-white/80 text-sm"><?= h($user['email']) ?></p>
            <span class="mt-2 inline-block bg-white/20 text-white px-3 py-1 rounded-full text-xs font-medium">
                <?= $isBusiness ? 'Business' : 'Personal' ?>
            </span>
        </div>
    </div>
    
    <!-- Account Settings -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-4">
        <div class="p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900 text-sm">Account Settings</h3>
        </div>
        <form action="/controllers/AuthController.php?action=updateProfile" method="POST" class="p-4 space-y-4">
            <div>
                <label for="name" class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                <input type="text" id="name" name="name" value="<?= h($user['name']) ?>" required minlength="2"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
            <div>
                <label for="email" class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                <input type="email" id="email" name="email" value="<?= h($user['email']) ?>" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
            <div class="pt-2 border-t border-gray-100">
                <p class="text-xs text-gray-500 mb-3">Change password (optional)</p>
                <div class="space-y-3">
                    <input type="password" id="password" name="password" minlength="6" placeholder="New password"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <input type="password" id="confirm_password" name="confirm_password" minlength="6" placeholder="Confirm password"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
            </div>
            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-lg font-medium text-sm transition">
                Save Changes
            </button>
        </form>
    </div>
    
    <?php if (!$isBusiness): ?>
    <!-- Neighborhood Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-4">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900 text-sm"><i class="fas fa-map-pin text-purple-500 mr-2"></i>My Neighborhood</h3>
            <button type="button" id="editNeighborhoodBtn" class="text-purple-600 hover:text-purple-700 text-xs font-medium">Edit</button>
        </div>
        <div class="p-4">
            <div id="neighborhoodView">
                <?php if ($userNeighborhood): ?>
                <span class="bg-purple-100 text-purple-700 px-3 py-1.5 rounded-full text-sm font-medium inline-flex items-center gap-2">
                    <i class="fas fa-map-marker-alt"></i><?= h($userNeighborhood['short_name'] ?? $userNeighborhood['name']) ?>
                </span>
                <?php else: ?>
                <p class="text-gray-500 text-xs">No neighborhood set</p>
                <?php endif; ?>
            </div>
            <form id="neighborhoodForm" action="/controllers/AuthController.php?action=updateNeighborhood" method="POST" class="hidden">
                <div class="relative mb-3">
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-search text-xs"></i></span>
                        <input type="text" id="neighborhoodSearchInput" placeholder="Search neighborhood..."
                            class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            autocomplete="off">
                    </div>
                    <input type="hidden" name="neighborhood_id" id="selectedNeighborhoodId" value="<?= $userNeighborhood ? $userNeighborhood['id'] : '' ?>">
                    <div id="neighborhoodDropdown" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden">
                        <?php foreach ($neighborhoods as $n): ?>
                        <div class="neighborhood-option px-3 py-2 hover:bg-purple-50 cursor-pointer text-sm transition <?= ($userNeighborhood && $userNeighborhood['id'] == $n['id']) ? 'bg-purple-100 text-purple-700' : 'text-gray-700' ?>" 
                             data-id="<?= $n['id'] ?>" data-name="<?= h($n['short_name'] ?? $n['name']) ?>">
                            <i class="fas fa-map-marker-alt text-purple-400 mr-2"></i><?= h($n['short_name'] ?? $n['name']) ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div id="selectedNeighborhoodDisplay" class="mb-3 <?= $userNeighborhood ? '' : 'hidden' ?>">
                    <span class="bg-purple-100 text-purple-700 px-3 py-1.5 rounded-full text-sm font-medium inline-flex items-center gap-2">
                        <i class="fas fa-map-marker-alt"></i>
                        <span id="selectedNeighborhoodName"><?= $userNeighborhood ? h($userNeighborhood['short_name'] ?? $userNeighborhood['name']) : '' ?></span>
                        <button type="button" id="clearNeighborhoodBtn" class="ml-1 text-purple-500 hover:text-purple-700"><i class="fas fa-times text-xs"></i></button>
                    </span>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-lg font-medium text-xs transition">Save</button>
                    <button type="button" id="cancelNeighborhoodBtn" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg font-medium text-xs transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Preferences Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-4">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900 text-sm"><i class="fas fa-heart text-purple-500 mr-2"></i>Interests</h3>
            <button type="button" id="editPrefsBtn" class="text-purple-600 hover:text-purple-700 text-xs font-medium">Edit</button>
        </div>
        <div class="p-4">
            <div id="prefsView" class="flex flex-wrap gap-2">
                <?php if (empty($userPrefs)): ?>
                <p class="text-gray-500 text-xs">No preferences set</p>
                <?php else: ?>
                <?php foreach ($userPrefs as $pref): $config = $categories[$pref] ?? null; if (!$config) continue; ?>
                <span class="bg-<?= $config['color'] ?>-100 text-<?= $config['color'] ?>-700 px-2 py-1 rounded-full text-xs font-medium inline-flex items-center gap-1">
                    <i class="fas <?= $config['icon'] ?> text-[10px]"></i><?= $config['label'] ?>
                </span>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <form id="prefsForm" action="/controllers/AuthController.php?action=updatePreferences" method="POST" class="hidden">
                <div class="grid grid-cols-3 gap-2 mb-3">
                    <?php foreach ($categories as $key => $cat): ?>
                    <label class="cursor-pointer">
                        <input type="checkbox" name="preferences[]" value="<?= $key ?>" <?= in_array($key, $userPrefs) ? 'checked' : '' ?> class="hidden peer">
                        <div class="p-2 border border-gray-200 rounded-lg text-center transition peer-checked:border-<?= $cat['color'] ?>-500 peer-checked:bg-<?= $cat['color'] ?>-50 hover:border-gray-300">
                            <i class="fas <?= $cat['icon'] ?> text-lg text-<?= $cat['color'] ?>-500"></i>
                            <p class="text-xs font-medium text-gray-700 mt-1"><?= $cat['label'] ?></p>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-lg font-medium text-xs transition">Save</button>
                    <button type="button" id="cancelPrefsBtn" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg font-medium text-xs transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Availability Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-4">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900 text-sm"><i class="fas fa-clock text-orange-500 mr-2"></i>Availability</h3>
            <button type="button" id="editAvailBtn" class="text-purple-600 hover:text-purple-700 text-xs font-medium">Edit</button>
        </div>
        <div class="p-4">
            <div id="availView">
                <?php if (empty($availability)): ?>
                <p class="text-gray-500 text-xs">No availability set</p>
                <?php else: ?>
                <div class="space-y-1">
                    <?php 
                    $availByDay = [];
                    foreach ($availability as $slot) {
                        $day = $slot['day_of_week'];
                        if (!isset($availByDay[$day])) $availByDay[$day] = [];
                        $availByDay[$day][] = $slot;
                    }
                    ?>
                    <?php foreach ($availByDay as $day => $slots): ?>
                    <div class="flex items-center gap-2 py-1">
                        <span class="text-xs font-medium text-gray-600 w-16"><?= $dayNamesShort[$day] ?></span>
                        <div class="flex flex-wrap gap-1">
                            <?php foreach ($slots as $slot): ?>
                            <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs"><?= date('H:i', strtotime($slot['start_time'])) ?>-<?= date('H:i', strtotime($slot['end_time'])) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <form id="availForm" action="/controllers/AuthController.php?action=updateAvailability" method="POST" class="hidden">
                <input type="hidden" name="availability_json" id="availabilityJson">
                <p class="text-xs text-gray-500 mb-3"><i class="fas fa-info-circle mr-1"></i>Tap to select time blocks</p>
                <div class="overflow-x-auto">
                    <div class="min-w-[300px]">
                        <div class="grid grid-cols-8 gap-px mb-1">
                            <div class="text-center text-[9px] font-medium text-gray-500"></div>
                            <?php for ($d = 1; $d <= 7; $d++): ?>
                            <div class="text-center text-[9px] font-medium text-gray-700"><?= substr($dayNamesShort[$d % 7], 0, 1) ?></div>
                            <?php endfor; ?>
                        </div>
                        <div class="border border-gray-200 rounded-lg overflow-hidden max-h-48 overflow-y-auto" id="availabilityGrid">
                            <?php for ($hour = 8; $hour < 23; $hour++): ?>
                            <div class="grid grid-cols-8 gap-px bg-gray-200">
                                <div class="bg-gray-50 text-center text-[9px] text-gray-500 py-1"><?= sprintf('%02d', $hour) ?></div>
                                <?php for ($d = 1; $d <= 7; $d++): $dayIndex = $d % 7; $isSelected = isset($availabilityMap[$dayIndex . '-' . $hour]); ?>
                                <div class="avail-cell hover:bg-green-100 cursor-pointer py-1 transition <?= $isSelected ? 'bg-green-500' : 'bg-white' ?>" 
                                     data-day="<?= $dayIndex ?>" data-hour="<?= $hour ?>" data-selected="<?= $isSelected ? 'true' : 'false' ?>"></div>
                                <?php endfor; ?>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2 mt-3">
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-lg font-medium text-xs transition">Save</button>
                    <button type="button" id="cancelAvailBtn" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg font-medium text-xs transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Account Info -->
    <div class="bg-gray-100 rounded-xl p-4 mb-4">
        <p class="text-xs text-gray-500"><i class="fas fa-calendar-alt mr-1"></i>Member since <?= date('M Y', strtotime($user['created_at'])) ?></p>
    </div>
    
    <!-- Logout Button (Mobile) -->
    <div class="md:hidden">
        <a href="/controllers/AuthController.php?action=logout" class="block w-full text-center bg-red-50 hover:bg-red-100 text-red-600 py-3 rounded-xl font-medium text-sm transition">
            <i class="fas fa-sign-out-alt mr-2"></i>Log Out
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle neighborhood with searchable select
    const editNeighborhoodBtn = document.getElementById('editNeighborhoodBtn');
    const cancelNeighborhoodBtn = document.getElementById('cancelNeighborhoodBtn');
    const neighborhoodView = document.getElementById('neighborhoodView');
    const neighborhoodForm = document.getElementById('neighborhoodForm');
    const neighborhoodSearchInput = document.getElementById('neighborhoodSearchInput');
    const neighborhoodDropdown = document.getElementById('neighborhoodDropdown');
    const selectedNeighborhoodId = document.getElementById('selectedNeighborhoodId');
    const selectedNeighborhoodDisplay = document.getElementById('selectedNeighborhoodDisplay');
    const selectedNeighborhoodName = document.getElementById('selectedNeighborhoodName');
    const clearNeighborhoodBtn = document.getElementById('clearNeighborhoodBtn');
    
    if (editNeighborhoodBtn) {
        editNeighborhoodBtn.addEventListener('click', () => {
            neighborhoodView.classList.add('hidden');
            neighborhoodForm.classList.remove('hidden');
            editNeighborhoodBtn.classList.add('hidden');
        });
        
        cancelNeighborhoodBtn.addEventListener('click', () => {
            neighborhoodView.classList.remove('hidden');
            neighborhoodForm.classList.add('hidden');
            editNeighborhoodBtn.classList.remove('hidden');
        });
        
        // Searchable neighborhood functionality
        if (neighborhoodSearchInput) {
            neighborhoodSearchInput.addEventListener('focus', () => {
                neighborhoodDropdown.classList.remove('hidden');
            });
            
            neighborhoodSearchInput.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase();
                const options = neighborhoodDropdown.querySelectorAll('.neighborhood-option');
                options.forEach(opt => {
                    const name = opt.dataset.name.toLowerCase();
                    opt.style.display = name.includes(query) ? 'block' : 'none';
                });
                neighborhoodDropdown.classList.remove('hidden');
            });
            
            // Select neighborhood option
            neighborhoodDropdown.querySelectorAll('.neighborhood-option').forEach(opt => {
                opt.addEventListener('click', () => {
                    selectedNeighborhoodId.value = opt.dataset.id;
                    selectedNeighborhoodName.textContent = opt.dataset.name;
                    selectedNeighborhoodDisplay.classList.remove('hidden');
                    neighborhoodSearchInput.value = '';
                    neighborhoodDropdown.classList.add('hidden');
                    
                    // Update selected state
                    neighborhoodDropdown.querySelectorAll('.neighborhood-option').forEach(o => {
                        o.classList.remove('bg-purple-100', 'text-purple-700');
                        o.classList.add('text-gray-700');
                    });
                    opt.classList.add('bg-purple-100', 'text-purple-700');
                    opt.classList.remove('text-gray-700');
                });
            });
            
            // Clear selection
            if (clearNeighborhoodBtn) {
                clearNeighborhoodBtn.addEventListener('click', () => {
                    selectedNeighborhoodId.value = '';
                    selectedNeighborhoodDisplay.classList.add('hidden');
                    neighborhoodDropdown.querySelectorAll('.neighborhood-option').forEach(o => {
                        o.classList.remove('bg-purple-100', 'text-purple-700');
                        o.classList.add('text-gray-700');
                    });
                });
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!neighborhoodForm.contains(e.target)) {
                    neighborhoodDropdown.classList.add('hidden');
                }
            });
        }
    }
    
    // Toggle preferences
    const editPrefsBtn = document.getElementById('editPrefsBtn');
    const cancelPrefsBtn = document.getElementById('cancelPrefsBtn');
    const prefsView = document.getElementById('prefsView');
    const prefsForm = document.getElementById('prefsForm');
    
    if (editPrefsBtn) {
        editPrefsBtn.addEventListener('click', () => {
            prefsView.classList.add('hidden');
            prefsForm.classList.remove('hidden');
            editPrefsBtn.classList.add('hidden');
        });
        
        cancelPrefsBtn.addEventListener('click', () => {
            prefsView.classList.remove('hidden');
            prefsForm.classList.add('hidden');
            editPrefsBtn.classList.remove('hidden');
        });
    }
    
    // Toggle availability
    const editAvailBtn = document.getElementById('editAvailBtn');
    const cancelAvailBtn = document.getElementById('cancelAvailBtn');
    const availView = document.getElementById('availView');
    const availForm = document.getElementById('availForm');
    
    if (editAvailBtn) {
        editAvailBtn.addEventListener('click', () => {
            availView.classList.add('hidden');
            availForm.classList.remove('hidden');
            editAvailBtn.classList.add('hidden');
        });
        
        cancelAvailBtn.addEventListener('click', () => {
            availView.classList.remove('hidden');
            availForm.classList.add('hidden');
            editAvailBtn.classList.remove('hidden');
        });
    }
    
    // Availability selection with drag
    const grid = document.getElementById('availabilityGrid');
    if (grid) {
        let isSelecting = false;
        let selectValue = true;
        
        grid.addEventListener('mousedown', function(e) {
            const cell = e.target.closest('.avail-cell');
            if (!cell) return;
            
            isSelecting = true;
            selectValue = cell.dataset.selected !== 'true';
            toggleCell(cell, selectValue);
            e.preventDefault();
        });
        
        grid.addEventListener('mouseover', function(e) {
            if (!isSelecting) return;
            const cell = e.target.closest('.avail-cell');
            if (cell) toggleCell(cell, selectValue);
        });
        
        document.addEventListener('mouseup', () => isSelecting = false);
        
        function toggleCell(cell, selected) {
            cell.dataset.selected = selected ? 'true' : 'false';
            if (selected) {
                cell.classList.add('bg-green-500');
                cell.classList.remove('bg-white');
            } else {
                cell.classList.remove('bg-green-500');
                cell.classList.add('bg-white');
            }
        }
        
        // Before submitting, collect data
        availForm.addEventListener('submit', function(e) {
            const cells = grid.querySelectorAll('.avail-cell[data-selected="true"]');
            const slots = {};
            
            cells.forEach(cell => {
                const day = parseInt(cell.dataset.day);
                const hour = parseInt(cell.dataset.hour);
                
                if (!slots[day]) slots[day] = [];
                slots[day].push(hour);
            });
            
            // Convert to ranges
            const availability = [];
            for (const day in slots) {
                const hours = slots[day].sort((a, b) => a - b);
                let start = hours[0];
                let prev = hours[0];
                
                for (let i = 1; i <= hours.length; i++) {
                    if (i < hours.length && hours[i] === prev + 1) {
                        prev = hours[i];
                    } else {
                        availability.push({
                            day: parseInt(day),
                            start: String(start).padStart(2, '0') + ':00',
                            end: String(prev + 1).padStart(2, '0') + ':00'
                        });
                        if (i < hours.length) {
                            start = hours[i];
                            prev = hours[i];
                        }
                    }
                }
            }
            
            document.getElementById('availabilityJson').value = JSON.stringify(availability);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
