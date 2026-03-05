<?php
// logout.php
session_start();

// Verificar que el logout viene desde un formulario POST con token CSRF
// (Esta es opcional pero recomendada para mayor seguridad)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF si está configurado
    if (isset($_SESSION['csrf_token']) && isset($_POST['csrf_token'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('Token CSRF inválido');
        }
    }
    
    // Destruir sesión
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }
    
    session_destroy();
    
    // Redireccionar
    header('Location: ../auth/login.php?logout=success');
    exit();
} else {
    // Si no es POST, redirigir al inicio o mostrar formulario
    header('Location: ../auth/login.php');
    exit();
}
?>