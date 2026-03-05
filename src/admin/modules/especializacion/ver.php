<?php
// src/admin/modules/especializacion/ver.php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/nav.php';
require_once __DIR__ . '/../../../../config/conexion.php';

// Obtener el ID del programa
$id_programa = $_GET['id'] ?? '';
if (empty($id_programa)) {
    header('Location: especializacion.php?error=ID no especificado');
    exit();
}

// Obtener información del programa
$sql_programa = "SELECT * FROM programas_posgrado WHERE id_programa = ?";
$stmt_programa = $mysqli->prepare($sql_programa);
$stmt_programa->bind_param("s", $id_programa);
$stmt_programa->execute();
$result_programa = $stmt_programa->get_result();
$programa = $result_programa->fetch_assoc();

if (!$programa) {
    header('Location: especializacion.php?error=Programa no encontrado');
    exit();
}

// Obtener materias del programa
$sql_materias = "SELECT * FROM materias WHERE id_programa = ? ORDER BY semestre, nombre";
$stmt_materias = $mysqli->prepare($sql_materias);
$stmt_materias->bind_param("s", $id_programa);
$stmt_materias->execute();
$materias = $stmt_materias->get_result();

// Contar materias
$total_materias = $materias->num_rows;

// Calcular total de créditos (por si acaso)
$sql_creditos = "SELECT SUM(unidades_credito) as total FROM materias WHERE id_programa = ?";
$stmt_creditos = $mysqli->prepare($sql_creditos);
$stmt_creditos->bind_param("s", $id_programa);
$stmt_creditos->execute();
$total_creditos = $stmt_creditos->get_result()->fetch_assoc()['total'] ?? 0;

// Obtener estudiantes inscritos en este programa
$sql_estudiantes = "SELECT COUNT(*) as total FROM inscripciones_programa WHERE id_programa = ? AND estado = 'activo'";
$stmt_estudiantes = $mysqli->prepare($sql_estudiantes);
$stmt_estudiantes->bind_param("s", $id_programa);
$stmt_estudiantes->execute();
$total_estudiantes = $stmt_estudiantes->get_result()->fetch_assoc()['total'] ?? 0;

// Colores según el tipo de programa
$color_primario = '#17a2b8'; // Especialización (turquesa)
$icono = 'fas fa-certificate';
$tipo_texto = 'Especialización';

if ($programa['tipo_programa'] == 'maestria') {
    $color_primario = '#8B1E3F'; // Rojo UNEFA
    $icono = 'fas fa-graduation-cap';
    $tipo_texto = 'Maestría';
} elseif ($programa['tipo_programa'] == 'doctorado') {
    $color_primario = '#F2A900'; // Amarillo
    $icono = 'fas fa-microscope';
    $tipo_texto = 'Doctorado';
}
?>

<style>
    /* ========================================
       ESTILOS PARA VER ESPECIALIZACIÓN
    ======================================== */
    .ver-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Cabecera */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        background: white;
        padding: 25px 30px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        border-left: 6px solid <?php echo $color_primario; ?>;
    }

    .header-title h1 {
        font-size: 1.8rem;
        color: <?php echo $color_primario; ?>;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .header-title p {
        color: #6c757d;
        margin-top: 5px;
    }

    .header-actions {
        display: flex;
        gap: 10px;
    }

    .btn-accion {
        padding: 12px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .btn-editar {
        background: #6c757d;
        color: white;
    }

    .btn-editar:hover {
        background: #5a6268;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
    }

    .btn-agregar {
        background: <?php echo $color_primario; ?>;
        color: white;
    }

    .btn-agregar:hover {
        filter: brightness(0.9);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(23, 162, 184, 0.3);
    }

    .btn-eliminar {
        background: #dc3545;
        color: white;
    }

    .btn-eliminar:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }

    /* Grid de información */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .info-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        border: 1px solid #e1e1e1;
        display: flex;
        align-items: center;
        gap: 20px;
        transition: all 0.3s ease;
    }

    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(23, 162, 184, 0.15);
        border-color: <?php echo $color_primario; ?>;
    }

    .info-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        background: <?php echo $color_primario; ?>;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.8rem;
    }

    .info-content h3 {
        font-size: 2rem;
        color: <?php echo $color_primario; ?>;
        margin-bottom: 5px;
        font-weight: 700;
    }

    .info-content p {
        color: #6c757d;
        font-size: 0.9rem;
        font-weight: 500;
    }

    /* Detalles del programa */
    .detalles-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        border: 1px solid #e1e1e1;
        margin-bottom: 30px;
        border-left: 4px solid <?php echo $color_primario; ?>;
    }

    .detalles-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }

    .detalles-header h2 {
        font-size: 1.3rem;
        color: <?php echo $color_primario; ?>;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .detalles-header h2 i {
        color: <?php echo $color_primario; ?>;
    }

    .detalles-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .detalle-item {
        display: flex;
        gap: 15px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
    }

    .detalle-icon {
        width: 40px;
        height: 40px;
        background: white;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: <?php echo $color_primario; ?>;
        font-size: 1.2rem;
        border: 1px solid #e1e1e1;
    }

    .detalle-contenido {
        flex: 1;
    }

    .detalle-etiqueta {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 3px;
    }

    .detalle-valor {
        font-size: 1.1rem;
        color: #495057;
        font-weight: 600;
    }

    .detalle-valor small {
        font-size: 0.85rem;
        color: #6c757d;
        font-weight: normal;
    }

    /* Materias */
    .materias-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        border: 1px solid #e1e1e1;
        margin-bottom: 30px;
    }

    .materias-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }

    .materias-header h2 {
        font-size: 1.3rem;
        color: <?php echo $color_primario; ?>;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .materias-header h2 i {
        color: <?php echo $color_primario; ?>;
    }

    .badge-count {
        background: <?php echo $color_primario; ?>;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .table-responsive {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        text-align: left;
        padding: 15px 10px;
        background: #f8f9fa;
        color: <?php echo $color_primario; ?>;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        border-bottom: 2px solid #e1e1e1;
    }

    td {
        padding: 15px 10px;
        border-bottom: 1px solid #e1e1e1;
        color: #495057;
    }

    .codigo-materia {
        font-family: monospace;
        font-weight: 600;
        color: <?php echo $color_primario; ?>;
        background: rgba(23, 162, 184, 0.1);
        padding: 4px 8px;
        border-radius: 5px;
        display: inline-block;
    }

    .creditos-badge {
        background: <?php echo $color_primario; ?>;
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }

    .estado-activo {
        background: #d4edda;
        color: #155724;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }

    .acciones-cell {
        display: flex;
        gap: 8px;
    }

    .btn-accion-tabla {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .btn-ver-materia {
        background: <?php echo $color_primario; ?>;
    }

    .btn-editar-materia {
        background: #6c757d;
    }

    .btn-eliminar-materia {
        background: #dc3545;
    }

    .btn-accion-tabla:hover {
        transform: scale(1.1);
        filter: brightness(1.1);
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: #f8f9fa;
        border-radius: 15px;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 4rem;
        color: <?php echo $color_primario; ?>;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .empty-state h3 {
        color: <?php echo $color_primario; ?>;
        margin-bottom: 10px;
    }

    .empty-state .btn {
        display: inline-block;
        margin-top: 20px;
        padding: 12px 30px;
        background: <?php echo $color_primario; ?>;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
    }

    /* Mensajes */
    .message {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .success {
        background: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }

    .error {
        background: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }

        .detalles-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="main-content">
    <div class="ver-container">
        <!-- Mensajes -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                Materia agregada exitosamente
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                Programa actualizado exitosamente
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                Materia eliminada exitosamente
            </div>
        <?php elseif (!empty($_GET['error'])): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Cabecera -->
        <div class="page-header">
            <div class="header-title">
                <h1>
                    <i class="<?php echo $icono; ?>"></i>
                    <?php echo htmlspecialchars($programa['nombre']); ?>
                </h1>
                <p>
                    <i class="fas fa-qrcode"></i> Código: <strong><?php echo htmlspecialchars($programa['codigo_programa']); ?></strong>
                    | <i class="fas fa-tag"></i> <?php echo $tipo_texto; ?>
                </p>
            </div>
            <div class="header-actions">
                <a href="editar.php?id=<?php echo urlencode($id_programa); ?>" class="btn-accion btn-editar">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="/POSGRADO/src/admin/modules/materias/agregar_materia.php?programa_id=<?php echo urlencode($id_programa); ?>" class="btn-accion btn-agregar">
                    <i class="fas fa-plus-circle"></i> Agregar Materia
                </a>
                <a href="eliminar.php?id=<?php echo urlencode($id_programa); ?>" class="btn-accion btn-eliminar" onclick="return confirm('¿Estás seguro de eliminar este programa?')">
                    <i class="fas fa-trash"></i> Eliminar
                </a>
            </div>
        </div>

        <!-- Grid de estadísticas -->
        <div class="info-grid">
            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="info-content">
                    <h3><?php echo $total_materias; ?></h3>
                    <p>Materias</p>
                </div>
            </div>
            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="info-content">
                    <h3><?php echo $total_creditos; ?></h3>
                    <p>Créditos totales</p>
                </div>
            </div>
            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="info-content">
                    <h3><?php echo $total_estudiantes; ?></h3>
                    <p>Estudiantes inscritos</p>
                </div>
            </div>
            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="info-content">
                    <h3><?php echo $programa['duracion_meses']; ?></h3>
                    <p>Meses de duración</p>
                </div>
            </div>
        </div>

        <!-- Detalles del programa -->
        <div class="detalles-card">
            <div class="detalles-header">
                <h2>
                    <i class="fas fa-info-circle"></i>
                    Información detallada
                </h2>
            </div>
            <div class="detalles-grid">
                <div class="detalle-item">
                    <div class="detalle-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="detalle-contenido">
                        <div class="detalle-etiqueta">Tipo de programa</div>
                        <div class="detalle-valor"><?php echo $tipo_texto; ?></div>
                    </div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div class="detalle-contenido">
                        <div class="detalle-etiqueta">Código</div>
                        <div class="detalle-valor"><?php echo htmlspecialchars($programa['codigo_programa']); ?></div>
                    </div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="detalle-contenido">
                        <div class="detalle-etiqueta">Duración</div>
                        <div class="detalle-valor"><?php echo $programa['duracion_meses']; ?> meses</div>
                    </div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="detalle-contenido">
                        <div class="detalle-etiqueta">Créditos totales</div>
                        <div class="detalle-valor"><?php echo $total_creditos; ?></div>
                    </div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="detalle-contenido">
                        <div class="detalle-etiqueta">Fecha de creación</div>
                        <div class="detalle-valor">
                            <?php echo date('d/m/Y', strtotime($programa['fecha_creacion'])); ?>
                            <small><?php echo date('H:i', strtotime($programa['fecha_creacion'])); ?></small>
                        </div>
                    </div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="detalle-contenido">
                        <div class="detalle-etiqueta">Coordinador</div>
                        <div class="detalle-valor"><?php echo htmlspecialchars($programa['coordinador'] ?? 'No asignado'); ?></div>
                    </div>
                </div>
                <?php if (!empty($programa['descripcion'])): ?>
                <div class="detalle-item" style="grid-column: span 2;">
                    <div class="detalle-icon">
                        <i class="fas fa-align-left"></i>
                    </div>
                    <div class="detalle-contenido">
                        <div class="detalle-etiqueta">Descripción</div>
                        <div class="detalle-valor"><?php echo nl2br(htmlspecialchars($programa['descripcion'])); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Lista de materias -->
        <div class="materias-card">
            <div class="materias-header">
                <h2>
                    <i class="fas fa-book"></i>
                    Materias del programa
                    <span class="badge-count"><?php echo $total_materias; ?></span>
                </h2>
                <a href="/POSGRADO/src/admin/modules/materias/agregar_materia.php?programa_id=<?php echo urlencode($id_programa); ?>" class="btn-accion btn-agregar">
                    <i class="fas fa-plus-circle"></i> Agregar materia
                </a>
            </div>

            <?php if ($total_materias > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Créditos</th>
                                <th>Semestre</th>
                                <th>Profesor</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($materia = $materias->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="codigo-materia">
                                        <?php echo htmlspecialchars($materia['codigo_materia'] ?? $materia['id_materia']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($materia['nombre']); ?></td>
                                <td>
                                    <span class="creditos-badge">
                                        <?php echo $materia['unidades_credito']; ?> créditos
                                    </span>
                                </td>
                                <td><?php echo $materia['semestre'] ?? '-'; ?></td>
                                <td><?php echo htmlspecialchars($materia['profesor'] ?? 'No asignado'); ?></td>
                                <td>
                                    <span class="estado-activo">
                                        <i class="fas fa-check-circle"></i> Activa
                                    </span>
                                </td>
                                <td>
                                    <div class="acciones-cell">
                                        <a href="/POSGRADO/src/admin/modules/materias/ver_materia.php?id=<?php echo urlencode($materia['id_materia']); ?>" class="btn-accion-tabla btn-ver-materia" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/POSGRADO/src/admin/modules/materias/editar_materia.php?id=<?php echo urlencode($materia['id_materia']); ?>" class="btn-accion-tabla btn-editar-materia" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/POSGRADO/src/admin/modules/materias/eliminar_materia.php?id=<?php echo urlencode($materia['id_materia']); ?>&programa_id=<?php echo urlencode($id_programa); ?>" class="btn-accion-tabla btn-eliminar-materia" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar esta materia?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>No hay materias registradas</h3>
                    <p>Este programa aún no tiene materias asignadas.</p>
                    <a href="/POSGRADO/src/admin/modules/materias/agregar_materia.php?programa_id=<?php echo urlencode($id_programa); ?>" class="btn">
                        <i class="fas fa-plus-circle"></i> Agregar primera materia
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Confirmación para eliminar
    document.querySelectorAll('.btn-eliminar-materia').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de eliminar esta materia? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>