<?php
/**
 * Registration Page
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
    <title>Sign Up - Muniverse</title>
    
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
                        },
                        accent: {
                            100: '#ffedd5',
                            500: '#f97316',
                            600: '#ea580c',
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
        .gradient-accent { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
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
        .user-type-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .user-type-card:hover {
            transform: translateY(-2px);
        }
        .user-type-card.selected {
            border-color: #d946ef;
            background: #fdf4ff;
        }
        .user-type-card.selected.business {
            border-color: #f97316;
            background: #fff7ed;
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
            <p class="text-gray-500 mt-1">Join our community</p>
        </div>
        
        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Create Account</h2>
            
            <?php if ($flash): ?>
            <div class="mb-6 rounded-lg p-4 <?= $flash['type'] === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' ?>">
                <div class="flex items-center gap-3">
                    <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <p class="text-sm"><?= $flash['message'] ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <form action="/controllers/AuthController.php?action=register" method="POST" class="space-y-5">
                <!-- User type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Account type</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="user-type-card selected p-4 rounded-xl border-2 border-gray-200">
                            <input type="radio" name="user_type" value="normal" class="hidden" checked onchange="updateUserType(this)">
                            <div class="text-center">
                                <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                    <i class="fas fa-user text-primary-600 text-xl"></i>
                                </div>
                                <p class="font-medium text-gray-900">Personal</p>
                                <p class="text-xs text-gray-500 mt-1">Create and join events</p>
                            </div>
                        </label>
                        <label class="user-type-card p-4 rounded-xl border-2 border-gray-200">
                            <input type="radio" name="user_type" value="business" class="hidden" onchange="updateUserType(this)">
                            <div class="text-center">
                                <div class="w-12 h-12 bg-accent-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                    <i class="fas fa-store text-accent-600 text-xl"></i>
                                </div>
                                <p class="font-medium text-gray-900">Business</p>
                                <p class="text-xs text-gray-500 mt-1">Offer spaces</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        <span id="name-label">Full name</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-user"></i>
                        </span>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            required
                            minlength="2"
                            class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                            placeholder="Your name"
                        >
                    </div>
                </div>
                
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
                            minlength="6"
                            class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                            placeholder="Minimum 6 characters"
                        >
                    </div>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm password</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required
                            minlength="6"
                            class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                            placeholder="Repeat your password"
                        >
                    </div>
                </div>
                
                <!-- Business fields (hidden by default) -->
                <div id="businessFields" class="hidden space-y-5">
                    <div class="p-4 bg-orange-50 rounded-xl border border-orange-200">
                        <p class="text-sm text-orange-800">
                            <i class="fas fa-info-circle mr-1"></i>
                            As a business account, we need contact information so users can find you.
                        </p>
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Contact phone <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="fas fa-phone"></i>
                            </span>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                                placeholder="+1 234 567 8900"
                            >
                        </div>
                    </div>
                    
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="fas fa-map-marker-alt"></i>
                            </span>
                            <input 
                                type="text" 
                                id="address" 
                                name="address" 
                                autocomplete="off"
                                class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                                placeholder="Start typing an address..."
                            >
                            <!-- Address suggestions dropdown -->
                            <div id="addressSuggestions" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Powered by OpenStreetMap
                        </p>
                    </div>
                </div>
                
                <button type="submit" class="w-full btn-primary text-white py-3 rounded-xl font-semibold">
                    Create Account
                </button>
            </form>
            
            <p class="mt-6 text-center text-gray-500 text-sm">
                Already have an account? 
                <a href="/views/auth/login.php" class="text-primary-600 hover:text-primary-700 font-medium">
                    Sign in
                </a>
            </p>
        </div>
    </div>
    
    <script>
    function updateUserType(radio) {
        // Remove selected class from all cards
        document.querySelectorAll('.user-type-card').forEach(card => {
            card.classList.remove('selected', 'business');
        });
        
        // Add selected class to parent label
        const card = radio.closest('.user-type-card');
        card.classList.add('selected');
        
        // Update label and placeholder based on type
        const nameLabel = document.getElementById('name-label');
        const nameInput = document.getElementById('name');
        const businessFields = document.getElementById('businessFields');
        const phoneInput = document.getElementById('phone');
        const addressInput = document.getElementById('address');
        
        if (radio.value === 'business') {
            card.classList.add('business');
            nameLabel.textContent = 'Business name';
            nameInput.placeholder = 'Your business name';
            businessFields.classList.remove('hidden');
            phoneInput.required = true;
            addressInput.required = true;
        } else {
            nameLabel.textContent = 'Full name';
            nameInput.placeholder = 'Your name';
            businessFields.classList.add('hidden');
            phoneInput.required = false;
            addressInput.required = false;
        }
    }
    
    // Address Autocomplete using Photon API (free, OpenStreetMap-based)
    const addressInput = document.getElementById('address');
    const suggestionsContainer = document.getElementById('addressSuggestions');
    let debounceTimer;
    
    addressInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(debounceTimer);
        
        if (query.length < 3) {
            suggestionsContainer.classList.add('hidden');
            return;
        }
        
        debounceTimer = setTimeout(() => {
            fetchAddressSuggestions(query);
        }, 300);
    });
    
    async function fetchAddressSuggestions(query) {
        try {
            // Photon API - free, no API key required, biased towards Munich
            const url = `https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&limit=5&lat=48.1351&lon=11.5820&lang=en`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.features && data.features.length > 0) {
                displaySuggestions(data.features);
            } else {
                suggestionsContainer.classList.add('hidden');
            }
        } catch (error) {
            console.error('Address lookup error:', error);
            suggestionsContainer.classList.add('hidden');
        }
    }
    
    function displaySuggestions(features) {
        suggestionsContainer.innerHTML = '';
        
        features.forEach(feature => {
            const props = feature.properties;
            
            // Build readable address
            let addressParts = [];
            if (props.name) addressParts.push(props.name);
            if (props.housenumber && props.street) {
                addressParts.push(`${props.street} ${props.housenumber}`);
            } else if (props.street) {
                addressParts.push(props.street);
            }
            if (props.postcode) addressParts.push(props.postcode);
            if (props.city) addressParts.push(props.city);
            if (props.country) addressParts.push(props.country);
            
            const fullAddress = addressParts.join(', ');
            
            // Create suggestion item
            const item = document.createElement('div');
            item.className = 'px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 flex items-start gap-3';
            item.innerHTML = `
                <i class="fas fa-map-marker-alt text-primary-500 mt-1"></i>
                <div>
                    <p class="text-sm font-medium text-gray-900">${props.name || props.street || 'Location'}</p>
                    <p class="text-xs text-gray-500">${fullAddress}</p>
                </div>
            `;
            
            item.addEventListener('click', () => {
                addressInput.value = fullAddress;
                suggestionsContainer.classList.add('hidden');
            });
            
            suggestionsContainer.appendChild(item);
        });
        
        suggestionsContainer.classList.remove('hidden');
    }
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!addressInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
            suggestionsContainer.classList.add('hidden');
        }
    });
    
    // Hide suggestions on escape key
    addressInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            suggestionsContainer.classList.add('hidden');
        }
    });
    </script>
</body>
</html>
