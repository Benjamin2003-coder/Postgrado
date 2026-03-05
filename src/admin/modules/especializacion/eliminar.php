<?php
// Iniciar buffer de salida para permitir cabeceras/redirecciones
if (session_status() === PHP_SESSION_NONE) {
    ob_start();
}
// src/admin/modules/especializacion/eliminar.php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/nav.php';
require_once __DIR__ . '/../../../../config/conexion.php';

// Obtener el ID del programa a eliminar
$id_programa = $_GET['id'] ?? '';
$tipo = $_GET['tipo'] ?? 'especializacion';

if (empty($id_programa)) {
    header('Location: especializacion.php?error=ID no especificado');
    exit();
}

// Primero, obtener información del programa para mostrar
$sql_info = "SELECT * FROM programas_posgrado WHERE id_programa = ?";
$stmt_info = $mysqli->prepare($sql_info);
$stmt_info->bind_param("s", $id_programa);
$stmt_info->execute();
$result_info = $stmt_info->get_result();
$programa = $result_info->fetch_assoc();

if (!$programa) {
    header('Location: especializacion.php?error=Programa no encontrado');
    exit();
}

$mensaje = '';
$error = '';

// Verificar si hay materias asociadas a este programa
$sql_materias = "SELECT COUNT(*) as total FROM materias WHERE id_programa = ?";
$stmt_materias = $mysqli->prepare($sql_materias);
$stmt_materias->bind_param("s", $id_programa);
$stmt_materias->execute();
$result_materias = $stmt_materias->get_result();
$total_materias = $result_materias->fetch_assoc()['total'];

// Verificar si hay estudiantes inscritos en este programa
$sql_estudiantes = "SELECT COUNT(*) as total FROM inscripciones_programa WHERE id_programa = ?";
$stmt_estudiantes = $mysqli->prepare($sql_estudiantes);
$stmt_estudiantes->bind_param("s", $id_programa);
$stmt_estudiantes->execute();
$result_estudiantes = $stmt_estudiantes->get_result();
$total_estudiantes = $result_estudiantes->fetch_assoc()['total'];

// Procesar la eliminación si se confirmó
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    
    // Verificar si hay restricciones
    if ($total_materias > 0 || $total_estudiantes > 0) {
        $error = "No se puede eliminar el programa porque tiene $total_materias materia(s) y $total_estudiantes estudiante(s) asociados.";
    } else {
        // Proceder a eliminar
        $sql_delete = "DELETE FROM programas_posgrado WHERE id_programa = ?";
        $stmt_delete = $mysqli->prepare($sql_delete);
        $stmt_delete->bind_param("s", $id_programa);
        
        if ($stmt_delete->execute()) {
            $mensaje = "Programa eliminado exitosamente";
            // Redirigir inmediatamente a la lista de especializaciones con mensaje
            header('Location: especializacion.php?msg=deleted');
            exit();
        } else {
            $error = "Error al eliminar: " . $mysqli->error;
        }
        $stmt_delete->close();
    }
}

// Cerrar conexiones
$stmt_info->close();
$stmt_materias->close();
$stmt_estudiantes->close();
?>

<style>
    /* ========================================
       ESTILOS PARA ELIMINAR
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

    .programa-info {
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

    .restriction-box {
        background: #f8d7da;
        border-left: 4px solid #dc3545;
        padding: 20px;
        border-radius: 10px;
        margin: 20px 0;
        text-align: left;
        color: #721c24;
    }

    .restriction-box i {
        font-size: 1.5rem;
        margin-right: 10px;
        color: #dc3545;
    }

    .restriction-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
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

    .btn-danger:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
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

    .badge-info {
        display: inline-block;
        background: #17a2b8;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-bottom: 20px;
    }
</style>

<div class="main-content">
    <div class="eliminar-container">
        <div class="delete-card">
            <div class="delete-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>

            <h1 class="delete-title">¿Eliminar Programa?</h1>
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

            <!-- Información del programa -->
            <div class="programa-info">
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Código del Programa</div>
                        <div class="info-value"><?php echo htmlspecialchars($programa['codigo_programa']); ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Nombre</div>
                        <div class="info-value"><?php echo htmlspecialchars($programa['nombre']); ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Duración</div>
                        <div class="info-value"><?php echo $programa['duracion_meses']; ?> meses</div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Créditos Totales</div>
                        <div class="info-value"><?php echo $programa['total_creditos'] ?? 0; ?></div>
                    </div>
                </div>
            </div>

            <!-- Verificar si hay dependencias -->
            <?php if ($total_materias > 0 || $total_estudiantes > 0): ?>
                <div class="restriction-box">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>No se puede eliminar el programa</strong>
                    </div>
                    
                    <?php if ($total_materias > 0): ?>
                        <div class="restriction-item">
                            <i class="fas fa-book"></i>
                            <span>Tiene <strong><?php echo $total_materias; ?> materia(s)</strong> asociadas</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($total_estudiantes > 0): ?>
                        <div class="restriction-item">
                            <i class="fas fa-users"></i>
                            <span>Tiene <strong><?php echo $total_estudiantes; ?> estudiante(s)</strong> inscritos</span>
                        </div>
                    <?php endif; ?>
                    
                    <p style="margin-top: 15px; font-size: 0.9rem;">
                        Primero debes eliminar o reasignar las materias y estudiantes asociados.
                    </p>
                </div>
            <?php else: ?>
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <p><strong>⚠️ Advertencia:</strong> Esta acción eliminará permanentemente el programa de especialización <strong>"<?php echo htmlspecialchars($programa['nombre']); ?>"</strong>.</p>
                        <p style="margin-top: 5px;">Todos los datos asociados serán eliminados de la base de datos.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Formulario de confirmación -->
            <form method="POST" action="">
                <div class="form-actions">
                    <?php if ($total_materias == 0 && $total_estudiantes == 0): ?>
                        <button type="submit" name="confirmar" value="1" class="btn-danger">
                            <i class="fas fa-trash"></i> SÍ, ELIMINAR PROGRAMA
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn-danger" disabled>
                            <i class="fas fa-ban"></i> NO SE PUEDE ELIMINAR
                        </button>
                    <?php endif; ?>
                    
                    <a href="especializacion.php" class="btn-secondary">
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
        if (!confirm('¿Estás completamente seguro de eliminar este programa? Esta acción no se puede deshacer.')) {
            e.preventDefault();
        }
    });
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>