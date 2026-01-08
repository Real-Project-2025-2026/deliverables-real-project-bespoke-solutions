<?php
/**
 * Events Controller
 * Handles creation, editing, participation and viewing of events
 */

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/User.php';

class EventController {
    private Event $eventModel;
    
    public function __construct() {
        $this->eventModel = new Event();
    }
    
    /**
     * Create event
     */
    public function create(): void {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /views/events/create.php');
            exit;
        }
        
        $location = trim($_POST['location'] ?? '');
        $neighborhoodId = !empty($_POST['neighborhood_id']) ? (int)$_POST['neighborhood_id'] : null;
        
        // Auto-detect neighborhood from location address for normal users
        if (empty($neighborhoodId) && !empty($location)) {
            $neighborhoodId = $this->detectNeighborhoodFromAddress($location);
        }
        
        $data = [
            'user_id' => $_SESSION['user_id'],
            'title' => trim($_POST['title'] ?? ''),
            'category' => trim($_POST['category'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'location' => $location,
            'neighborhood_id' => $neighborhoodId,
            'event_date' => $_POST['event_date'] ?? '',
            'event_time' => $_POST['event_time'] ?? '',
            'max_participants' => !empty($_POST['max_participants']) ? (int)$_POST['max_participants'] : null,
            'is_sponsored' => !empty($_POST['is_sponsored']) ? 1 : 0
        ];
        
        // Validations
        $errors = [];
        
        if (empty($data['title'])) {
            $errors[] = 'Title is required.';
        }
        
        if (empty($data['category'])) {
            $errors[] = 'Activity type is required.';
        }
        
        if (empty($data['event_date'])) {
            $errors[] = 'Date is required.';
        }
        
        if (empty($data['event_time'])) {
            $errors[] = 'Time is required.';
        }
        
        if (!empty($errors)) {
            setFlashMessage('error', implode('<br>', $errors));
            header('Location: /views/events/create.php');
            exit;
        }
        
        $eventId = $this->eventModel->create($data);
        
        if ($eventId) {
            // If normal user selected "Include me as participant", add them
            if (!empty($_POST['include_me']) && empty($data['is_sponsored'])) {
                $this->eventModel->join($eventId, $_SESSION['user_id']);
            }
            
            setFlashMessage('success', 'Event created successfully.');
            header('Location: /views/events/show.php?id=' . $eventId);
        } else {
            setFlashMessage('error', 'Error creating event.');
            header('Location: /views/events/create.php');
        }
        exit;
    }
    
    /**
     * Update event
     */
    public function update(): void {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /views/events/index.php');
            exit;
        }
        
        $eventId = (int)($_POST['event_id'] ?? 0);
        $event = $this->eventModel->findById($eventId);
        
        // Verify event exists and user is the creator
        if (!$event || $event['user_id'] !== $_SESSION['user_id']) {
            setFlashMessage('error', 'You do not have permission to edit this event.');
            header('Location: /views/events/index.php');
            exit;
        }
        
        $location = trim($_POST['location'] ?? '');
        $neighborhoodId = !empty($_POST['neighborhood_id']) ? (int)$_POST['neighborhood_id'] : null;
        
        // Auto-detect neighborhood from location address if not set
        if (empty($neighborhoodId) && !empty($location)) {
            $neighborhoodId = $this->detectNeighborhoodFromAddress($location);
        }
        
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'category' => trim($_POST['category'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'location' => $location,
            'neighborhood_id' => $neighborhoodId,
            'event_date' => $_POST['event_date'] ?? '',
            'event_time' => $_POST['event_time'] ?? '',
            'max_participants' => !empty($_POST['max_participants']) ? (int)$_POST['max_participants'] : null
        ];
        
        // Validations
        $errors = [];
        
        if (empty($data['title'])) {
            $errors[] = 'Title is required.';
        }
        
        if (empty($data['category'])) {
            $errors[] = 'Activity type is required.';
        }
        
        if (empty($data['event_date'])) {
            $errors[] = 'Date is required.';
        }
        
        if (empty($data['event_time'])) {
            $errors[] = 'Time is required.';
        }
        
        if (!empty($errors)) {
            setFlashMessage('error', implode('<br>', $errors));
            header('Location: /views/events/edit.php?id=' . $eventId);
            exit;
        }
        
        if ($this->eventModel->update($eventId, $data)) {
            setFlashMessage('success', 'Event updated successfully.');
            header('Location: /views/events/show.php?id=' . $eventId);
        } else {
            setFlashMessage('error', 'Error updating event.');
            header('Location: /views/events/edit.php?id=' . $eventId);
        }
        exit;
    }
    
    /**
     * Delete event
     */
    public function delete(): void {
        requireLogin();
        
        $eventId = (int)($_GET['id'] ?? 0);
        
        if ($this->eventModel->delete($eventId, $_SESSION['user_id'])) {
            setFlashMessage('success', 'Event deleted successfully.');
        } else {
            setFlashMessage('error', 'You do not have permission to delete this event.');
        }
        
        header('Location: /views/events/index.php');
        exit;
    }
    
    /**
     * Join event
     */
    public function join(): void {
        requireLogin();
        
        $eventId = (int)($_GET['id'] ?? 0);
        $event = $this->eventModel->findById($eventId);
        
        if (!$event) {
            setFlashMessage('error', 'Event does not exist.');
            header('Location: /views/events/index.php');
            exit;
        }
        
        // Don't allow joining own events
        if ($event['user_id'] === $_SESSION['user_id']) {
            setFlashMessage('error', 'You cannot join your own event.');
            header('Location: /views/events/show.php?id=' . $eventId);
            exit;
        }
        
        if ($this->eventModel->join($eventId, $_SESSION['user_id'])) {
            setFlashMessage('success', 'You have joined the event!');
        } else {
            setFlashMessage('error', 'Could not join event. You may already be registered or the event may be full.');
        }
        
        header('Location: /views/events/show.php?id=' . $eventId);
        exit;
    }
    
    /**
     * Leave event
     */
    public function leave(): void {
        requireLogin();
        
        $eventId = (int)($_GET['id'] ?? 0);
        
        if ($this->eventModel->leave($eventId, $_SESSION['user_id'])) {
            setFlashMessage('success', 'You have left the event.');
        } else {
            setFlashMessage('error', 'Could not process request.');
        }
        
        header('Location: /views/events/show.php?id=' . $eventId);
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
            '80799' => 3, '80801' => 3, '80802' => 3,
            // Schwabing-West (4)
            '80796' => 4, '80797' => 4, '80798' => 4, '80804' => 4,
            // Au-Haidhausen (5)
            '81541' => 5, '81543' => 5, '81667' => 5, '81669' => 5, '81671' => 5,
            // Sendling (6)
            '81369' => 6, '81371' => 6, '81373' => 6,
            // Sendling-Westpark (7)
            '81377' => 7, '81379' => 7,
            // Schwanthalerhöhe (8)
            '80339' => 8,
            // Neuhausen-Nymphenburg (9)
            '80634' => 9, '80636' => 9, '80637' => 9, '80638' => 9, '80639' => 9,
            // Moosach (10)
            '80993' => 10, '80997' => 10, '80999' => 10,
            // Milbertshofen-Am Hart (11)
            '80807' => 11, '80809' => 11, '80937' => 11, '80939' => 11,
            // Schwabing-Freimann (12)
            '80803' => 12, '80805' => 12,
            // Bogenhausen (13)
            '81675' => 13, '81677' => 13, '81679' => 13, '81925' => 13, '81927' => 13, '81929' => 13,
            // Berg am Laim (14)
            '81673' => 14, '81735' => 14,
            // Trudering-Riem (15)
            '81825' => 15, '81827' => 15, '81829' => 15,
            // Ramersdorf-Perlach (16)
            '81539' => 16, '81549' => 16, '81737' => 16, '81739' => 16,
            // Obergiesing (17)
            '81547' => 17,
            // Untergiesing-Harlaching (18)
            '81545' => 18,
            // Thalkirchen-Obersendling-Forstenried-Fürstenried-Solln (19)
            '81475' => 19, '81476' => 19, '81477' => 19, '81479' => 19,
            // Hadern (20)
            '80689' => 20, '81375' => 20,
            // Pasing-Obermenzing (21)
            '81241' => 21, '81243' => 21, '81245' => 21, '81247' => 21, '81249' => 21,
            // Aubing-Lochhausen-Langwied (22)
            '81249' => 22,
            // Allach-Untermenzing (23)
            '80999' => 23,
            // Feldmoching-Hasenbergl (24)
            '80933' => 24, '80935' => 24, '80995' => 24,
            // Laim (25)
            '80686' => 25, '80687' => 25,
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
}

// Simple router
$action = $_GET['action'] ?? '';
$controller = new EventController();

switch ($action) {
    case 'create':
        $controller->create();
        break;
    case 'update':
        $controller->update();
        break;
    case 'delete':
        $controller->delete();
        break;
    case 'join':
        $controller->join();
        break;
    case 'leave':
        $controller->leave();
        break;
    default:
        header('Location: /views/events/index.php');
        exit;
}
