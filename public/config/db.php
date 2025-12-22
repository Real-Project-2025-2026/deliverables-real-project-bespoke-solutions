<?php
/**
 * Configuración de conexión a la base de datos MySQL
 * 
 * INSTRUCCIONES:
 * 1. Modifica estos valores con los datos de tu hosting en DonDominio
 * 2. Normalmente encontrarás estos datos en el panel de phpMyAdmin
 */

define('DB_HOST', '');        		// Servidor MySQL
define('DB_NAME', '');        		// Nombre de tu base de datos
define('DB_USER', '');            	// Usuario de MySQL
define('DB_PASS', '');            	// Contraseña de MySQL
define('DB_CHARSET', 'utf8mb4');

/**
 * Función para obtener conexión PDO
 * @return PDO
 */
function getConnection(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

/**
 * Función auxiliar para verificar sesión
 */
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /views/auth/login.php');
        exit;
    }
}

/**
 * Función para obtener la URL base del sitio
 */
function getBaseUrl(): string {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host;
}

/**
 * Función para sanitizar salida HTML
 */
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Obtener mensaje flash y limpiarlo
 */
function getFlashMessage(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Establecer mensaje flash
 */
function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
