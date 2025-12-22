<?php
/**
 * User Model
 * Handles all database operations related to users
 */

require_once __DIR__ . '/../config/db.php';

class User {
    private PDO $db;
    
    public function __construct() {
        $this->db = getConnection();
    }
    
    /**
     * Register new user
     */
    public function register(string $name, string $email, string $password, string $userType = 'normal', ?string $phone = null, ?string $address = null): int|false {
        // Check if email already exists
        if ($this->findByEmail($email)) {
            return false;
        }
        
        // Get role_id
        $roleId = $userType === 'business' ? 2 : 1;
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, password, role_id, phone, address, onboarding_completed, created_at) 
                VALUES (:name, :email, :password, :role_id, :phone, :address, 0, NOW())";
        $stmt = $this->db->prepare($sql);
        
        $success = $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':role_id' => $roleId,
            ':phone' => $phone,
            ':address' => $address
        ]);
        
        return $success ? (int)$this->db->lastInsertId() : false;
    }
    
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array {
        $sql = "SELECT u.*, r.role_name, n.name as neighborhood_name, n.short_name as neighborhood_short 
                FROM users u 
                JOIN user_roles r ON u.role_id = r.id 
                LEFT JOIN neighborhoods n ON u.neighborhood_id = n.id
                WHERE u.email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        $user = $stmt->fetch();
        return $user ?: null;
    }
    
    /**
     * Find user by ID
     */
    public function findById(int $id): ?array {
        $sql = "SELECT u.*, r.role_name, n.name as neighborhood_name, n.short_name as neighborhood_short 
                FROM users u 
                JOIN user_roles r ON u.role_id = r.id 
                LEFT JOIN neighborhoods n ON u.neighborhood_id = n.id
                WHERE u.id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $user = $stmt->fetch();
        return $user ?: null;
    }
    
    /**
     * Authenticate user
     */
    public function authenticate(string $email, string $password): ?array {
        $user = $this->findByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return null;
    }
    
    /**
     * Update user profile
     */
    public function updateProfile(int $id, string $name, string $email, ?string $password = null): bool {
        // Check if email exists for another user
        $existingUser = $this->findByEmail($email);
        if ($existingUser && $existingUser['id'] !== $id) {
            return false;
        }
        
        if ($password) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET name = :name, email = :email, password = :password, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':id' => $id
            ]);
        } else {
            $sql = "UPDATE users SET name = :name, email = :email, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':id' => $id
            ]);
        }
    }
    
    /**
     * Check if user is business type
     */
    public function isBusiness(int $userId): bool {
        $user = $this->findById($userId);
        return $user && $user['role_name'] === 'business';
    }
    
    /**
     * Complete onboarding
     */
    public function completeOnboarding(int $userId): bool {
        $sql = "UPDATE users SET onboarding_completed = 1, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $userId]);
    }
    
    /**
     * Save user preferences (categories)
     */
    public function savePreferences(int $userId, array $categories): bool {
        // Delete previous preferences
        $sql = "DELETE FROM user_preferences WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        // Insert new preferences
        $sql = "INSERT INTO user_preferences (user_id, category) VALUES (:user_id, :category)";
        $stmt = $this->db->prepare($sql);
        
        foreach ($categories as $category) {
            $stmt->execute([':user_id' => $userId, ':category' => $category]);
        }
        
        return true;
    }
    
    /**
     * Get user preferences
     */
    public function getPreferences(int $userId): array {
        $sql = "SELECT category FROM user_preferences WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Save user availability
     */
    public function saveAvailability(int $userId, array $availability): bool {
        // Delete previous availability
        $sql = "DELETE FROM user_availability WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        // Insert new availability
        $sql = "INSERT INTO user_availability (user_id, day_of_week, start_time, end_time) 
                VALUES (:user_id, :day_of_week, :start_time, :end_time)";
        $stmt = $this->db->prepare($sql);
        
        foreach ($availability as $slot) {
            $stmt->execute([
                ':user_id' => $userId,
                ':day_of_week' => $slot['day'],
                ':start_time' => $slot['start'],
                ':end_time' => $slot['end']
            ]);
        }
        
        return true;
    }
    
    /**
     * Get user availability
     */
    public function getAvailability(int $userId): array {
        $sql = "SELECT day_of_week, start_time, end_time FROM user_availability WHERE user_id = :user_id ORDER BY day_of_week, start_time";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Set user neighborhood
     */
    public function setNeighborhood(int $userId, int $neighborhoodId): bool {
        $sql = "UPDATE users SET neighborhood_id = :neighborhood_id, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':neighborhood_id' => $neighborhoodId,
            ':id' => $userId
        ]);
    }
    
    /**
     * Get user neighborhood
     */
    public function getNeighborhood(int $userId): ?array {
        $sql = "SELECT n.* FROM neighborhoods n 
                JOIN users u ON u.neighborhood_id = n.id 
                WHERE u.id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $neighborhood = $stmt->fetch();
        return $neighborhood ?: null;
    }
    
    /**
     * Get all neighborhoods
     */
    public function getAllNeighborhoods(): array {
        $sql = "SELECT * FROM neighborhoods ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Find neighborhood by ID
     */
    public function findNeighborhoodById(int $id): ?array {
        $sql = "SELECT * FROM neighborhoods WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $neighborhood = $stmt->fetch();
        return $neighborhood ?: null;
    }
}
