<?php
/**
 * Calendar - Main view with suggestions
 */
$pageTitle = 'My Week - Muniverse';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Event.php';
require_once __DIR__ . '/../../models/User.php';

session_start();
requireLogin();

$eventModel = new Event();
$userModel = new User();

// Get user preferences
$userPreferences = $userModel->getPreferences($_SESSION['user_id']);
$preferredCategories = array_column($userPreferences, 'category');

// Get user availability
$userAvailability = $userModel->getAvailability($_SESSION['user_id']);
$availabilityByDay = [];
foreach ($userAvailability as $slot) {
    $day = $slot['day_of_week'];
    if (!isset($availabilityByDay[$day])) {
        $availabilityByDay[$day] = [];
    }
    $availabilityByDay[$day][] = [
        'start' => (int)substr($slot['start_time'], 0, 2),
        'end' => (int)substr($slot['end_time'], 0, 2)
    ];
}

// Get week offset and view mode from URL
$weekOffset = (int)($_GET['week'] ?? 0);
$viewMode = $_GET['view'] ?? 'calendar'; // calendar or list

// Calculate week dates
$today = new DateTime();
$today->modify($weekOffset . ' weeks');

// Get Monday of the week
$monday = clone $today;
$monday->modify('monday this week');

// Get Sunday of the week
$sunday = clone $monday;
$sunday->modify('+6 days');

// Get events JOINED by user
$joinedEvents = $eventModel->getJoinedEvents($_SESSION['user_id']);
$joinedEventIds = array_column($joinedEvents, 'id');

// Get events CREATED by user (so business users see their own events)
$createdEvents = $eventModel->getByUser($_SESSION['user_id']);

// Merge both lists (avoid duplicates)
$allUserEvents = $joinedEvents;
foreach ($createdEvents as $created) {
    if (!in_array($created['id'], $joinedEventIds)) {
        $allUserEvents[] = $created;
    }
}

// Organize all user events by day and hour
$eventsByDayHour = [];
foreach ($allUserEvents as $event) {
    $eventDate = $event['event_date'];
    // Only show if in current week range
    if ($eventDate >= $monday->format('Y-m-d') && $eventDate <= $sunday->format('Y-m-d')) {
        $hour = (int)substr($event['event_time'], 0, 2);
        if (!isset($eventsByDayHour[$eventDate])) {
            $eventsByDayHour[$eventDate] = [];
        }
        if (!isset($eventsByDayHour[$eventDate][$hour])) {
            $eventsByDayHour[$eventDate][$hour] = [];
        }
        $eventsByDayHour[$eventDate][$hour][] = $event;
    }
}

// Get suggestions based on preferences
$suggestedEvents = [];
if (!empty($preferredCategories)) {
    $allUpcoming = $eventModel->getUpcoming(50);
    foreach ($allUpcoming as $event) {
        $cat = $event['category'] ?? '';
        if (in_array($cat, $preferredCategories) && !in_array($event['id'], $joinedEventIds) && $event['user_id'] !== $_SESSION['user_id']) {
            $suggestedEvents[] = $event;
            if (count($suggestedEvents) >= 6) break;
        }
    }
}

// Sponsored spaces
$sponsoredSpaces = $eventModel->getSponsoredSpaces();

// Categories with colors and icons
$categoryConfig = [
    'culture' => ['icon' => 'fa-theater-masks', 'color' => 'bg-purple-500', 'light' => 'bg-purple-100 text-purple-700', 'label' => 'Culture'],
    'food' => ['icon' => 'fa-utensils', 'color' => 'bg-orange-500', 'light' => 'bg-orange-100 text-orange-700', 'label' => 'Food'],
    'games' => ['icon' => 'fa-gamepad', 'color' => 'bg-pink-500', 'light' => 'bg-pink-100 text-pink-700', 'label' => 'Games'],
    'language' => ['icon' => 'fa-language', 'color' => 'bg-blue-500', 'light' => 'bg-blue-100 text-blue-700', 'label' => 'Languages'],
    'sports' => ['icon' => 'fa-running', 'color' => 'bg-green-500', 'light' => 'bg-green-100 text-green-700', 'label' => 'Sports'],
];

// Day names in English
$dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$dayNamesMap = [1 => 0, 2 => 1, 3 => 2, 4 => 3, 5 => 4, 6 => 5, 0 => 6];
$monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

$hours = range(8, 22);

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Week Navigation -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">My Week</h1>
            <p class="text-gray-500 text-sm">
                <?= $monday->format('d') ?> - <?= $sunday->format('d') ?> <?= $monthNames[(int)$monday->format('n') - 1] ?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <!-- View Toggle -->
            <div class="flex items-center bg-gray-100 rounded-lg p-1">
                <a href="?week=<?= $weekOffset ?>&view=calendar" 
                   class="px-3 py-1.5 rounded-md text-sm font-medium transition <?= $viewMode === 'calendar' ? 'bg-white shadow text-purple-600' : 'text-gray-600 hover:text-gray-900' ?>">
                    <i class="fas fa-calendar-alt mr-1"></i>Calendar
                </a>
                <a href="?week=<?= $weekOffset ?>&view=list" 
                   class="px-3 py-1.5 rounded-md text-sm font-medium transition <?= $viewMode === 'list' ? 'bg-white shadow text-purple-600' : 'text-gray-600 hover:text-gray-900' ?>">
                    <i class="fas fa-list mr-1"></i>Agenda
                </a>
            </div>
            
            <!-- Week Navigation -->
            <div class="flex items-center gap-1">
                <a href="?week=<?= $weekOffset - 1 ?>&view=<?= $viewMode ?>" class="p-2 hover:bg-gray-100 rounded-lg transition">
                    <i class="fas fa-chevron-left text-gray-600"></i>
                </a>
                <?php if ($weekOffset !== 0): ?>
                <a href="?week=0&view=<?= $viewMode ?>" class="px-3 py-1 text-sm text-purple-600 hover:bg-purple-50 rounded-lg">Today</a>
                <?php endif; ?>
                <a href="?week=<?= $weekOffset + 1 ?>&view=<?= $viewMode ?>" class="p-2 hover:bg-gray-100 rounded-lg transition">
                    <i class="fas fa-chevron-right text-gray-600"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Legend -->
    <div class="flex flex-wrap gap-4 text-xs mb-4">
        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-green-200 border border-green-400"></span> Available (click to search)</span>
        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-purple-500"></span> Your activities</span>
    </div>
    
    <?php if ($viewMode === 'calendar'): ?>
    <!-- Weekly Calendar Grid - Responsive -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <!-- Desktop Calendar -->
        <div class="hidden md:block">
            <div class="overflow-x-auto">
                <div class="min-w-[700px]">
                    <!-- Header row with days -->
                    <div class="grid grid-cols-8 border-b border-gray-100">
                        <div class="p-2 text-xs text-gray-400 text-right border-r border-gray-100 w-14">Time</div>
                        <?php 
                        $currentDay = clone $monday;
                        $todayStr = (new DateTime())->format('Y-m-d');
                        
                        for ($i = 0; $i < 7; $i++): 
                            $dayStr = $currentDay->format('Y-m-d');
                            $isToday = $dayStr === $todayStr;
                        ?>
                        <div class="p-2 text-center <?= $isToday ? 'bg-purple-50' : 'bg-gray-50' ?> <?= $i < 6 ? 'border-r border-gray-100' : '' ?>">
                            <p class="text-xs text-gray-500 uppercase"><?= $dayNames[$i] ?></p>
                            <p class="text-lg font-semibold <?= $isToday ? 'text-purple-600' : 'text-gray-900' ?>">
                                <?= $currentDay->format('d') ?>
                            </p>
                        </div>
                        <?php 
                            $currentDay->modify('+1 day');
                        endfor; 
                        ?>
                    </div>
                    
                    <!-- Time slots -->
                    <?php foreach ($hours as $hour): ?>
                    <div class="grid grid-cols-8 border-b border-gray-50">
                        <div class="p-1 text-xs text-gray-400 text-right border-r border-gray-100 bg-gray-50 w-14">
                            <?= sprintf('%02d:00', $hour) ?>
                        </div>
                        <?php 
                        $currentDay = clone $monday;
                        for ($i = 0; $i < 7; $i++): 
                            $dayStr = $currentDay->format('Y-m-d');
                            $isToday = $dayStr === $todayStr;
                            $phpWeekday = (int)$currentDay->format('w');
                            
                            // Check if this hour is in user's availability
                            $isAvailable = false;
                            if (isset($availabilityByDay[$phpWeekday])) {
                                foreach ($availabilityByDay[$phpWeekday] as $slot) {
                                    if ($hour >= $slot['start'] && $hour < $slot['end']) {
                                        $isAvailable = true;
                                        break;
                                    }
                                }
                            }
                            
                            // Get events for this slot (only joined)
                            $slotEvents = $eventsByDayHour[$dayStr][$hour] ?? [];
                        ?>
                        <div class="min-h-[40px] p-0.5 <?= $isToday ? 'bg-purple-50/30' : '' ?> <?= $isAvailable && empty($slotEvents) ? 'bg-green-50 cursor-pointer hover:bg-green-100 transition-colors' : '' ?> <?= $i < 6 ? 'border-r border-gray-50' : '' ?> relative availability-slot"
                             <?php if ($isAvailable && empty($slotEvents)): ?>
                             data-date="<?= $dayStr ?>"
                             data-hour="<?= $hour ?>"
                             onclick="openActivityModal('<?= $dayStr ?>', <?= $hour ?>)"
                             <?php endif; ?>>
                            <?php if ($isAvailable && empty($slotEvents)): ?>
                            <div class="absolute inset-0.5 border border-dashed border-green-300 rounded flex items-center justify-center group">
                                <i class="fas fa-plus text-green-400 opacity-0 group-hover:opacity-100 transition-opacity text-xs"></i>
                            </div>
                            <?php endif; ?>
                            
                            <?php foreach ($slotEvents as $event): ?>
                            <?php 
                                $cat = $event['category'] ?? 'culture';
                                $config = $categoryConfig[$cat] ?? $categoryConfig['culture'];
                            ?>
                            <a href="/views/events/show.php?id=<?= $event['id'] ?>" 
                               class="block text-xs bg-purple-600 text-white px-1 py-0.5 rounded mb-0.5 hover:opacity-90 transition truncate"
                               title="<?= h($event['title']) ?>">
                                <i class="fas <?= $config['icon'] ?> text-[10px]"></i>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php 
                            $currentDay->modify('+1 day');
                        endfor; 
                        ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Mobile Calendar - Compact -->
        <div class="md:hidden">
            <!-- Header row with days -->
            <div class="grid grid-cols-8 border-b border-gray-100">
                <div class="p-1 text-[10px] text-gray-400 text-center border-r border-gray-100"></div>
                <?php 
                $currentDay = clone $monday;
                $todayStr = (new DateTime())->format('Y-m-d');
                
                for ($i = 0; $i < 7; $i++): 
                    $dayStr = $currentDay->format('Y-m-d');
                    $isToday = $dayStr === $todayStr;
                ?>
                <div class="p-1 text-center <?= $isToday ? 'bg-purple-50' : 'bg-gray-50' ?> <?= $i < 6 ? 'border-r border-gray-100' : '' ?>">
                    <p class="text-[10px] text-gray-500 uppercase"><?= substr($dayNames[$i], 0, 1) ?></p>
                    <p class="text-sm font-semibold <?= $isToday ? 'text-purple-600' : 'text-gray-900' ?>">
                        <?= $currentDay->format('d') ?>
                    </p>
                </div>
                <?php 
                    $currentDay->modify('+1 day');
                endfor; 
                ?>
            </div>
            
            <!-- Time slots - Compact -->
            <?php foreach ($hours as $hour): ?>
            <div class="grid grid-cols-8 border-b border-gray-50">
                <div class="p-0.5 text-[9px] text-gray-400 text-center border-r border-gray-100 bg-gray-50">
                    <?= sprintf('%02d', $hour) ?>
                </div>
                <?php 
                $currentDay = clone $monday;
                for ($i = 0; $i < 7; $i++): 
                    $dayStr = $currentDay->format('Y-m-d');
                    $isToday = $dayStr === $todayStr;
                    $phpWeekday = (int)$currentDay->format('w');
                    
                    // Check if this hour is in user's availability
                    $isAvailable = false;
                    if (isset($availabilityByDay[$phpWeekday])) {
                        foreach ($availabilityByDay[$phpWeekday] as $slot) {
                            if ($hour >= $slot['start'] && $hour < $slot['end']) {
                                $isAvailable = true;
                                break;
                            }
                        }
                    }
                    
                    // Get events for this slot (only joined)
                    $slotEvents = $eventsByDayHour[$dayStr][$hour] ?? [];
                ?>
                <div class="min-h-[28px] p-0 <?= $isToday ? 'bg-purple-50/30' : '' ?> <?= $isAvailable && empty($slotEvents) ? 'bg-green-50 cursor-pointer' : '' ?> <?= $i < 6 ? 'border-r border-gray-50' : '' ?> relative"
                     <?php if ($isAvailable && empty($slotEvents)): ?>
                     data-date="<?= $dayStr ?>"
                     data-hour="<?= $hour ?>"
                     onclick="openActivityModal('<?= $dayStr ?>', <?= $hour ?>)"
                     <?php endif; ?>>
                    <?php if (!empty($slotEvents)): ?>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <?php 
                            $cat = $slotEvents[0]['category'] ?? 'culture';
                            $config = $categoryConfig[$cat] ?? $categoryConfig['culture'];
                        ?>
                        <a href="/views/events/show.php?id=<?= $slotEvents[0]['id'] ?>" 
                           class="w-5 h-5 bg-purple-600 rounded-full flex items-center justify-center"
                           title="<?= h($slotEvents[0]['title']) ?>">
                            <i class="fas <?= $config['icon'] ?> text-white text-[8px]"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php 
                    $currentDay->modify('+1 day');
                endfor; 
                ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php else: ?>
    <!-- List/Agenda View -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <?php 
        // Organize events by date for list view (using merged events)
        $eventsByDate = [];
        foreach ($allUserEvents as $event) {
            $eventDate = $event['event_date'];
            if ($eventDate >= $monday->format('Y-m-d') && $eventDate <= $sunday->format('Y-m-d')) {
                if (!isset($eventsByDate[$eventDate])) {
                    $eventsByDate[$eventDate] = [];
                }
                $eventsByDate[$eventDate][] = $event;
            }
        }
        ksort($eventsByDate);
        
        $hasEvents = !empty($eventsByDate);
        ?>
        
        <?php if ($hasEvents): ?>
        <div class="divide-y divide-gray-100">
            <?php 
            $currentDay = clone $monday;
            for ($i = 0; $i < 7; $i++): 
                $dayStr = $currentDay->format('Y-m-d');
                $isToday = $dayStr === (new DateTime())->format('Y-m-d');
                $dayEvents = $eventsByDate[$dayStr] ?? [];
                usort($dayEvents, fn($a, $b) => strcmp($a['event_time'], $b['event_time']));
                
                // Check availability for this day
                $phpWeekday = (int)$currentDay->format('w');
                $dayAvailability = $availabilityByDay[$phpWeekday] ?? [];
            ?>
            <div class="<?= $isToday ? 'bg-purple-50/30' : '' ?>">
                <!-- Day Header -->
                <div class="px-6 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center <?= $isToday ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700' ?>">
                            <span class="font-bold"><?= $currentDay->format('d') ?></span>
                        </div>
                        <div>
                            <p class="font-medium <?= $isToday ? 'text-purple-600' : 'text-gray-900' ?>">
                                <?= $dayNames[$i] ?>
                                <?php if ($isToday): ?><span class="text-xs ml-1">(Today)</span><?php endif; ?>
                            </p>
                            <p class="text-xs text-gray-500"><?= $currentDay->format('d') ?> <?= $monthNames[(int)$currentDay->format('n') - 1] ?></p>
                        </div>
                    </div>
                    <?php if (!empty($dayAvailability)): ?>
                    <div class="flex items-center gap-2 text-xs text-green-600">
                        <i class="fas fa-clock"></i>
                        <span>Available: 
                            <?php 
                            $slots = array_map(fn($s) => sprintf('%02d:00-%02d:00', $s['start'], $s['end']), $dayAvailability);
                            echo implode(', ', $slots);
                            ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Day Events -->
                <div class="px-6 py-3">
                    <?php if (!empty($dayEvents)): ?>
                    <div class="space-y-2">
                        <?php foreach ($dayEvents as $event): ?>
                        <?php 
                            $cat = $event['category'] ?? 'culture';
                            $config = $categoryConfig[$cat] ?? $categoryConfig['culture'];
                        ?>
                        <a href="/views/events/show.php?id=<?= $event['id'] ?>" 
                           class="flex items-center gap-4 p-3 rounded-lg hover:bg-gray-50 transition group">
                            <div class="flex-shrink-0 w-16 text-center">
                                <span class="text-lg font-semibold text-gray-900"><?= date('H:i', strtotime($event['event_time'])) ?></span>
                            </div>
                            <div class="w-1 h-10 rounded-full <?= $config['color'] ?>"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="<?= $config['light'] ?> px-2 py-0.5 rounded text-xs font-medium">
                                        <i class="fas <?= $config['icon'] ?> mr-1"></i><?= $config['label'] ?>
                                    </span>
                                    <?php if ($event['is_sponsored'] ?? false): ?>
                                    <span class="text-orange-500 text-xs"><i class="fas fa-star"></i></span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="font-medium text-gray-900 group-hover:text-purple-600 transition truncate"><?= h($event['title']) ?></h3>
                                <?php if ($event['location']): ?>
                                <p class="text-xs text-gray-500 truncate">
                                    <i class="fas fa-map-marker-alt mr-1"></i><?= h($event['location']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <span class="text-xs text-gray-500">
                                    <i class="fas fa-users mr-1"></i>
                                    <?= $event['participants'] ?? 0 ?><?= $event['max_participants'] ? '/' . $event['max_participants'] : '' ?>
                                </span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-sm text-gray-400 py-2">
                        <i class="fas fa-calendar-times mr-2"></i>No activities scheduled
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php 
                $currentDay->modify('+1 day');
            endfor; 
            ?>
        </div>
        <?php else: ?>
        <div class="p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-calendar-alt text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No activities this week</h3>
            <p class="text-sm text-gray-500 mb-4">Explore events and join the ones you like</p>
            <a href="/views/events/index.php" class="btn-primary text-white px-4 py-2 rounded-lg text-sm inline-flex items-center gap-2">
                <i class="fas fa-compass"></i>Explore events
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Featured Events Section (for normal users) -->
    <?php if (!empty($sponsoredSpaces) && ($_SESSION['user_role'] ?? 'normal') === 'normal'): ?>
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 bg-gradient-to-br from-purple-600 to-orange-500 rounded-lg flex items-center justify-center">
                <i class="fas fa-star text-white text-sm"></i>
            </div>
            <h2 class="text-lg font-semibold text-gray-900">Featured Events</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach (array_slice($sponsoredSpaces, 0, 3) as $space): ?>
            <?php 
                $cat = $space['category'] ?? 'culture';
                $config = $categoryConfig[$cat] ?? $categoryConfig['culture'];
            ?>
            <a href="/views/events/show.php?id=<?= $space['id'] ?>" class="block bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-purple-200 transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="<?= $config['light'] ?> px-2 py-0.5 rounded text-xs font-medium">
                        <i class="fas <?= $config['icon'] ?> mr-1"></i><?= $config['label'] ?>
                    </span>
                    <span class="text-orange-500 text-xs"><i class="fas fa-star"></i> Featured</span>
                </div>
                <h3 class="font-medium text-gray-900 mb-1 truncate"><?= h($space['title']) ?></h3>
                <p class="text-xs text-gray-500 mb-2">
                    <?= date('M d', strtotime($space['event_date'])) ?> · <?= date('H:i', strtotime($space['event_time'])) ?>h
                </p>
                <?php if ($space['location']): ?>
                <p class="text-xs text-gray-400 truncate">
                    <i class="fas fa-map-marker-alt mr-1"></i><?= h($space['location']) ?>
                </p>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Suggestions Section -->
    <?php if (!empty($suggestedEvents)): ?>
    <div>
        <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-lightbulb text-purple-600 text-sm"></i>
            </div>
            <h2 class="text-lg font-semibold text-gray-900">Suggestions for you</h2>
            <span class="text-xs text-gray-500">Based on your interests</span>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($suggestedEvents as $event): ?>
            <?php 
                $cat = $event['category'] ?? 'culture';
                $config = $categoryConfig[$cat] ?? $categoryConfig['culture'];
            ?>
            <a href="/views/events/show.php?id=<?= $event['id'] ?>" 
               class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-purple-200 transition block">
                <div class="flex items-center gap-2 mb-2">
                    <span class="<?= $config['light'] ?> px-2 py-0.5 rounded text-xs font-medium">
                        <i class="fas <?= $config['icon'] ?> mr-1"></i><?= $config['label'] ?>
                    </span>
                    <?php if ($event['is_sponsored'] ?? false): ?>
                    <span class="text-orange-500 text-xs"><i class="fas fa-star"></i></span>
                    <?php endif; ?>
                </div>
                <h3 class="font-medium text-gray-900 mb-1"><?= h($event['title']) ?></h3>
                <p class="text-sm text-gray-500">
                    <i class="fas fa-calendar mr-1"></i><?= date('M d', strtotime($event['event_date'])) ?>
                    <i class="fas fa-clock ml-2 mr-1"></i><?= date('H:i', strtotime($event['event_time'])) ?>h
                </p>
                <?php if ($event['location']): ?>
                <p class="text-xs text-gray-400 mt-1 truncate">
                    <i class="fas fa-map-marker-alt mr-1"></i><?= h($event['location']) ?>
                </p>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif (empty($preferredCategories)): ?>
    <div class="bg-purple-50 rounded-xl p-6 text-center">
        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-cog text-purple-600"></i>
        </div>
        <p class="text-gray-700 mb-2">Set up your preferences to see personalized suggestions</p>
        <a href="/views/auth/profile.php" class="text-purple-600 hover:text-purple-700 text-sm font-medium">
            Go to my profile <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Activity Selection Modal -->
<div id="activityModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full max-h-[80vh] overflow-hidden shadow-2xl">
        <!-- Modal Header -->
        <div class="p-6 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">What are you in the mood for?</h3>
                    <p class="text-sm text-gray-500 mt-1" id="modalTimeLabel"></p>
                </div>
                <button onclick="closeActivityModal()" class="text-gray-400 hover:text-gray-600 p-2">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Category Selection -->
        <div class="p-6 border-b border-gray-100">
            <div class="grid grid-cols-5 gap-3" id="categoryButtons">
                <button onclick="selectCategory('culture')" data-category="culture" 
                        class="category-btn flex flex-col items-center p-4 rounded-xl border-2 border-gray-200 hover:border-purple-400 hover:bg-purple-50 transition">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-theater-masks text-purple-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium text-gray-700">Culture</span>
                </button>
                <button onclick="selectCategory('food')" data-category="food"
                        class="category-btn flex flex-col items-center p-4 rounded-xl border-2 border-gray-200 hover:border-orange-400 hover:bg-orange-50 transition">
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-utensils text-orange-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium text-gray-700">Food</span>
                </button>
                <button onclick="selectCategory('games')" data-category="games"
                        class="category-btn flex flex-col items-center p-4 rounded-xl border-2 border-gray-200 hover:border-pink-400 hover:bg-pink-50 transition">
                    <div class="w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-gamepad text-pink-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium text-gray-700">Games</span>
                </button>
                <button onclick="selectCategory('language')" data-category="language"
                        class="category-btn flex flex-col items-center p-4 rounded-xl border-2 border-gray-200 hover:border-blue-400 hover:bg-blue-50 transition">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-language text-blue-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium text-gray-700">Languages</span>
                </button>
                <button onclick="selectCategory('sports')" data-category="sports"
                        class="category-btn flex flex-col items-center p-4 rounded-xl border-2 border-gray-200 hover:border-green-400 hover:bg-green-50 transition">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-running text-green-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium text-gray-700">Sports</span>
                </button>
            </div>
        </div>
        
        <!-- Events List -->
        <div class="overflow-y-auto max-h-[300px]" id="eventsContainer">
            <div class="p-6 text-center text-gray-500" id="eventsPlaceholder">
                <i class="fas fa-hand-pointer text-3xl text-gray-300 mb-3"></i>
                <p>Select a category to see available activities</p>
            </div>
            <div class="hidden" id="eventsList"></div>
            <div class="hidden p-6 text-center" id="eventsLoading">
                <i class="fas fa-spinner fa-spin text-2xl text-purple-500 mb-2"></i>
                <p class="text-gray-500">Searching activities...</p>
            </div>
            <div class="hidden p-6 text-center" id="eventsEmpty">
                <i class="fas fa-calendar-times text-3xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">No activities at this time</p>
                <a id="createEventLink" href="/views/events/create.php" class="inline-block mt-3 text-purple-600 hover:text-purple-700 text-sm font-medium">
                    <i class="fas fa-plus mr-1"></i>Create an activity
                </a>
            </div>
        </div>
    </div>
</div>

<script>
let currentDate = '';
let currentHour = 0;
let currentCategory = '';

const categoryConfig = {
    culture: { icon: 'fa-theater-masks', color: 'purple', label: 'Culture' },
    food: { icon: 'fa-utensils', color: 'orange', label: 'Food' },
    games: { icon: 'fa-gamepad', color: 'pink', label: 'Games' },
    language: { icon: 'fa-language', color: 'blue', label: 'Languages' },
    sports: { icon: 'fa-running', color: 'green', label: 'Sports' }
};

function openActivityModal(date, hour) {
    currentDate = date;
    currentHour = hour;
    currentCategory = '';
    
    // Format date for display
    const dateObj = new Date(date);
    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const timeLabel = `${dayNames[dateObj.getDay()]} ${dateObj.getDate()} ${monthNames[dateObj.getMonth()]} · ${String(hour).padStart(2, '0')}:00h`;
    
    document.getElementById('modalTimeLabel').textContent = timeLabel;
    
    // Reset state
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.classList.remove('border-purple-500', 'border-orange-500', 'border-pink-500', 'border-blue-500', 'border-green-500', 'bg-purple-50', 'bg-orange-50', 'bg-pink-50', 'bg-blue-50', 'bg-green-50');
        btn.classList.add('border-gray-200');
    });
    
    document.getElementById('eventsPlaceholder').classList.remove('hidden');
    document.getElementById('eventsList').classList.add('hidden');
    document.getElementById('eventsLoading').classList.add('hidden');
    document.getElementById('eventsEmpty').classList.add('hidden');
    
    // Show modal
    const modal = document.getElementById('activityModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeActivityModal() {
    const modal = document.getElementById('activityModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function selectCategory(category) {
    currentCategory = category;
    const config = categoryConfig[category];
    
    // Update button styles
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.classList.remove('border-purple-500', 'border-orange-500', 'border-pink-500', 'border-blue-500', 'border-green-500', 'bg-purple-50', 'bg-orange-50', 'bg-pink-50', 'bg-blue-50', 'bg-green-50');
        btn.classList.add('border-gray-200');
    });
    
    const activeBtn = document.querySelector(`[data-category="${category}"]`);
    activeBtn.classList.remove('border-gray-200');
    activeBtn.classList.add(`border-${config.color}-500`, `bg-${config.color}-50`);
    
    // Load events
    loadEvents(category);
}

async function loadEvents(category) {
    document.getElementById('eventsPlaceholder').classList.add('hidden');
    document.getElementById('eventsList').classList.add('hidden');
    document.getElementById('eventsEmpty').classList.add('hidden');
    document.getElementById('eventsLoading').classList.remove('hidden');
    
    try {
        const response = await fetch(`/controllers/CalendarController.php?action=getEventsBySlot&date=${currentDate}&hour=${currentHour}&category=${category}`);
        const data = await response.json();
        
        document.getElementById('eventsLoading').classList.add('hidden');
        
        if (data.events && data.events.length > 0) {
            renderEvents(data.events);
        } else {
            document.getElementById('eventsEmpty').classList.remove('hidden');
            // Update create link with date/time
            document.getElementById('createEventLink').href = `/views/events/create.php?date=${currentDate}&time=${String(currentHour).padStart(2, '0')}:00&category=${currentCategory}`;
        }
    } catch (error) {
        console.error('Error loading events:', error);
        document.getElementById('eventsLoading').classList.add('hidden');
        document.getElementById('eventsEmpty').classList.remove('hidden');
        // Update create link with date/time
        document.getElementById('createEventLink').href = `/views/events/create.php?date=${currentDate}&time=${String(currentHour).padStart(2, '0')}:00`;
    }
}

function renderEvents(events) {
    const container = document.getElementById('eventsList');
    const config = categoryConfig[currentCategory];
    
    container.innerHTML = events.map(event => `
        <div class="p-4 border-b border-gray-100 hover:bg-gray-50 transition">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="bg-${config.color}-100 text-${config.color}-700 px-2 py-0.5 rounded text-xs font-medium">
                            <i class="fas ${config.icon} mr-1"></i>${config.label}
                        </span>
                        ${event.is_sponsored ? '<span class="text-orange-500 text-xs"><i class="fas fa-star"></i></span>' : ''}
                    </div>
                    <h4 class="font-medium text-gray-900">${escapeHtml(event.title)}</h4>
                    <p class="text-sm text-gray-500 mt-1">
                        <i class="fas fa-clock mr-1"></i>${event.event_time.substring(0, 5)}h
                        ${event.location ? `<span class="ml-2"><i class="fas fa-map-marker-alt mr-1"></i>${escapeHtml(event.location)}</span>` : ''}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">By ${escapeHtml(event.creator_name)}</p>
                </div>
                <button onclick="joinEvent(${event.id}, this)" 
                        class="px-4 py-2 bg-gradient-to-r from-purple-600 to-orange-500 text-white rounded-lg text-sm font-medium hover:opacity-90 transition whitespace-nowrap">
                    <i class="fas fa-plus mr-1"></i>Join
                </button>
            </div>
        </div>
    `).join('');
    
    container.classList.remove('hidden');
}

async function joinEvent(eventId, button) {
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    try {
        const formData = new FormData();
        formData.append('action', 'joinEvent');
        formData.append('event_id', eventId);
        
        const response = await fetch('/controllers/CalendarController.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            button.innerHTML = '<i class="fas fa-check mr-1"></i>Joined';
            button.classList.remove('from-purple-600', 'to-orange-500');
            button.classList.add('bg-green-500');
            
            // Reload page after short delay to show updated calendar
            setTimeout(() => {
                window.location.reload();
            }, 800);
        } else {
            button.innerHTML = '<i class="fas fa-times mr-1"></i>Error';
            button.classList.remove('from-purple-600', 'to-orange-500');
            button.classList.add('bg-red-500');
        }
    } catch (error) {
        console.error('Error joining event:', error);
        button.innerHTML = '<i class="fas fa-times mr-1"></i>Error';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal on backdrop click
document.getElementById('activityModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeActivityModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeActivityModal();
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
