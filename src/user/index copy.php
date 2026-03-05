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

// Si tiene registro, obtener información adicional
if ($tiene_registro) {
    // Consultar materias en curso (para el contador)
    $sql_materias = "SELECT COUNT(*) as total 
                     FROM inscripciones_materias 
                     WHERE id_estudiante = ? AND estado = 'cursando'";
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
    }

    .schedule-header p {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .schedule-header p i {
        color: #F2A900;
        margin-right: 5px;
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
        padding: 10px;
        background: #f8f9fa;
        border-radius: 10px;
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
        border: 1px solid #e1e1e1;
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
        min-height: 80px;
        border: 1px solid rgba(139, 30, 63, 0.1);
        transition: all 0.3s ease;
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
    }

    .class-code {
        font-size: 0.75rem;
        color: #8B1E3F;
        font-weight: 700;
        margin-bottom: 3px;
    }

    .class-name {
        font-size: 0.75rem;
        color: #495057;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .class-teacher {
        font-size: 0.65rem;
        color: #6c757d;
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
    }

    .legend-color.empty {
        background: #fff5f5;
        border: 1px solid rgba(139, 30, 63, 0.1);
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
                 <img src="<?php echo $_SESSION['usuario_foto_url'] ?? '/POSGRADO/public/images/default-avatar.png'; ?>" 
                     alt="Avatar" 
                     class="profile-avatar-large"
                     onerror="this.src='/POSGRADO/public/images/default-avatar.png'">
                <div class="profile-title">
                    <h2><?php echo $_SESSION['usuario_nombre'] . ' ' . $_SESSION['usuario_apellido']; ?></h2>
                    <p><i class="fas fa-id-card"></i> <?php echo $_SESSION['usuario_id']; ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo $_SESSION['usuario_email']; ?></p>
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
                        <h3><?php echo $estudiante['codigo_estudiante'] ? 'Activo' : 'Inactivo'; ?></h3>
                        <p>Estado</p>
                    </div>
                </div>

                <!-- Detalles del perfil académico -->
                <div class="profile-details">
                    <div class="detail-row">
                        <div class="detail-icon"><i class="fas fa-qrcode"></i></div>
                        <div class="detail-content">
                            <div class="detail-label">Código de Estudiante</div>
                            <div class="detail-value"><?php echo $estudiante['codigo_estudiante'] ?? 'No asignado'; ?></div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-icon"><i class="fas fa-university"></i></div>
                        <div class="detail-content">
                            <div class="detail-label">Título de Pregrado</div>
                            <div class="detail-value"><?php echo $estudiante['titulo_pregrado'] ?? 'No especificado'; ?></div>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-icon"><i class="fas fa-building"></i></div>
                        <div class="detail-content">
                            <div class="detail-label">Universidad de Egreso</div>
                            <div class="detail-value"><?php echo $estudiante['universidad_egreso'] ?? 'No especificada'; ?></div>
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
                <h2><i class="fas fa-calendar-alt" style="color: #F2A900; margin-right: 10px;"></i> Horario Académico</h2>
                <p><i class="fas fa-info-circle"></i> Período Académico 2025-01 (Enero - Marzo)</p>
            </div>

            <!-- Días de la semana -->
            <div class="week-days">
                <div class="day-header active">Lunes</div>
                <div class="day-header">Martes</div>
                <div class="day-header">Miércoles</div>
                <div class="day-header">Jueves</div>
                <div class="day-header">Viernes</div>
                <div class="day-header">Sábado</div>
            </div>

            <!-- Grid de horario -->
            <div class="schedule-grid">
                <!-- Horas -->
                <div class="time-slot">08:00</div>
                <div class="schedule-cell has-class">
                    <div class="class-code">MAT-101</div>
                    <div class="class-name">Matemáticas I</div>
                    <div class="class-teacher">Prof. José Martínez</div>
                </div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell has-class">
                    <div class="class-code">FIS-202</div>
                    <div class="class-name">Física General</div>
                    <div class="class-teacher">Prof. Ana Rodríguez</div>
                </div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>

                <div class="time-slot">09:00</div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell has-class">
                    <div class="class-code">PROG-101</div>
                    <div class="class-name">Programación I</div>
                    <div class="class-teacher">Prof. Carlos Méndez</div>
                </div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>

                <div class="time-slot">10:00</div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell has-class">
                    <div class="class-code">BD-201</div>
                    <div class="class-name">Bases de Datos</div>
                    <div class="class-teacher">Prof. Laura Silva</div>
                </div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell has-class">
                    <div class="class-code">ING-301</div>
                    <div class="class-name">Inglés Técnico</div>
                    <div class="class-teacher">Prof. Miguel Torres</div>
                </div>

                <div class="time-slot">11:00</div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell has-class">
                    <div class="class-code">EST-101</div>
                    <div class="class-name">Estadística</div>
                    <div class="class-teacher">Prof. Diana Pérez</div>
                </div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>

                <div class="time-slot">14:00</div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell has-class">
                    <div class="class-code">RED-202</div>
                    <div class="class-name">Redes I</div>
                    <div class="class-teacher">Prof. Roberto Gómez</div>
                </div>
                <div class="schedule-cell"></div>

                <div class="time-slot">15:00</div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell has-class">
                    <div class="class-code">SO-301</div>
                    <div class="class-name">Sistemas Operativos</div>
                    <div class="class-teacher">Prof. Andrea Castro</div>
                </div>

                <div class="time-slot">16:00</div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
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
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Cerrar conexiones
if (isset($stmt_check)) $stmt_check->close();
if (isset($stmt_materias)) $stmt_materias->close();
if (isset($stmt_pagos)) $stmt_pagos->close();

require_once __DIR__ . '/../includes/footer.php';
?>