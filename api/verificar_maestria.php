<?php
// /posgrado/api/verificar_maestria.php
session_start();
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'tiene_maestria' => false,
        'error' => 'Usuario no autenticado'
    ]);
    exit();
}

$id_usuario = $_SESSION['usuario_id'];

try {
    if (!$mysqli) throw new Exception('No hay conexión a la base de datos');

    // Obtener id_estudiante
    if (!tableExists($mysqli, 'estudiantes_posgrado')) {
        echo json_encode(['success' => true, 'tiene_maestria' => false, 'mensaje' => 'Tabla estudiantes_posgrado no disponible']);
        exit();
    }

    $sql_estudiante = "SELECT id_estudiante FROM estudiantes_posgrado WHERE id_usuario = ?";
    $stmt_est = $mysqli->prepare($sql_estudiante);
    $stmt_est->bind_param("s", $id_usuario);
    $stmt_est->execute();
    $res_est = $stmt_est->get_result();

    if ($res_est->num_rows === 0) {
        echo json_encode(['success' => true, 'tiene_maestria' => false, 'mensaje' => 'Perfil académico incompleto']);
        exit();
    }

    $estudiante = $res_est->fetch_assoc();
    $id_estudiante = $estudiante['id_estudiante'];

    // Si la tabla de inscripciones de programa no existe, responder false
    if (!tableExists($mysqli, 'inscripciones_programa')) {
        echo json_encode(['success' => true, 'tiene_maestria' => false, 'mensaje' => 'Sistema sin inscripción de programas']);
        exit();
    }

    // Contar inscripciones activas
    $sql_count = "SELECT COUNT(*) AS total FROM inscripciones_programa WHERE id_estudiante = ? AND estado = 'activo'";
    $stmt_cnt = $mysqli->prepare($sql_count);
    $stmt_cnt->bind_param("i", $id_estudiante);
    $stmt_cnt->execute();
    $res_cnt = $stmt_cnt->get_result();
    $row = $res_cnt->fetch_assoc();
    $tiene = ($row['total'] > 0);

    $info = null;
    if ($tiene) {
        // Obtener info del programa (si existe la tabla programas_posgrado)
        if (tableExists($mysqli, 'programas_posgrado')) {
            $sql_info = "SELECT ip.*, p.nombre AS programa_nombre, p.codigo_programa
                         FROM inscripciones_programa ip
                         JOIN programas_posgrado p ON ip.id_programa = p.id_programa
                         WHERE ip.id_estudiante = ? AND ip.estado = 'activo' LIMIT 1";
            $stmt_info = $mysqli->prepare($sql_info);
            $stmt_info->bind_param("i", $id_estudiante);
            $stmt_info->execute();
            $res_info = $stmt_info->get_result();
            $info = $res_info->fetch_assoc();
            // Añadir alias para compatibilidad con código antiguo
            if ($info) {
                if (!isset($info['codigo_maestria']) && isset($info['codigo_programa'])) {
                    $info['codigo_maestria'] = $info['codigo_programa'];
                }
                if (!isset($info['maestria_nombre']) && isset($info['programa_nombre'])) {
                    $info['maestria_nombre'] = $info['programa_nombre'];
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'tiene_maestria' => $tiene,
        'info_maestria' => $info,
        'id_estudiante' => $id_estudiante
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'tiene_maestria' => false, 'error' => $e->getMessage()]);
}

?>
