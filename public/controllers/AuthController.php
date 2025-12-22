<?php
/**
 * Authentication Controller
 * Handles login, registration, logout, profile and onboarding
 */

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private User $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Process login
     */
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /views/auth/login.php');
            exit;
        }
        
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            setFlashMessage('error', 'Please fill in all fields.');
            header('Location: /views/auth/login.php');
            exit;
        }
        
        $user = $this->userModel->authenticate($email, $password);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role_name'];
            
            setFlashMessage('success', 'Welcome, ' . h($user['name']) . '!');
            
            // Check if onboarding needs to be completed
            if (!$user['onboarding_completed'] && $user['role_name'] === 'normal') {
                header('Location: /views/auth/onboarding.php');
            } else {
                header('Location: /views/calendar/index.php');
            }
        } else {
            setFlashMessage('error', 'Incorrect email or password.');
            header('Location: /views/auth/login.php');
        }
        exit;
    }
    
    /**
     * Process registration
     */
    public function register(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /views/auth/register.php');
            exit;
        }
        
        $name = trim($_POST['name'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $userType = $_POST['user_type'] ?? 'normal';
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        // Validations
        $errors = [];
        
        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters.';
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email.';
        }
        
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (!in_array($userType, ['normal', 'business'])) {
            $userType = 'normal';
        }
        
        // Business validations
        if ($userType === 'business') {
            if (empty($phone)) {
                $errors[] = 'Phone is required for business accounts.';
            }
            if (empty($address)) {
                $errors[] = 'Address is required for business accounts.';
            }
        }
        
        if (!empty($errors)) {
            setFlashMessage('error', implode('<br>', $errors));
            header('Location: /views/auth/register.php');
            exit;
        }
        
        $phoneValue = $userType === 'business' ? $phone : null;
        $addressValue = $userType === 'business' ? $address : null;
        
        $userId = $this->userModel->register($name, $email, $password, $userType, $phoneValue, $addressValue);
        
        if ($userId) {
            // Auto-assign neighborhood for business users based on address postal code
            if ($userType === 'business' && !empty($address)) {
                $neighborhoodId = $this->detectNeighborhoodFromAddress($address);
                if ($neighborhoodId) {
                    $this->userModel->setNeighborhood($userId, $neighborhoodId);
                }
            }
            
            setFlashMessage('success', 'Account created successfully. You can now sign in!');
            header('Location: /views/auth/login.php');
        } else {
            setFlashMessage('error', 'This email is already registered.');
            header('Location: /views/auth/register.php');
        }
        exit;
    }
    
    /**
     * Detect Munich neighborhood from address postal code
     */
    private function detectNeighborhoodFromAddress(string $address): ?int {
        // Munich postal code to neighborhood mapping
        $postalCodeMap = [
            // Altstadt-Lehel (1)
            '80331' => 1, '80333' => 1, '80538' => 1, '80539' => 1,
            // Ludwigsvorstadt-Isarvorstadt (2)
            '80335' => 2, '80336' => 2, '80337' => 2, '80469' => 2,
            // Maxvorstadt (3)
            '80333' => 3, '80799' => 3, '80801' => 3, '80802' => 3,
            // Schwabing-West (4)
            '80796' => 4, '80797' => 4, '80798' => 4, '80804' => 4,
            // Au-Haidhausen (5)
            '81541' => 5, '81543' => 5, '81667' => 5, '81669' => 5, '81671' => 5,
            // Sendling (6)
            '81369' => 6, '81371' => 6, '81373' => 6,
            // Sendling-Westpark (7)
            '81373' => 7, '81377' => 7, '81379' => 7,
            // Schwanthalerhöhe (8)
            '80339' => 8, '80336' => 8,
            // Neuhausen-Nymphenburg (9)
            '80634' => 9, '80636' => 9, '80637' => 9, '80638' => 9, '80639' => 9,
            // Moosach (10)
            '80993' => 10, '80997' => 10, '80999' => 10,
            // Milbertshofen-Am Hart (11)
            '80807' => 11, '80809' => 11, '80937' => 11, '80939' => 11,
            // Schwabing-Freimann (12)
            '80803' => 12, '80805' => 12, '80807' => 12, '80939' => 12,
            // Bogenhausen (13)
            '81675' => 13, '81677' => 13, '81679' => 13, '81925' => 13, '81927' => 13, '81929' => 13,
            // Berg am Laim (14)
            '81671' => 14, '81673' => 14, '81735' => 14,
            // Trudering-Riem (15)
            '81735' => 15, '81825' => 15, '81827' => 15, '81829' => 15,
            // Ramersdorf-Perlach (16)
            '81539' => 16, '81549' => 16, '81669' => 16, '81737' => 16, '81739' => 16,
            // Obergiesing (17)
            '81539' => 17, '81541' => 17, '81547' => 17,
            // Untergiesing-Harlaching (18)
            '81543' => 18, '81545' => 18, '81547' => 18,
            // Thalkirchen-Obersendling-Forstenried-Fürstenried-Solln (19)
            '81369' => 19, '81475' => 19, '81476' => 19, '81477' => 19, '81479' => 19,
            // Hadern (20)
            '80689' => 20, '81375' => 20, '81377' => 20,
            // Pasing-Obermenzing (21)
            '81241' => 21, '81243' => 21, '81245' => 21, '81247' => 21, '81249' => 21,
            // Aubing-Lochhausen-Langwied (22)
            '81243' => 22, '81245' => 22, '81249' => 22,
            // Allach-Untermenzing (23)
            '80997' => 23, '80999' => 23,
            // Feldmoching-Hasenbergl (24)
            '80933' => 24, '80935' => 24, '80995' => 24,
            // Laim (25)
            '80686' => 25, '80687' => 25, '80689' => 25,
        ];
        
        // Extract postal code from address (look for 5-digit German postal code)
        if (preg_match('/\b(8\d{4})\b/', $address, $matches)) {
            $postalCode = $matches[1];
            if (isset($postalCodeMap[$postalCode])) {
                return $postalCodeMap[$postalCode];
            }
        }
        
        return null;
    }
    
    /**
     * Logout
     */
    public function logout(): void {
        session_destroy();
        header('Location: /views/auth/login.php');
        exit;
    }
    
    /**
     * Update profile
     */
    public function updateProfile(): void {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /views/auth/profile.php');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $name = trim($_POST['name'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validations
        $errors = [];
        
        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters.';
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email.';
        }
        
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters.';
            }
            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            }
        }
        
        if (!empty($errors)) {
            setFlashMessage('error', implode('<br>', $errors));
            header('Location: /views/auth/profile.php');
            exit;
        }
        
        $passwordToUpdate = !empty($password) ? $password : null;
        
        if ($this->userModel->updateProfile($userId, $name, $email, $passwordToUpdate)) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            setFlashMessage('success', 'Profile updated successfully.');
        } else {
            setFlashMessage('error', 'This email is already in use by another user.');
        }
        
        header('Location: /views/auth/profile.php');
        exit;
    }
    
    /**
     * Save onboarding
     */
    public function saveOnboarding(): void {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /views/auth/onboarding.php');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $categories = $_POST['categories'] ?? [];
        $neighborhoodId = !empty($_POST['neighborhood_id']) ? (int)$_POST['neighborhood_id'] : null;
        $availability = [];
        
        // Save neighborhood
        if ($neighborhoodId) {
            $this->userModel->setNeighborhood($userId, $neighborhoodId);
        }
        
        // Process availability from JSON (new visual calendar format)
        if (isset($_POST['availability_data'])) {
            $availabilityData = json_decode($_POST['availability_data'], true);
            if (is_array($availabilityData)) {
                foreach ($availabilityData as $slot) {
                    if (isset($slot['day'], $slot['start'], $slot['end'])) {
                        // Convert day from 0-6 (Mon-Sun) to PHP weekday (1=Mon...0=Sun)
                        $phpDay = $slot['day'] == 6 ? 0 : $slot['day'] + 1;
                        $availability[] = [
                            'day' => $phpDay,
                            'start' => $slot['start'],
                            'end' => $slot['end']
                        ];
                    }
                }
            }
        }
        
        // Save preferences
        if (!empty($categories)) {
            $this->userModel->savePreferences($userId, $categories);
        }
        
        // Save availability
        if (!empty($availability)) {
            $this->userModel->saveAvailability($userId, $availability);
        }
        
        // Mark onboarding as completed
        $this->userModel->completeOnboarding($userId);
        
        setFlashMessage('success', 'Settings saved! You can now explore events.');
        header('Location: /views/calendar/index.php');
        exit;
    }
    
    /**
     * Update preferences from profile
     */
    public function updatePreferences(): void {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /views/auth/profile.php');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $categories = $_POST['preferences'] ?? [];
        
        $this->userModel->savePreferences($userId, $categories);
        
        setFlashMessage('success', 'Preferences updated successfully.');
        header('Location: /views/auth/profile.php');
        exit;
    }
    
    /**
     * Update availability from profile
     */
    public function updateAvailability(): void {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /views/auth/profile.php');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $availability = [];
        
        if (isset($_POST['availability_json'])) {
            $availabilityData = json_decode($_POST['availability_json'], true);
            if (is_array($availabilityData)) {
                foreach ($availabilityData as $slot) {
                    if (isset($slot['day'], $slot['start'], $slot['end'])) {
                        $availability[] = [
                            'day' => $slot['day'],
                            'start' => $slot['start'],
                            'end' => $slot['end']
                        ];
                    }
                }
            }
        }
        
        $this->userModel->saveAvailability($userId, $availability);
        
        setFlashMessage('success', 'Availability updated successfully.');
        header('Location: /views/auth/profile.php');
        exit;
    }
    
    /**
     * Update neighborhood
     */
    public function updateNeighborhood(): void {
        requireLogin();
        
        $userId = $_SESSION['user_id'];
        $neighborhoodId = (int)($_POST['neighborhood_id'] ?? 0);
        
        $this->userModel->setNeighborhood($userId, $neighborhoodId ?: null);
        
        setFlashMessage('success', 'Neighborhood updated successfully.');
        header('Location: /views/auth/profile.php');
        exit;
    }
}

// Simple router
$action = $_GET['action'] ?? '';
$controller = new AuthController();

switch ($action) {
    case 'login':
        $controller->login();
        break;
    case 'register':
        $controller->register();
        break;
    case 'logout':
        $controller->logout();
        break;
    case 'updateProfile':
        $controller->updateProfile();
        break;
    case 'save-onboarding':
        $controller->saveOnboarding();
        break;
    case 'updatePreferences':
        $controller->updatePreferences();
        break;
    case 'updateAvailability':
        $controller->updateAvailability();
        break;
    case 'updateNeighborhood':
        $controller->updateNeighborhood();
        break;
    default:
        header('Location: /views/auth/login.php');
        exit;
}
