<?php
// src/admin/modules/materias/agregar_materia.php
// Buffer output to allow redirects even if header.php outputs HTML
ob_start();
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/nav.php';
require_once __DIR__ . '/../../../../config/conexion.php';

// Obtener el ID del programa desde la URL o mediante selección
$programa_id = $_GET['programa_id'] ?? '';

// Si no se pasó por GET, permitir selección de programa (form) desde la misma página
if (empty($programa_id)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['programa_seleccionado'])) {
        header('Location: agregar_materia.php?programa_id=' . urlencode($_POST['programa_seleccionado']));
        exit();
    }

    // Mostrar formulario simple para seleccionar un programa antes de crear la materia
    $sql_list = "SELECT id_programa, nombre FROM programas_posgrado WHERE tipo_programa = 'especializacion' ORDER BY nombre";
    $result_list = $mysqli->query($sql_list);

    echo '<div class="main-content"><div class="agregar-container"><div class="form-card">';
    echo '<div class="form-header"><i class="fas fa-book"></i><h2>Selecciona un programa</h2><p>Antes de agregar una materia debes seleccionar el programa al que pertenecerá.</p></div>';
    echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">';
    echo '<div class="form-group"><label>Programa</label><select name="programa_seleccionado">';
    if ($result_list && $result_list->num_rows > 0) {
        while ($r = $result_list->fetch_assoc()) {
            echo '<option value="' . htmlspecialchars($r['id_programa']) . '">' . htmlspecialchars($r['nombre']) . '</option>';
        }
    } else {
        echo '<option value="">No hay programas disponibles</option>';
    }
    echo '</select></div>';
    echo '<div class="form-actions"><button type="submit" class="btn-primary"><i class="fas fa-arrow-right"></i> Continuar</button> <a href="/POSGRADO/src/admin/modules/especializacion/especializacion.php" class="btn-secondary">Cancelar</a></div>';
    echo '</form></div></div></div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// Obtener información del programa
$sql_programa = "SELECT * FROM programas_posgrado WHERE id_programa = ?";
$stmt_programa = $mysqli->prepare($sql_programa);
$stmt_programa->bind_param("s", $programa_id);
$stmt_programa->execute();
$result_programa = $stmt_programa->get_result();
$programa = $result_programa->fetch_assoc();

if (!$programa) {
    header('Location: /POSGRADO/src/admin/modules/especializacion/especializacion.php?error=Programa no encontrado');
    exit();
}

// Función para generar código de materia automático
function generarCodigoMateria($mysqli, $programa_id) {
    // Obtener el código del programa para usarlo como prefijo
    $sql = "SELECT codigo_programa FROM programas_posgrado WHERE id_programa = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $programa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $programa = $result->fetch_assoc();
    $prefijo = $programa['codigo_programa'] ?? 'MAT';

    // Detectar columna FK en la tabla materias (id_programa o id_maestria)
    $fk_col = null;
    $res = $mysqli->query("SHOW COLUMNS FROM materias LIKE 'id_programa'");
    if ($res && $res->num_rows > 0) {
        $fk_col = 'id_programa';
    } else {
        $res = $mysqli->query("SHOW COLUMNS FROM materias LIKE 'id_maestria'");
        if ($res && $res->num_rows > 0) {
            $fk_col = 'id_maestria';
        }
    }

    // Detectar columna de código (codigo_materia o id_materia)
    $code_col = null;
    $res = $mysqli->query("SHOW COLUMNS FROM materias LIKE 'codigo_materia'");
    if ($res && $res->num_rows > 0) {
        $code_col = 'codigo_materia';
    } else {
        $res = $mysqli->query("SHOW COLUMNS FROM materias LIKE 'id_materia'");
        if ($res && $res->num_rows > 0) {
            $code_col = 'id_materia';
        }
    }

    // Construir consulta segura según las columnas detectadas
    if ($code_col && $fk_col) {
        $sql = "SELECT `" . $code_col . "` as codigo FROM materias WHERE `" . $fk_col . "` = ? ORDER BY `" . $code_col . "` DESC LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $programa_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $ultimo = $result->fetch_assoc()['codigo'];
            // Intentar extraer un número al final después de un guion
            if (strpos($ultimo, '-') !== false) {
                $partes = explode('-', $ultimo);
                $numero = intval(end($partes));
                $nuevo_numero = $numero + 1;
            } else {
                // Si no tiene guion, incrementar numérico si hay dígitos al final
                preg_match('/(\d+)$/', $ultimo, $m);
                if (!empty($m[1])) {
                    $nuevo_numero = intval($m[1]) + 1;
                } else {
                    $nuevo_numero = 1;
                }
            }
        } else {
            $nuevo_numero = 1;
        }
    } else {
        // Si no se detectaron columnas esperadas, usar un contador simple
        $nuevo_numero = 1;
    }

    // Formatear con 3 dígitos
    $numero_formateado = str_pad($nuevo_numero, 3, '0', STR_PAD_LEFT);

    return substr($prefijo, 0, 3) . "-$numero_formateado";
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

// Generar código sugerido
$codigo_sugerido = generarCodigoMateria($mysqli, $programa_id);

$mensaje = '';
$error = '';

// Detectar columnas reales en la tabla materias para compatibilidad
$fk_col = null;
$res = $mysqli->query("SHOW COLUMNS FROM materias LIKE 'id_programa'");
if ($res && $res->num_rows > 0) {
    $fk_col = 'id_programa';
} else {
    $res = $mysqli->query("SHOW COLUMNS FROM materias LIKE 'id_maestria'");
    if ($res && $res->num_rows > 0) {
        $fk_col = 'id_maestria';
    }
}

$code_col = null;
$res = $mysqli->query("SHOW COLUMNS FROM materias LIKE 'codigo_materia'");
if ($res && $res->num_rows > 0) {
    $code_col = 'codigo_materia';
} else {
    $res = $mysqli->query("SHOW COLUMNS FROM materias LIKE 'id_materia'");
    if ($res && $res->num_rows > 0) {
        $code_col = 'id_materia';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    // El código puede ser provisto por el administrador o tomar el sugerido automáticamente
    $id_input = trim($_POST['id_materia'] ?? $_POST['codigo_materia'] ?? '');
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
    
    // Si no se proporcionó código, usar el sugerido
    if (empty($id_input)) {
        $id_input = $codigo_sugerido;
    }

    // Verificar unicidad según la columna real de la tabla
    if ($code_col === 'codigo_materia') {
        $sql_check = "SELECT id_materia FROM materias WHERE codigo_materia = ?";
    } else {
        $sql_check = "SELECT id_materia FROM materias WHERE id_materia = ?";
    }
    $stmt_check = $mysqli->prepare($sql_check);
    $stmt_check->bind_param("s", $id_input);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $errors[] = "El código de materia '$id_input' ya existe. Elige otro código.";
    }
    
    if (empty($errors)) {
        // Usar el código provisto por el administrador como ID de la materia
        $id_materia = strtoupper($id_input);

        // Construir INSERT según las columnas detectadas
        if ($code_col === 'codigo_materia' && $fk_col === 'id_programa') {
            $sql = "INSERT INTO materias 
                    (id_materia, codigo_materia, id_programa, nombre, descripcion, 
                     unidades_credito, horas_teoricas, horas_practicas, semestre, profesor, estado)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activa')";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sssssiiiis", 
                $id_materia,
                $id_input,
                $programa_id,
                $nombre,
                $descripcion,
                $unidades_credito,
                $horas_teoricas,
                $horas_practicas,
                $semestre,
                $profesor
            );
        } elseif ($code_col === 'id_materia' && $fk_col === 'id_maestria') {
            $sql = "INSERT INTO materias 
                    (id_materia, id_maestria, nombre, descripcion, 
                     unidades_credito, horas_teoricas, horas_practicas, semestre, profesor, estado)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activa')";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ssssiiiis", 
                $id_materia,
                $programa_id,
                $nombre,
                $descripcion,
                $unidades_credito,
                $horas_teoricas,
                $horas_practicas,
                $semestre,
                $profesor
            );
        } else {
            // Fallback genérico: intentar insertar con id_materia y fk_col si existe
            if ($fk_col) {
                $sql = "INSERT INTO materias (id_materia, `" . $fk_col . "`, nombre, descripcion, unidades_credito, horas_teoricas, horas_practicas, semestre, profesor, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activa')";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("ssssiiiis", 
                    $id_materia,
                    $programa_id,
                    $nombre,
                    $descripcion,
                    $unidades_credito,
                    $horas_teoricas,
                    $horas_practicas,
                    $semestre,
                    $profesor
                );
            } else {
                $errors[] = "Estructura de la tabla 'materias' no reconocida. Contacta al administrador.";
            }
        }
        
        if ($stmt->execute()) {
            // Actualizar los créditos totales del programa
            $nuevo_total = actualizarCreditosPrograma($mysqli, $programa_id);

            // Redirigir inmediatamente a la lista de especializaciones con mensaje de éxito
            header('Location: /POSGRADO/src/admin/modules/especializacion/especializacion.php?msg=added');
            exit();
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
       ESTILOS PARA AGREGAR MATERIA
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
        border-left: 6px solid #F2A900;
    }

    .form-header {
        text-align: center;
        margin-bottom: 30px;
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

    .programa-info {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
        border-left: 4px solid #8B1E3F;
    }

    .programa-info h3 {
        color: #8B1E3F;
        margin-bottom: 10px;
        font-size: 1.2rem;
    }

    .programa-info p {
        margin: 5px 0;
        color: #495057;
    }

    .programa-info strong {
        color: #8B1E3F;
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

    .creditos-info {
        background: #d4edda;
        border-left: 4px solid #28a745;
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .creditos-info i {
        font-size: 2rem;
        color: #28a745;
    }

    .creditos-info p {
        color: #155724;
        margin: 0;
        font-size: 0.95rem;
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
        color: #F2A900;
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
        border-color: #F2A900;
        background: white;
        box-shadow: 0 0 0 4px rgba(242, 169, 0, 0.1);
    }

    input:focus + i {
        color: #F2A900;
    }

    textarea {
        resize: vertical;
        min-height: 80px;
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

    /* Botones */
    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .btn-primary {
        flex: 1;
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
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(139, 30, 63, 0.3);
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
        color: #8B1E3F;
        border-color: #8B1E3F;
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

    .badge-programa {
        display: inline-block;
        background: #8B1E3F;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: 10px;
    }
</style>

<div class="main-content">
    <div class="agregar-container">
        <div class="form-card">
            <div class="form-header">
                <i class="fas fa-book"></i>
                <h2>Agregar Nueva Materia</h2>
                <p>Completa los datos para crear una nueva materia</p>
            </div>

            <!-- Información del programa -->
            <div class="programa-info">
                <h3>
                    <i class="fas fa-graduation-cap"></i> 
                    <?php echo htmlspecialchars($programa['nombre']); ?>
                    <span class="badge-programa"><?php echo htmlspecialchars($programa['codigo_programa']); ?></span>
                </h3>
                <p><i class="fas fa-clock"></i> Duración: <strong><?php echo $programa['duracion_meses']; ?> meses</strong></p>
                <p><i class="fas fa-star"></i> Créditos actuales: <strong><?php echo $programa['total_creditos'] ?? 0; ?></strong> (se actualizarán automáticamente)</p>
            </div>

            <!-- Info box -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <p>Se generará automáticamente un código de materia basado en el programa. Puedes modificarlo si es necesario.</p>
            </div>

            <!-- Créditos info -->
            <div class="creditos-info">
                <i class="fas fa-calculator"></i>
                <div>
                    <p><strong>⚡ Los créditos se suman automáticamente</strong></p>
                    <p>Al agregar esta materia, sus créditos se sumarán al total del programa. Si luego modificas o eliminas materias, el total se recalculará automáticamente.</p>
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
                    <!-- Código de Materia -->
                    <div class="form-group full-width">
                        <label for="id_materia" class="required-field">
                            <i class="fas fa-qrcode"></i> CÓDIGO DE LA MATERIA
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-hashtag"></i>
                            <input type="text" 
                                id="id_materia" 
                                name="id_materia" 
                                value="<?php echo htmlspecialchars($_POST['id_materia'] ?? $codigo_sugerido); ?>"
                                class="code-generated"
                                placeholder="<?php echo htmlspecialchars($codigo_sugerido); ?>">
                        </div>
                        <span class="code-suggestion">
                            <i class="fas fa-lightbulb"></i> Ingresa el código asignado por el administrador
                        </span>
                    </div>

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
                                   value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
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
                                      placeholder="Describe el contenido de la materia..."><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
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
                                   value="<?php echo htmlspecialchars($_POST['unidades_credito'] ?? '3'); ?>"
                                   min="1"
                                   max="10"
                                   step="1"
                                   required>
                        </div>
                        <small style="color: #6c757d;">Valor entre 1 y 10 créditos</small>
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
                                   value="<?php echo htmlspecialchars($_POST['horas_teoricas'] ?? '2'); ?>"
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
                                   value="<?php echo htmlspecialchars($_POST['horas_practicas'] ?? '2'); ?>"
                                   min="0"
                                   max="10">
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
                                   value="<?php echo htmlspecialchars($_POST['semestre'] ?? '1'); ?>"
                                   min="1"
                                   max="6">
                        </div>
                    </div>

                    <!-- Profesor -->
                    <div class="form-group">
                        <label for="profesor">
                            <i class="fas fa-user-tie"></i> PROFESOR
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" 
                                   id="profesor" 
                                   name="profesor" 
                                   value="<?php echo htmlspecialchars($_POST['profesor'] ?? ''); ?>"
                                   placeholder="Ej: Prof. Carlos Méndez">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> GUARDAR MATERIA
                    </button>
                    <a href="agregar_materia.php?programa_id=<?php echo urlencode($programa_id); ?>" class="btn-secondary">
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
// Flush output buffer and include footer
ob_end_flush();
require_once __DIR__ . '/../../includes/footer.php';
?>