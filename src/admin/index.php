<?php
// src/admin/index.php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/../../config/conexion.php';

// Obtener estadísticas generales
$stats = [];

// Total de estudiantes
$sql_estudiantes = "SELECT COUNT(*) as total FROM estudiantes_posgrado";
$result = $mysqli->query($sql_estudiantes);
$stats['estudiantes'] = $result->fetch_assoc()['total'];

// Total de programas (especializaciones, maestrías, doctorados)
$sql_programas = "SELECT 
    SUM(CASE WHEN tipo_programa = 'especializacion' THEN 1 ELSE 0 END) as especializaciones,
    SUM(CASE WHEN tipo_programa = 'maestria' THEN 1 ELSE 0 END) as maestrias,
    SUM(CASE WHEN tipo_programa = 'doctorado' THEN 1 ELSE 0 END) as doctorados,
    COUNT(*) as total
FROM programas_posgrado WHERE estado = 'activo'";
$result = $mysqli->query($sql_programas);
$prog_stats = $result->fetch_assoc();
$stats['programas_total'] = $prog_stats['total'] ?? 0;
$stats['especializaciones'] = $prog_stats['especializaciones'] ?? 0;
$stats['maestrias'] = $prog_stats['maestrias'] ?? 0;
$stats['doctorados'] = $prog_stats['doctorados'] ?? 0;

// Total de materias
$sql_materias = "SELECT COUNT(*) as total FROM materias WHERE estado = 'activa'";
$result = $mysqli->query($sql_materias);
$stats['materias'] = $result->fetch_assoc()['total'];

// Pagos pendientes
$sql_pagos = "SELECT COUNT(*) as total FROM pagos_materias WHERE estado_pago = 'pendiente'";
$result = $mysqli->query($sql_pagos);
$stats['pagos_pendientes'] = $result->fetch_assoc()['total'];

// Ingresos totales del mes (simulado)
$stats['ingresos_mes'] = 12500.00;

// Obtener período actual
$periodo_actual = '2025-01';
$sql_periodo = "SELECT id_periodo FROM periodos_academicos WHERE codigo_periodo = ? AND activo = 1";
$stmt_periodo = $mysqli->prepare($sql_periodo);
$stmt_periodo->bind_param("s", $periodo_actual);
$stmt_periodo->execute();
$result_periodo = $stmt_periodo->get_result();
$id_periodo_actual = $result_periodo->fetch_assoc()['id_periodo'] ?? 1;

// Inscripciones del período actual
$sql_inscripciones = "SELECT COUNT(*) as total FROM inscripciones_materias WHERE id_periodo = ?";
$stmt_insc = $mysqli->prepare($sql_inscripciones);
$stmt_insc->bind_param("i", $id_periodo_actual);
$stmt_insc->execute();
$stats['inscripciones'] = $stmt_insc->get_result()->fetch_assoc()['total'];

// Últimos estudiantes registrados
$sql_ultimos = "SELECT u.nombre, u.apellido, u.email, u.foto_perfil, 
                       e.codigo_estudiante, e.fecha_registro_academico
                FROM estudiantes_posgrado e
                JOIN usuarios u ON e.id_usuario = u.id_usuario
                ORDER BY e.fecha_registro_academico DESC
                LIMIT 5";
$ultimos_estudiantes = $mysqli->query($sql_ultimos);

// Pagos recientes
$sql_pagos_recientes = "SELECT p.*, u.nombre, u.apellido, u.foto_perfil
                        FROM pagos_materias p
                        JOIN estudiantes_posgrado e ON p.id_estudiante = e.id_estudiante
                        JOIN usuarios u ON e.id_usuario = u.id_usuario
                        ORDER BY p.fecha_registro DESC
                        LIMIT 5";
$pagos_recientes = $mysqli->query($sql_pagos_recientes);

// Materias más cursadas
$sql_materias_populares = "SELECT m.nombre, m.id_materia AS codigo_materia, 
                                  COUNT(i.id_inscripcion) as total_inscripciones
                           FROM materias m
                           LEFT JOIN inscripciones_materias i ON m.id_materia = i.id_materia
                           WHERE m.estado = 'activa'
                           GROUP BY m.id_materia
                           ORDER BY total_inscripciones DESC
                           LIMIT 5";
$materias_populares = $mysqli->query($sql_materias_populares);

// Datos para gráficos
$meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'];
$inscripciones_mensuales = [45, 52, 68, 74, 89, 95];
$pagos_mensuales = [3800, 4200, 5100, 6300, 7200, 8500];
?>

<style>
    /* ========================================
       ESTILOS DEL DASHBOARD ADMIN
    ======================================== */
    
    .admin-dashboard {
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Bienvenida */
    .welcome-section {
        background: linear-gradient(135deg, #8B1E3F 0%, #6a1730 100%);
        border-radius: 25px;
        padding: 30px;
        margin-bottom: 30px;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 10px 30px rgba(139, 30, 63, 0.3);
    }

    .welcome-text h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .welcome-text p {
        font-size: 1rem;
        opacity: 0.9;
    }

    .welcome-date {
        background: rgba(255,255,255,0.1);
        padding: 15px 25px;
        border-radius: 15px;
        text-align: center;
        border: 1px solid rgba(242,169,0,0.3);
    }

    .welcome-date .day {
        font-size: 2rem;
        font-weight: 700;
        color: #F2A900;
    }

    .welcome-date .month {
        font-size: 1rem;
        text-transform: uppercase;
    }

    /* Tarjetas de estadísticas */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 25px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        border: 1px solid rgba(139, 30, 63, 0.1);
        display: flex;
        align-items: center;
        gap: 20px;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(139, 30, 63, 0.15);
        border-color: #8B1E3F;
    }

    .stat-icon {
        width: 70px;
        height: 70px;
        border-radius: 18px;
        background: linear-gradient(135deg, #8B1E3F 0%, #6a1730 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
    }

    .stat-info h3 {
        font-size: 2rem;
        color: #8B1E3F;
        margin-bottom: 5px;
        font-weight: 700;
    }

    .stat-info p {
        color: #6c757d;
        font-size: 0.9rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-trend {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 8px;
        font-size: 0.8rem;
    }

    .trend-up {
        color: #28a745;
    }

    .trend-down {
        color: #dc3545;
    }

    /* Grid de dos columnas */
    .dashboard-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
        margin-bottom: 30px;
    }

    /* Tarjetas de contenido */
    .content-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        border: 1px solid rgba(139, 30, 63, 0.1);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }

    .card-header h2 {
        font-size: 1.3rem;
        color: #8B1E3F;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-header h2 i {
        color: #F2A900;
    }

    .card-header .btn-more {
        color: #8B1E3F;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .card-header .btn-more:hover {
        color: #F2A900;
        transform: translateX(5px);
    }

    /* Tablas */
    .table-responsive {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        text-align: left;
        padding: 12px 10px;
        color: #6c757d;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        border-bottom: 2px solid #f0f0f0;
    }

    td {
        padding: 15px 10px;
        border-bottom: 1px solid #f0f0f0;
        color: #495057;
    }

    .user-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .user-avatar-small {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        object-fit: cover;
    }

    .user-info-small {
        line-height: 1.3;
    }

    .user-info-small .name {
        font-weight: 600;
        color: #8B1E3F;
    }

    .user-info-small .email {
        font-size: 0.8rem;
        color: #6c757d;
    }

    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-completado {
        background: #d4edda;
        color: #155724;
    }

    .status-pendiente {
        background: #fff3cd;
        color: #856404;
    }

    .status-activo {
        background: #cce5ff;
        color: #004085;
    }

    .amount {
        font-weight: 700;
        color: #8B1E3F;
    }

    /* Gráfico */
    .chart-container {
        height: 300px;
        margin-top: 20px;
    }

    /* Accesos rápidos */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-top: 20px;
    }

    .quick-action {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .quick-action:hover {
        background: white;
        border-color: #8B1E3F;
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(139,30,63,0.1);
    }

    .quick-action i {
        font-size: 2rem;
        color: #8B1E3F;
        margin-bottom: 10px;
    }

    .quick-action span {
        display: block;
        color: #495057;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .quick-action small {
        color: #6c757d;
        font-size: 0.75rem;
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-row {
            grid-template-columns: 1fr;
        }

        .welcome-section {
            flex-direction: column;
            text-align: center;
            gap: 20px;
        }
    }
</style>

<div class="main-content">
    <div class="admin-dashboard">
        <!-- Sección de bienvenida -->
        <div class="welcome-section">
            <div class="welcome-text">
                <h1>¡Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>!</h1>
                <p>Panel de Administración - Dirección de Investigación y Posgrado UNEFA</p>
            </div>
            <div class="welcome-date">
                <?php
                setlocale(LC_TIME, 'spanish');
                $dia = date('d');
                $mes = strftime('%B');
                $anio = date('Y');
                ?>
                <div class="day"><?php echo $dia; ?></div>
                <div class="month"><?php echo ucfirst($mes) . ' ' . $anio; ?></div>
            </div>
        </div>

        <!-- Tarjetas de estadísticas - AHORA CON DESGLOSE -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['estudiantes']; ?></h3>
                    <p>Estudiantes</p>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        <span>+12% este mes</span>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['especializaciones']; ?></h3>
                    <p>Especializaciones</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['maestrias']; ?></h3>
                    <p>Maestrías</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #F2A900 0%, #d48b00 100%);">
                    <i class="fas fa-microscope"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['doctorados']; ?></h3>
                    <p>Doctorados</p>
                </div>
            </div>
        </div>

        <!-- Fila 1: Gráfico y Accesos rápidos -->
        <div class="dashboard-row">
            <!-- Gráfico de inscripciones -->
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-line"></i> Inscripciones por Mes</h2>
                    <a href="modules/reportes/inscripciones.php" class="btn-more">Ver detalles <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="chart-container">
                    <canvas id="inscripcionesChart"></canvas>
                </div>
            </div>

            <!-- Accesos rápidos -->
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-bolt"></i> Accesos Rápidos</h2>
                </div>
                <div class="quick-actions">
                    <a href="modules/programas/agregar.php?tipo=especializacion" class="quick-action">
                        <i class="fas fa-plus-circle" style="color: #17a2b8;"></i>
                        <span>Nueva Especialización</span>
                        <small>1 año</small>
                    </a>
                    <a href="modules/programas/agregar.php?tipo=maestria" class="quick-action">
                        <i class="fas fa-plus-circle"></i>
                        <span>Nueva Maestría</span>
                        <small>2 años</small>
                    </a>
                    <a href="modules/programas/agregar.php?tipo=doctorado" class="quick-action">
                        <i class="fas fa-plus-circle" style="color: #F2A900;"></i>
                        <span>Nuevo Doctorado</span>
                        <small>3-4 años</small>
                    </a>
                    <a href="modules/reportes/estudiantes.php" class="quick-action">
                        <i class="fas fa-file-pdf"></i>
                        <span>Reportes</span>
                        <small>Generar informes</small>
                    </a>
                </div>

                <!-- Resumen de ingresos -->
                <div style="margin-top: 25px; padding: 20px; background: #f8f9fa; border-radius: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <span style="color: #6c757d;">Ingresos del mes:</span>
                        <span style="font-size: 1.5rem; font-weight: 700; color: #8B1E3F;">$<?php echo number_format($stats['ingresos_mes'], 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; color: #6c757d; font-size: 0.85rem;">
                        <span><i class="fas fa-circle" style="color: #28a745;"></i> Completados: 85%</span>
                        <span><i class="fas fa-circle" style="color: #ffc107;"></i> Pendientes: 15%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila 2: Últimos estudiantes y Pagos recientes -->
        <div class="dashboard-row">
            <!-- Últimos estudiantes registrados -->
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-user-graduate"></i> Últimos Estudiantes</h2>
                    <a href="modules/estudiantes/index.php" class="btn-more">Ver todos <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Código</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($ultimos_estudiantes->num_rows > 0): ?>
                                <?php while($est = $ultimos_estudiantes->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <?php
                                            $default_avatar = '/POSGRADO/public/images/default-avatar.png';
                                            $foto_filename = basename($est['foto_perfil'] ?? '');
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
                                            <?php if ($foto_filename !== '' && is_file(rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/POSGRADO/assets/uploads/perfil/' . $foto_filename)): ?>
                                                <img src="<?php echo htmlspecialchars($foto_url); ?>" 
                                                     alt="Avatar" 
                                                     class="user-avatar-small"
                                                     onerror="this.src='/POSGRADO/public/images/default-avatar.png'">
                                            <?php else: ?>
                                                <div class="user-avatar-small" style="display:flex;align-items:center;justify-content:center;background:#f0f0f0;color:#8B1E3F;font-size:1.1rem;">
                                                    <i class="fas fa-user-circle"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="user-info-small">
                                                <div class="name"><?php echo htmlspecialchars($est['nombre'] . ' ' . $est['apellido']); ?></div>
                                                <div class="email"><?php echo htmlspecialchars($est['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($est['codigo_estudiante'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($est['fecha_registro_academico'])); ?></td>
                                    <td><span class="status-badge status-activo">Activo</span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #6c757d;">No hay estudiantes registrados</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagos recientes -->
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-credit-card"></i> Pagos Recientes</h2>
                    <a href="modules/pagos/historial.php" class="btn-more">Ver todos <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Referencia</th>
                                <th>Monto</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pagos_recientes->num_rows > 0): ?>
                                <?php while($pago = $pagos_recientes->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                    <div class="user-cell">
                                            <?php
                                            $default_avatar = '/POSGRADO/public/images/default-avatar.png';
                                            $foto_filename = basename($pago['foto_perfil'] ?? '');
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
                                            <?php if ($foto_filename !== '' && is_file(rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/POSGRADO/assets/uploads/perfil/' . $foto_filename)): ?>
                                                <img src="<?php echo htmlspecialchars($foto_url); ?>" 
                                                     alt="Avatar" 
                                                     class="user-avatar-small"
                                                     onerror="this.src='/POSGRADO/public/images/default-avatar.png'">
                                            <?php else: ?>
                                                <div class="user-avatar-small" style="display:flex;align-items:center;justify-content:center;background:#f0f0f0;color:#8B1E3F;font-size:1.1rem;">
                                                    <i class="fas fa-user-circle"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="user-info-small">
                                                <div class="name"><?php echo htmlspecialchars($pago['nombre'] . ' ' . $pago['apellido']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($pago['numero_referencia']); ?></td>
                                    <td class="amount">$<?php echo number_format($pago['monto_final'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $pago['estado_pago'] == 'verificado' ? 'status-completado' : 'status-pendiente'; ?>">
                                            <?php echo ucfirst($pago['estado_pago']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #6c757d;">No hay pagos recientes</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Fila 3: Materias populares -->
        <div class="dashboard-row">
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-star"></i> Materias más cursadas</h2>
                    <a href="modules/materias/index.php" class="btn-more">Ver todas <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Materia</th>
                                <th>Inscripciones</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($materias_populares->num_rows > 0): ?>
                                <?php while($mat = $materias_populares->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($mat['codigo_materia']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($mat['nombre']); ?></td>
                                    <td><?php echo $mat['total_inscripciones']; ?> estudiantes</td>
                                    <td>
                                        <a href="modules/materias/ver.php?id=<?php echo urlencode($mat['codigo_materia']); ?>" style="color: #8B1E3F;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #6c757d;">No hay materias registradas</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Resumen de actividades recientes -->
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-clock"></i> Actividad Reciente</h2>
                </div>
                <div style="padding: 10px;">
                    <div style="display: flex; gap: 15px; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                        <i class="fas fa-user-plus" style="color: #8B1E3F; font-size: 1.5rem;"></i>
                        <div>
                            <strong>Nuevo estudiante inscrito</strong>
                            <p style="color: #6c757d; font-size: 0.85rem;">María González se inscribió en Maestría de Sistemas</p>
                            <small style="color: #999;">Hace 15 minutos</small>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                        <i class="fas fa-credit-card" style="color: #28a745; font-size: 1.5rem;"></i>
                        <div>
                            <strong>Pago verificado</strong>
                            <p style="color: #6c757d; font-size: 0.85rem;">Pago de $450.00 de Juan Pérez</p>
                            <small style="color: #999;">Hace 1 hora</small>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                        <i class="fas fa-book" style="color: #F2A900; font-size: 1.5rem;"></i>
                        <div>
                            <strong>Nueva materia agregada</strong>
                            <p style="color: #6c757d; font-size: 0.85rem;">"Inteligencia Artificial" - 4 créditos</p>
                            <small style="color: #999;">Hace 3 horas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Gráfico de inscripciones
    const ctx = document.getElementById('inscripcionesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($meses); ?>,
            datasets: [{
                label: 'Inscripciones',
                data: <?php echo json_encode($inscripciones_mensuales); ?>,
                borderColor: '#8B1E3F',
                backgroundColor: 'rgba(139, 30, 63, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                }
            }
        }
    });

    // Actualizar título de la página
    updatePageTitle('Dashboard Administrativo');
</script>

<?php
// Cerrar conexiones
if (isset($stmt_periodo)) $stmt_periodo->close();
if (isset($stmt_insc)) $stmt_insc->close();

require_once __DIR__ . '/includes/footer.php';
?>