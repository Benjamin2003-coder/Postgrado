<?php
// src/admin/modules/especializacion/programa.php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/nav.php';
require_once __DIR__ . '/../../../../config/conexion.php';

// Obtener todas las especializaciones
$sql = "SELECT * FROM programas_posgrado 
        WHERE tipo_programa = 'especializacion' 
        ORDER BY nombre";
$result = $mysqli->query($sql);
?>

<style>
    /* ========================================
       ESTILOS PARA ESPECIALIZACIONES
    ======================================== */
    .programas-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        background: white;
        padding: 25px 30px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        border-left: 6px solid #17a2b8;
    }

    .header-title h1 {
        font-size: 1.8rem;
        color: #17a2b8;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .header-title p {
        color: #6c757d;
        margin-top: 5px;
    }

    .btn-nuevo {
        background: #17a2b8;
        color: white;
        padding: 12px 25px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .btn-nuevo:hover {
        background: #138496;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(23, 162, 184, 0.3);
        color: white;
    }

    /* Grid de programas */
    .programas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
    }

    .programa-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        border: 1px solid #e1e1e1;
        transition: all 0.3s ease;
        border-left: 4px solid #17a2b8;
        position: relative;
        overflow: hidden;
    }

    .programa-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(23, 162, 184, 0.15);
        border-color: #17a2b8;
    }

    .programa-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(23, 162, 184, 0.1);
        color: #17a2b8;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .programa-badge i {
        font-size: 0.8rem;
    }

    .programa-codigo {
        background: #f8f9fa;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        color: #6c757d;
        display: inline-block;
        margin-bottom: 15px;
        font-family: monospace;
    }

    .programa-nombre {
        font-size: 1.3rem;
        color: #17a2b8;
        margin-bottom: 10px;
        font-weight: 700;
        padding-right: 80px;
    }

    .programa-descripcion {
        color: #495057;
        font-size: 0.9rem;
        line-height: 1.5;
        margin-bottom: 20px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .programa-detalles {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        padding: 15px 0;
        border-top: 1px dashed #e1e1e1;
        border-bottom: 1px dashed #e1e1e1;
    }

    .detalle-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #6c757d;
        font-size: 0.85rem;
    }

    .detalle-item i {
        color: #17a2b8;
        width: 18px;
    }

    .detalle-item strong {
        color: #17a2b8;
        margin-right: 3px;
    }

    .programa-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .programa-coordinador {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #6c757d;
        font-size: 0.85rem;
    }

    .programa-coordinador i {
        color: #17a2b8;
    }

    .programa-acciones {
        display: flex;
        gap: 10px;
    }

    .btn-accion {
        width: 35px;
        height: 35px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-ver {
        background: #17a2b8;
    }

    .btn-editar {
        background: #6c757d;
    }

    .btn-eliminar {
        background: #dc3545;
    }

    .btn-agregar {
        background: #F2A900;
        color: #8B1E3F;
    }

    .btn-accion:hover {
        transform: scale(1.1);
        filter: brightness(1.1);
    }

    /* Estado vacío */
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 15px;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 4rem;
        color: #17a2b8;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .empty-state h3 {
        color: #17a2b8;
        margin-bottom: 10px;
    }

    .empty-state .btn {
        display: inline-block;
        margin-top: 20px;
        padding: 12px 30px;
        background: #17a2b8;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }

        .programas-grid {
            grid-template-columns: 1fr;
        }

        .programa-detalles {
            flex-direction: column;
            gap: 10px;
        }
    }
</style>

<div class="main-content">
    <div class="programas-container">
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div style="padding:12px 18px; border-radius:8px; background:#d4edda; color:#155724; margin-bottom:18px; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-check-circle"></i>
                <strong>Programa eliminado exitosamente</strong>
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
            <div style="padding:12px 18px; border-radius:8px; background:#d4edda; color:#155724; margin-bottom:18px; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-check-circle"></i>
                <strong>Materia agregada exitosamente</strong>
            </div>
        <?php elseif (!empty($_GET['error'])): ?>
            <div style="padding:12px 18px; border-radius:8px; background:#f8d7da; color:#721c24; margin-bottom:18px; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-exclamation-circle"></i>
                <strong><?php echo htmlspecialchars($_GET['error']); ?></strong>
            </div>
        <?php endif; ?>
        <!-- Cabecera -->
        <div class="page-header">
            <div class="header-title">
                <h1>
                    <i class="fas fa-certificate"></i> 
                    Especializaciones
                </h1>
                <p>Gestión de programas de especialización (1 año de duración)</p>
            </div>
            <a href="agregar.php?tipo=especializacion" class="btn-nuevo">
                <i class="fas fa-plus-circle"></i> Nueva Especialización
            </a>
        </div>

        <!-- Grid de especializaciones -->
        <div class="programas-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while($prog = $result->fetch_assoc()): ?>
                <?php
                    // Obtener total de materias y créditos desde la tabla materias
                    $program_id = $prog['id_programa'];
                    $sql_mats = "SELECT COUNT(*) AS total_materias, SUM(unidades_credito) AS total_creditos FROM materias WHERE id_programa = ? AND estado = 'activa'";
                    $stmt_m = $mysqli->prepare($sql_mats);
                    if ($stmt_m) {
                        $stmt_m->bind_param("s", $program_id);
                        $stmt_m->execute();
                        $res_m = $stmt_m->get_result();
                        $row_m = $res_m->fetch_assoc();
                        $total_materias = $row_m['total_materias'] ?? 0;
                        $total_creditos = $row_m['total_creditos'] ?? null;
                        $stmt_m->close();

                        // Contar estudiantes activos inscritos en este programa
                        $sql_est = "SELECT COUNT(*) AS total_estudiantes FROM inscripciones_programa WHERE id_programa = ? AND estado = 'activo'";
                        $stmt_e = $mysqli->prepare($sql_est);
                        if ($stmt_e) {
                            $stmt_e->bind_param("s", $program_id);
                            $stmt_e->execute();
                            $res_e = $stmt_e->get_result();
                            $row_e = $res_e->fetch_assoc();
                            $total_estudiantes = $row_e['total_estudiantes'] ?? 0;
                            $stmt_e->close();
                        } else {
                            $total_estudiantes = 0;
                        }
                    } else {
                        $total_materias = 0;
                        $total_creditos = null;
                        $total_estudiantes = 0;
                    }
                ?>
                <div class="programa-card">
                    <div class="programa-badge">
                        <i class="fas fa-clock"></i> <?php echo $prog['duracion_meses']; ?> meses
                    </div>
                    
                    <div class="programa-codigo">
                        <i class="fas fa-qrcode"></i> <?php echo htmlspecialchars($prog['codigo_programa']); ?>
                    </div>
                    
                    <h2 class="programa-nombre"><?php echo htmlspecialchars($prog['nombre']); ?></h2>
                    
                    <div class="programa-descripcion">
                        <?php echo htmlspecialchars($prog['descripcion'] ?? 'Sin descripción'); ?>
                    </div>
                    
                    <div class="programa-detalles">
                        <div class="detalle-item">
                            <i class="fas fa-star"></i>
                            <span><strong><?php echo ($total_creditos !== null) ? $total_creditos : ($prog['total_creditos'] ?? 'N/A'); ?></strong> créditos</span>
                        </div>
                        <div class="detalle-item">
                            <i class="fas fa-book"></i>
                            <span><strong><?php echo $total_materias; ?></strong> materias</span>
                        </div>
                        <div class="detalle-item">
                            <i class="fas fa-users"></i>
                            <span><strong><?php echo $total_estudiantes; ?></strong> estudiantes</span>
                        </div>
                    </div>
                    
                    <div class="programa-footer">
                        <div class="programa-coordinador">
                            <i class="fas fa-user-tie"></i>
                            <span><?php echo htmlspecialchars($prog['coordinador'] ?? 'No asignado'); ?></span>
                        </div>
                        
                        <div class="programa-acciones">
                            <a href="ver.php?id=<?php echo urlencode($prog['id_programa']); ?>" class="btn-accion btn-ver" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="editar.php?id=<?php echo urlencode($prog['id_programa']); ?>" class="btn-accion btn-editar" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <!-- Botón para agregar/gestionar materias del programa -->
                            <a href="/POSGRADO/src/admin/modules/materias/agregar_materia.php" class="btn-accion btn-agregar" title="Agregar materias">
                                <i class="fas fa-book"></i>
                            </a>
                            <a href="javascript:void(0)" onclick="confirmarEliminacion('<?php echo $prog['id_programa']; ?>', '<?php echo addslashes($prog['nombre']); ?>')" class="btn-accion btn-eliminar" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-certificate"></i>
                    <h3>No hay especializaciones registradas</h3>
                    <p>Comienza agregando tu primera especialización</p>
                    <a href="agregar.php?tipo=especializacion" class="btn">
                        <i class="fas fa-plus-circle"></i> Agregar Especialización
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmarEliminacion(id, nombre) {
    if (confirm('¿Estás seguro de eliminar la especialización "' + nombre + '"? Esta acción no se puede deshacer.')) {
        window.location.href = 'eliminar.php?id=' + encodeURIComponent(id) + '&tipo=especializacion';
    }
}
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>