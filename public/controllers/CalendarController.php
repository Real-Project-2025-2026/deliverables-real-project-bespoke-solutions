<?php
/**
 * Calendar Controller - Handles AJAX actions
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Event.php';

session_start();

header('Content-Type: application/json');

// Verify authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$eventModel = new Event();

switch ($action) {
    case 'getEventsBySlot':
        $date = $_GET['date'] ?? '';
        $hour = (int)($_GET['hour'] ?? 0);
        $category = $_GET['category'] ?? null;
        
        if (!$date || !$hour) {
            echo json_encode(['error' => 'Invalid parameters']);
            exit;
        }
        
        $events = $eventModel->findByTimeSlot($date, $hour, $category, $_SESSION['user_id']);
        echo json_encode(['events' => $events]);
        break;
        
    case 'joinEvent':
        $eventId = (int)($_POST['event_id'] ?? 0);
        
        if (!$eventId) {
            echo json_encode(['success' => false, 'error' => 'Invalid event ID']);
            exit;
        }
        
        $success = $eventModel->join($eventId, $_SESSION['user_id']);
        echo json_encode(['success' => $success]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
