<?php
/**
 * Modelo de Eventos
 * Gestiona todas las operaciones de base de datos relacionadas con eventos
 */

require_once __DIR__ . '/../config/db.php';

class Event {
    private PDO $db;
    
    public function __construct() {
        $this->db = getConnection();
    }
    
    /**
     * Crear nuevo evento
     */
    public function create(array $data): int|false {
        $sql = "INSERT INTO events (user_id, title, category, description, location, neighborhood_id, event_date, event_time, max_participants, participants, is_sponsored, created_at) 
                VALUES (:user_id, :title, :category, :description, :location, :neighborhood_id, :event_date, :event_time, :max_participants, 0, :is_sponsored, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':user_id' => $data['user_id'],
            ':title' => $data['title'],
            ':category' => $data['category'],
            ':description' => $data['description'],
            ':location' => $data['location'],
            ':neighborhood_id' => $data['neighborhood_id'] ?? null,
            ':event_date' => $data['event_date'],
            ':event_time' => $data['event_time'],
            ':max_participants' => $data['max_participants'] ?? null,
            ':is_sponsored' => $data['is_sponsored'] ?? 0
        ]);
        
        return $success ? (int)$this->db->lastInsertId() : false;
    }
    
    /**
     * Obtener evento por ID
     */
    public function findById(int $id): ?array {
        $sql = "SELECT e.*, u.name as creator_name, u.email as creator_email, u.phone as creator_phone, u.address as creator_address, r.role_name as creator_role,
                n.name as neighborhood_name, n.short_name as neighborhood_short
                FROM events e 
                JOIN users u ON e.user_id = u.id 
                JOIN user_roles r ON u.role_id = r.id
                LEFT JOIN neighborhoods n ON e.neighborhood_id = n.id
                WHERE e.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $event = $stmt->fetch();
        return $event ?: null;
    }
    
    /**
     * Obtener todos los eventos
     */
    public function getAll(): array {
        $sql = "SELECT e.*, u.name as creator_name, r.role_name as creator_role,
                n.name as neighborhood_name, n.short_name as neighborhood_short
                FROM events e 
                JOIN users u ON e.user_id = u.id 
                JOIN user_roles r ON u.role_id = r.id
                LEFT JOIN neighborhoods n ON e.neighborhood_id = n.id
                ORDER BY e.event_date ASC, e.event_time ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener eventos de un usuario
     */
    public function getByUser(int $userId): array {
        $sql = "SELECT e.*, u.name as creator_name, n.name as neighborhood_name, n.short_name as neighborhood_short
                FROM events e 
                JOIN users u ON e.user_id = u.id 
                LEFT JOIN neighborhoods n ON e.neighborhood_id = n.id
                WHERE e.user_id = :user_id 
                ORDER BY e.event_date ASC, e.event_time ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener próximos eventos (no patrocinados)
     */
    public function getUpcoming(int $limit = 10): array {
        $sql = "SELECT e.*, u.name as creator_name, r.role_name as creator_role,
                n.name as neighborhood_name, n.short_name as neighborhood_short
                FROM events e 
                JOIN users u ON e.user_id = u.id 
                JOIN user_roles r ON u.role_id = r.id
                LEFT JOIN neighborhoods n ON e.neighborhood_id = n.id
                WHERE e.event_date >= CURDATE() AND e.is_sponsored = 0
                ORDER BY e.event_date ASC, e.event_time ASC 
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener espacios patrocinados
     */
    public function getSponsoredSpaces(int $limit = 10): array {
        $sql = "SELECT e.*, u.name as creator_name, n.name as neighborhood_name, n.short_name as neighborhood_short
                FROM events e 
                JOIN users u ON e.user_id = u.id 
                LEFT JOIN neighborhoods n ON e.neighborhood_id = n.id
                WHERE e.event_date >= CURDATE() AND e.is_sponsored = 1
                ORDER BY e.event_date ASC, e.event_time ASC 
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener eventos por rango de fechas
     */
    public function getByDateRange(string $startDate, string $endDate): array {
        $sql = "SELECT e.*, u.name as creator_name, r.role_name as creator_role,
                n.name as neighborhood_name, n.short_name as neighborhood_short
                FROM events e 
                JOIN users u ON e.user_id = u.id 
                JOIN user_roles r ON u.role_id = r.id
                LEFT JOIN neighborhoods n ON e.neighborhood_id = n.id
                WHERE e.event_date BETWEEN :start_date AND :end_date 
                ORDER BY e.event_date ASC, e.event_time ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        return $stmt->fetchAll();
    }
    
    /**
     * Unirse a un evento
     */
    public function join(int $eventId, int $userId): bool {
        // Verificar si ya está unido
        if ($this->isParticipant($eventId, $userId)) {
            return false;
        }
        
        // Verificar si hay espacio
        $event = $this->findById($eventId);
        if ($event['max_participants'] && $event['participants'] >= $event['max_participants']) {
            return false;
        }
        
        $this->db->beginTransaction();
        
        try {
            // Insertar participación
            $sql = "INSERT INTO event_participants (event_id, user_id, joined_at) VALUES (:event_id, :user_id, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':event_id' => $eventId, ':user_id' => $userId]);
            
            // Incrementar contador
            $sql = "UPDATE events SET participants = participants + 1 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $eventId]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    /**
     * Salir de un evento
     */
    public function leave(int $eventId, int $userId): bool {
        // Verificar si está unido
        if (!$this->isParticipant($eventId, $userId)) {
            return false;
        }
        
        $this->db->beginTransaction();
        
        try {
            // Eliminar participación
            $sql = "DELETE FROM event_participants WHERE event_id = :event_id AND user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':event_id' => $eventId, ':user_id' => $userId]);
            
            // Decrementar contador
            $sql = "UPDATE events SET participants = GREATEST(participants - 1, 0) WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $eventId]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    /**
     * Verificar si un usuario es participante
     */
    public function isParticipant(int $eventId, int $userId): bool {
        $sql = "SELECT COUNT(*) FROM event_participants WHERE event_id = :event_id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':event_id' => $eventId, ':user_id' => $userId]);
        return (int)$stmt->fetchColumn() > 0;
    }
    
    /**
     * Obtener participantes de un evento
     */
    public function getParticipants(int $eventId): array {
        $sql = "SELECT u.id, u.name, u.email, ep.joined_at 
                FROM event_participants ep 
                JOIN users u ON ep.user_id = u.id 
                WHERE ep.event_id = :event_id 
                ORDER BY ep.joined_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':event_id' => $eventId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener eventos a los que un usuario está unido
     */
    public function getJoinedEvents(int $userId): array {
        $sql = "SELECT e.*, u.name as creator_name, ep.joined_at, n.name as neighborhood_name, n.short_name as neighborhood_short
                FROM events e 
                JOIN users u ON e.user_id = u.id 
                JOIN event_participants ep ON e.id = ep.event_id 
                LEFT JOIN neighborhoods n ON e.neighborhood_id = n.id
                WHERE ep.user_id = :user_id 
                ORDER BY e.event_date ASC, e.event_time ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Eliminar evento
     */
    public function delete(int $eventId, int $userId): bool {
        // Solo el creador puede eliminar
        $event = $this->findById($eventId);
        if (!$event || $event['user_id'] !== $userId) {
            return false;
        }
        
        $this->db->beginTransaction();
        
        try {
            // Eliminar participantes
            $sql = "DELETE FROM event_participants WHERE event_id = :event_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':event_id' => $eventId]);
            
            // Eliminar evento
            $sql = "DELETE FROM events WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $eventId]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    /**
     * Buscar eventos por categoría y hora disponible
     */
    public function findByTimeSlot(string $date, int $hour, ?string $category = null, int $userId = 0): array {
        $hourStart = sprintf('%02d:00:00', $hour);
        $hourEnd = sprintf('%02d:59:59', $hour);
        
        $sql = "SELECT e.*, u.name as creator_name, r.role_name as creator_role
                FROM events e 
                JOIN users u ON e.user_id = u.id 
                JOIN user_roles r ON u.role_id = r.id
                LEFT JOIN event_participants ep ON e.id = ep.event_id AND ep.user_id = :user_id
                WHERE e.event_date = :date 
                AND e.event_time BETWEEN :hour_start AND :hour_end
                AND ep.id IS NULL
                AND e.user_id != :user_id2";
        
        if ($category) {
            $sql .= " AND e.category = :category";
        }
        
        $sql .= " AND (e.max_participants IS NULL OR e.participants < e.max_participants)
                  ORDER BY e.event_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $params = [
            ':date' => $date,
            ':hour_start' => $hourStart,
            ':hour_end' => $hourEnd,
            ':user_id' => $userId,
            ':user_id2' => $userId
        ];
        
        if ($category) {
            $params[':category'] = $category;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Actualizar evento
     */
    public function update(int $eventId, array $data): bool {
        $sql = "UPDATE events SET 
                title = :title, 
                category = :category, 
                description = :description, 
                location = :location, 
                neighborhood_id = :neighborhood_id,
                event_date = :event_date, 
                event_time = :event_time, 
                max_participants = :max_participants,
                updated_at = NOW() 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':title' => $data['title'],
            ':category' => $data['category'],
            ':description' => $data['description'],
            ':location' => $data['location'],
            ':neighborhood_id' => $data['neighborhood_id'] ?? null,
            ':event_date' => $data['event_date'],
            ':event_time' => $data['event_time'],
            ':max_participants' => $data['max_participants'] ?? null,
            ':id' => $eventId
        ]);
    }
}
