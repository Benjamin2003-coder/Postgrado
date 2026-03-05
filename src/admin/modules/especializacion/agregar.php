<?php
// src/admin/modules/especializacion/agregar.php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/nav.php';
require_once __DIR__ . '/../../../../config/conexion.php';

// Obtener el tipo de programa desde la URL (viene del enlace del menú)
$tipo_programa = $_GET['tipo'] ?? 'especializacion';
$mensaje = '';
$error = '';

// Función para generar código de programa automático
function generarCodigoPrograma($mysqli, $tipo) {
    $anio = date('Y');
    
    // Prefijo según el tipo
    $prefijo = [
        'especializacion' => 'ESP',
        'maestria' => 'MAE',
        'doctorado' => 'DOC'
    ][$tipo] ?? 'PRO';
    
    // Buscar el último código del año actual para este tipo
    $sql = "SELECT codigo_programa FROM programas_posgrado 
            WHERE codigo_programa LIKE '$prefijo-$anio-%' 
            ORDER BY codigo_programa DESC LIMIT 1";
    $result = $mysqli->query($sql);
    
    if ($result->num_rows > 0) {
        $ultimo = $result->fetch_assoc()['codigo_programa'];
        $partes = explode('-', $ultimo);
        $numero = intval(end($partes));
        $nuevo_numero = $numero + 1;
    } else {
        $nuevo_numero = 1;
    }
    
    // Formatear con 3 dígitos
    $numero_formateado = str_pad($nuevo_numero, 3, '0', STR_PAD_LEFT);
    
    return "$prefijo-$anio-$numero_formateado";
}

// Generar código sugerido
$codigo_sugerido = generarCodigoPrograma($mysqli, $tipo_programa);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $codigo_programa = trim($_POST['codigo_programa'] ?? $codigo_sugerido);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $duracion_meses = trim($_POST['duracion_meses'] ?? '');
    $coordinador = trim($_POST['coordinador'] ?? '');
    
    $errors = [];
    
    // Validaciones
    if (empty($nombre)) $errors[] = "El nombre del programa es requerido";
    if (empty($duracion_meses)) $errors[] = "La duración en meses es requerida";
    if (!is_numeric($duracion_meses) || $duracion_meses < 1) $errors[] = "La duración debe ser un número válido";
    
    // Verificar que el código no exista ya
    $sql_check = "SELECT id_programa FROM programas_posgrado WHERE codigo_programa = ?";
    $stmt_check = $mysqli->prepare($sql_check);
    $stmt_check->bind_param("s", $codigo_programa);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $errors[] = "El código de programa '$codigo_programa' ya existe. Usa el código sugerido o cámbialo.";
    }
    
    if (empty($errors)) {
        // Generar ID único para el programa
        $id_programa = strtoupper(substr($tipo_programa, 0, 3)) . '-' . uniqid();
        
        // Insertar en la base de datos con total_creditos = 0 (se calculará después)
        $total_creditos = 0; // Inicialmente 0, se actualizará al agregar materias
        
        $sql = "INSERT INTO programas_posgrado 
                (id_programa, codigo_programa, tipo_programa, nombre, descripcion, 
                 duracion_meses, total_creditos, coordinador, estado, fecha_creacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'activo', NOW())";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssssiis", 
            $id_programa, 
            $codigo_programa, 
            $tipo_programa, 
            $nombre, 
            $descripcion, 
            $duracion_meses, 
            $total_creditos,  // ← Ahora es 0
            $coordinador
        );
        
        if ($stmt->execute()) {
            $mensaje = "¡Especialización creada exitosamente!";
            
            // Obtener el ID insertado
            $nuevo_id = $id_programa;
            
            // Redirigir después de 2 segundos a la lista de especializaciones
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'especializacion.php';
                }, 2000);
            </script>";
        } else {
            $error = "Error al guardar: " . $mysqli->error;
        }
        $stmt->close();
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<style>
    /* ========================================
       ESTILOS PARA EL FORMULARIO DE AGREGAR
    ======================================== */
    .agregar-container {
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
        border-left: 6px solid #17a2b8;
    }

    .form-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .form-header i {
        font-size: 4rem;
        color: #17a2b8;
        margin-bottom: 15px;
    }

    .form-header h2 {
        color: #17a2b8;
        font-size: 2rem;
        margin-bottom: 10px;
    }

    .form-header p {
        color: #6c757d;
        font-size: 1rem;
    }

    .info-box {
        background: #e7f3ff;
        border-left: 4px solid #17a2b8;
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .info-box i {
        font-size: 2rem;
        color: #17a2b8;
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
        color: #17a2b8;
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
        border-color: #17a2b8;
        background: white;
        box-shadow: 0 0 0 4px rgba(23, 162, 184, 0.1);
    }

    input:focus + i {
        color: #17a2b8;
    }

    textarea {
        resize: vertical;
        min-height: 100px;
        padding-left: 45px;
    }

    /* Código generado */
    .code-generated {
        background: #f0f7ff;
        border-color: #17a2b8;
        color: #17a2b8;
        font-weight: 600;
        font-size: 1rem;
        letter-spacing: 1px;
    }

    .code-suggestion {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 5px;
        display: block;
    }

    .code-suggestion i {
        color: #17a2b8;
    }

    /* Nota sobre créditos */
    .creditos-nota {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px 20px;
        border-radius: 10px;
        margin: 20px 0;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .creditos-nota i {
        font-size: 2rem;
        color: #ffc107;
    }

    .creditos-nota p {
        color: #856404;
        margin: 0;
        font-size: 0.95rem;
    }

    .creditos-nota strong {
        color: #8B1E3F;
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
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
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
        box-shadow: 0 8px 20px rgba(23, 162, 184, 0.3);
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
        color: #17a2b8;
        border-color: #17a2b8;
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
    <div class="agregar-container">
        <div class="form-card">
            <div class="form-header">
                <i class="fas fa-certificate"></i>
                <h2>Nueva Especialización</h2>
                <p>Completa los datos para crear un nuevo programa de especialización</p>
            </div>

            <div class="badge-info">
                <i class="fas fa-clock"></i> Duración: 1 año (12-18 meses)
            </div>

            <!-- Info box -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <p>Se generará automáticamente un código con el formato <strong>ESP-AÑO-NÚMERO</strong> (ej: ESP-2026-001). Puedes modificarlo si es necesario.</p>
            </div>

            <!-- NOTA IMPORTANTE SOBRE CRÉDITOS -->
            <div class="creditos-nota">
                <i class="fas fa-calculator"></i>
                <div>
                    <p><strong>⚡ Los créditos totales se calculan automáticamente</strong></p>
                    <p>Al crear el programa, los créditos totales serán <strong>0</strong>. Luego, al ir agregando materias, el sistema sumará automáticamente los créditos de cada materia para obtener el total.</p>
                </div>
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
                    <!-- Código de Programa -->
                    <div class="form-group full-width">
                        <label for="codigo_programa" class="required-field">
                            <i class="fas fa-qrcode"></i> CÓDIGO DEL PROGRAMA
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-hashtag"></i>
                            <input type="text" 
                                   id="codigo_programa" 
                                   name="codigo_programa" 
                                   value="<?php echo htmlspecialchars($_POST['codigo_programa'] ?? $codigo_sugerido); ?>"
                                   class="code-generated"
                                   required>
                        </div>
                        <span class="code-suggestion">
                            <i class="fas fa-lightbulb"></i> Código sugerido basado en el último registro
                        </span>
                    </div>

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
                                   value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
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
                                      placeholder="Describe el programa, su enfoque, objetivos..."><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
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
                                   value="<?php echo htmlspecialchars($_POST['duracion_meses'] ?? '12'); ?>"
                                   min="6"
                                   max="24"
                                   required>
                        </div>
                        <small style="color: #6c757d;">Las especializaciones suelen durar entre 12 y 18 meses</small>
                    </div>

                    <!-- Coordinador -->
                    <div class="form-group full-width">
                        <label for="coordinador">
                            <i class="fas fa-user-tie"></i> COORDINADOR ACADÉMICO
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" 
                                   id="coordinador" 
                                   name="coordinador" 
                                   value="<?php echo htmlspecialchars($_POST['coordinador'] ?? ''); ?>"
                                   placeholder="Ej: MSc. José Gregorio León">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> GUARDAR ESPECIALIZACIÓN
                    </button>
                    <a href="especializacion.php" class="btn-secondary">
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