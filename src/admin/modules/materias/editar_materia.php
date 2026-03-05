<?php
// src/admin/modules/materias/editar_materia.php
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
$sql_materia = "SELECT m.*, p.nombre as programa_nombre, p.codigo_programa, p.tipo_programa, p.id_programa
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $unidades_credito = trim($_POST['unidades_credito'] ?? '');
    $horas_teoricas = trim($_POST['horas_teoricas'] ?? '');
    $horas_practicas = trim($_POST['horas_practicas'] ?? '');
    $semestre = trim($_POST['semestre'] ?? '');
    $profesor = trim($_POST['profesor'] ?? '');
    
    $errors = [];
    
    // Validaciones
    if (empty($nombre)) $errors[] = "El nombre de la materia es requerido";
    if (empty($unidades_credito)) $errors[] = "Las unidades de crédito son requeridas";
    if (!is_numeric($unidades_credito) || $unidades_credito < 1) $errors[] = "Las unidades de crédito deben ser un número válido";
    if (!empty($horas_teoricas) && (!is_numeric($horas_teoricas) || $horas_teoricas < 0)) $errors[] = "Las horas teóricas deben ser un número válido";
    if (!empty($horas_practicas) && (!is_numeric($horas_practicas) || $horas_practicas < 0)) $errors[] = "Las horas prácticas deben ser un número válido";
    if (!empty($semestre) && (!is_numeric($semestre) || $semestre < 1)) $errors[] = "El semestre debe ser un número válido";
    
    if (empty($errors)) {
        // Actualizar en la base de datos
        $sql = "UPDATE materias SET 
                nombre = ?, 
                descripcion = ?, 
                unidades_credito = ?, 
                horas_teoricas = ?, 
                horas_practicas = ?, 
                semestre = ?, 
                profesor = ? 
                WHERE id_materia = ?";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssiiiiss", 
            $nombre,
            $descripcion,
            $unidades_credito,
            $horas_teoricas,
            $horas_practicas,
            $semestre,
            $profesor,
            $id_materia
        );
        
        if ($stmt->execute()) {
            // Actualizar los créditos totales del programa
            $nuevo_total = actualizarCreditosPrograma($mysqli, $materia['id_programa']);
            
            // Redirigir a la página de ver materia
            header('Location: ver_materia.php?id=' . urlencode($id_materia) . '&msg=updated');
            exit();
        } else {
            $error = "Error al actualizar: " . $mysqli->error;
        }
        $stmt->close();
    } else {
        $error = implode("<br>", $errors);
    }
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
       ESTILOS PARA EDITAR MATERIA
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

    .programa-info {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px 20px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
        border-left: 4px solid <?php echo $color_primario; ?>;
    }

    .programa-info i {
        font-size: 2rem;
        color: <?php echo $color_primario; ?>;
    }

    .programa-info p {
        color: #495057;
        margin: 0;
    }

    .programa-info strong {
        color: <?php echo $color_primario; ?>;
    }

    .codigo-info {
        background: <?php echo $color_primario . '10'; ?>;
        border-radius: 8px;
        padding: 12px 15px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid <?php echo $color_primario; ?>;
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
        min-height: 80px;
        padding-left: 45px;
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
                <i class="fas fa-book"></i>
                <h2>Editar Materia</h2>
                <p>Modifica los datos de la materia</p>
            </div>

            <!-- Información del programa -->
            <div class="programa-info">
                <i class="fas fa-graduation-cap"></i>
                <div>
                    <p>Programa: <strong><?php echo htmlspecialchars($materia['programa_nombre']); ?></strong></p>
                    <p><small>Código: <?php echo htmlspecialchars($materia['codigo_programa']); ?></small></p>
                </div>
            </div>

            <!-- Código (no editable) -->
            <div class="codigo-info">
                <i class="fas fa-qrcode"></i>
                <span>Código de materia: <strong><?php echo htmlspecialchars($materia['codigo_materia'] ?? $materia['id_materia']); ?></strong> (no editable)</span>
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
                    <!-- Nombre de la Materia -->
                    <div class="form-group full-width">
                        <label for="nombre" class="required-field">
                            <i class="fas fa-graduation-cap"></i> NOMBRE DE LA MATERIA
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-pencil-alt"></i>
                            <input type="text" 
                                   id="nombre" 
                                   name="nombre" 
                                   value="<?php echo htmlspecialchars($_POST['nombre'] ?? $materia['nombre']); ?>"
                                   placeholder="Ej: Fundamentos de Sistemas"
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
                                      rows="3"
                                      placeholder="Describe el contenido de la materia..."><?php echo htmlspecialchars($_POST['descripcion'] ?? $materia['descripcion']); ?></textarea>
                        </div>
                    </div>

                    <!-- Unidades de Crédito -->
                    <div class="form-group">
                        <label for="unidades_credito" class="required-field">
                            <i class="fas fa-star"></i> UNIDADES DE CRÉDITO
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-calculator"></i>
                            <input type="number" 
                                   id="unidades_credito" 
                                   name="unidades_credito" 
                                   value="<?php echo htmlspecialchars($_POST['unidades_credito'] ?? $materia['unidades_credito']); ?>"
                                   min="1"
                                   max="10"
                                   step="1"
                                   required>
                        </div>
                    </div>

                    <!-- Semestre -->
                    <div class="form-group">
                        <label for="semestre">
                            <i class="fas fa-layer-group"></i> SEMESTRE
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-hashtag"></i>
                            <input type="number" 
                                   id="semestre" 
                                   name="semestre" 
                                   value="<?php echo htmlspecialchars($_POST['semestre'] ?? $materia['semestre']); ?>"
                                   min="1"
                                   max="6">
                        </div>
                    </div>

                    <!-- Horas Teóricas -->
                    <div class="form-group">
                        <label for="horas_teoricas">
                            <i class="fas fa-chalkboard-teacher"></i> HORAS TEÓRICAS
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-clock"></i>
                            <input type="number" 
                                   id="horas_teoricas" 
                                   name="horas_teoricas" 
                                   value="<?php echo htmlspecialchars($_POST['horas_teoricas'] ?? $materia['horas_teoricas']); ?>"
                                   min="0"
                                   max="10">
                        </div>
                    </div>

                    <!-- Horas Prácticas -->
                    <div class="form-group">
                        <label for="horas_practicas">
                            <i class="fas fa-flask"></i> HORAS PRÁCTICAS
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-clock"></i>
                            <input type="number" 
                                   id="horas_practicas" 
                                   name="horas_practicas" 
                                   value="<?php echo htmlspecialchars($_POST['horas_practicas'] ?? $materia['horas_practicas']); ?>"
                                   min="0"
                                   max="10">
                        </div>
                    </div>

                    <!-- Profesor -->
                    <div class="form-group full-width">
                        <label for="profesor">
                            <i class="fas fa-user-tie"></i> PROFESOR
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" 
                                   id="profesor" 
                                   name="profesor" 
                                   value="<?php echo htmlspecialchars($_POST['profesor'] ?? $materia['profesor']); ?>"
                                   placeholder="Ej: Prof. Carlos Méndez">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> GUARDAR CAMBIOS
                    </button>
                    <a href="ver_materia.php?id=<?php echo urlencode($id_materia); ?>" class="btn-secondary">
                        <i class="fas fa-times"></i> CANCELAR
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Validación de números positivos
    document.getElementById('unidades_credito').addEventListener('input', function() {
        if (this.value < 1) this.value = 1;
        if (this.value > 10) this.value = 10;
    });

    document.getElementById('horas_teoricas').addEventListener('input', function() {
        if (this.value < 0) this.value = 0;
        if (this.value > 20) this.value = 20;
    });

    document.getElementById('horas_practicas').addEventListener('input', function() {
        if (this.value < 0) this.value = 0;
        if (this.value > 20) this.value = 20;
    });

    document.getElementById('semestre').addEventListener('input', function() {
        if (this.value < 1) this.value = 1;
        if (this.value > 10) this.value = 10;
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