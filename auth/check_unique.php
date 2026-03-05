<?php
// auth/check_unique.php - Verifica en tiempo real si email o documento ya existen
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

// Acepta JSON o form-data
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$field = $input['field'] ?? '';
$value = trim($input['value'] ?? '');

if (empty($field) || $value === '') {
    echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
    exit;
}

try {
    if ($field === 'email') {
        $exists = emailExiste($value, $mysqli);
        echo json_encode(['success' => true, 'exists' => (bool)$exists]);
        exit;
    }

    if ($field === 'id_usuario') {
        $clean = preg_replace('/\D/', '', $value);
        if ($clean === '') {
            echo json_encode(['success' => true, 'exists' => false]);
            exit;
        }
        $exists = cedulaExiste($clean, $mysqli);
        echo json_encode(['success' => true, 'exists' => (bool)$exists]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Campo no soportado']);
} catch (Exception $e) {
    error_log('check_unique error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}

exit;
?>
