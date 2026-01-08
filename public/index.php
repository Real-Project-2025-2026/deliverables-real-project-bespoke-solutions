<?php
/**
 * Punto de entrada principal - Muniverse
 * Redirige a la página de eventos o login según el estado de sesión
 */

session_start();
require_once __DIR__ . '/config/db.php';

// Verificar si hay sesión activa
if (isset($_SESSION['user_id'])) {
    header('Location: views/events/index.php');
} else {
    header('Location: views/auth/login.php');
}
exit;
