<?php
/**
 * My Activities - Activities created by user and activities they've joined
 */
$pageTitle = 'My Activities - Muniverse';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Event.php';

session_start();
requireLogin();

$eventModel = new Event();
$myCreatedEvents = $eventModel->getByUser($_SESSION['user_id']);
$myJoinedEvents = $eventModel->getJoinedEvents($_SESSION['user_id']);

$userRole = $_SESSION['user_role'] ?? 'normal';
$isBusiness = $userRole === 'business';

// Categories with icons and colors
$categoryConfig = [
    'culture' => ['icon' => 'fa-theater-masks', 'color' => 'bg-purple-100 text-purple-700'],
    'food' => ['icon' => 'fa-utensils', 'color' => 'bg-orange-100 text-orange-700'],
    'games' => ['icon' => 'fa-gamepad', 'color' => 'bg-pink-100 text-pink-700'],
    'language' => ['icon' => 'fa-language', 'color' => 'bg-blue-100 text-blue-700'],
    'sports' => ['icon' => 'fa-running', 'color' => 'bg-green-100 text-green-700'],
];

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                <?= $isBusiness ? 'My Spaces' : 'My Activities' ?>
            </h1>
            <p class="text-gray-500 mt-1">
                <?= $isBusiness ? 'Manage the spaces you offer' : 'Manage your activities and participations' ?>
            </p>
        </div>
        
        <a href="/views/events/create.php" class="bg-gradient-to-r from-purple-600 to-orange-500 hover:opacity-90 text-white px-6 py-3 rounded-xl font-medium inline-flex items-center gap-2 transition">
            <i class="fas fa-plus"></i>
            <span><?= $isBusiness ? 'Offer Space' : 'Create Activity' ?></span>
        </a>
    </div>
    
    <!-- Tabs -->
    <div class="mb-8">
        <div class="border-b border-gray-200">
            <nav class="flex gap-8" id="tabs">
                <button onclick="showTab('created')" class="tab-btn active pb-4 border-b-2 border-purple-500 text-purple-600 font-medium" data-tab="created">
                    <i class="fas <?= $isBusiness ? 'fa-building' : 'fa-crown' ?> mr-2"></i>
                    <?= $isBusiness ? 'Offered spaces' : 'Activities I organize' ?> (<?= count($myCreatedEvents) ?>)
                </button>
                <?php if (!$isBusiness): ?>
                <button onclick="showTab('joined')" class="tab-btn pb-4 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium" data-tab="joined">
                    <i class="fas fa-user-check mr-2"></i>Joined activities (<?= count($myJoinedEvents) ?>)
                </button>
                <?php endif; ?>
            </nav>
        </div>
    </div>
    
    <!-- Created Events -->
    <div id="tab-created" class="tab-content">
        <?php if (empty($myCreatedEvents)): ?>
        <div class="bg-white rounded-2xl p-12 text-center shadow-sm border border-gray-100">
            <div class="w-16 h-16 bg-gradient-to-br from-purple-100 to-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas <?= $isBusiness ? 'fa-store' : 'fa-calendar-plus' ?> text-purple-500 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">
                <?= $isBusiness ? "You haven't offered any spaces" : "You haven't created any activities" ?>
            </h3>
            <p class="text-gray-500 mb-6">
                <?= $isBusiness ? 'Offer your first space to the community!' : 'Create your first activity and connect with Munich!' ?>
            </p>
            <a href="/views/events/create.php" class="bg-gradient-to-r from-purple-600 to-orange-500 hover:opacity-90 text-white px-6 py-3 rounded-xl font-medium inline-flex items-center gap-2 transition">
                <i class="fas fa-plus"></i>
                <span><?= $isBusiness ? 'Offer Space' : 'Create Activity' ?></span>
            </a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($myCreatedEvents as $event): ?>
            <?php 
                $category = $event['category'] ?? 'culture';
                $config = $categoryConfig[$category] ?? $categoryConfig['culture'];
                $isSponsored = $event['is_sponsored'] ?? false;
            ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <span class="<?= $config['color'] ?> px-3 py-1 rounded-full text-sm font-medium inline-flex items-center gap-2">
                            <i class="fas <?= $config['icon'] ?>"></i>
                            <?= h(ucfirst($category)) ?>
                        </span>
                        <?php if ($isSponsored): ?>
                        <span class="bg-gradient-to-r from-purple-600 to-orange-500 text-white px-2 py-1 rounded text-xs font-medium">
                            <i class="fas fa-star mr-1"></i>Featured
                        </span>
                        <?php else: ?>
                        <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-medium">
                            Organizer
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                        <a href="/views/events/show.php?id=<?= $event['id'] ?>" class="hover:text-purple-600 transition">
                            <?= h($event['title']) ?>
                        </a>
                    </h3>
                    
                    <div class="space-y-2 text-sm text-gray-600">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-calendar text-gray-400 w-4"></i>
                            <span><?= date('M d, Y', strtotime($event['event_date'])) ?></span>
                            <span class="text-gray-300">•</span>
                            <span><?= date('H:i', strtotime($event['event_time'])) ?>h</span>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <i class="fas fa-users text-gray-400 w-4"></i>
                            <span><?= $event['participants'] ?><?= $event['max_participants'] ? '/' . $event['max_participants'] : '' ?> participants</span>
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <a href="/views/events/edit.php?id=<?= $event['id'] ?>" class="text-gray-500 hover:text-gray-700 font-medium text-sm">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </a>
                    <a href="/views/events/show.php?id=<?= $event['id'] ?>" class="text-purple-600 hover:text-purple-700 font-medium text-sm">
                        View more <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Joined Activities (only for normal users) -->
    <?php if (!$isBusiness): ?>
    <div id="tab-joined" class="tab-content hidden">
        <?php if (empty($myJoinedEvents)): ?>
        <div class="bg-white rounded-2xl p-12 text-center shadow-sm border border-gray-100">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-search text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">You're not registered for any activities</h3>
            <p class="text-gray-500 mb-6">Explore available activities and join some</p>
            <a href="/views/events/index.php" class="bg-gradient-to-r from-purple-600 to-orange-500 hover:opacity-90 text-white px-6 py-3 rounded-xl font-medium inline-flex items-center gap-2 transition">
                <i class="fas fa-search"></i>
                <span>Explore Activities</span>
            </a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($myJoinedEvents as $event): ?>
            <?php 
                $category = $event['category'] ?? 'culture';
                $config = $categoryConfig[$category] ?? $categoryConfig['culture'];
            ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <span class="<?= $config['color'] ?> px-3 py-1 rounded-full text-sm font-medium inline-flex items-center gap-2">
                            <i class="fas <?= $config['icon'] ?>"></i>
                            <?= h(ucfirst($category)) ?>
                        </span>
                        <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-medium">
                            Joined
                        </span>
                    </div>
                    
                    <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                        <a href="/views/events/show.php?id=<?= $event['id'] ?>" class="hover:text-purple-600 transition">
                            <?= h($event['title']) ?>
                        </a>
                    </h3>
                    
                    <div class="space-y-2 text-sm text-gray-600">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-calendar text-gray-400 w-4"></i>
                            <span><?= date('M d, Y', strtotime($event['event_date'])) ?></span>
                            <span class="text-gray-300">•</span>
                            <span><?= date('H:i', strtotime($event['event_time'])) ?>h</span>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <i class="fas fa-user text-gray-400 w-4"></i>
                            <span>By <?= h($event['creator_name']) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <a href="/controllers/EventController.php?action=leave&id=<?= $event['id'] ?>" 
                       onclick="return confirm('Are you sure you want to leave this event?')"
                       class="text-gray-500 hover:text-red-600 font-medium text-sm">
                        <i class="fas fa-sign-out-alt mr-1"></i>Leave
                    </a>
                    <a href="/views/events/show.php?id=<?= $event['id'] ?>" class="text-purple-600 hover:text-purple-700 font-medium text-sm">
                        View more <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active', 'border-purple-500', 'text-purple-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab
    document.getElementById('tab-' + tabName).classList.remove('hidden');
    document.querySelector('[data-tab="' + tabName + '"]').classList.add('active', 'border-purple-500', 'text-purple-600');
    document.querySelector('[data-tab="' + tabName + '"]').classList.remove('border-transparent', 'text-gray-500');
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
