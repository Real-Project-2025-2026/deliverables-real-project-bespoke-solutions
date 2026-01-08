<?php
/**
 * Activity detail view
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Event.php';

session_start();
requireLogin();

$eventId = (int)($_GET['id'] ?? 0);
$eventModel = new Event();
$event = $eventModel->findById($eventId);

if (!$event) {
    setFlashMessage('error', 'Activity does not exist.');
    header('Location: /views/events/index.php');
    exit;
}

$participants = $eventModel->getParticipants($eventId);
$isOwner = $event['user_id'] == $_SESSION['user_id'];
$isParticipant = $eventModel->isParticipant($eventId, $_SESSION['user_id']);
$isFull = $event['max_participants'] && $event['participants'] >= $event['max_participants'];
$isSponsored = $event['is_sponsored'] ?? false;

$pageTitle = h($event['title']) . ' - Muniverse';

// Categories with icons and colors
$categoryConfig = [
    'culture' => ['icon' => 'fa-theater-masks', 'color' => 'bg-purple-100 text-purple-700', 'gradient' => 'from-purple-600 to-purple-400'],
    'food' => ['icon' => 'fa-utensils', 'color' => 'bg-orange-100 text-orange-700', 'gradient' => 'from-orange-600 to-orange-400'],
    'games' => ['icon' => 'fa-gamepad', 'color' => 'bg-pink-100 text-pink-700', 'gradient' => 'from-pink-600 to-pink-400'],
    'language' => ['icon' => 'fa-language', 'color' => 'bg-blue-100 text-blue-700', 'gradient' => 'from-blue-600 to-blue-400'],
    'sports' => ['icon' => 'fa-running', 'color' => 'bg-green-100 text-green-700', 'gradient' => 'from-green-600 to-green-400'],
];

$category = $event['category'] ?? 'culture';
$config = $categoryConfig[$category] ?? $categoryConfig['culture'];

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php 
    // Determine back link based on referer
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $backUrl = '/views/events/index.php';
    $backText = 'Back to activities';
    
    if (strpos($referer, '/views/calendar/') !== false) {
        $backUrl = '/views/calendar/index.php';
        $backText = 'Back to calendar';
    } elseif (strpos($referer, '/views/events/my-events') !== false) {
        $backUrl = '/views/events/my-events.php';
        $backText = 'Back to my activities';
    }
    ?>
    <!-- Back link -->
    <a href="<?= $backUrl ?>" class="text-gray-500 hover:text-gray-700 mb-6 inline-flex items-center gap-2">
        <i class="fas fa-arrow-left"></i>
        <?= $backText ?>
    </a>
    
    <!-- Event Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mt-4">
        <!-- Header -->
        <div class="bg-gradient-to-r <?= $config['gradient'] ?> px-8 py-8">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <span class="bg-white/20 text-white px-3 py-1 rounded-full text-sm font-medium inline-flex items-center gap-2">
                            <i class="fas <?= $config['icon'] ?>"></i>
                            <?= h(ucfirst($category)) ?>
                        </span>
                        <?php if ($isSponsored): ?>
                        <span class="bg-gradient-to-r from-yellow-400 to-orange-400 text-white px-3 py-1 rounded-full text-sm font-medium">
                            <i class="fas fa-star mr-1"></i>Featured
                        </span>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-3xl font-bold text-white"><?= h($event['title']) ?></h1>
                    <p class="text-white/80 mt-2">
                        <?= $isSponsored ? 'Offered by ' : 'Created by ' ?><?= h($event['creator_name']) ?>
                    </p>
                </div>
                
                <?php if ($isOwner): ?>
                <div class="flex gap-2">
                    <a href="/views/events/edit.php?id=<?= $event['id'] ?>" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium text-sm transition">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </a>
                    <a href="/controllers/EventController.php?action=delete&id=<?= $event['id'] ?>" 
                       onclick="return confirm('Are you sure you want to delete this event?')"
                       class="bg-red-500/80 hover:bg-red-500 text-white px-4 py-2 rounded-lg font-medium text-sm transition">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Content -->
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Main Info -->
                <div class="md:col-span-2 space-y-6">
                    <!-- Description -->
                    <?php if ($event['description']): ?>
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">Description</h3>
                        <p class="text-gray-600 whitespace-pre-line"><?= h($event['description']) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Details Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar text-purple-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Date</p>
                                <p class="font-medium text-gray-900"><?= date('M d, Y', strtotime($event['event_date'])) ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-clock text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Time</p>
                                <p class="font-medium text-gray-900"><?= date('H:i', strtotime($event['event_time'])) ?>h</p>
                            </div>
                        </div>
                        
                        <?php if ($event['location'] || $event['neighborhood_name']): ?>
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl sm:col-span-2">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-map-marker-alt text-orange-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Location</p>
                                <p class="font-medium text-gray-900">
                                    <?php if ($event['neighborhood_name']): ?>
                                    <span class="text-purple-600"><?= h($event['neighborhood_short'] ?? $event['neighborhood_name']) ?></span>
                                    <?php if ($event['location']): ?> · <?php endif; ?>
                                    <?php endif; ?>
                                    <?= h($event['location'] ?? '') ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Participants List -->
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">
                            Participants (<?= $event['participants'] ?><?= $event['max_participants'] ? '/' . $event['max_participants'] : '' ?>)
                        </h3>
                        
                        <?php if (empty($participants)): ?>
                        <p class="text-gray-500 text-sm">No participants registered yet.</p>
                        <?php else: ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($participants as $participant): ?>
                            <div class="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded-lg">
                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-purple-600 text-sm"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700"><?= h($participant['name']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Action Card -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="text-center mb-6">
                            <div class="text-3xl font-bold text-gray-900"><?= $event['participants'] ?></div>
                            <p class="text-gray-500">
                                <?= $event['max_participants'] ? 'of ' . $event['max_participants'] . ' spots' : 'participants' ?>
                            </p>
                            
                            <?php if ($event['max_participants']): ?>
                            <div class="mt-3 w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-purple-600 to-orange-500 h-2 rounded-full" style="width: <?= min(100, ($event['participants'] / $event['max_participants']) * 100) ?>%"></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($isOwner): ?>
                        <div class="bg-purple-50 border border-purple-100 rounded-lg p-4 text-center">
                            <i class="fas fa-crown text-purple-600 text-xl mb-2"></i>
                            <p class="text-purple-700 font-medium">You are the organizer</p>
                        </div>
                        <?php elseif ($isParticipant): ?>
                        <div class="space-y-3">
                            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 text-center">
                                <i class="fas fa-check-circle text-blue-600 text-xl mb-2"></i>
                                <p class="text-blue-700 font-medium">You are registered</p>
                            </div>
                        <a href="/controllers/EventController.php?action=leave&id=<?= $event['id'] ?>" 
                               onclick="return confirm('Are you sure you want to leave this activity?')"
                               class="block w-full text-center bg-gray-200 hover:bg-gray-300 text-gray-700 py-3 rounded-xl font-medium transition">
                                <i class="fas fa-sign-out-alt mr-2"></i>Leave activity
                            </a>
                        </div>
                        <?php elseif ($isFull): ?>
                        <div class="bg-red-50 border border-red-100 rounded-lg p-4 text-center">
                            <i class="fas fa-times-circle text-red-600 text-xl mb-2"></i>
                            <p class="text-red-700 font-medium">Activity is full</p>
                        </div>
                        <?php else: ?>
                        <a href="/controllers/EventController.php?action=join&id=<?= $event['id'] ?>" 
                           class="block w-full text-center bg-gradient-to-r from-purple-600 to-orange-500 hover:opacity-90 text-white py-3 rounded-xl font-semibold transition">
                            <i class="fas fa-plus mr-2"></i>Join activity
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Creator Info -->
                    <div class="bg-white border border-gray-100 rounded-xl p-6">
                        <h4 class="font-medium text-gray-900 mb-4">
                            <?= $isSponsored ? 'Business' : 'Organizer' ?>
                        </h4>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-600 to-orange-500 rounded-full flex items-center justify-center">
                                <i class="fas <?= $isSponsored ? 'fa-building' : 'fa-user' ?> text-white"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900"><?= h($event['creator_name']) ?></p>
                                <p class="text-gray-500 text-sm"><?= h($event['creator_email']) ?></p>
                            </div>
                        </div>
                        
                        <?php if ($isSponsored && !empty($event['creator_phone'])): ?>
                        <div class="mt-4 pt-4 border-t border-gray-100 space-y-2">
                            <div class="flex items-center gap-2 text-sm">
                                <i class="fas fa-phone text-gray-400 w-4"></i>
                                <a href="tel:<?= h($event['creator_phone']) ?>" class="text-purple-600 hover:text-purple-700">
                                    <?= h($event['creator_phone']) ?>
                                </a>
                            </div>
                            <?php if (!empty($event['creator_address'])): ?>
                            <div class="flex items-start gap-2 text-sm">
                                <i class="fas fa-map-marker-alt text-gray-400 w-4 mt-0.5"></i>
                                <span class="text-gray-600"><?= h($event['creator_address']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
