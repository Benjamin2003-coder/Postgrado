<?php
// src/user/modules/perfil/perfil.php
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/nav.php';
require_once __DIR__ . '/../../../../config/conexion.php';

$id_usuario = $_SESSION['usuario_id'];
$mensaje = '';
$error = '';

// Obtener datos completos del usuario y estudiante
$sql = "SELECT u.*, e.* 
        FROM usuarios u 
        LEFT JOIN estudiantes_posgrado e ON u.id_usuario = e.id_usuario 
        WHERE u.id_usuario = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Verificar si el perfil académico está completo
$perfil_academico_completo = !empty($user['id_estudiante']);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'actualizar_perfil') {
        // Datos de usuarios (si el formulario no envía estos campos, usar los existentes)
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        if ($nombre === '') $nombre = $user['nombre'] ?? '';
        if ($apellido === '') $apellido = $user['apellido'] ?? '';
        if ($email === '') $email = $user['email'] ?? '';
        if ($telefono === '') $telefono = $user['telefono'] ?? '';
        if ($direccion === '') $direccion = $user['direccion'] ?? '';

        // Datos de estudiantes_posgrado
        $titulo_pregrado = trim($_POST['titulo_pregrado'] ?? '');
        $universidad_egreso = trim($_POST['universidad_egreso'] ?? '');
        $anio_graduacion = trim($_POST['anio_graduacion'] ?? '');
        $profesion = trim($_POST['profesion'] ?? '');
        $direccion_alterna = trim($_POST['direccion_alterna'] ?? '');
        $telefono_alterno = trim($_POST['telefono_alterno'] ?? '');
        $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
        $nacionalidad = trim($_POST['nacionalidad'] ?? '');

        // Conservar valores existentes si el usuario no los modificó (evitar sobrescribir con vacíos)
        if ($titulo_pregrado === '') $titulo_pregrado = $user['titulo_pregrado'] ?? null;
        if ($universidad_egreso === '') $universidad_egreso = $user['universidad_egreso'] ?? null;
        if ($anio_graduacion === '') $anio_graduacion = $user['anio_graduacion'] ?? null;
        if ($profesion === '') $profesion = $user['profesion'] ?? null;
        if ($direccion_alterna === '') $direccion_alterna = $user['direccion_alterna'] ?? null;
        if ($telefono_alterno === '') $telefono_alterno = $user['telefono_alterno'] ?? null;
        if ($nacionalidad === '') $nacionalidad = $user['nacionalidad'] ?? null;

        // Para fecha de nacimiento: si no se envía, conservar la existente; si se envía, validar formato
        if ($fecha_nacimiento === '') {
            $fecha_nacimiento = $user['fecha_nacimiento'] ?? null;
        } else {
            $d = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
            if (!($d && $d->format('Y-m-d') === $fecha_nacimiento)) {
                $errors[] = "La fecha de nacimiento no tiene un formato válido (AAAA-MM-DD)";
            }
        }

        // Inicializar array de errores para validaciones posteriores (no sobreescribe errores previos)
        if (!isset($errors)) $errors = [];

        // Validaciones mínimas: si tras conservar valores siguen vacíos, son errores
        if (empty($nombre)) $errors[] = "El nombre es requerido";
        if (empty($apellido)) $errors[] = "El apellido es requerido";
        if (empty($email)) $errors[] = "El email es requerido";
        
        // Validar email único (excepto el propio)
        $sql_check = "SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?";
        $stmt_check = $mysqli->prepare($sql_check);
        $stmt_check->bind_param("ss", $email, $id_usuario);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $errors[] = "El email ya está registrado por otro usuario";
        }

        // Validar teléfonos
        if (!empty($telefono)) {
            $telefono = preg_replace('/\D/', '', $telefono);
            if (strlen($telefono) < 10 || strlen($telefono) > 11) {
                $errors[] = "El teléfono debe tener entre 10 y 11 dígitos";
            }
        }
        
        if (!empty($telefono_alterno)) {
            $telefono_alterno = preg_replace('/\D/', '', $telefono_alterno);
            if (strlen($telefono_alterno) < 10 || strlen($telefono_alterno) > 11) {
                $errors[] = "El teléfono alterno debe tener entre 10 y 11 dígitos";
            }
        }

        // Validar año de graduación
        if (!empty($anio_graduacion) && (!is_numeric($anio_graduacion) || $anio_graduacion < 1900 || $anio_graduacion > date('Y'))) {
            $errors[] = "El año de graduación no es válido";
        }

        // Validar profesión (si se proporciona)
        $profesiones_permitidas = ['Ingeniero', 'Licenciado', 'Arquitecto', 'Abogado', 'Médico', 'Economista', 'Administrador', 'Contador', 'Psicólogo', 'Otro'];
        if (!empty($profesion) && !in_array($profesion, $profesiones_permitidas)) {
            $errors[] = "Profesión no válida";
        }

        if (empty($errors)) {
            // Iniciar transacción
            $mysqli->begin_transaction();

            try {
                // Actualizar tabla usuarios
                $sql_user = "UPDATE usuarios SET 
                            nombre = ?, apellido = ?, email = ?, 
                            telefono = ?, direccion = ? 
                            WHERE id_usuario = ?";
                $stmt_user = $mysqli->prepare($sql_user);
                $stmt_user->bind_param("ssssss", $nombre, $apellido, $email, $telefono, $direccion, $id_usuario);
                $stmt_user->execute();

                // Si existe el perfil académico, actualizarlo
                if ($perfil_academico_completo) {
                    $sql_est = "UPDATE estudiantes_posgrado SET 
                                titulo_pregrado = ?, universidad_egreso = ?,
                                anio_graduacion = ?, profesion = ?,
                                direccion_alterna = ?, telefono_alterno = ?,
                                fecha_nacimiento = ?, nacionalidad = ?
                                WHERE id_usuario = ?";
                    $stmt_est = $mysqli->prepare($sql_est);
                    $stmt_est->bind_param(
                        "ssissssss",
                        $titulo_pregrado, $universidad_egreso,
                        $anio_graduacion, $profesion,
                        $direccion_alterna, $telefono_alterno,
                        $fecha_nacimiento, $nacionalidad,
                        $id_usuario
                    );
                    $stmt_est->execute();
                }

                $mysqli->commit();
                
                // Actualizar sesión
                $_SESSION['usuario_nombre'] = $nombre;
                $_SESSION['usuario_apellido'] = $apellido;
                $_SESSION['usuario_email'] = $email;
                
                $mensaje = "Perfil actualizado correctamente";
                
                // Recargar datos
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

            } catch (Exception $e) {
                $mysqli->rollback();
                $error = "Error al actualizar: " . $e->getMessage();
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }
    
    elseif ($accion === 'cambiar_foto') {
        // Procesar cambio de foto
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $foto = $_FILES['foto_perfil'];
            $extension = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($extension, $permitidas) && $foto['size'] <= 10 * 1024 * 1024) {
                // Eliminar foto anterior si existe
                if (!empty($user['foto_perfil'])) {
                    $ruta_anterior = __DIR__ . '/../../../../assets/uploads/perfil/' . $user['foto_perfil'];
                    if (file_exists($ruta_anterior)) {
                        unlink($ruta_anterior);
                    }
                }
                
                $nombre_archivo = 'perfil_' . $id_usuario . '_' . time() . '.' . $extension;
                $ruta_destino = __DIR__ . '/../../../../assets/uploads/perfil/' . $nombre_archivo;
                
                if (move_uploaded_file($foto['tmp_name'], $ruta_destino)) {
                    $sql = "UPDATE usuarios SET foto_perfil = ? WHERE id_usuario = ?";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param("ss", $nombre_archivo, $id_usuario);
                    $stmt->execute();
                    
                    $_SESSION['usuario_foto'] = $nombre_archivo;
                    // Actualizar también la URL pública en sesión
                    $_SESSION['usuario_foto_url'] = '/POSGRADO/assets/uploads/perfil/' . $nombre_archivo;
                    $user['foto_perfil'] = $nombre_archivo;
                    $mensaje = "Foto de perfil actualizada";
                    // Actualizar imagen en la cabecera sin recargar la página
                    $foto_js_url = $_SESSION['usuario_foto_url'];
                    echo "<script> (function(){ var u=" . json_encode($foto_js_url) . "; document.querySelectorAll('.user-avatar, .dropdown-header img').forEach(function(img){ if(img) img.src = u; }); })();</script>";
                } else {
                    $error = "Error al subir la foto";
                }
            } else {
                $error = "Formato no válido o archivo muy grande (máx 10MB)";
            }
        }
    }
    
    elseif ($accion === 'cambiar_password') {
        $password_actual = $_POST['password_actual'] ?? '';
        $password_nuevo = $_POST['password_nuevo'] ?? '';
        $password_confirmar = $_POST['password_confirmar'] ?? '';
        
        // Verificar contraseña actual
        $sql = "SELECT password_hash FROM usuarios WHERE id_usuario = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $id_usuario);
        $stmt->execute();
        $hash_actual = $stmt->get_result()->fetch_assoc()['password_hash'];
        
        if (!password_verify($password_actual, $hash_actual)) {
            $error = "La contraseña actual es incorrecta";
        } elseif (strlen($password_nuevo) < 6) {
            $error = "La nueva contraseña debe tener al menos 6 caracteres";
        } elseif ($password_nuevo !== $password_confirmar) {
            $error = "Las contraseñas no coinciden";
        } elseif (password_verify($password_nuevo, $hash_actual)) {
            $error = "La nueva contraseña no puede ser igual a la actual";
        } else {
            $hash_nuevo = password_hash($password_nuevo, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ss", $hash_nuevo, $id_usuario);
            
            if ($stmt->execute()) {
                $mensaje = "Contraseña cambiada correctamente";
            } else {
                $error = "Error al cambiar la contraseña";
            }
        }
    }
    
    elseif ($accion === 'actualizar_preguntas') {
        // Actualizar preguntas de seguridad
        $preguntas = [];
        $respuestas = [];
        
        for ($i = 1; $i <= 3; $i++) {
            $sel = $_POST["pregunta$i"] ?? '';
            if ($sel === '__personalizada__') {
                $pregunta = trim($_POST["pregunta_custom$i"] ?? '');
            } else {
                $pregunta = trim($sel);
            }
            $respuesta = trim($_POST["respuesta$i"] ?? '');

            if (!empty($pregunta) && !empty($respuesta)) {
                $preguntas[] = $pregunta;
                $respuestas[] = $respuesta;
            }
        }
        
        // Verificar que no haya preguntas duplicadas
        if (count($preguntas) !== count(array_unique($preguntas))) {
            $error = "No puedes repetir la misma pregunta de seguridad";
        } else {
            // Actualizar en la base de datos
            $sql = "UPDATE usuarios SET 
                    pregunta_seguridad = ?, respuesta_seguridad = ?,
                    pregunta_seguridad_2 = ?, respuesta_seguridad_2 = ?,
                    pregunta_seguridad_3 = ?, respuesta_seguridad_3 = ?
                    WHERE id_usuario = ?";
            
            $stmt = $mysqli->prepare($sql);
            $p1 = $preguntas[0] ?? null;
            $r1 = $respuestas[0] ?? null;
            $p2 = $preguntas[1] ?? null;
            $r2 = $respuestas[1] ?? null;
            $p3 = $preguntas[2] ?? null;
            $r3 = $respuestas[2] ?? null;
            
            $stmt->bind_param("sssssss", $p1, $r1, $p2, $r2, $p3, $r3, $id_usuario);
            
            if ($stmt->execute()) {
                $mensaje = "Preguntas de seguridad actualizadas";
                // Recargar datos
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $error = "Error al actualizar las preguntas";
            }
        }
    }
}

// Preparar array de preguntas para el formulario
$preguntas_actuales = [
    1 => ['pregunta' => $user['pregunta_seguridad'] ?? '', 'respuesta' => $user['respuesta_seguridad'] ?? ''],
    2 => ['pregunta' => $user['pregunta_seguridad_2'] ?? '', 'respuesta' => $user['respuesta_seguridad_2'] ?? ''],
    3 => ['pregunta' => $user['pregunta_seguridad_3'] ?? '', 'respuesta' => $user['respuesta_seguridad_3'] ?? '']
];

$preguntas_disponibles = [
    '¿Cuál es el nombre de tu madre?',
    '¿Cuál es el nombre de tu padre?',
    '¿Cuál es el nombre de tu primera mascota?',
    '¿En qué ciudad naciste?',
    '¿Cuál es el nombre de tu escuela primaria?',
    '¿Cuál es tu comida favorita?',
    '¿Cuál fue tu primer trabajo?',
    '¿Cuál es el nombre de tu abuela?',
    '¿Cuál es tu libro favorito?',
    '¿Cuál es tu película favorita?'
];
?>

<style>
    /* Estilos del perfil */
    .perfil-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .perfil-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }

    @media (max-width: 992px) {
        .perfil-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Tarjetas */
    .perfil-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        border: 1px solid rgba(139, 30, 63, 0.1);
        margin-bottom: 25px;
        transition: all 0.3s ease;
    }

    .perfil-card:hover {
        box-shadow: 0 15px 40px rgba(139, 30, 63, 0.15);
        border-color: #8B1E3F;
    }

    .card-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }

    .card-header i {
        font-size: 1.8rem;
        color: #F2A900;
        background: rgba(242, 169, 0, 0.1);
        padding: 15px;
        border-radius: 15px;
    }

    .card-header h2 {
        font-size: 1.4rem;
        color: #8B1E3F;
        font-weight: 600;
    }

    .card-header p {
        color: #6c757d;
        font-size: 0.85rem;
        margin-top: 5px;
    }

    /* Foto de perfil */
    .foto-section {
        display: flex;
        align-items: center;
        gap: 30px;
        margin-bottom: 30px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 15px;
    }

    .foto-container {
        position: relative;
    }

    .foto-perfil {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #8B1E3F;
        box-shadow: 0 10px 20px rgba(139, 30, 63, 0.2);
    }

    .foto-placeholder {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8B1E3F 0%, #6a1730 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 3rem;
        border: 4px solid #F2A900;
    }

    .foto-upload {
        flex: 1;
    }

    .foto-upload .btn {
        display: inline-block;
        padding: 12px 25px;
        background: #8B1E3F;
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .foto-upload .btn:hover {
        background: #6a1730;
        transform: translateY(-2px);
    }

    .foto-info {
        margin-top: 10px;
        font-size: 0.8rem;
        color: #6c757d;
    }

    /* Grid del formulario */
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
        font-size: 0.8rem;
        color: #495057;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    label i {
        margin-right: 5px;
        color: #F2A900;
    }

    input, select, textarea {
        width: 100%;
        padding: 12px 15px;
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

    input[readonly] {
        background: #e9ecef;
        cursor: not-allowed;
        border-color: #ced4da;
        color: #495057;
    }

    textarea {
        resize: vertical;
        min-height: 80px;
    }

    /* Código de estudiante - especial */
    .codigo-estudiante {
        background: #f0f7ff;
        border-color: #8B1E3F;
        color: #8B1E3F;
        font-weight: 600;
        font-size: 1rem;
        letter-spacing: 1px;
    }

    /* Password container */
    .password-container {
        position: relative;
    }

    .password-toggle {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #6c757d;
        cursor: pointer;
        font-size: 1rem;
    }

    .password-toggle:hover {
        color: #8B1E3F;
    }

    /* Fortaleza de contraseña */
    .password-strength {
        height: 5px;
        background: #e1e1e1;
        border-radius: 10px;
        margin-top: 8px;
        overflow: hidden;
    }

    .strength-bar {
        height: 100%;
        width: 0;
        transition: width 0.3s ease;
    }

    .strength-weak { background: #dc3545; }
    .strength-fair { background: #ffc107; }
    .strength-good { background: #17a2b8; }
    .strength-strong { background: #28a745; }

    /* Preguntas de seguridad */
    .pregunta-group {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 15px;
        border-left: 4px solid #8B1E3F;
    }

    .pregunta-group select {
        margin-bottom: 10px;
    }

    /* Botones */
    .form-actions {
        margin-top: 25px;
        display: flex;
        justify-content: flex-end;
    }

    .btn-primary {
        padding: 14px 30px;
        background: linear-gradient(135deg, #8B1E3F 0%, #6a1730 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
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

    /* Info adicional */
    .info-box {
        background: #e7f3ff;
        border-left: 4px solid #8B1E3F;
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
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

    /* Separador */
    .separator {
        height: 2px;
        background: linear-gradient(90deg, transparent, #F2A900, transparent);
        margin: 30px 0;
    }
</style>

<div class="main-content">
    <div class="perfil-container">
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

        <div class="perfil-grid">
            <!-- COLUMNA IZQUIERDA -->
            <div class="perfil-columna">
                <!-- Foto de perfil (formulario separado) -->
                <div class="perfil-card">
                    <div class="card-header">
                        <i class="fas fa-camera"></i>
                        <div>
                            <h2>Foto de Perfil</h2>
                            <p>Actualiza tu imagen personal</p>
                        </div>
                    </div>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="accion" value="cambiar_foto">
                        
                        <div class="foto-section">
                            <div class="foto-container">
                                              <?php if (!empty($user['foto_perfil'])): ?>
                                                 <img src="<?php echo $_SESSION['usuario_foto_url'] ?? '/POSGRADO/assets/uploads/perfil/' . $user['foto_perfil']; ?>" 
                                                     alt="Foto de perfil" 
                                                     class="foto-perfil"
                                                     id="fotoPreview"
                                                     onerror="this.src='/POSGRADO/public/images/default-avatar.png'">
                                <?php else: ?>
                                    <div class="foto-placeholder">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="foto-upload">
                                <label for="foto_perfil" class="btn">
                                    <i class="fas fa-upload"></i> Seleccionar foto
                                </label>
                                <input type="file" id="foto_perfil" name="foto_perfil" 
                                       accept="image/jpeg,image/png,image/gif,image/webp" 
                                       onchange="previewImage(this)" hidden>
                                <p class="foto-info">
                                    <i class="fas fa-info-circle"></i> 
                                    JPG, PNG, GIF, WEBP - Máx. 10MB
                                </p>
                                <button type="submit" class="btn" style="margin-top: 10px;">
                                    <i class="fas fa-save"></i> Guardar foto
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Información Personal (usuarios) -->
                <div class="perfil-card">
                    <div class="card-header">
                        <i class="fas fa-id-card"></i>
                        <div>
                            <h2>Información Personal</h2>
                            <p>Tus datos básicos de contacto</p>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="accion" value="actualizar_perfil">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Nombre *</label>
                                <input type="text" name="nombre" required 
                                       value="<?php echo htmlspecialchars($user['nombre'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Apellido *</label>
                                <input type="text" name="apellido" required 
                                       value="<?php echo htmlspecialchars($user['apellido'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email *</label>
                                <input type="email" name="email" required 
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-phone"></i> Teléfono</label>
                                <input type="tel" name="telefono" 
                                       value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>"
                                       placeholder="Ej: 04121234567">
                            </div>
                            
                            <div class="form-group full-width">
                                <label><i class="fas fa-map-marker-alt"></i> Dirección</label>
                                <textarea name="direccion" rows="3"><?php echo htmlspecialchars($user['direccion'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Cambiar Contraseña -->
                <div class="perfil-card">
                    <div class="card-header">
                        <i class="fas fa-lock"></i>
                        <div>
                            <h2>Cambiar Contraseña</h2>
                            <p>Actualiza tu clave de acceso</p>
                        </div>
                    </div>

                    <form method="POST" action="" id="passwordForm">
                        <input type="hidden" name="accion" value="cambiar_password">
                        
                        <div class="form-group">
                            <label><i class="fas fa-key"></i> Contraseña actual *</label>
                            <div class="password-container">
                                <input type="password" name="password_actual" id="password_actual" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('password_actual')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Nueva contraseña *</label>
                            <div class="password-container">
                                <input type="password" name="password_nuevo" id="password_nuevo" required minlength="6">
                                <button type="button" class="password-toggle" onclick="togglePassword('password_nuevo')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="strength-bar" id="passwordStrength"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Confirmar contraseña *</label>
                            <div class="password-container">
                                <input type="password" name="password_confirmar" id="password_confirmar" required minlength="6">
                                <button type="button" class="password-toggle" onclick="togglePassword('password_confirmar')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-key"></i> Cambiar contraseña
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- COLUMNA DERECHA -->
            <div class="perfil-columna">
                <!-- Perfil Académico -->
                <div class="perfil-card">
                    <div class="card-header">
                        <i class="fas fa-graduation-cap"></i>
                        <div>
                            <h2>Perfil Académico</h2>
                            <p>Tu información de estudiante</p>
                        </div>
                    </div>

                    <?php if (!$perfil_academico_completo): ?>
                        <div class="info-box">
                            <i class="fas fa-info-circle"></i>
                            <p>No has completado tu perfil académico. 
                               <a href="/posgrado/src/user/modules/perfil/completar_perfil.php" style="color: #8B1E3F; font-weight: 600;">
                                   Haz clic aquí para completarlo
                               </a>
                            </p>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="accion" value="actualizar_perfil">
                            
                            <div class="form-grid">
                                <!-- Código de estudiante (no editable) -->
                                <div class="form-group full-width">
                                    <label><i class="fas fa-qrcode"></i> Código de Estudiante</label>
                                    <input type="text" class="codigo-estudiante" readonly
                                           value="<?php echo htmlspecialchars($user['codigo_estudiante'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-certificate"></i> Título de Pregrado</label>
                                    <input type="text" name="titulo_pregrado" 
                                           value="<?php echo htmlspecialchars($user['titulo_pregrado'] ?? ''); ?>"
                                           placeholder="Ej: Ingeniero de Sistemas">
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-university"></i> Universidad de Egreso</label>
                                    <input type="text" name="universidad_egreso" 
                                           value="<?php echo htmlspecialchars($user['universidad_egreso'] ?? ''); ?>"
                                           placeholder="Ej: UNEFA">
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-calendar"></i> Año de Graduación</label>
                                    <select name="anio_graduacion">
                                        <option value="">Selecciona</option>
                                        <?php for($year = date('Y'); $year >= 1980; $year--): ?>
                                            <option value="<?php echo $year; ?>" 
                                                <?php echo ($user['anio_graduacion'] ?? '') == $year ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-briefcase"></i> Profesión</label>
                                    <select name="profesion">
                                        <option value="">Selecciona</option>
                                        <option value="Ingeniero" <?php echo ($user['profesion'] ?? '') == 'Ingeniero' ? 'selected' : ''; ?>>Ingeniero</option>
                                        <option value="Licenciado" <?php echo ($user['profesion'] ?? '') == 'Licenciado' ? 'selected' : ''; ?>>Licenciado</option>
                                        <option value="Arquitecto" <?php echo ($user['profesion'] ?? '') == 'Arquitecto' ? 'selected' : ''; ?>>Arquitecto</option>
                                        <option value="Abogado" <?php echo ($user['profesion'] ?? '') == 'Abogado' ? 'selected' : ''; ?>>Abogado</option>
                                        <option value="Médico" <?php echo ($user['profesion'] ?? '') == 'Médico' ? 'selected' : ''; ?>>Médico</option>
                                        <option value="Economista" <?php echo ($user['profesion'] ?? '') == 'Economista' ? 'selected' : ''; ?>>Economista</option>
                                        <option value="Administrador" <?php echo ($user['profesion'] ?? '') == 'Administrador' ? 'selected' : ''; ?>>Administrador</option>
                                        <option value="Contador" <?php echo ($user['profesion'] ?? '') == 'Contador' ? 'selected' : ''; ?>>Contador</option>
                                        <option value="Psicólogo" <?php echo ($user['profesion'] ?? '') == 'Psicólogo' ? 'selected' : ''; ?>>Psicólogo</option>
                                        <option value="Otro" <?php echo ($user['profesion'] ?? '') == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-birthday-cake"></i> Fecha de Nacimiento</label>
                                    <input type="date" name="fecha_nacimiento" 
                                           value="<?php echo htmlspecialchars($user['fecha_nacimiento'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-globe"></i> Nacionalidad</label>
                                    <select name="nacionalidad">
                                        <option value="">Selecciona</option>
                                        <option value="Venezolana" <?php echo ($user['nacionalidad'] ?? '') == 'Venezolana' ? 'selected' : ''; ?>>Venezolana</option>
                                        <option value="Colombiana" <?php echo ($user['nacionalidad'] ?? '') == 'Colombiana' ? 'selected' : ''; ?>>Colombiana</option>
                                        <option value="Española" <?php echo ($user['nacionalidad'] ?? '') == 'Española' ? 'selected' : ''; ?>>Española</option>
                                        <option value="Italiana" <?php echo ($user['nacionalidad'] ?? '') == 'Italiana' ? 'selected' : ''; ?>>Italiana</option>
                                        <option value="Portuguesa" <?php echo ($user['nacionalidad'] ?? '') == 'Portuguesa' ? 'selected' : ''; ?>>Portuguesa</option>
                                        <option value="Otra" <?php echo ($user['nacionalidad'] ?? '') == 'Otra' ? 'selected' : ''; ?>>Otra</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-phone-alt"></i> Teléfono Alterno</label>
                                    <input type="tel" name="telefono_alterno" 
                                           value="<?php echo htmlspecialchars($user['telefono_alterno'] ?? ''); ?>"
                                           placeholder="Ej: 04121234567">
                                </div>
                                
                                <div class="form-group full-width">
                                    <label><i class="fas fa-map-marker-alt"></i> Dirección Alterna</label>
                                    <textarea name="direccion_alterna" rows="3"><?php echo htmlspecialchars($user['direccion_alterna'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i> Guardar cambios académicos
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Preguntas de Seguridad -->
                <div class="perfil-card">
                    <div class="card-header">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <h2>Preguntas de Seguridad</h2>
                            <p>Para recuperar tu cuenta</p>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="accion" value="actualizar_preguntas">
                        
                        <p class="form-description" style="margin-bottom: 20px; color: #6c757d;">
                            Puedes tener hasta 3 preguntas de seguridad. Las preguntas no pueden repetirse.
                        </p>
                        
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                        <div class="pregunta-group">
                            <label>Pregunta <?php echo $i; ?> <?php echo $i === 1 ? '*' : '(opcional)'; ?></label>
                            <?php $current = $preguntas_actuales[$i]['pregunta'] ?? ''; ?>
                            <select name="pregunta<?php echo $i; ?>" class="pregunta-select" data-index="<?php echo $i; ?>" id="pregunta_select_<?php echo $i; ?>">
                                <option value=""><?php echo $i === 1 ? 'Selecciona una pregunta' : '-- Ninguna --'; ?></option>
                                <?php foreach ($preguntas_disponibles as $pregunta): ?>
                                    <option value="<?php echo htmlspecialchars($pregunta); ?>" 
                                        <?php echo $current == $pregunta ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pregunta); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="__personalizada__" <?php echo ($current !== '' && !in_array($current, $preguntas_disponibles)) ? 'selected' : ''; ?>>Personalizada</option>
                            </select>

                            <input type="text" name="pregunta_custom<?php echo $i; ?>" id="pregunta_custom_<?php echo $i; ?>" placeholder="Escribe tu pregunta personalizada"
                                   value="<?php echo htmlspecialchars((!in_array($current, $preguntas_disponibles) ? $current : '')); ?>"
                                   style="margin-top:8px; display: <?php echo ($current !== '' && !in_array($current, $preguntas_disponibles)) ? 'block' : 'none'; ?>;">

                            <input type="text" name="respuesta<?php echo $i; ?>" 
                                   placeholder="Tu respuesta"
                                   value="<?php echo htmlspecialchars($preguntas_actuales[$i]['respuesta'] ?? ''); ?>"
                                   style="margin-top: 10px;">
                        </div>
                        <?php endfor; ?>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-shield-alt"></i> Guardar preguntas
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Información de Cuenta -->
                <div class="perfil-card">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <h2>Información de Cuenta</h2>
                            <p>Detalles de tu registro</p>
                        </div>
                    </div>

                    <div style="padding: 20px; background: #f8f9fa; border-radius: 12px;">
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                            <i class="fas fa-id-card" style="color: #8B1E3F; font-size: 1.2rem;"></i>
                            <div>
                                <div style="font-size: 0.75rem; color: #6c757d;">ID de Usuario</div>
                                <div style="font-weight: 600; color: #495057;"><?php echo $user['id_usuario']; ?></div>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                            <i class="fas fa-user-tag" style="color: #8B1E3F; font-size: 1.2rem;"></i>
                            <div>
                                <div style="font-size: 0.75rem; color: #6c757d;">Rol</div>
                                <div><span style="background: #8B1E3F; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem;">Estudiante</span></div>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                            <i class="fas fa-calendar-plus" style="color: #8B1E3F; font-size: 1.2rem;"></i>
                            <div>
                                <div style="font-size: 0.75rem; color: #6c757d;">Fecha de Registro</div>
                                <div style="font-weight: 600; color: #495057;">
                                    <?php echo date('d/m/Y', strtotime($user['fecha_registro'] ?? 'now')); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <i class="fas fa-check-circle" style="color: #8B1E3F; font-size: 1.2rem;"></i>
                            <div>
                                <div style="font-size: 0.75rem; color: #6c757d;">Estado de la Cuenta</div>
                                <div><span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem;">Activa</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Previsualización de imagen
    function previewImage(input) {
        const preview = document.getElementById('fotoPreview');
        if (!preview) {
            const container = document.querySelector('.foto-container');
            const img = document.createElement('img');
            img.id = 'fotoPreview';
            img.className = 'foto-perfil';
            container.innerHTML = '';
            container.appendChild(img);
        }
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('fotoPreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Mostrar/ocultar contraseña
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const button = input.nextElementSibling;
        const icon = button.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Fortaleza de contraseña
    document.getElementById('password_nuevo')?.addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.getElementById('passwordStrength');
        if (!strengthBar) return;
        
        let strength = 0;
        
        if (password.length >= 6) strength += 25;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
        if (password.match(/\d/)) strength += 25;
        if (password.match(/[^a-zA-Z\d]/)) strength += 25;
        
        strengthBar.style.width = strength + '%';
        
        strengthBar.className = 'strength-bar';
        if (strength < 50) {
            strengthBar.classList.add('strength-weak');
        } else if (strength < 75) {
            strengthBar.classList.add('strength-fair');
        } else if (strength < 100) {
            strengthBar.classList.add('strength-good');
        } else {
            strengthBar.classList.add('strength-strong');
        }
    });

    // Validar contraseñas en el formulario
    document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
        const nueva = document.getElementById('password_nuevo').value;
        const confirmar = document.getElementById('password_confirmar').value;
        
        if (nueva !== confirmar) {
            e.preventDefault();
            alert('Las contraseñas no coinciden');
        }
    });

    // Sincronizar preguntas de seguridad
    function syncSecurityQuestions() {
        const selects = document.querySelectorAll('.pregunta-select');
        const values = Array.from(selects).map(s => s.value).filter(v => v);
        
        selects.forEach(select => {
            Array.from(select.options).forEach(opt => {
                if (opt.value && values.includes(opt.value) && opt.value !== select.value) {
                    opt.disabled = true;
                } else {
                    opt.disabled = false;
                }
            });
        });
    }

    document.querySelectorAll('.pregunta-select').forEach(select => {
        select.addEventListener('change', syncSecurityQuestions);
    });

    window.addEventListener('load', syncSecurityQuestions);
    // Mostrar campos personalizados cuando se selecciona la opción
    function togglePersonalizadaPerfil(i) {
        const sel = document.getElementById('pregunta_select_' + i);
        const cust = document.getElementById('pregunta_custom_' + i);
        if (!sel || !cust) return;
        function update() {
            if (sel.value === '__personalizada__') {
                cust.style.display = 'block';
                cust.required = true;
            } else {
                cust.style.display = 'none';
                cust.required = false;
            }
        }
        sel.addEventListener('change', update);
        update();
    }

    togglePersonalizadaPerfil(1);
    togglePersonalizadaPerfil(2);
    togglePersonalizadaPerfil(3);

    // Validación de teléfonos (solo números)
    document.querySelectorAll('input[type="tel"]').forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });
    });
</script>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>