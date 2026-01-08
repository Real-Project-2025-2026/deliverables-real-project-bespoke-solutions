<?php
/**
 * Explore Activities - Main activities list
 */
$pageTitle = 'Explore - Muniverse';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Event.php';
require_once __DIR__ . '/../../models/User.php';

session_start();
requireLogin();

$eventModel = new Event();
$userModel = new User();
$neighborhoods = $userModel->getAllNeighborhoods();

$userAvailability = $userModel->getAvailability($_SESSION['user_id']);
$userPreferences = $userModel->getPreferences($_SESSION['user_id']);
$userPrefs = array_map(fn($p) => is_array($p) ? $p['category'] : $p, $userPreferences);

$filterSearch = trim($_GET['search'] ?? '');
$filterCategories = $_GET['categories'] ?? [];
$filterMySchedule = ($_GET['my_schedule'] ?? '1') === '1';

// Default neighborhood to user's neighborhood if not specified in URL
$userNeighborhood = $userModel->getNeighborhood($_SESSION['user_id']);
$defaultNeighborhood = $userNeighborhood ? $userNeighborhood['id'] : 0;
$filterNeighborhood = isset($_GET['neighborhood']) ? (int)$_GET['neighborhood'] : $defaultNeighborhood;

$upcomingEvents = $eventModel->getUpcoming(100);

$weekOffset = (int)($_GET['week'] ?? 0);
$today = new DateTime();
$today->modify($weekOffset . ' weeks');
$startOfWeek = clone $today;
$startOfWeek->modify('monday this week');
$endOfWeek = clone $startOfWeek;
$endOfWeek->modify('+6 days');

$dayNamesShort = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

$availabilitySlots = [];
foreach ($userAvailability as $slot) {
    $day = $slot['day_of_week'];
    $startHour = (int)date('G', strtotime($slot['start_time']));
    $endHour = (int)date('G', strtotime($slot['end_time']));
    for ($h = $startHour; $h < $endHour; $h++) {
        $availabilitySlots[$day][$h] = true;
    }
}

function eventMatchesAvailability($event, $availabilitySlots) {
    $eventDate = new DateTime($event['event_date']);
    $dayOfWeek = (int)$eventDate->format('w');
    $eventHour = (int)date('G', strtotime($event['event_time']));
    return isset($availabilitySlots[$dayOfWeek][$eventHour]);
}

$filteredEvents = array_filter($upcomingEvents, function($event) use ($filterSearch, $filterCategories, $filterMySchedule, $availabilitySlots, $filterNeighborhood) {
    // Search filter
    if ($filterSearch) {
        $searchLower = strtolower($filterSearch);
        $titleMatch = stripos($event['title'] ?? '', $filterSearch) !== false;
        $descMatch = stripos($event['description'] ?? '', $filterSearch) !== false;
        $locationMatch = stripos($event['location'] ?? '', $filterSearch) !== false;
        if (!$titleMatch && !$descMatch && !$locationMatch) return false;
    }
    // Categories filter (multi-select)
    if (!empty($filterCategories) && !in_array($event['category'] ?? '', $filterCategories)) return false;
    // Neighborhood filter
    if ($filterNeighborhood > 0 && ($event['neighborhood_id'] ?? 0) != $filterNeighborhood) return false;
    // Schedule filter
    if ($filterMySchedule && !eventMatchesAvailability($event, $availabilitySlots)) return false;
    return true;
});

$categoryIcons = ['sports' => 'fa-running', 'culture' => 'fa-palette', 'food' => 'fa-utensils', 'games' => 'fa-gamepad', 'language' => 'fa-language'];
$categoryColors = ['sports' => 'bg-green-500', 'culture' => 'bg-purple-500', 'food' => 'bg-orange-500', 'games' => 'bg-blue-500', 'language' => 'bg-cyan-500'];
$categoryLabels = ['sports' => 'Sports', 'culture' => 'Culture', 'food' => 'Food', 'games' => 'Games', 'language' => 'Languages'];

$viewMode = $_GET['view'] ?? 'calendar';

// Group events by neighborhood for map view
$eventsByNeighborhood = [];
foreach ($filteredEvents as $event) {
    $nId = $event['neighborhood_id'] ?? 0;
    if (!isset($eventsByNeighborhood[$nId])) {
        $eventsByNeighborhood[$nId] = ['count' => 0, 'events' => [], 'name' => $event['neighborhood_name'] ?? 'Unknown'];
    }
    $eventsByNeighborhood[$nId]['count']++;
    $eventsByNeighborhood[$nId]['events'][] = $event;
}

$calendarEvents = [];
foreach ($filteredEvents as $event) {
    $eventDate = new DateTime($event['event_date']);
    if ($eventDate >= $startOfWeek && $eventDate <= $endOfWeek) {
        $dayKey = $eventDate->format('Y-m-d');
        $hour = (int)date('G', strtotime($event['event_time']));
        if (!isset($calendarEvents[$dayKey])) $calendarEvents[$dayKey] = [];
        if (!isset($calendarEvents[$dayKey][$hour])) $calendarEvents[$dayKey][$hour] = [];
        $calendarEvents[$dayKey][$hour][] = $event;
    }
}

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Explore Activities</h1>
            <p class="text-gray-500 text-sm mt-1">Discover activities and connect with people in Munich</p>
        </div>
        <a href="/views/events/create.php" class="btn-primary text-white px-5 py-2.5 rounded-xl font-medium inline-flex items-center gap-2 text-sm">
            <i class="fas fa-plus"></i><span>Create Activity</span>
        </a>
    </div>
    
    <!-- Simplified Filter -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <form method="GET" class="space-y-4" id="filterForm">
            <input type="hidden" name="week" value="<?= $weekOffset ?>">
            <input type="hidden" name="my_schedule" id="myScheduleHidden" value="<?= $filterMySchedule ? '1' : '0' ?>">
            
            <!-- Search + Schedule toggle row -->
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" value="<?= h($filterSearch) ?>" 
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="Search events...">
                </div>
                
                <label class="flex items-center gap-2 cursor-pointer <?= $filterMySchedule ? 'bg-green-100 border-green-400' : 'bg-gray-50 border-gray-200' ?> px-4 py-2.5 rounded-lg hover:bg-green-50 transition border shrink-0">
                    <input type="checkbox" id="myScheduleCheckbox" <?= $filterMySchedule ? 'checked' : '' ?> class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                    <span class="text-sm font-medium <?= $filterMySchedule ? 'text-green-700' : 'text-gray-600' ?>"><i class="fas fa-clock mr-1"></i>In my schedule</span>
                </label>
            </div>
            
            <!-- Category chips -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm text-gray-500 mr-1">Categories:</span>
                <?php foreach ($categoryLabels as $key => $label): 
                    $isSelected = in_array($key, $filterCategories);
                    $icon = $categoryIcons[$key];
                ?>
                <label class="cursor-pointer">
                    <input type="checkbox" name="categories[]" value="<?= $key ?>" <?= $isSelected ? 'checked' : '' ?> class="hidden peer">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium border transition
                        peer-checked:bg-purple-100 peer-checked:text-purple-700 peer-checked:border-purple-300
                        bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100">
                        <i class="fas <?= $icon ?> text-xs"></i><?= $label ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
            
            <!-- Neighborhood filter -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm text-gray-500 mr-1"><i class="fas fa-map-pin"></i> Neighborhood:</span>
                <select name="neighborhood" class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="0">All Munich</option>
                    <?php foreach ($neighborhoods as $n): ?>
                    <option value="<?= $n['id'] ?>" <?= $filterNeighborhood == $n['id'] ? 'selected' : '' ?>><?= h($n['short_name'] ?? $n['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Submit -->
            <div class="flex items-center gap-3">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-lg font-medium text-sm transition inline-flex items-center gap-2">
                    <i class="fas fa-search"></i>Search
                </button>
                <?php if ($filterSearch || !empty($filterCategories) || !$filterMySchedule || $filterNeighborhood): ?>
                <a href="/views/events/index.php?week=<?= $weekOffset ?>&my_schedule=1" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                    <i class="fas fa-times mr-1"></i>Clear filters
                </a>
                <?php endif; ?>
                <span class="text-sm text-gray-500 ml-auto"><span class="font-semibold text-gray-900"><?= count($filteredEvents) ?></span> activities found</span>
            </div>
        </form>
    </div>
    
    <!-- View Toggle + Week navigation -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <?php if ($viewMode === 'calendar'): ?>
            <a href="?week=<?= $weekOffset - 1 ?>&search=<?= urlencode($filterSearch) ?><?= !empty($filterCategories) ? '&' . http_build_query(['categories' => $filterCategories]) : '' ?>&my_schedule=<?= $filterMySchedule ? '1' : '0' ?>&neighborhood=<?= $filterNeighborhood ?>&view=calendar" class="p-2 hover:bg-gray-100 rounded-lg transition"><i class="fas fa-chevron-left text-gray-600"></i></a>
            <span class="font-semibold text-gray-900"><?= $startOfWeek->format('M d') ?> - <?= $endOfWeek->format('M d, Y') ?></span>
            <a href="?week=<?= $weekOffset + 1 ?>&search=<?= urlencode($filterSearch) ?><?= !empty($filterCategories) ? '&' . http_build_query(['categories' => $filterCategories]) : '' ?>&my_schedule=<?= $filterMySchedule ? '1' : '0' ?>&neighborhood=<?= $filterNeighborhood ?>&view=calendar" class="p-2 hover:bg-gray-100 rounded-lg transition"><i class="fas fa-chevron-right text-gray-600"></i></a>
            <?php if ($weekOffset != 0): ?>
            <a href="?week=0&search=<?= urlencode($filterSearch) ?><?= !empty($filterCategories) ? '&' . http_build_query(['categories' => $filterCategories]) : '' ?>&my_schedule=<?= $filterMySchedule ? '1' : '0' ?>&neighborhood=<?= $filterNeighborhood ?>&view=calendar" class="text-sm text-purple-600 hover:text-purple-700 font-medium ml-2">Today</a>
            <?php endif; ?>
            <?php else: ?>
            <span class="font-semibold text-gray-900"><i class="fas fa-map-marker-alt text-purple-600 mr-2"></i>Munich Neighborhoods</span>
            <?php endif; ?>
        </div>
        
        <!-- View Toggle -->
        <div class="flex items-center bg-gray-100 rounded-lg p-1">
            <a href="?week=<?= $weekOffset ?>&search=<?= urlencode($filterSearch) ?><?= !empty($filterCategories) ? '&' . http_build_query(['categories' => $filterCategories]) : '' ?>&my_schedule=<?= $filterMySchedule ? '1' : '0' ?>&neighborhood=<?= $filterNeighborhood ?>&view=calendar" 
               class="px-3 py-1.5 rounded-md text-sm font-medium transition <?= $viewMode === 'calendar' ? 'bg-white text-purple-600 shadow-sm' : 'text-gray-600 hover:text-gray-900' ?>">
                <i class="fas fa-calendar-alt mr-1.5"></i>Calendar
            </a>
            <a href="?week=<?= $weekOffset ?>&search=<?= urlencode($filterSearch) ?><?= !empty($filterCategories) ? '&' . http_build_query(['categories' => $filterCategories]) : '' ?>&my_schedule=<?= $filterMySchedule ? '1' : '0' ?>&neighborhood=<?= $filterNeighborhood ?>&view=map" 
               class="px-3 py-1.5 rounded-md text-sm font-medium transition <?= $viewMode === 'map' ? 'bg-white text-purple-600 shadow-sm' : 'text-gray-600 hover:text-gray-900' ?>">
                <i class="fas fa-map mr-1.5"></i>Map
            </a>
        </div>
    </div>
    
    <?php if ($viewMode === 'calendar'): ?>
    <!-- Calendar grid - Desktop -->
    <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <div class="min-w-[800px]">
                <div class="grid grid-cols-8 border-b border-gray-200">
                    <div class="p-3 text-center text-xs font-medium text-gray-500 bg-gray-50">Time</div>
                    <?php $currentDay = clone $startOfWeek; for ($d = 0; $d < 7; $d++): $isToday = $currentDay->format('Y-m-d') === (new DateTime())->format('Y-m-d'); ?>
                    <div class="p-3 text-center bg-gray-50 <?= $isToday ? 'bg-purple-50' : '' ?>">
                        <div class="text-xs font-medium <?= $isToday ? 'text-purple-600' : 'text-gray-500' ?>"><?= $dayNamesShort[$d] ?></div>
                        <div class="text-lg font-bold <?= $isToday ? 'text-purple-600' : 'text-gray-900' ?>"><?= $currentDay->format('d') ?></div>
                    </div>
                    <?php $currentDay->modify('+1 day'); endfor; ?>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php for ($hour = 8; $hour < 23; $hour++): ?>
                    <div class="grid grid-cols-8 min-h-[60px]">
                        <div class="p-2 text-center text-xs text-gray-500 bg-gray-50 border-r border-gray-100 flex items-center justify-center"><?= sprintf('%02d:00', $hour) ?></div>
                        <?php $currentDay = clone $startOfWeek; for ($d = 0; $d < 7; $d++): $dayKey = $currentDay->format('Y-m-d'); $dayOfWeek = (int)$currentDay->format('w'); $hasAvailability = isset($availabilitySlots[$dayOfWeek][$hour]); $eventsAtSlot = $calendarEvents[$dayKey][$hour] ?? []; ?>
                        <div class="p-1 border-r border-gray-50 <?= $hasAvailability ? 'bg-green-50' : '' ?>">
                            <?php foreach ($eventsAtSlot as $event): $cat = $event['category'] ?? 'sports'; $bgColor = $categoryColors[$cat] ?? 'bg-gray-500'; $icon = $categoryIcons[$cat] ?? 'fa-calendar'; $isParticipant = $eventModel->isParticipant($event['id'], $_SESSION['user_id']); ?>
                            <a href="/views/events/show.php?id=<?= $event['id'] ?>" class="block <?= $bgColor ?> text-white p-2 rounded-lg text-xs mb-1 hover:opacity-90 transition truncate <?= $isParticipant ? 'ring-2 ring-offset-1 ring-blue-400' : '' ?>">
                                <div class="flex items-center gap-1"><i class="fas <?= $icon ?> text-[10px]"></i><span class="font-medium truncate"><?= h($event['title']) ?></span></div>
                                <div class="text-[10px] opacity-80 mt-0.5"><i class="fas fa-users"></i> <?= $event['participants'] ?? 0 ?><?= $event['max_participants'] ? '/' . $event['max_participants'] : '' ?><?php if ($isParticipant): ?><span class="ml-1"><i class="fas fa-check-circle"></i></span><?php endif; ?></div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php $currentDay->modify('+1 day'); endfor; ?>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Calendar grid - Mobile -->
    <div class="md:hidden bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="grid grid-cols-8 border-b border-gray-200">
            <div class="p-1 text-center text-[9px] font-medium text-gray-500 bg-gray-50"></div>
            <?php 
            $dayLetters = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
            $currentDay = clone $startOfWeek; 
            for ($d = 0; $d < 7; $d++): 
                $isToday = $currentDay->format('Y-m-d') === (new DateTime())->format('Y-m-d'); 
            ?>
            <div class="p-1 text-center bg-gray-50 <?= $isToday ? 'bg-purple-50' : '' ?>">
                <div class="text-[9px] font-medium <?= $isToday ? 'text-purple-600' : 'text-gray-500' ?>"><?= $dayLetters[$d] ?></div>
                <div class="text-xs font-bold <?= $isToday ? 'text-purple-600' : 'text-gray-900' ?>"><?= $currentDay->format('d') ?></div>
            </div>
            <?php $currentDay->modify('+1 day'); endfor; ?>
        </div>
        <div class="divide-y divide-gray-100 max-h-[60vh] overflow-y-auto">
            <?php for ($hour = 8; $hour < 23; $hour++): ?>
            <div class="grid grid-cols-8 min-h-[36px]">
                <div class="p-0.5 text-center text-[9px] text-gray-500 bg-gray-50 border-r border-gray-100 flex items-center justify-center"><?= sprintf('%02d', $hour) ?></div>
                <?php $currentDay = clone $startOfWeek; for ($d = 0; $d < 7; $d++): $dayKey = $currentDay->format('Y-m-d'); $dayOfWeek = (int)$currentDay->format('w'); $hasAvailability = isset($availabilitySlots[$dayOfWeek][$hour]); $eventsAtSlot = $calendarEvents[$dayKey][$hour] ?? []; ?>
                <div class="p-0.5 border-r border-gray-50 <?= $hasAvailability ? 'bg-green-50' : '' ?> flex flex-col items-center justify-center gap-0.5">
                    <?php foreach ($eventsAtSlot as $event): $cat = $event['category'] ?? 'sports'; $bgColor = $categoryColors[$cat] ?? 'bg-gray-500'; $icon = $categoryIcons[$cat] ?? 'fa-calendar'; $isParticipant = $eventModel->isParticipant($event['id'], $_SESSION['user_id']); ?>
                    <a href="/views/events/show.php?id=<?= $event['id'] ?>" class="w-5 h-5 <?= $bgColor ?> text-white rounded-full flex items-center justify-center hover:opacity-90 transition <?= $isParticipant ? 'ring-1 ring-blue-400' : '' ?>">
                        <i class="fas <?= $icon ?> text-[8px]"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php $currentDay->modify('+1 day'); endfor; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    
    <!-- Legend -->
    <div class="mt-4 flex flex-wrap gap-4 text-xs text-gray-600">
        <div class="flex items-center gap-2"><span class="w-3 h-3 bg-green-50 border border-green-300 rounded"></span><span>Your availability</span></div>
        <div class="flex items-center gap-2"><span class="w-3 h-3 bg-blue-400 rounded ring-2 ring-offset-1 ring-blue-400"></span><span>Joined</span></div>
        <?php foreach ($categoryLabels as $key => $label): ?>
        <div class="flex items-center gap-2"><span class="w-3 h-3 <?= $categoryColors[$key] ?> rounded"></span><span><?= $label ?></span></div>
        <?php endforeach; ?>
    </div>
    
    <?php else: ?>
    <!-- Map View -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Map -->
            <div class="lg:col-span-2">
                <div class="relative bg-gradient-to-br from-purple-50 to-orange-50 rounded-xl p-4 min-h-[500px]">
                    <svg viewBox="0 0 400 450" class="w-full h-auto" id="munichMap">
                        <!-- Munich neighborhoods as clickable regions -->
                        <?php
                        // Simplified positions for Munich's 25 Stadtbezirke (arranged roughly geographically)
                        $neighborhoodPositions = [
                            1 => ['x' => 185, 'y' => 195, 'name' => 'Altstadt-Lehel'],
                            2 => ['x' => 145, 'y' => 195, 'name' => 'Ludwigsvorstadt-Isarvorstadt'],
                            3 => ['x' => 105, 'y' => 195, 'name' => 'Maxvorstadt'],
                            4 => ['x' => 65, 'y' => 195, 'name' => 'Schwabing-West'],
                            5 => ['x' => 225, 'y' => 155, 'name' => 'Au-Haidhausen'],
                            6 => ['x' => 105, 'y' => 235, 'name' => 'Sendling'],
                            7 => ['x' => 145, 'y' => 275, 'name' => 'Sendling-Westpark'],
                            8 => ['x' => 65, 'y' => 275, 'name' => 'Schwanthalerhöhe'],
                            9 => ['x' => 25, 'y' => 155, 'name' => 'Neuhausen-Nymphenburg'],
                            10 => ['x' => 25, 'y' => 115, 'name' => 'Moosach'],
                            11 => ['x' => 65, 'y' => 75, 'name' => 'Milbertshofen-Am Hart'],
                            12 => ['x' => 105, 'y' => 115, 'name' => 'Schwabing-Freimann'],
                            13 => ['x' => 265, 'y' => 115, 'name' => 'Bogenhausen'],
                            14 => ['x' => 305, 'y' => 195, 'name' => 'Berg am Laim'],
                            15 => ['x' => 345, 'y' => 235, 'name' => 'Trudering-Riem'],
                            16 => ['x' => 265, 'y' => 235, 'name' => 'Ramersdorf-Perlach'],
                            17 => ['x' => 185, 'y' => 275, 'name' => 'Obergiesing-Fasangarten'],
                            18 => ['x' => 225, 'y' => 315, 'name' => 'Untergiesing-Harlaching'],
                            19 => ['x' => 145, 'y' => 355, 'name' => 'Thalkirchen-Obersendling'],
                            20 => ['x' => 65, 'y' => 355, 'name' => 'Hadern'],
                            21 => ['x' => 25, 'y' => 315, 'name' => 'Pasing-Obermenzing'],
                            22 => ['x' => 25, 'y' => 235, 'name' => 'Aubing-Lochhausen'],
                            23 => ['x' => 25, 'y' => 75, 'name' => 'Allach-Untermenzing'],
                            24 => ['x' => 145, 'y' => 35, 'name' => 'Feldmoching-Hasenbergl'],
                            25 => ['x' => 225, 'y' => 75, 'name' => 'Laim'],
                        ];
                        
                        foreach ($neighborhoods as $n):
                            $pos = $neighborhoodPositions[$n['id']] ?? ['x' => 200, 'y' => 200, 'name' => $n['name']];
                            $count = $eventsByNeighborhood[$n['id']]['count'] ?? 0;
                            $radius = min(30, max(18, 18 + ($count * 2)));
                            $opacity = $count > 0 ? min(1, 0.4 + ($count * 0.1)) : 0.3;
                            $isUserNeighborhood = $userNeighborhood && $userNeighborhood['id'] == $n['id'];
                        ?>
                        <g class="neighborhood-marker cursor-pointer hover:scale-110 transition-transform" 
                           data-id="<?= $n['id'] ?>" 
                           data-name="<?= h($n['name']) ?>"
                           data-count="<?= $count ?>"
                           onclick="showNeighborhoodEvents(<?= $n['id'] ?>)">
                            <circle cx="<?= $pos['x'] ?>" cy="<?= $pos['y'] ?>" r="<?= $radius ?>" 
                                    fill="<?= $isUserNeighborhood ? '#f97316' : '#8b5cf6' ?>" 
                                    fill-opacity="<?= $opacity ?>"
                                    stroke="<?= $isUserNeighborhood ? '#ea580c' : '#7c3aed' ?>" 
                                    stroke-width="2"/>
                            <?php if ($count > 0): ?>
                            <text x="<?= $pos['x'] ?>" y="<?= $pos['y'] + 4 ?>" 
                                  text-anchor="middle" fill="white" font-size="11" font-weight="bold"><?= $count ?></text>
                            <?php endif; ?>
                        </g>
                        <text x="<?= $pos['x'] ?>" y="<?= $pos['y'] + $radius + 12 ?>" 
                              text-anchor="middle" fill="#374151" font-size="8" class="pointer-events-none">
                            <?= h($n['short_name'] ?? $n['name']) ?>
                        </text>
                        <?php endforeach; ?>
                    </svg>
                    
                    <!-- Legend -->
                    <div class="absolute bottom-4 left-4 bg-white/90 backdrop-blur-sm rounded-lg p-3 text-xs space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full bg-purple-500"></span>
                            <span class="text-gray-700">Neighborhood</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full bg-orange-500"></span>
                            <span class="text-gray-700">Your neighborhood</span>
                        </div>
                        <div class="text-gray-500 mt-1">Circle size = activity count</div>
                    </div>
                </div>
            </div>
            
            <!-- Activities List -->
            <div class="lg:col-span-1">
                <div id="neighborhoodEventsPanel" class="bg-gray-50 rounded-xl p-4 min-h-[500px]">
                    <h3 class="font-semibold text-gray-900 mb-4" id="panelTitle">
                        <i class="fas fa-hand-pointer text-purple-500 mr-2"></i>Click a neighborhood
                    </h3>
                    <p class="text-sm text-gray-500" id="panelHint">Select a neighborhood on the map to see available activities.</p>
                    
                    <div id="neighborhoodEventsList" class="space-y-3 hidden">
                        <!-- Events will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('myScheduleCheckbox').addEventListener('change', function() {
    document.getElementById('myScheduleHidden').value = this.checked ? '1' : '0';
});

// Map view: neighborhood events data
const eventsByNeighborhood = <?= json_encode($eventsByNeighborhood) ?>;
const categoryIcons = <?= json_encode($categoryIcons) ?>;
const categoryColors = {
    'sports': 'bg-green-500',
    'culture': 'bg-purple-500', 
    'food': 'bg-orange-500',
    'games': 'bg-blue-500',
    'language': 'bg-cyan-500'
};

function showNeighborhoodEvents(neighborhoodId) {
    const panel = document.getElementById('neighborhoodEventsPanel');
    const title = document.getElementById('panelTitle');
    const hint = document.getElementById('panelHint');
    const list = document.getElementById('neighborhoodEventsList');
    
    const data = eventsByNeighborhood[neighborhoodId];
    
    if (!data || data.count === 0) {
        title.innerHTML = '<i class="fas fa-map-pin text-purple-500 mr-2"></i>' + (data?.name || 'Unknown');
        hint.textContent = 'No activities found in this neighborhood.';
        hint.classList.remove('hidden');
        list.classList.add('hidden');
        return;
    }
    
    title.innerHTML = '<i class="fas fa-map-pin text-purple-500 mr-2"></i>' + data.name + ' <span class="text-sm font-normal text-gray-500">(' + data.count + ' activities)</span>';
    hint.classList.add('hidden');
    list.classList.remove('hidden');
    
    let html = '';
    data.events.slice(0, 10).forEach(event => {
        const cat = event.category || 'sports';
        const icon = categoryIcons[cat] || 'fa-calendar';
        const bgColor = categoryColors[cat] || 'bg-gray-500';
        const date = new Date(event.event_date).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
        const time = event.event_time ? event.event_time.substring(0, 5) : '';
        
        html += `
            <a href="/views/events/show.php?id=${event.id}" class="block bg-white rounded-lg p-3 shadow-sm border border-gray-100 hover:border-purple-200 transition">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 ${bgColor} text-white rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas ${icon} text-xs"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-900 text-sm truncate">${event.title}</h4>
                        <p class="text-xs text-gray-500 mt-0.5">
                            <i class="fas fa-calendar-alt mr-1"></i>${date} ${time}
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            <i class="fas fa-users mr-1"></i>${event.participants || 0}${event.max_participants ? '/' + event.max_participants : ''} participants
                        </p>
                    </div>
                </div>
            </a>
        `;
    });
    
    if (data.count > 10) {
        html += `<p class="text-xs text-gray-500 text-center pt-2">+ ${data.count - 10} more activities</p>`;
    }
    
    list.innerHTML = html;
    
    // Highlight selected neighborhood
    document.querySelectorAll('.neighborhood-marker circle').forEach(c => c.classList.remove('ring-4'));
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
