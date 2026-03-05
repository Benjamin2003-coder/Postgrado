<?php
// includes/funciones.php - VERSIÓN COMPLETA PARA UNEFA POSTGRADO

/**
 * Verificar si usuario ya existe
 */
function usuarioExiste($email, $id_usuario, $mysqli) {
    // Buscar por email o por id_usuario (que es el número de documento)
    $sql = "SELECT id_usuario FROM usuarios WHERE email = ? OR id_usuario = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Error en prepare: " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("ss", $email, $id_usuario);
    $stmt->execute();
    $stmt->store_result();
    $existe = $stmt->num_rows > 0;
    $stmt->close();
    return $existe;
}

/**
 * Verifica si un email ya existe
 */
function emailExiste($email, $mysqli) {
    $sql = "SELECT id_usuario FROM usuarios WHERE email = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $existe = $stmt->num_rows > 0;
    $stmt->close();
    return $existe;
}

/**
 * Verifica si una cédula (id_usuario) ya existe
 */
function cedulaExiste($id_usuario, $mysqli) {
    $sql = "SELECT id_usuario FROM usuarios WHERE id_usuario = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("s", $id_usuario);
    $stmt->execute();
    $stmt->store_result();
    $existe = $stmt->num_rows > 0;
    $stmt->close();
    return $existe;
}

/**
 * Subir imagen de perfil - ACTUALIZADO A 10MB
 */
function subirImagenPerfil($file, $id_usuario) {
    // Configuración
    $upload_dir = __DIR__ . '/../assets/uploads/perfil/';
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']; // Añadido webp
    $max_size = 10 * 1024 * 1024; // 10MB (aumentado de 5MB a 10MB)
    
    // Crear directorio si no existe
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error al subir archivo'];
    }
    
    // Validar tamaño
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'La imagen es muy grande (máximo 10MB)'];
    }
    
    // Validar tipo
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        return ['success' => false, 'error' => 'Formato no permitido. Solo JPG, PNG, GIF o WEBP'];
    }
    
    // Obtener extensión
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $nombre_archivo = 'perfil_' . $id_usuario . '_' . time() . '.' . $extension;
    $ruta_destino = $upload_dir . $nombre_archivo;
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $ruta_destino)) {
        return [
            'success' => true, 
            'nombre_archivo' => $nombre_archivo,
            'ruta_relativa' => 'assets/uploads/perfil/' . $nombre_archivo
        ];
    }
    
    return ['success' => false, 'error' => 'No se pudo guardar la imagen'];
}

/**
 * Registrar nuevo usuario - CON TODAS LAS VALIDACIONES
 */
function registrarUsuario($datos, $mysqli) {
    // Validar datos requeridos (al menos la primera pregunta)
    $required = ['id_usuario', 'tipo_documento', 'email', 'nombre', 'apellido', 'password', 'pregunta1', 'respuesta1'];
    foreach ($required as $field) {
        if (empty($datos[$field])) {
            return ['success' => false, 'error' => "El campo $field es requerido"];
        }
    }

    // Validar contraseña (mínimo 6 caracteres)
    if (strlen($datos['password']) < 6) {
        return ['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres'];
    }

    // Validar que las contraseñas coincidan
    if ($datos['password'] !== $datos['confirm_password']) {
        return ['success' => false, 'error' => 'Las contraseñas no coinciden'];
    }

    // 1) Email único
    if (emailExiste($datos['email'], $mysqli)) {
        return ['success' => false, 'error' => 'El correo electrónico ya está registrado'];
    }

    // 2) id_usuario: validar que sea sólo dígitos y tenga entre 7 y 9 caracteres
    $id_clean = preg_replace('/\D/', '', $datos['id_usuario']);
    if ($id_clean === '' || mb_strlen($id_clean) < 7 || mb_strlen($id_clean) > 9) {
        return ['success' => false, 'error' => 'La cédula debe tener entre 7 y 9 números'];
    }
    if (cedulaExiste($id_clean, $mysqli)) {
        return ['success' => false, 'error' => 'El número de documento ya está registrado'];
    }
    // asegurar que el valor se use normalizado
    $datos['id_usuario'] = $id_clean;

    // 3) Email: validar formato y longitud
    $parts = explode('@', $datos['email']);
    if (count($parts) !== 2) {
        return ['success' => false, 'error' => 'Formato de correo inválido'];
    }
    $local = $parts[0];
    $dominio = $parts[1];
    
    // Validar parte local (alfanumérico entre 6 y 20 caracteres)
    if (!preg_match('/^[\p{L}\d]{6,20}$/u', $local)) {
        return ['success' => false, 'error' => 'La parte antes de @ debe contener sólo letras y/o números y tener entre 6 y 20 caracteres'];
    }
    
    // Validar dominio (debe tener al menos un punto)
    if (!preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $dominio)) {
        return ['success' => false, 'error' => 'El dominio del correo no es válido'];
    }

    // 4) Nombre y apellido solo letras y espacios, máximo 20 caracteres
    $nombre = trim($datos['nombre']);
    $apellido = trim($datos['apellido']);
    
    if (mb_strlen($nombre) > 20 || !preg_match('/^[\p{L} ]{1,20}$/u', $nombre)) {
        return ['success' => false, 'error' => 'El nombre sólo debe contener letras y espacios, máximo 20 caracteres'];
    }
    if (mb_strlen($apellido) > 20 || !preg_match('/^[\p{L} ]{1,20}$/u', $apellido)) {
        return ['success' => false, 'error' => 'El apellido sólo debe contener letras y espacios, máximo 20 caracteres'];
    }

    // 5) Validar que las preguntas de seguridad no se repitan
    $preguntas = [];
    if (!empty($datos['pregunta1'])) $preguntas[] = $datos['pregunta1'];
    if (!empty($datos['pregunta2'])) $preguntas[] = $datos['pregunta2'];
    if (!empty($datos['pregunta3'])) $preguntas[] = $datos['pregunta3'];
    
    if (count($preguntas) !== count(array_unique($preguntas))) {
        return ['success' => false, 'error' => 'No puede repetir la misma pregunta de seguridad'];
    }

    // Asegurar que si pregunta está seleccionada, su respuesta no esté vacía
    if (!empty($datos['pregunta1']) && empty(trim($datos['respuesta1']))) {
        return ['success' => false, 'error' => 'Debe responder la pregunta 1'];
    }
    if (!empty($datos['pregunta2']) && empty(trim($datos['respuesta2']))) {
        return ['success' => false, 'error' => 'Debe responder la pregunta 2'];
    }
    if (!empty($datos['pregunta3']) && empty(trim($datos['respuesta3']))) {
        return ['success' => false, 'error' => 'Debe responder la pregunta 3'];
    }

    // Validar teléfono (si se proporciona)
    if (!empty($datos['telefono'])) {
        $telefono_clean = preg_replace('/\D/', '', $datos['telefono']);
        if (strlen($telefono_clean) < 10 || strlen($telefono_clean) > 11) {
            return ['success' => false, 'error' => 'El teléfono debe tener entre 10 y 11 dígitos'];
        }
        $datos['telefono'] = $telefono_clean;
    }

    // Hash de contraseña
    $password_hash = password_hash($datos['password'], PASSWORD_DEFAULT);

    // Preparar consulta - soporta hasta 3 preguntas/respuestas
    $sql = "INSERT INTO usuarios (
        id_usuario, tipo_documento, email, password_hash, nombre, apellido, telefono,
        pregunta_seguridad, respuesta_seguridad, pregunta_seguridad_2, respuesta_seguridad_2, 
        pregunta_seguridad_3, respuesta_seguridad_3, rol, activo, fecha_registro
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'estudiante', 1, NOW())";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Error en prepare: " . $mysqli->error);
        return ['success' => false, 'error' => 'Error al preparar consulta: ' . $mysqli->error];
    }

    // Preparar variables locales
    $id_usuario_v     = $datos['id_usuario'];
    $tipo_documento_v = $datos['tipo_documento'];
    $email_v          = $datos['email'];
    $nombre_v         = $datos['nombre'];
    $apellido_v       = $datos['apellido'];
    $telefono_v       = $datos['telefono'] ?? null;
    $preg1_v          = $datos['pregunta1'];
    $res1_v           = $datos['respuesta1'];
    $preg2_v          = $datos['pregunta2'] ?? null;
    $res2_v           = $datos['respuesta2'] ?? null;
    $preg3_v          = $datos['pregunta3'] ?? null;
    $res3_v           = $datos['respuesta3'] ?? null;

    $stmt->bind_param(
        "sssssssssssss",
        $id_usuario_v,
        $tipo_documento_v,
        $email_v,
        $password_hash,
        $nombre_v,
        $apellido_v,
        $telefono_v,
        $preg1_v,
        $res1_v,
        $preg2_v,
        $res2_v,
        $preg3_v,
        $res3_v
    );

    if ($stmt->execute()) {
        $id = $datos['id_usuario'];
        $stmt->close();
        return ['success' => true, 'id_usuario' => $id];
    } else {
        $error = $mysqli->error;
        $stmt->close();
        error_log("Error en execute: " . $error);
        return ['success' => false, 'error' => 'Error al registrar: ' . $error];
    }
}

/**
 * Iniciar sesión - Acepta email o id_usuario
 */
function iniciarSesion($identificador, $password, $mysqli) {
    $sql = "SELECT id_usuario, email, password_hash, nombre, apellido, rol, foto_perfil, activo 
            FROM usuarios WHERE email = ? OR id_usuario = ?";
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Error en prepare login: " . $mysqli->error);
        return ['success' => false, 'error' => 'Error en el sistema'];
    }
    
    $stmt->bind_param("ss", $identificador, $identificador);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        // Verificar contraseña
        if (password_verify($password, $usuario['password_hash'])) {
            if ($usuario['activo'] == 1) {
                // Crear sesión
                $_SESSION['usuario_id'] = $usuario['id_usuario'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_apellido'] = $usuario['apellido'];
                $_SESSION['usuario_rol'] = $usuario['rol'];
                $_SESSION['usuario_foto'] = $usuario['foto_perfil'];
                
                return ['success' => true, 'usuario' => $usuario];
            } else {
                return ['success' => false, 'error' => 'Usuario inactivo. Contacte al administrador'];
            }
        }
    }
    
    return ['success' => false, 'error' => 'Credenciales incorrectas'];
}

/**
 * Recuperar contraseña - Paso 1: Verificar email y obtener pregunta
 */
function recuperarContraseña($email, $mysqli) {
    $sql = "SELECT id_usuario, pregunta_seguridad, pregunta_seguridad_2, pregunta_seguridad_3 
            FROM usuarios WHERE email = ? AND activo = 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Error en prepare recuperar: " . $mysqli->error);
        return ['success' => false, 'error' => 'Error en el sistema'];
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        // Recoger preguntas no vacías
        $preguntas = [];
        if (!empty($usuario['pregunta_seguridad'])) $preguntas[1] = $usuario['pregunta_seguridad'];
        if (!empty($usuario['pregunta_seguridad_2'])) $preguntas[2] = $usuario['pregunta_seguridad_2'];
        if (!empty($usuario['pregunta_seguridad_3'])) $preguntas[3] = $usuario['pregunta_seguridad_3'];

        if (empty($preguntas)) {
            return ['success' => false, 'error' => 'No hay preguntas de seguridad registradas'];
        }

        // Seleccionar una pregunta aleatoria
        $indices = array_keys($preguntas);
        $chosenIndex = $indices[array_rand($indices)];
        $chosenQuestion = $preguntas[$chosenIndex];

        return [
            'success' => true,
            'pregunta' => $chosenQuestion,
            'id_usuario' => $usuario['id_usuario'],
            'index' => $chosenIndex
        ];
    }

    return ['success' => false, 'error' => 'Email no encontrado o usuario inactivo'];
}

/**
 * Verificar respuesta de seguridad - Paso 2
 */
function verificarRespuesta($id_usuario, $respuesta, $index, $mysqli) {
    // Limpiar respuesta
    $respuesta = trim($respuesta);
    if (empty($respuesta)) return false;
    
    // index indica qué respuesta comparar (1,2,3)
    $col = '';
    switch ($index) {
        case 1: $col = 'respuesta_seguridad'; break;
        case 2: $col = 'respuesta_seguridad_2'; break;
        case 3: $col = 'respuesta_seguridad_3'; break;
        default: return false;
    }

    $sql = "SELECT id_usuario FROM usuarios WHERE id_usuario = ? AND $col = ? AND activo = 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Error en prepare verificar respuesta: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("ss", $id_usuario, $respuesta);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->close();
        return true;
    }

    $stmt->close();
    return false;
}

/**
 * Actualizar contraseña - Paso 3
 */
function actualizarPassword($id_usuario, $nueva_password, $mysqli) {
    // Validar longitud mínima
    if (strlen($nueva_password) < 6) {
        return false;
    }
    
    // Primero, obtener la contraseña actual para verificar que no sea la misma
    $sql_actual = "SELECT password_hash FROM usuarios WHERE id_usuario = ? AND activo = 1";
    $stmt_actual = $mysqli->prepare($sql_actual);
    if (!$stmt_actual) {
        error_log("Error en prepare obtener password: " . $mysqli->error);
        return false;
    }
    
    $stmt_actual->bind_param("s", $id_usuario);
    $stmt_actual->execute();
    $result_actual = $stmt_actual->get_result();
    
    if ($result_actual->num_rows === 1) {
        $usuario = $result_actual->fetch_assoc();
        $password_actual_hash = $usuario['password_hash'];
        
        // Verificar que la nueva contraseña NO sea igual a la actual
        if (password_verify($nueva_password, $password_actual_hash)) {
            error_log("Intento de cambiar a la misma contraseña para usuario: $id_usuario");
            $stmt_actual->close();
            return false; // No permite la misma contraseña
        }
    } else {
        error_log("Usuario no encontrado al cambiar password: $id_usuario");
        $stmt_actual->close();
        return false;
    }
    $stmt_actual->close();
    
    // Si pasó la validación, actualizar con la nueva contraseña
    $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    
    $sql_update = "UPDATE usuarios SET password_hash = ? WHERE id_usuario = ? AND activo = 1";
    $stmt_update = $mysqli->prepare($sql_update);
    if (!$stmt_update) {
        error_log("Error en prepare actualizar password: " . $mysqli->error);
        return false;
    }
    
    $stmt_update->bind_param("ss", $password_hash, $id_usuario);
    
    if ($stmt_update->execute()) {
        $stmt_update->close();
        return true;
    }
    
    $stmt_update->close();
    return false;
}

/**
 * Obtener datos de usuario por ID
 */
function obtenerUsuarioPorId($id_usuario, $mysqli) {
    $sql = "SELECT id_usuario, email, nombre, apellido, telefono, tipo_documento, 
                   rol, foto_perfil, activo, fecha_registro
            FROM usuarios WHERE id_usuario = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return null;
    
    $stmt->bind_param("s", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Actualizar foto de perfil (para edición de perfil)
 */
function actualizarFotoPerfil($id_usuario, $nombre_archivo, $mysqli) {
    $sql = "UPDATE usuarios SET foto_perfil = ? WHERE id_usuario = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;
    
    $stmt->bind_param("ss", $nombre_archivo, $id_usuario);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Verificar si el usuario es admin
 */
function esAdmin() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}

/**
 * Verificar si el usuario está logueado
 */
function estaLogueado() {
    return isset($_SESSION['usuario_id']);
}

/**
 * Redireccionar si no está logueado
 */
function requerirLogin() {
    if (!estaLogueado()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /posgrado/auth/login.php');
        exit();
    }
}

/**
 * Redireccionar si no es admin
 */
function requerirAdmin() {
    requerirLogin();
    if (!esAdmin()) {
        header('Location: /posgrado/src/user/index.php');
        exit();
    }
}

/**
 * Generar token CSRF
 */
function generarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verificarTokenCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Sanitizar entrada para evitar XSS
 */
function sanitizar($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Mostrar errores de formulario de manera amigable
 */
function mostrarError($campo, $errores) {
    if (isset($errores[$campo])) {
        return '<div class="field-feedback error">' . $errores[$campo] . '</div>';
    }
    return '';
}

/**
 * Mostrar valor previo en formulario
 */
function valorPrevio($campo, $default = '') {
    return isset($_POST[$campo]) ? sanitizar($_POST[$campo]) : $default;
}

?>