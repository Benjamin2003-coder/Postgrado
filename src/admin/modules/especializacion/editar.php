<?php
// src/admin/modules/especializacion/editar.php
require_once __DIR__ . '/../../../../config/conexion.php';

// Obtener el ID del programa (redirigir antes de emitir HTML)
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
$stmt_programa->close();

if (!$programa) {
    header('Location: especializacion.php?error=Programa no encontrado');
    exit();
}

// Manejo del formulario: procesar POST antes de incluir cualquier salida (header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $duracion_meses = trim($_POST['duracion_meses'] ?? '');
    $coordinador = trim($_POST['coordinador'] ?? '');
    
    $errors = [];
    
    // Validaciones
    if (empty($nombre)) $errors[] = "El nombre del programa es requerido";
    if (empty($duracion_meses)) $errors[] = "La duración en meses es requerida";
    if (!is_numeric($duracion_meses) || $duracion_meses < 1) $errors[] = "La duración debe ser un número válido";
    
    if (empty($errors)) {
        // Actualizar en la base de datos
        $sql = "UPDATE programas_posgrado SET 
                nombre = ?, 
                descripcion = ?, 
                duracion_meses = ?, 
                coordinador = ? 
                WHERE id_programa = ?";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssiss", 
            $nombre, 
            $descripcion, 
            $duracion_meses, 
            $coordinador,
            $id_programa
        );
        
        if ($stmt->execute()) {
            // Redirigir a la página de ver con mensaje de éxito
            header('Location: ver.php?id=' . urlencode($id_programa) . '&msg=updated');
            exit();
        } else {
            $error = "Error al actualizar: " . $mysqli->error;
        }
        $stmt->close();
    } else {
        $error = implode("<br>", $errors);
    }
}

// Incluir header y navegación después de posibles redirecciones
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/nav.php';

$mensaje = '';
$error = '';

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
       ESTILOS PARA EDITAR PROGRAMA
    ======================================== */
    .editar-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .form-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        border: 1px solid rgba(139, 30, 63, 0.1);
        border-left: 6px solid <?php echo $color_primario; ?>;
    }

    .form-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .form-header i {
        font-size: 4rem;
        color: <?php echo $color_primario; ?>;
        margin-bottom: 15px;
    }

    .form-header h2 {
        color: <?php echo $color_primario; ?>;
        font-size: 2rem;
        margin-bottom: 10px;
    }

    .form-header p {
        color: #6c757d;
        font-size: 1rem;
    }

    .info-box {
        background: <?php echo $color_primario . '10'; ?>;
        border-left: 4px solid <?php echo $color_primario; ?>;
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .info-box i {
        font-size: 2rem;
        color: <?php echo $color_primario; ?>;
    }

    .info-box p {
        color: #495057;
        font-size: 0.9rem;
        margin: 0;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .full-width {
        grid-column: span 2;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        .full-width {
            grid-column: span 1;
        }
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        color: #495057;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    label i {
        margin-right: 5px;
        color: <?php echo $color_primario; ?>;
    }

    .required-field::after {
        content: ' *';
        color: #dc3545;
        font-weight: bold;
    }

    .input-wrapper {
        position: relative;
    }

    .input-wrapper i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
        font-size: 1rem;
        transition: color 0.3s ease;
    }

    input, select, textarea {
        width: 100%;
        padding: 14px 15px 14px 45px;
        border: 2px solid #e1e1e1;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        font-family: 'Inter', sans-serif;
        background: #f8f9fa;
    }

    input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: <?php echo $color_primario; ?>;
        background: white;
        box-shadow: 0 0 0 4px <?php echo $color_primario . '20'; ?>;
    }

    input:focus + i {
        color: <?php echo $color_primario; ?>;
    }

    textarea {
        resize: vertical;
        min-height: 100px;
        padding-left: 45px;
    }

    .codigo-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 12px 15px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid #e1e1e1;
    }

    .codigo-info i {
        color: <?php echo $color_primario; ?>;
        font-size: 1.2rem;
    }

    .codigo-info strong {
        color: <?php echo $color_primario; ?>;
        font-family: monospace;
        font-size: 1.1rem;
        margin-left: 5px;
    }

    /* Botones */
    .form-actions {
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
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px <?php echo $color_primario . '80'; ?>;
    }

    .btn-primary i {
        transition: transform 0.3s ease;
    }

    .btn-primary:hover i {
        transform: translateX(5px);
    }

    .btn-secondary {
        padding: 16px 30px;
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
</style>

<div class="main-content">
    <div class="editar-container">
        <div class="form-card">
            <div class="form-header">
                <i class="<?php echo $icono; ?>"></i>
                <h2>Editar <?php echo $tipo_texto; ?></h2>
                <p>Modifica los datos del programa</p>
            </div>

            <!-- Info del código (no editable) -->
            <div class="codigo-info">
                <i class="fas fa-qrcode"></i>
                <span>Código del programa: <strong><?php echo htmlspecialchars($programa['codigo_programa']); ?></strong> (no editable)</span>
            </div>

            <!-- Info box -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <p>Los créditos totales se calculan automáticamente según las materias agregadas.</p>
            </div>

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

            <form method="POST" action="">
                <div class="form-grid">
                    <!-- Nombre del Programa -->
                    <div class="form-group full-width">
                        <label for="nombre" class="required-field">
                            <i class="fas fa-graduation-cap"></i> NOMBRE DEL PROGRAMA
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-pencil-alt"></i>
                            <input type="text" 
                                   id="nombre" 
                                   name="nombre" 
                                   value="<?php echo htmlspecialchars($_POST['nombre'] ?? $programa['nombre']); ?>"
                                   placeholder="Ej: Especialización en Gerencia de Proyectos"
                                   required>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="form-group full-width">
                        <label for="descripcion">
                            <i class="fas fa-align-left"></i> DESCRIPCIÓN
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-file-alt"></i>
                            <textarea id="descripcion" 
                                      name="descripcion" 
                                      rows="4"
                                      placeholder="Describe el programa, su enfoque, objetivos..."><?php echo htmlspecialchars($_POST['descripcion'] ?? $programa['descripcion']); ?></textarea>
                        </div>
                    </div>

                    <!-- Duración en Meses -->
                    <div class="form-group">
                        <label for="duracion_meses" class="required-field">
                            <i class="fas fa-clock"></i> DURACIÓN (MESES)
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-hourglass-half"></i>
                            <input type="number" 
                                   id="duracion_meses" 
                                   name="duracion_meses" 
                                   value="<?php echo htmlspecialchars($_POST['duracion_meses'] ?? $programa['duracion_meses']); ?>"
                                   min="1"
                                   max="60"
                                   required>
                        </div>
                    </div>

                    <!-- Coordinador -->
                    <div class="form-group">
                        <label for="coordinador">
                            <i class="fas fa-user-tie"></i> COORDINADOR ACADÉMICO
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" 
                                   id="coordinador" 
                                   name="coordinador" 
                                   value="<?php echo htmlspecialchars($_POST['coordinador'] ?? $programa['coordinador']); ?>"
                                   placeholder="Ej: MSc. José Gregorio León">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> GUARDAR CAMBIOS
                    </button>
                    <a href="ver.php?id=<?php echo urlencode($id_programa); ?>" class="btn-secondary">
                        <i class="fas fa-times"></i> CANCELAR
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Validación de números positivos
    document.getElementById('duracion_meses').addEventListener('input', function() {
        if (this.value < 1) this.value = 1;
        if (this.value > 60) this.value = 60;
    });

    // Efecto de foco en inputs
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
            this.parentElement.style.transition = 'transform 0.3s ease';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>