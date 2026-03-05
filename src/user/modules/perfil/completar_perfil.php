<?php
// src/user/modules/perfil/completar_perfil.php
// Incluir configuración y conexión sin enviar salida. Hacemos comprobaciones y redirecciones antes de cargar header.php (que genera salida HTML).
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/conexion.php';

// Verificar sesión (redirigir a login si no hay usuario)
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /posgrado/auth/login.php');
    exit();
}

// Verificar si ya tiene registro
$id_usuario = $_SESSION['usuario_id'];
$sql_check = "SELECT id_estudiante FROM estudiantes_posgrado WHERE id_usuario = ?";
$stmt_check = $mysqli->prepare($sql_check);
$stmt_check->bind_param("s", $id_usuario);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

// Si ya tiene registro, redirigir al perfil normal (antes de cargar header.php)
if ($result_check->num_rows > 0) {
    header('Location: /posgrado/src/user/index.php');
    exit();
}

// Ahora que las comprobaciones y redirecciones tempranas están hechas, incluir header y nav (que imprimen HTML)
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/nav.php';

// Función para generar código de estudiante automático
function generarCodigoEstudiante($mysqli) {
    $anio_actual = date('Y');
    
    // Buscar el último código del año actual
    $sql = "SELECT codigo_estudiante FROM estudiantes_posgrado 
            WHERE codigo_estudiante LIKE 'EST-$anio_actual-%' 
            ORDER BY codigo_estudiante DESC LIMIT 1";
    $result = $mysqli->query($sql);
    
    if ($result->num_rows > 0) {
        $ultimo = $result->fetch_assoc()['codigo_estudiante'];
        // Extraer el número del último código (ej: EST-2025-001 -> 001)
        $partes = explode('-', $ultimo);
        $numero = intval(end($partes));
        $nuevo_numero = $numero + 1;
    } else {
        $nuevo_numero = 1;
    }
    
    // Formatear el número con ceros a la izquierda (3 dígitos)
    $numero_formateado = str_pad($nuevo_numero, 3, '0', STR_PAD_LEFT);
    
    return "EST-$anio_actual-$numero_formateado";
}

$mensaje = '';
$error = '';

// Generar código sugerido
$codigo_sugerido = generarCodigoEstudiante($mysqli);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $codigo_estudiante = trim($_POST['codigo_estudiante'] ?? '');
    $titulo_pregrado = trim($_POST['titulo_pregrado'] ?? '');
    $universidad_egreso = trim($_POST['universidad_egreso'] ?? '');
    $anio_graduacion = trim($_POST['anio_graduacion'] ?? '');
    $profesion = trim($_POST['profesion'] ?? '');
    $direccion_alterna = trim($_POST['direccion_alterna'] ?? '');
    $telefono_alterno = trim($_POST['telefono_alterno'] ?? '');
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    // Normalizar fecha: convertir cadena vacía a NULL y validar formato YYYY-MM-DD
    if ($fecha_nacimiento === '') {
        $fecha_nacimiento = null;
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
        if (!($d && $d->format('Y-m-d') === $fecha_nacimiento)) {
            $errors[] = 'La fecha de nacimiento no tiene un formato válido (AAAA-MM-DD)';
        }
    }
    $nacionalidad = trim($_POST['nacionalidad'] ?? '');

    // Validaciones
    $errors = [];
    
    if (empty($codigo_estudiante)) {
        $errors[] = 'El código de estudiante es requerido';
    }
    
    if (empty($titulo_pregrado)) {
        $errors[] = 'El título de pregrado es requerido';
    }
    
    if (empty($universidad_egreso)) {
        $errors[] = 'La universidad de egreso es requerida';
    }
    
    // Validar profesión (solo valores permitidos)
    $profesiones_permitidas = ['Ingeniero', 'Licenciado', 'Arquitecto', 'Abogado', 'Médico', 'Economista', 'Administrador', 'Contador', 'Psicólogo', 'Otro'];
    if (empty($profesion)) {
        $errors[] = 'La profesión es requerida';
    } elseif (!in_array($profesion, $profesiones_permitidas)) {
        $errors[] = 'Profesión no válida. Selecciona una de las opciones.';
    }
    
    if (!empty($anio_graduacion) && (!is_numeric($anio_graduacion) || $anio_graduacion < 1900 || $anio_graduacion > date('Y'))) {
        $errors[] = 'El año de graduación no es válido';
    }
    
    if (!empty($telefono_alterno)) {
        $telefono_alterno = preg_replace('/\D/', '', $telefono_alterno);
        if (strlen($telefono_alterno) < 10 || strlen($telefono_alterno) > 11) {
            $errors[] = 'El teléfono alterno debe tener entre 10 y 11 dígitos';
        }
    }

    if (empty($errors)) {
        // Insertar en estudiantes_posgrado
        $sql = "INSERT INTO estudiantes_posgrado (
                    id_usuario, codigo_estudiante, titulo_pregrado, 
                    universidad_egreso, anio_graduacion, profesion,
                    direccion_alterna, telefono_alterno, fecha_nacimiento, 
                    nacionalidad, fecha_registro_academico
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param(
            "ssssisssss",
            $id_usuario,
            $codigo_estudiante,
            $titulo_pregrado,
            $universidad_egreso,
            $anio_graduacion,
            $profesion,
            $direccion_alterna,
            $telefono_alterno,
            $fecha_nacimiento,
            $nacionalidad
        );
        
        if ($stmt->execute()) {
            $mensaje = '¡Perfil académico completado exitosamente!';
            // En lugar de enviar cabeceras (ya se envió salida en header.php), usaremos redirección por JavaScript
            $redirect_url = '/POSGRADO/src/user/index.php';
            $redirect_delay_ms = 2000; // 2 segundos
        } else {
            $error = 'Error al guardar los datos: ' . $mysqli->error;
        }
        $stmt->close();
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<style>
    .completar-perfil-container {
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
    }

    .form-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .form-header i {
        font-size: 4rem;
        color: #F2A900;
        margin-bottom: 15px;
    }

    .form-header h2 {
        color: #8B1E3F;
        font-size: 2rem;
        margin-bottom: 10px;
    }

    .form-header p {
        color: #6c757d;
        font-size: 1rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    .full-width {
        grid-column: span 2;
    }

    @media (max-width: 768px) {
        .full-width {
            grid-column: span 1;
        }
    }

    .input-group {
        margin-bottom: 0;
    }

    label {
        display: block;
        margin-bottom: 8px;
        color: #495057;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
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
        border-color: #8B1E3F;
        background: white;
        box-shadow: 0 0 0 4px rgba(139, 30, 63, 0.1);
    }

    input:focus + i {
        color: #8B1E3F;
    }

    textarea {
        resize: vertical;
        min-height: 100px;
        padding-left: 45px;
    }

    /* Estilo especial para el código generado */
    .code-generated {
        background: #f0f7ff;
        border-color: #8B1E3F;
        color: #8B1E3F;
        font-weight: 600;
        font-size: 1.1rem;
        letter-spacing: 1px;
    }

    .code-generated:readonly {
        background: #e9ecef;
        cursor: not-allowed;
    }

    .btn-submit {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #8B1E3F 0%, #6a1730 100%);
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
        margin-top: 30px;
        box-shadow: 0 4px 15px rgba(139, 30, 63, 0.3);
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(139, 30, 63, 0.4);
    }

    .btn-submit i {
        transition: transform 0.3s ease;
    }

    .btn-submit:hover i {
        transform: translateX(5px);
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

    .form-note {
        text-align: center;
        margin-top: 20px;
        color: #6c757d;
        font-size: 0.85rem;
    }

    .form-note i {
        color: #F2A900;
    }

    .info-box {
        background: #f0f7ff;
        border-left: 4px solid #8B1E3F;
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .info-box i {
        font-size: 2rem;
        color: #8B1E3F;
    }

    .info-box p {
        color: #495057;
        font-size: 0.9rem;
        margin: 0;
    }
</style>

<div class="main-content">
    <div class="completar-perfil-container">
        <div class="form-card">
            <div class="form-header">
                <i class="fas fa-graduation-cap"></i>
                <h2>Completar Perfil Académico</h2>
                <p>Ingresa tu información académica para poder acceder al sistema de inscripciones</p>
            </div>

            <!-- Info box -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <p>Tu código de estudiante será generado automáticamente con el formato <strong>EST-AÑO-NÚMERO</strong>. El número se asigna secuencialmente según el último registro.</p>
            </div>

            <?php if ($mensaje): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $mensaje; ?>
                </div>
                <?php if (!empty($redirect_url)): ?>
                    <script>
                        setTimeout(function(){ window.location.href = '<?php echo $redirect_url; ?>'; }, <?php echo $redirect_delay_ms; ?>);
                    </script>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-grid">
                    <!-- Código de Estudiante (AUTO-GENERADO) -->
                    <div class="input-group full-width">
                        <label for="codigo_estudiante" class="required-field">CÓDIGO DE ESTUDIANTE</label>
                        <div class="input-wrapper">
                            <i class="fas fa-qrcode"></i>
                            <input type="text" id="codigo_estudiante" name="codigo_estudiante" 
                                   value="<?php echo htmlspecialchars($_POST['codigo_estudiante'] ?? $codigo_sugerido); ?>"
                                   class="code-generated"
                                   readonly
                                   required>
                        </div>
                        <small style="color: #6c757d; margin-top: 5px; display: block;">
                            <i class="fas fa-clock"></i> Código generado automáticamente
                        </small>
                    </div>

                    <!-- Título de Pregrado -->
                    <div class="input-group">
                        <label for="titulo_pregrado" class="required-field">TÍTULO DE PREGRADO</label>
                        <div class="input-wrapper">
                            <i class="fas fa-certificate"></i>
                            <input type="text" id="titulo_pregrado" name="titulo_pregrado" 
                                   placeholder="Ej: Ingeniero de Sistemas" 
                                   value="<?php echo htmlspecialchars($_POST['titulo_pregrado'] ?? ''); ?>"
                                   required>
                        </div>
                    </div>

                    <!-- Universidad de Egreso -->
                    <div class="input-group">
                        <label for="universidad_egreso" class="required-field">UNIVERSIDAD DE EGRESO</label>
                        <div class="input-wrapper">
                            <i class="fas fa-university"></i>
                            <input type="text" id="universidad_egreso" name="universidad_egreso" 
                                   placeholder="Ej: UNEFA" 
                                   value="<?php echo htmlspecialchars($_POST['universidad_egreso'] ?? ''); ?>"
                                   required>
                        </div>
                    </div>

                    <!-- Profesión (SOLO VALORES PERMITIDOS) -->
                    <div class="input-group">
                        <label for="profesion" class="required-field">PROFESIÓN</label>
                        <div class="input-wrapper">
                            <i class="fas fa-briefcase"></i>
                            <select id="profesion" name="profesion" required>
                                <option value="">Selecciona tu profesión</option>
                                <option value="Ingeniero" <?php echo (($_POST['profesion'] ?? '') == 'Ingeniero') ? 'selected' : ''; ?>>Ingeniero</option>
                                <option value="Licenciado" <?php echo (($_POST['profesion'] ?? '') == 'Licenciado') ? 'selected' : ''; ?>>Licenciado</option>
                                <option value="Arquitecto" <?php echo (($_POST['profesion'] ?? '') == 'Arquitecto') ? 'selected' : ''; ?>>Arquitecto</option>
                                <option value="Abogado" <?php echo (($_POST['profesion'] ?? '') == 'Abogado') ? 'selected' : ''; ?>>Abogado</option>
                                <option value="Médico" <?php echo (($_POST['profesion'] ?? '') == 'Médico') ? 'selected' : ''; ?>>Médico</option>
                                <option value="Economista" <?php echo (($_POST['profesion'] ?? '') == 'Economista') ? 'selected' : ''; ?>>Economista</option>
                                <option value="Administrador" <?php echo (($_POST['profesion'] ?? '') == 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
                                <option value="Contador" <?php echo (($_POST['profesion'] ?? '') == 'Contador') ? 'selected' : ''; ?>>Contador</option>
                                <option value="Psicólogo" <?php echo (($_POST['profesion'] ?? '') == 'Psicólogo') ? 'selected' : ''; ?>>Psicólogo</option>
                                <option value="Otro" <?php echo (($_POST['profesion'] ?? '') == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                            </select>
                        </div>
                    </div>

                    <!-- Año de Graduación -->
                    <div class="input-group">
                        <label for="anio_graduacion">AÑO DE GRADUACIÓN</label>
                        <div class="input-wrapper">
                            <i class="fas fa-calendar"></i>
                            <select id="anio_graduacion" name="anio_graduacion">
                                <option value="">Selecciona un año</option>
                                <?php for($year = date('Y'); $year >= 1980; $year--): ?>
                                    <option value="<?php echo $year; ?>" <?php echo (($_POST['anio_graduacion'] ?? '') == $year) ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Fecha de Nacimiento -->
                    <div class="input-group">
                        <label for="fecha_nacimiento">FECHA DE NACIMIENTO</label>
                        <div class="input-wrapper">
                            <i class="fas fa-birthday-cake"></i>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" 
                                   value="<?php echo htmlspecialchars($_POST['fecha_nacimiento'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Nacionalidad -->
                    <div class="input-group">
                        <label for="nacionalidad">NACIONALIDAD</label>
                        <div class="input-wrapper">
                            <i class="fas fa-globe"></i>
                            <select id="nacionalidad" name="nacionalidad">
                                <option value="">Selecciona</option>
                                <option value="Venezolana" <?php echo (($_POST['nacionalidad'] ?? '') == 'Venezolana') ? 'selected' : ''; ?>>Venezolana</option>
                                <option value="Colombiana" <?php echo (($_POST['nacionalidad'] ?? '') == 'Colombiana') ? 'selected' : ''; ?>>Colombiana</option>
                                <option value="Española" <?php echo (($_POST['nacionalidad'] ?? '') == 'Española') ? 'selected' : ''; ?>>Española</option>
                                <option value="Italiana" <?php echo (($_POST['nacionalidad'] ?? '') == 'Italiana') ? 'selected' : ''; ?>>Italiana</option>
                                <option value="Portuguesa" <?php echo (($_POST['nacionalidad'] ?? '') == 'Portuguesa') ? 'selected' : ''; ?>>Portuguesa</option>
                                <option value="Otra" <?php echo (($_POST['nacionalidad'] ?? '') == 'Otra') ? 'selected' : ''; ?>>Otra</option>
                            </select>
                        </div>
                    </div>

                    <!-- Teléfono Alterno -->
                    <div class="input-group">
                        <label for="telefono_alterno">TELÉFONO ALTERNO</label>
                        <div class="input-wrapper">
                            <i class="fas fa-phone-alt"></i>
                            <input type="tel" id="telefono_alterno" name="telefono_alterno" 
                                   placeholder="Ej: 04121234567" 
                                   value="<?php echo htmlspecialchars($_POST['telefono_alterno'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Dirección Alterna -->
                    <div class="input-group full-width">
                        <label for="direccion_alterna">DIRECCIÓN ALTERNA</label>
                        <div class="input-wrapper">
                            <i class="fas fa-map-marker-alt"></i>
                            <textarea id="direccion_alterna" name="direccion_alterna" 
                                      placeholder="Escribe tu dirección de residencia actual..."><?php echo htmlspecialchars($_POST['direccion_alterna'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> GUARDAR PERFIL ACADÉMICO
                </button>

                <div class="form-note">
                    <i class="fas fa-info-circle"></i> Los campos marcados con * son obligatorios
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Validación de teléfono (solo números)
    document.getElementById('telefono_alterno').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
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
require_once __DIR__ . '/../../../includes/footer.php';
?>