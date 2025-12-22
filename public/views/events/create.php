<?php
/**
 * Create new activity
 */
$pageTitle = 'Create Activity - Muniverse';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/User.php';

session_start();
requireLogin();

$userModel = new User();
$isBusiness = $userModel->isBusiness($_SESSION['user_id']);
$neighborhoods = $userModel->getAllNeighborhoods();

// Get business user's address for pre-fill
$businessAddress = '';
$businessNeighborhoodId = null;
if ($isBusiness) {
    $currentUser = $userModel->findById($_SESSION['user_id']);
    $businessAddress = $currentUser['address'] ?? '';
    $businessNeighborhoodId = $currentUser['neighborhood_id'] ?? null;
}

// Pre-fill from URL params (from calendar modal)
$prefillDate = $_GET['date'] ?? '';
$prefillTime = $_GET['time'] ?? '';
$prefillCategory = $_GET['category'] ?? '';

require_once __DIR__ . '/../layouts/header.php';

// Categories with icons and colors
$categories = [
    'sports' => ['icon' => 'fa-running', 'label' => 'Sports', 'color' => 'green'],
    'culture' => ['icon' => 'fa-palette', 'label' => 'Culture', 'color' => 'purple'],
    'food' => ['icon' => 'fa-utensils', 'label' => 'Food', 'color' => 'orange'],
    'games' => ['icon' => 'fa-gamepad', 'label' => 'Games', 'color' => 'blue'],
    'language' => ['icon' => 'fa-language', 'label' => 'Languages', 'color' => 'cyan']
];
?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <a href="/views/events/index.php" class="text-gray-500 hover:text-gray-700 mb-4 inline-flex items-center gap-2">
            <i class="fas fa-arrow-left"></i>
            Back to activities
        </a>
        <h1 class="text-3xl font-bold text-gray-900 mt-4">
            <?= $isBusiness ? 'Offer Space' : 'Create New Activity' ?>
        </h1>
        <p class="text-gray-500 mt-2">
            <?= $isBusiness ? 'Offer your space for the Munich community to organize activities' : 'Organize an activity and connect with people in Munich' ?>
        </p>
    </div>
    
    <?php if ($isBusiness): ?>
    <div class="bg-accent-50 border border-accent-200 rounded-xl p-4 mb-6">
        <div class="flex items-center gap-3">
            <i class="fas fa-store text-accent-600"></i>
            <p class="text-accent-800 text-sm">
                <strong>Business Account:</strong> Spaces you offer will appear highlighted as featured.
            </p>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <form action="/controllers/EventController.php?action=create" method="POST" class="space-y-6">
            <?php if ($isBusiness): ?>
            <input type="hidden" name="is_sponsored" value="1">
            <?php endif; ?>
            
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    <?= $isBusiness ? 'Space/activity name' : 'Activity title' ?> <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title" required
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                    placeholder="<?= $isBusiness ? 'E.g.: Meeting space at Central Café' : 'E.g.: Morning run at Englischer Garten' ?>">
            </div>
            
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                    Category <span class="text-red-500">*</span>
                </label>
                <select id="category" name="category" required
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $key => $cat): ?>
                    <option value="<?= $key ?>" <?= $prefillCategory === $key ? 'selected' : '' ?>><?= $cat['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="event_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="event_date" name="event_date" required min="<?= date('Y-m-d') ?>"
                        value="<?= h($prefillDate) ?>"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                </div>
                <div>
                    <label for="event_time" class="block text-sm font-medium text-gray-700 mb-2">
                        Time <span class="text-red-500">*</span>
                    </label>
                    <input type="time" id="event_time" name="event_time" required
                        value="<?= h($prefillTime) ?>"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                </div>
            </div>
            
            <div>
                <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-map-marker-alt text-purple-500 mr-1"></i>Location <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-map-marker-alt"></i></span>
                    <input type="text" id="location" name="location" required
                        value="<?= $isBusiness ? h($businessAddress) : '' ?>"
                        class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                        placeholder="Enter address..."
                        <?= $isBusiness ? 'readonly' : '' ?>>
                    <?php if (!$isBusiness): ?>
                    <div id="locationSuggestions" class="absolute z-50 w-full bg-white border border-gray-200 rounded-xl mt-1 shadow-lg hidden max-h-60 overflow-y-auto"></div>
                    <?php endif; ?>
                </div>
                <?php if ($isBusiness): ?>
                <p class="text-xs text-gray-500 mt-1">Using your registered business address</p>
                <?php else: ?>
                <p class="text-xs text-gray-500 mt-1">Start typing to search for an address in Munich</p>
                <?php endif; ?>
            </div>
            
            <!-- Hidden field for neighborhood_id (auto-detected from address) -->
            <input type="hidden" id="neighborhood_id" name="neighborhood_id" value="<?= $isBusiness ? ($businessNeighborhoodId ?? '') : '' ?>">
            
            <div>
                <label for="max_participants" class="block text-sm font-medium text-gray-700 mb-2">Maximum participants</label>
                <input type="number" id="max_participants" name="max_participants" min="2" max="1000"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                    placeholder="Leave empty for unlimited">
            </div>
            
            <?php if (!$isBusiness): ?>
            <div class="flex items-center gap-3">
                <input type="checkbox" id="include_me" name="include_me" value="1" checked
                    class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                <label for="include_me" class="text-sm font-medium text-gray-700">
                    Include me as a participant
                </label>
            </div>
            <?php endif; ?>
            
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea id="description" name="description" rows="4"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition resize-none"
                    placeholder="<?= $isBusiness ? 'Describe the space, included services, discounts...' : 'Describe the activity, required level, what to bring...' ?>"></textarea>
            </div>
            
            <div class="flex gap-4 pt-4">
                <button type="submit" class="flex-1 <?= $isBusiness ? 'btn-accent' : 'btn-primary' ?> text-white py-3 rounded-xl font-semibold">
                    <i class="fas <?= $isBusiness ? 'fa-store' : 'fa-plus' ?> mr-2"></i><?= $isBusiness ? 'Publish Space' : 'Create Activity' ?>
                </button>
                <a href="/views/events/index.php" class="px-6 py-3 border border-gray-200 rounded-xl font-medium text-gray-600 hover:bg-gray-50 transition text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (!$isBusiness): ?>
<script>
// Munich postal code to neighborhood mapping
const postalCodeMap = {
    '80331': 1, '80333': 1, '80538': 1, '80539': 1,
    '80335': 2, '80336': 2, '80337': 2, '80469': 2,
    '80799': 3, '80801': 3, '80802': 3,
    '80796': 4, '80797': 4, '80798': 4, '80804': 4,
    '81541': 5, '81543': 5, '81667': 5, '81669': 5, '81671': 5,
    '81369': 6, '81371': 6, '81373': 6,
    '81377': 7, '81379': 7,
    '80339': 8,
    '80634': 9, '80636': 9, '80637': 9, '80638': 9, '80639': 9,
    '80993': 10, '80997': 10, '80999': 10,
    '80807': 11, '80809': 11, '80937': 11, '80939': 11,
    '80803': 12, '80805': 12,
    '81675': 13, '81677': 13, '81679': 13, '81925': 13, '81927': 13, '81929': 13,
    '81673': 14, '81735': 14,
    '81825': 15, '81827': 15, '81829': 15,
    '81539': 16, '81549': 16, '81737': 16, '81739': 16,
    '81547': 17,
    '81545': 18,
    '81475': 19, '81476': 19, '81477': 19, '81479': 19,
    '80689': 20, '81375': 20,
    '81241': 21, '81243': 21, '81245': 21, '81247': 21, '81249': 21,
    '81249': 22,
    '80999': 23,
    '80933': 24, '80935': 24, '80995': 24,
    '80686': 25, '80687': 25
};

// Address autocomplete using Photon API (free, no API key)
const locationInput = document.getElementById('location');
const neighborhoodInput = document.getElementById('neighborhood_id');
const suggestionsContainer = document.getElementById('locationSuggestions');
let debounceTimer;

locationInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const query = this.value.trim();
    
    if (query.length < 3) {
        suggestionsContainer.classList.add('hidden');
        return;
    }
    
    debounceTimer = setTimeout(() => {
        // Photon API with Munich bias
        fetch(`https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&lat=48.137154&lon=11.576124&limit=5&lang=en`)
            .then(response => response.json())
            .then(data => {
                suggestionsContainer.innerHTML = '';
                
                if (data.features && data.features.length > 0) {
                    data.features.forEach(feature => {
                        const props = feature.properties;
                        const name = props.name || '';
                        const street = props.street || '';
                        const housenumber = props.housenumber || '';
                        const postcode = props.postcode || '';
                        const city = props.city || props.locality || '';
                        const country = props.country || '';
                        
                        // Build display address
                        let displayParts = [];
                        if (name && name !== street) displayParts.push(name);
                        if (street) {
                            displayParts.push(street + (housenumber ? ' ' + housenumber : ''));
                        }
                        if (postcode) displayParts.push(postcode);
                        if (city) displayParts.push(city);
                        if (country) displayParts.push(country);
                        
                        const displayAddress = displayParts.join(', ');
                        
                        const div = document.createElement('div');
                        div.className = 'px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0';
                        div.innerHTML = `
                            <div class="flex items-start gap-3">
                                <i class="fas fa-map-marker-alt text-purple-500 mt-1"></i>
                                <div>
                                    <div class="font-medium text-gray-900">${name || street || 'Location'}</div>
                                    <div class="text-sm text-gray-500">${displayAddress}</div>
                                </div>
                            </div>
                        `;
                        div.addEventListener('click', () => {
                            locationInput.value = displayAddress;
                            suggestionsContainer.classList.add('hidden');
                            
                            // Auto-detect neighborhood from postal code
                            if (postcode && postalCodeMap[postcode]) {
                                neighborhoodInput.value = postalCodeMap[postcode];
                                console.log('Neighborhood detected:', postcode, '->', postalCodeMap[postcode]);
                            } else {
                                neighborhoodInput.value = '';
                            }
                        });
                        suggestionsContainer.appendChild(div);
                    });
                    suggestionsContainer.classList.remove('hidden');
                } else {
                    suggestionsContainer.classList.add('hidden');
                }
            })
            .catch(() => {
                suggestionsContainer.classList.add('hidden');
            });
    }, 300);
});

// Hide suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!locationInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
        suggestionsContainer.classList.add('hidden');
    }
});

// Hide suggestions on escape
locationInput.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        suggestionsContainer.classList.add('hidden');
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
