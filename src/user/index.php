<?php
// src/user/index.php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../../config/conexion.php';

// Obtener datos del usuario
$id_usuario = $_SESSION['usuario_id'];

// Verificar si el usuario tiene registro en estudiantes_posgrado
$sql_check = "SELECT id_estudiante, codigo_estudiante, titulo_pregrado, universidad_egreso
              FROM estudiantes_posgrado WHERE id_usuario = ?";
$stmt_check = $mysqli->prepare($sql_check);
$stmt_check->bind_param("s", $id_usuario);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$tiene_registro = $result_check->num_rows > 0;
$estudiante = $result_check->fetch_assoc();

// Variables para el horario
$horario_por_dia = [];
$horas_disponibles = ['08:00', '09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'];
$dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$periodo_actual = '2025-01'; // Esto debería venir de configuración
$maestria_info = null;

// Si tiene registro, obtener información adicional y horario
if ($tiene_registro) {
    // Consultar materias en curso (para el contador)
    $sql_materias = "SELECT COUNT(*) as total 
                     FROM inscripciones_materias 
                     WHERE id_estudiante = ? AND estado IN ('inscrito', 'cursando')";
    $stmt_materias = $mysqli->prepare($sql_materias);
    $stmt_materias->bind_param("i", $estudiante['id_estudiante']);
    $stmt_materias->execute();
    $result_materias = $stmt_materias->get_result();
    $materias_curso = $result_materias->fetch_assoc()['total'];

    // Consultar pagos pendientes
    $sql_pagos = "SELECT COUNT(*) as total 
                  FROM pagos_materias 
                  WHERE id_estudiante = ? AND estado_pago = 'pendiente'";
    $stmt_pagos = $mysqli->prepare($sql_pagos);
    $stmt_pagos->bind_param("i", $estudiante['id_estudiante']);
    $stmt_pagos->execute();
    $result_pagos = $stmt_pagos->get_result();
    $pagos_pendientes = $result_pagos->fetch_assoc()['total'];
    
    // Determinar programa (antes 'maestria') del estudiante consultando inscripciones_programa
    $id_programa = null;
    if (function_exists('tableExists') && tableExists($mysqli, 'inscripciones_programa')) {
        $sql_prog = "SELECT id_programa FROM inscripciones_programa WHERE id_estudiante = ? AND estado = 'activo' ORDER BY fecha_inscripcion DESC LIMIT 1";
        $stmt_prog = $mysqli->prepare($sql_prog);
        $stmt_prog->bind_param("i", $estudiante['id_estudiante']);
        $stmt_prog->execute();
        $res_prog = $stmt_prog->get_result();
        if ($res_prog && $res_prog->num_rows > 0) {
            $id_programa = $res_prog->fetch_assoc()['id_programa'];
        }
    }

    // Obtener información del programa si existe
    if (!empty($id_programa) && function_exists('tableExists') && tableExists($mysqli, 'programas_posgrado')) {
        $sql_prog_info = "SELECT id_programa, codigo_programa, nombre as programa_nombre FROM programas_posgrado WHERE id_programa = ?";
        $stmt_prog_info = $mysqli->prepare($sql_prog_info);
        $stmt_prog_info->bind_param("s", $id_programa);
        $stmt_prog_info->execute();
        $result_prog_info = $stmt_prog_info->get_result();
        $maestria_info = $result_prog_info->fetch_assoc();
    }
    
    // Inicializar matriz de horario
    foreach ($dias_semana as $dia) {
        $horario_por_dia[$dia] = [];
        foreach ($horas_disponibles as $hora) {
            $horario_por_dia[$dia][$hora] = null;
        }
    }
    
    // Obtener el horario REAL de las materias en las que el estudiante está inscrito
        if (!empty($id_programa)) {
                $sql_horario = "SELECT 
                                                        h.*, 
                                                        m.nombre as materia_nombre, 
                                                        m.codigo_materia,
                                                        m.unidades_credito,
                                                        p.codigo_programa
                                                FROM horario h
                                                JOIN materias m ON h.id_materia = m.id_materia
                                                JOIN programas_posgrado p ON m.id_programa = p.id_programa
                                                JOIN inscripciones_materias i ON i.id_materia = m.id_materia
                                                WHERE i.id_estudiante = ? 
                                                    AND i.estado IN ('inscrito', 'cursando')
                                                    AND m.id_programa = ?
                                                    AND h.periodo_academico = ?
                                                    AND h.activo = 1
                                                ORDER BY 
                                                        FIELD(h.dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'),
                                                        h.hora_inicio";

                $stmt_horario = $mysqli->prepare($sql_horario);
                $stmt_horario->bind_param("iss", $estudiante['id_estudiante'], $id_programa, $periodo_actual);
                $stmt_horario->execute();
                $result_horario = $stmt_horario->get_result();
        
        // Llenar la matriz con los datos reales
        while ($clase = $result_horario->fetch_assoc()) {
            $hora = substr($clase['hora_inicio'], 0, 5); // Tomar solo HH:MM
            $dia = $clase['dia_semana'];
            
            // Verificar si la hora está en nuestro array de horas disponibles
            if (in_array($hora, $horas_disponibles)) {
                $hora_fin = substr($clase['hora_fin'], 0, 5);
                
                $horario_por_dia[$dia][$hora] = [
                    'codigo' => $clase['codigo_materia'],
                    'nombre' => $clase['materia_nombre'],
                    'profesor' => $clase['profesor'],
                    'salon' => $clase['salon'],
                    'hora_fin' => $hora_fin,
                    'creditos' => $clase['unidades_credito'],
                    'id_horario' => $clase['id_horario']
                ];
            }
        }
    }
}
?>

<style>
    /* Estilos del dashboard */
    .dashboard-container {
        display: grid;
        grid-template-columns: 1fr 1.2fr;
        gap: 30px;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Columna izquierda - Perfil */
    .profile-section {
        background: white;
        border-radius: 25px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        border: 1px solid rgba(139, 30, 63, 0.1);
    }

    .profile-header {
        display: flex;
        align-items: center;
        gap: 25px;
        margin-bottom: 30px;
        padding-bottom: 25px;
        border-bottom: 2px solid #f0f0f0;
    }

    .profile-avatar-large {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #8B1E3F;
        box-shadow: 0 10px 20px rgba(139, 30, 63, 0.2);
    }

    .profile-title h2 {
        font-size: 1.8rem;
        color: #8B1E3F;
        margin-bottom: 5px;
    }

    .profile-title p {
        color: #6c757d;
        font-size: 0.95rem;
    }

    .profile-title p i {
        color: #F2A900;
        margin-right: 5px;
    }

    .profile-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-bottom: 30px;
    }

    .stat-box {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 20px 15px;
        border-radius: 15px;
        text-align: center;
        border: 1px solid #e1e1e1;
        transition: all 0.3s ease;
    }

    .stat-box:hover {
        transform: translateY(-5px);
        border-color: #8B1E3F;
        box-shadow: 0 10px 20px rgba(139, 30, 63, 0.1);
    }

    .stat-box i {
        font-size: 2rem;
        color: #8B1E3F;
        margin-bottom: 10px;
    }

    .stat-box h3 {
        font-size: 1.8rem;
        color: #F2A900;
        margin-bottom: 5px;
    }

    .stat-box p {
        color: #495057;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .profile-details {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
    }

    .detail-row {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 0;
        border-bottom: 1px dashed #e1e1e1;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-icon {
        width: 40px;
        height: 40px;
        background: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #8B1E3F;
        font-size: 1.2rem;
        border: 1px solid #e1e1e1;
    }

    .detail-content {
        flex: 1;
    }

    .detail-label {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-value {
        font-size: 1rem;
        color: #495057;
        font-weight: 600;
    }

    .btn-profile {
        display: inline-block;
        padding: 15px 30px;
        background: linear-gradient(135deg, #8B1E3F 0%, #6a1730 100%);
        color: white;
        text-decoration: none;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        width: 100%;
        text-align: center;
        font-size: 1rem;
    }

    .btn-profile:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(139, 30, 63, 0.3);
    }

    /* Columna derecha - Horario Académico */
    .schedule-section {
        background: white;
        border-radius: 25px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        border: 1px solid rgba(139, 30, 63, 0.1);
    }

    .schedule-header {
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f0f0f0;
    }

    .schedule-header h2 {
        font-size: 1.5rem;
        color: #8B1E3F;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .schedule-header h2 i {
        color: #F2A900;
    }

    .schedule-header p {
        color: #6c757d;
        font-size: 0.9rem;
        margin: 5px 0;
    }

    .schedule-header p i {
        color: #F2A900;
        margin-right: 5px;
    }

    .maestria-badge {
        display: inline-block;
        background: #8B1E3F;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-top: 10px;
    }

    /* Días de la semana */
    .week-days {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 10px;
        margin-bottom: 20px;
    }

    .day-header {
        text-align: center;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 10px;
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
        border: 1px solid #e1e1e1;
        transition: all 0.3s ease;
    }

    .day-header.active {
        background: #8B1E3F;
        color: white;
        border-color: #8B1E3F;
    }

    /* Grid del horario */
    .schedule-grid {
        display: grid;
        grid-template-columns: 80px repeat(6, 1fr);
        gap: 8px;
        margin-bottom: 25px;
    }

    .time-slot {
        text-align: center;
        padding: 12px 5px;
        background: #f8f9fa;
        border-radius: 8px;
        color: #495057;
        font-weight: 600;
        font-size: 0.85rem;
        border: 1px solid #e1e1e1;
    }

    .schedule-cell {
        background: #fff5f5;
        border-radius: 8px;
        padding: 8px;
        min-height: 85px;
        border: 1px solid rgba(139, 30, 63, 0.1);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .schedule-cell:hover {
        background: #fff0f0;
        border-color: #8B1E3F;
        transform: scale(1.02);
        box-shadow: 0 5px 15px rgba(139, 30, 63, 0.1);
    }

    .schedule-cell.has-class {
        background: #e8f4fd;
        border-left: 4px solid #F2A900;
        border-top: 1px solid #8B1E3F;
        border-right: 1px solid #8B1E3F;
        border-bottom: 1px solid #8B1E3F;
    }

    .class-code {
        font-size: 0.7rem;
        color: #8B1E3F;
        font-weight: 700;
        margin-bottom: 3px;
        background: rgba(139, 30, 63, 0.1);
        display: inline-block;
        padding: 2px 6px;
        border-radius: 4px;
    }

    .class-name {
        font-size: 0.75rem;
        color: #495057;
        font-weight: 600;
        margin-bottom: 2px;
        line-height: 1.2;
    }

    .class-teacher {
        font-size: 0.65rem;
        color: #6c757d;
        margin-bottom: 2px;
    }

    .class-salon {
        font-size: 0.6rem;
        color: #8B1E3F;
        font-weight: 500;
        background: rgba(242, 169, 0, 0.1);
        display: inline-block;
        padding: 2px 6px;
        border-radius: 4px;
    }

    /* Leyenda */
    .schedule-legend {
        margin-top: 25px;
        padding-top: 20px;
        border-top: 2px solid #f0f0f0;
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.85rem;
        color: #495057;
    }

    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: 5px;
    }

    .legend-color.class {
        background: #e8f4fd;
        border-left: 4px solid #F2A900;
        border: 1px solid #8B1E3F;
    }

    .legend-color.empty {
        background: #fff5f5;
        border: 1px solid rgba(139, 30, 63, 0.1);
    }

    /* Tooltip para más información */
    .schedule-cell {
        position: relative;
    }

    .schedule-cell.has-class::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: #8B1E3F;
        color: white;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 0.75rem;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        pointer-events: none;
        z-index: 100;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .schedule-cell.has-class:hover::after {
        opacity: 1;
        visibility: visible;
        bottom: calc(100% + 10px);
    }

    /* Mensaje sin horario */
    .no-schedule {
        text-align: center;
        padding: 60px 20px;
        background: #f8f9fa;
        border-radius: 15px;
        color: #6c757d;
    }

    .no-schedule i {
        font-size: 4rem;
        color: #F2A900;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .no-schedule h3 {
        color: #8B1E3F;
        margin-bottom: 10px;
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .dashboard-container {
            grid-template-columns: 1fr;
            gap: 20px;
        }
    }

    @media (max-width: 768px) {
        .profile-header {
            flex-direction: column;
            text-align: center;
        }

        .week-days {
            grid-template-columns: repeat(3, 1fr);
        }

        .schedule-grid {
            overflow-x: auto;
            min-width: 100%;
        }
    }
</style>

<div class="main-content">
    <div class="dashboard-container">
        <!-- Columna Izquierda - Perfil del Usuario -->
        <div class="profile-section">
            <div class="profile-header">
                <?php
                // Evitar que la página quede 'colgando' si no hay foto: usar fallback inmediato
                $default_avatar = '/POSGRADO/public/images/default-avatar.png';
                $foto_filename = basename($_SESSION['usuario_foto'] ?? '');

                if ($foto_filename !== '') {
                    $server_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/POSGRADO/assets/uploads/perfil/' . $foto_filename;
                    if (is_file($server_path) && file_exists($server_path)) {
                        $foto_url = '/POSGRADO/assets/uploads/perfil/' . $foto_filename;
                    } else {
                        $foto_url = $default_avatar;
                    }
                } else {
                    $foto_url = $default_avatar;
                }
                ?>
                <img src="<?php echo htmlspecialchars($foto_url); ?>" 
                     alt="Avatar" 
                     class="profile-avatar-large"
                     onerror="this.src='/POSGRADO/public/images/default-avatar.png'">
                <div class="profile-title">
                    <h2><?php echo htmlspecialchars($_SESSION['usuario_nombre'] . ' ' . $_SESSION['usuario_apellido']); ?></h2>
                    <p><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($_SESSION['usuario_id']); ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($_SESSION['usuario_email']); ?></p>
                </div>
            </div>

            <?php if (!$tiene_registro): ?>
                <!-- NO TIENE REGISTRO ACADÉMICO - Mensaje grande -->
                <div style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-graduation-cap" style="font-size: 4rem; color: #F2A900; margin-bottom: 20px;"></i>
                    <h3 style="color: #8B1E3F; font-size: 1.8rem; margin-bottom: 15px;">¡Completa tu perfil académico!</h3>
                    <p style="color: #6c757d; font-size: 1rem; margin-bottom: 30px; max-width: 400px; margin-left: auto; margin-right: auto;">
                        Para poder inscribirte en materias y acceder a todas las funcionalidades del sistema, necesitas completar tu información académica.
                    </p>
                    <a href="modules/perfil/completar_perfil.php" class="btn-profile" style="width: auto; padding: 15px 50px;">
                        <i class="fas fa-edit"></i> COMPLETAR PERFIL ACADÉMICO
                    </a>
                </div>
            <?php else: ?>
                <!-- TIENE REGISTRO - Muestra estadísticas -->
                <div class="profile-stats">
                    <div class="stat-box">
                        <i class="fas fa-book-open"></i>
                        <h3><?php echo $materias_curso ?? 0; ?></h3>
                        <p>Materias en curso</p>
                    </div>
                    <div class="stat-box">
                        <i class="fas fa-credit-card"></i>
                        <h3><?php echo $pagos_pendientes ?? 0; ?></h3>
                        <p>Pagos pendientes</p>
                    </div>
                    <div class="stat-box">
                        <i class="fas fa-star"></i>
                        <h3><?php echo !empty($estudiante['codigo_estudiante']) ? 'Activo' : 'Inactivo'; ?></h3>
                        <p>Estado</p>
                    </div>
                </div>

                <!-- Detalles del perfil académico -->
                <div class="profile-details">
                    <div class="detail-row">
                        <div class="detail-icon"><i class="fas fa-qrcode"></i></div>
                        <div class="detail-content">
                            <div class="detail-label">Código de Estudiante</div>
                            <div class="detail-value"><?php echo htmlspecialchars($estudiante['codigo_estudiante'] ?? 'No asignado'); ?></div>
                        </div>
                    </div>
                    <?php if (!empty($maestria_info)): ?>
                    <div class="detail-row">
                        <div class="detail-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div class="detail-content">
                            <div class="detail-label">Programa</div>
                            <div class="detail-value"><?php echo htmlspecialchars(($maestria_info['codigo_programa'] ?? $maestria_info['codigo_maestria'] ?? '') . ' - ' . ($maestria_info['programa_nombre'] ?? $maestria_info['maestria_nombre'] ?? $maestria_info['nombre'] ?? '')); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <div class="detail-icon"><i class="fas fa-university"></i></div>
                        <div class="detail-content">
                            <div class="detail-label">Título de Pregrado</div>
                            <div class="detail-value"><?php echo htmlspecialchars($estudiante['titulo_pregrado'] ?? 'No especificado'); ?></div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-icon"><i class="fas fa-building"></i></div>
                        <div class="detail-content">
                            <div class="detail-label">Universidad de Egreso</div>
                            <div class="detail-value"><?php echo htmlspecialchars($estudiante['universidad_egreso'] ?? 'No especificada'); ?></div>
                        </div>
                    </div>
                </div>

                <a href="modules/perfil/perfil.php" class="btn-profile">
                    <i class="fas fa-user-edit"></i> VER MI PERFIL COMPLETO
                </a>
            <?php endif; ?>
        </div>

        <!-- Columna Derecha - Horario Académico (Solo si tiene registro) -->
        <?php if ($tiene_registro): ?>
        <div class="schedule-section">
            <div class="schedule-header">
                <h2>
                    <i class="fas fa-calendar-alt"></i> 
                    Mi Horario 
                </h2>
                <p><i class="fas fa-info-circle"></i> Período Académico <?php echo $periodo_actual; ?></p>
                <?php if (!empty($maestria_info)): ?>
                <div class="maestria-badge">
                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($maestria_info['codigo_programa'] ?? $maestria_info['codigo_maestria'] ?? ''); ?> - <?php echo htmlspecialchars($maestria_info['programa_nombre'] ?? $maestria_info['maestria_nombre'] ?? $maestria_info['nombre'] ?? ''); ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Verificar si hay materias inscritas -->
            <?php
            $tiene_materias = false;
            foreach ($horario_por_dia as $dia => $horas) {
                foreach ($horas as $hora => $clase) {
                    if ($clase !== null) {
                        $tiene_materias = true;
                        break 2;
                    }
                }
            }
            ?>

            <?php if ($tiene_materias): ?>
                <!-- Días de la semana -->
                <div class="week-days">
                    <?php foreach ($dias_semana as $index => $dia): ?>
                    <div class="day-header <?php echo $index === 0 ? 'active' : ''; ?>"><?php echo $dia; ?></div>
                    <?php endforeach; ?>
                </div>

                <!-- Grid de horario -->
                <div class="schedule-grid">
                    <?php foreach ($horas_disponibles as $hora): ?>
                        <!-- Fila por cada hora -->
                        <div class="time-slot"><?php echo $hora; ?></div>
                        
                        <?php foreach ($dias_semana as $dia): ?>
                            <?php if (isset($horario_por_dia[$dia][$hora]) && $horario_por_dia[$dia][$hora] !== null): 
                                $clase = $horario_por_dia[$dia][$hora];
                                $tooltip = "Salón: " . ($clase['salon'] ?? 'No asignado') . " | Créditos: " . $clase['creditos'] . " | Hasta: " . $clase['hora_fin'];
                            ?>
                            <div class="schedule-cell has-class" data-tooltip="<?php echo htmlspecialchars($tooltip); ?>">
                                <div class="class-code"><?php echo htmlspecialchars($clase['codigo']); ?></div>
                                <div class="class-name"><?php echo htmlspecialchars($clase['nombre']); ?></div>
                                <div class="class-teacher"><?php echo htmlspecialchars($clase['profesor']); ?></div>
                                <?php if (!empty($clase['salon'])): ?>
                                <div class="class-salon"><?php echo htmlspecialchars($clase['salon']); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="schedule-cell"></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                    <?php endforeach; ?>
                </div>

                <!-- Leyenda -->
                <div class="schedule-legend">
                    <div class="legend-item">
                        <div class="legend-color class"></div>
                        <span>Clases asignadas</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color empty"></div>
                        <span>Horario disponible</span>
                    </div>
                    <div class="legend-item">
                        <i class="fas fa-info-circle" style="color: #8B1E3F;"></i>
                        <span>Pasa el mouse sobre las clases para más detalles</span>
                    </div>
                </div>
                
                <!-- Resumen de materias inscritas -->
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px; text-align: center;">
                    <p style="color: #495057; margin: 0;">
                        <i class="fas fa-info-circle" style="color: #8B1E3F;"></i> 
                        Actualmente tienes <strong><?php echo $materias_curso ?? 0; ?></strong> materia(s) inscrita(s) en este período académico.
                        <?php if (!empty($maestria_info)): ?>
                        <br><small>Código del Programa: <strong><?php echo htmlspecialchars($maestria_info['codigo_programa'] ?? $maestria_info['codigo_maestria'] ?? ''); ?></strong></small>
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <!-- No hay materias inscritas -->
                <div class="no-schedule">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No tienes materias inscritas</h3>
                    <p>Aún no te has inscrito en ninguna materia para el período <?php echo $periodo_actual; ?>.</p>
                    <?php if (!empty($maestria_info)): ?>
                    <p style="margin-top: 15px; font-size: 0.9rem;">
                        <i class="fas fa-graduation-cap" style="color: #8B1E3F;"></i> 
                        Tu programa: <strong><?php echo htmlspecialchars($maestria_info['codigo_programa'] ?? $maestria_info['codigo_maestria'] ?? ''); ?></strong> - <?php echo htmlspecialchars($maestria_info['programa_nombre'] ?? $maestria_info['maestria_nombre'] ?? $maestria_info['nombre'] ?? ''); ?>
                    </p>
                    <?php endif; ?>
                    <a href="modules/inscripcion/inscripcion.php" class="btn-profile" style="width: auto; padding: 12px 30px; margin-top: 20px; display: inline-block;">
                        <i class="fas fa-pencil-alt"></i> INSCRIBIRME AHORA
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Cerrar conexiones
if (isset($stmt_check)) $stmt_check->close();
if (isset($stmt_materias)) $stmt_materias->close();
if (isset($stmt_pagos)) $stmt_pagos->close();
if (isset($stmt_horario)) $stmt_horario->close();
if (isset($stmt_maestria)) $stmt_maestria->close();

require_once __DIR__ . '/../includes/footer.php';
?>