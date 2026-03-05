<?php
// src/admin/modules/materias/ver_materia.php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/nav.php';
require_once __DIR__ . '/../../../../config/conexion.php';

// Obtener el ID de la materia
$id_materia = $_GET['id'] ?? '';
if (empty($id_materia)) {
    header('Location: /POSGRADO/src/admin/modules/especializacion/especializacion.php?error=ID de materia no especificado');
    exit();
}

// Obtener información de la materia
$sql_materia = "SELECT m.*, p.nombre as programa_nombre, p.codigo_programa, p.tipo_programa 
                FROM materias m
                JOIN programas_posgrado p ON m.id_programa = p.id_programa
                WHERE m.id_materia = ?";
$stmt_materia = $mysqli->prepare($sql_materia);
$stmt_materia->bind_param("s", $id_materia);
$stmt_materia->execute();
$result_materia = $stmt_materia->get_result();
$materia = $result_materia->fetch_assoc();

if (!$materia) {
    header('Location: /POSGRADO/src/admin/modules/especializacion/especializacion.php?error=Materia no encontrada');
    exit();
}

// Colores según el tipo de programa
$color_primario = '#17a2b8'; // Especialización (turquesa)
if ($materia['tipo_programa'] == 'maestria') {
    $color_primario = '#8B1E3F'; // Rojo UNEFA
} elseif ($materia['tipo_programa'] == 'doctorado') {
    $color_primario = '#F2A900'; // Amarillo
}
?>

<style>
    /* ========================================
       ESTILOS PARA VER MATERIA
    ======================================== */
    .ver-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .materia-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        border: 1px solid rgba(139, 30, 63, 0.1);
        border-left: 6px solid <?php echo $color_primario; ?>;
    }

    .materia-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f0f0f0;
    }

    .materia-header h1 {
        font-size: 2rem;
        color: <?php echo $color_primario; ?>;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .materia-header h1 i {
        font-size: 2rem;
    }

    .badge-programa {
        background: <?php echo $color_primario; ?>;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .info-programa {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px 20px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
        border-left: 4px solid <?php echo $color_primario; ?>;
    }

    .info-programa i {
        font-size: 2rem;
        color: <?php echo $color_primario; ?>;
    }

    .info-programa p {
        color: #495057;
        margin: 0;
    }

    .info-programa strong {
        color: <?php echo $color_primario; ?>;
        font-size: 1.1rem;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .info-item {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.3s ease;
    }

    .info-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .info-icon {
        width: 50px;
        height: 50px;
        background: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: <?php echo $color_primario; ?>;
        font-size: 1.5rem;
        border: 1px solid #e1e1e1;
    }

    .info-contenido {
        flex: 1;
    }

    .info-etiqueta {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }

    .info-valor {
        font-size: 1.2rem;
        color: #495057;
        font-weight: 600;
    }

    .info-valor small {
        font-size: 0.85rem;
        color: #6c757d;
        font-weight: normal;
    }

    .descripcion-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 30px;
    }

    .descripcion-card h3 {
        color: <?php echo $color_primario; ?>;
        margin-bottom: 15px;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .descripcion-card p {
        color: #495057;
        line-height: 1.6;
        font-size: 1rem;
        margin: 0;
    }

    .acciones {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .btn-primary {
        flex: 1;
        padding: 16px;
        background: linear-gradient(135deg, <?php echo $color_primario; ?> 0%, <?php echo $color_primario; ?> 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px <?php echo $color_primario . '80'; ?>;
    }

    .btn-secondary {
        flex: 1;
        padding: 16px;
        background: #f8f9fa;
        color: #495057;
        border: 2px solid #e1e1e1;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-secondary:hover {
        background: #e9ecef;
        color: <?php echo $color_primario; ?>;
        border-color: <?php echo $color_primario; ?>;
    }

    .btn-danger {
        flex: 1;
        padding: 16px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-danger:hover {
        background: #c82333;
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
    }

    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }

        .acciones {
            flex-direction: column;
        }
    }
</style>

<div class="main-content">
    <div class="ver-container">
        <div class="materia-card">
            <div class="materia-header">
                <h1>
                    <i class="fas fa-book"></i>
                    <?php echo htmlspecialchars($materia['nombre']); ?>
                </h1>
                <span class="badge-programa">
                    <?php echo htmlspecialchars($materia['codigo_materia'] ?? $materia['id_materia']); ?>
                </span>
            </div>

            <!-- Información del programa padre -->
            <div class="info-programa">
                <i class="fas fa-graduation-cap"></i>
                <div>
                    <p>Programa: <strong><?php echo htmlspecialchars($materia['programa_nombre']); ?></strong></p>
                    <p><small>Código: <?php echo htmlspecialchars($materia['codigo_programa']); ?></small></p>
                </div>
            </div>

            <!-- Grid de información -->
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="info-contenido">
                        <div class="info-etiqueta">Créditos</div>
                        <div class="info-valor"><?php echo $materia['unidades_credito']; ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="info-contenido">
                        <div class="info-etiqueta">Semestre</div>
                        <div class="info-valor"><?php echo $materia['semestre'] ?? 'N/A'; ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="info-contenido">
                        <div class="info-etiqueta">Horas Teóricas</div>
                        <div class="info-valor"><?php echo $materia['horas_teoricas'] ?? '0'; ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-flask"></i>
                    </div>
                    <div class="info-contenido">
                        <div class="info-etiqueta">Horas Prácticas</div>
                        <div class="info-valor"><?php echo $materia['horas_practicas'] ?? '0'; ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="info-contenido">
                        <div class="info-etiqueta">Profesor</div>
                        <div class="info-valor"><?php echo htmlspecialchars($materia['profesor'] ?? 'No asignado'); ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="info-contenido">
                        <div class="info-etiqueta">Fecha Creación</div>
                        <div class="info-valor">
                            <?php echo date('d/m/Y', strtotime($materia['fecha_creacion'] ?? 'now')); ?>
                            <small><?php echo date('H:i', strtotime($materia['fecha_creacion'] ?? 'now')); ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Descripción -->
            <?php if (!empty($materia['descripcion'])): ?>
            <div class="descripcion-card">
                <h3>
                    <i class="fas fa-align-left"></i>
                    Descripción
                </h3>
                <p><?php echo nl2br(htmlspecialchars($materia['descripcion'])); ?></p>
            </div>
            <?php endif; ?>

            <!-- Acciones -->
            <div class="acciones">
                <a href="editar_materia.php?id=<?php echo urlencode($materia['id_materia']); ?>" class="btn-primary">
                    <i class="fas fa-edit"></i> Editar Materia
                </a>
                <a href="/POSGRADO/src/admin/modules/especializacion/ver.php?id=<?php echo urlencode($materia['id_programa']); ?>" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al Programa
                </a>
                <a href="eliminar_materia.php?id=<?php echo urlencode($materia['id_materia']); ?>&programa_id=<?php echo urlencode($materia['id_programa']); ?>" class="btn-danger" onclick="return confirm('¿Estás seguro de eliminar esta materia?')">
                    <i class="fas fa-trash"></i> Eliminar
                </a>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>