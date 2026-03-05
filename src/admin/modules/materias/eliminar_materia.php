<?php
// src/admin/modules/materias/eliminar_materia.php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/nav.php';
require_once __DIR__ . '/../../../../config/conexion.php';

// Obtener el ID de la materia y el programa
$id_materia = $_GET['id'] ?? '';
$programa_id = $_GET['programa_id'] ?? '';

if (empty($id_materia) || empty($programa_id)) {
    header('Location: /POSGRADO/src/admin/modules/especializacion/especializacion.php?error=ID no especificado');
    exit();
}

// Obtener información de la materia
$sql_materia = "SELECT * FROM materias WHERE id_materia = ?";
$stmt_materia = $mysqli->prepare($sql_materia);
$stmt_materia->bind_param("s", $id_materia);
$stmt_materia->execute();
$result_materia = $stmt_materia->get_result();
$materia = $result_materia->fetch_assoc();

if (!$materia) {
    header('Location: /POSGRADO/src/admin/modules/especializacion/ver.php?id=' . urlencode($programa_id) . '&error=Materia no encontrada');
    exit();
}

// Función para actualizar créditos totales del programa
function actualizarCreditosPrograma($mysqli, $programa_id) {
    $sql = "SELECT SUM(unidades_credito) as total_creditos 
            FROM materias 
            WHERE id_programa = ? AND estado = 'activa'";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $programa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total_creditos'] ?? 0;
    
    $sql_update = "UPDATE programas_posgrado SET total_creditos = ? WHERE id_programa = ?";
    $stmt_update = $mysqli->prepare($sql_update);
    $stmt_update->bind_param("is", $total, $programa_id);
    $stmt_update->execute();
    $stmt_update->close();
    
    return $total;
}

$mensaje = '';
$error = '';

// Procesar la eliminación si se confirmó
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    
    // Verificar si la materia está siendo utilizada en inscripciones
    $sql_check = "SELECT COUNT(*) as total FROM inscripciones_materias WHERE id_materia = ?";
    $stmt_check = $mysqli->prepare($sql_check);
    $stmt_check->bind_param("s", $id_materia);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $total_inscripciones = $result_check->fetch_assoc()['total'];
    
    if ($total_inscripciones > 0) {
        $error = "No se puede eliminar la materia porque tiene $total_inscripciones estudiante(s) inscritos.";
    } else {
        // Proceder a eliminar
        $sql_delete = "DELETE FROM materias WHERE id_materia = ?";
        $stmt_delete = $mysqli->prepare($sql_delete);
        $stmt_delete->bind_param("s", $id_materia);
        
        if ($stmt_delete->execute()) {
            // Actualizar los créditos totales del programa
            $nuevo_total = actualizarCreditosPrograma($mysqli, $programa_id);
            
            // Redirigir a la página del programa
            header('Location: /POSGRADO/src/admin/modules/especializacion/ver.php?id=' . urlencode($programa_id) . '&msg=deleted');
            exit();
        } else {
            $error = "Error al eliminar: " . $mysqli->error;
        }
        $stmt_delete->close();
    }
}
?>

<style>
    /* ========================================
       ESTILOS PARA ELIMINAR MATERIA
    ======================================== */
    .eliminar-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 20px;
    }

    .delete-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        border: 1px solid rgba(139, 30, 63, 0.1);
        border-left: 6px solid #dc3545;
        text-align: center;
    }

    .delete-icon {
        width: 100px;
        height: 100px;
        background: #fee;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        color: #dc3545;
        font-size: 3rem;
        border: 4px solid #dc3545;
    }

    .delete-icon i {
        font-size: 3rem;
    }

    .delete-title {
        color: #dc3545;
        font-size: 2rem;
        margin-bottom: 15px;
        font-weight: 700;
    }

    .delete-subtitle {
        color: #6c757d;
        margin-bottom: 30px;
        font-size: 1.1rem;
    }

    .materia-info {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        text-align: left;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 0;
        border-bottom: 1px dashed #e1e1e1;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-icon {
        width: 40px;
        height: 40px;
        background: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #dc3545;
        font-size: 1.2rem;
        border: 1px solid #e1e1e1;
    }

    .info-content {
        flex: 1;
    }

    .info-label {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value {
        font-size: 1.1rem;
        color: #495057;
        font-weight: 600;
    }

    .warning-box {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px 20px;
        border-radius: 10px;
        margin: 20px 0;
        display: flex;
        align-items: center;
        gap: 15px;
        text-align: left;
    }

    .warning-box i {
        font-size: 2rem;
        color: #ffc107;
    }

    .warning-box p {
        color: #856404;
        margin: 0;
        font-size: 0.95rem;
    }

    .warning-box strong {
        color: #8B1E3F;
    }

    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .btn-danger {
        flex: 1;
        padding: 16px;
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-danger:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
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
        color: #dc3545;
        border-color: #dc3545;
    }

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
</style>

<div class="main-content">
    <div class="eliminar-container">
        <div class="delete-card">
            <div class="delete-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>

            <h1 class="delete-title">¿Eliminar Materia?</h1>
            <p class="delete-subtitle">Esta acción no se puede deshacer</p>

            <?php if ($mensaje): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Información de la materia -->
            <div class="materia-info">
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Código</div>
                        <div class="info-value"><?php echo htmlspecialchars($materia['codigo_materia'] ?? $materia['id_materia']); ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Nombre</div>
                        <div class="info-value"><?php echo htmlspecialchars($materia['nombre']); ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Créditos</div>
                        <div class="info-value"><?php echo $materia['unidades_credito']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Verificar si hay inscripciones -->
            <?php
            $sql_check = "SELECT COUNT(*) as total FROM inscripciones_materias WHERE id_materia = ?";
            $stmt_check = $mysqli->prepare($sql_check);
            $stmt_check->bind_param("s", $id_materia);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $total_inscripciones = $result_check->fetch_assoc()['total'];
            
            if ($total_inscripciones > 0): ?>
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <p><strong>⚠️ No se puede eliminar</strong></p>
                        <p>Esta materia tiene <strong><?php echo $total_inscripciones; ?> estudiante(s)</strong> inscritos.</p>
                        <p style="margin-top: 5px;">Primero debes eliminar las inscripciones asociadas.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <p><strong>⚠️ Advertencia:</strong> Esta acción eliminará permanentemente la materia <strong>"<?php echo htmlspecialchars($materia['nombre']); ?>"</strong>.</p>
                        <p style="margin-top: 5px;">Los créditos totales del programa se actualizarán automáticamente.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Formulario de confirmación -->
            <form method="POST" action="">
                <div class="form-actions">
                    <?php if ($total_inscripciones == 0): ?>
                        <button type="submit" name="confirmar" value="1" class="btn-danger">
                            <i class="fas fa-trash"></i> SÍ, ELIMINAR MATERIA
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn-danger" disabled>
                            <i class="fas fa-ban"></i> NO SE PUEDE ELIMINAR
                        </button>
                    <?php endif; ?>
                    
                    <a href="ver_materia.php?id=<?php echo urlencode($id_materia); ?>" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> CANCELAR
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Confirmación adicional antes de enviar
    document.querySelector('form')?.addEventListener('submit', function(e) {
        if (!confirm('¿Estás completamente seguro de eliminar esta materia? Esta acción no se puede deshacer.')) {
            e.preventDefault();
        }
    });
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>