<?php
// config/conexion.php - Versión para UNEFA Postgrado

$host = 'localhost';
$usuario = 'root';
$contrasena = '1234';
$nombre_db = 'posgrado'; // Cambiado a posgrado
$puerto = 3307; // cambiado a 3307 para evitar conflictos con MySQL estándar en 3306

// ========== CONEXIÓN MYSQLI ==========
$mysqli = @new mysqli($host, $usuario, $contrasena, $nombre_db, $puerto);

if ($mysqli->connect_error) {
    error_log("Error MySQLi: " . $mysqli->connect_error);
    $mysqli = null;
    $conn = null;
} else {
    $mysqli->set_charset("utf8mb4");
    error_log("✓ Conexión MySQLi establecida a base '$nombre_db' en puerto $puerto");
}

$conn = $mysqli; // Alias para compatibilidad

// ========== CONEXIÓN PDO ==========
try {
    $pdo = new PDO(
        "mysql:host=$host;port=$puerto;dbname=$nombre_db;charset=utf8mb4",
        $usuario,
        $contrasena,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    error_log("✓ Conexión PDO establecida");
} catch (PDOException $e) {
    error_log("Error PDO: " . $e->getMessage());
    $pdo = null;
}

    /**
     * Comprueba si una tabla existe en la base de datos (mysqli)
     */
    function tableExists($mysqli, $tableName) {
        if (!$mysqli) return false;
        $tableName = $mysqli->real_escape_string($tableName);
        $db = $mysqli->real_escape_string($GLOBALS['nombre_db'] ?? 'posgrado');
        $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = '$db' AND table_name = '$tableName' LIMIT 1";
        $res = $mysqli->query($sql);
        return ($res && $res->num_rows > 0);
    }
?>