<?php
// recuperar-password.php
require_once '../config/config.php';

// Asegurar que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$paso = 1;
$mensaje = '';
$error = '';
$pregunta = '';
$id_usuario = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/funciones.php';
    
    if (isset($_POST['email'])) {
        // Paso 1: Solicitar email
        $email = trim($_POST['email']);
        $resultado = recuperarContraseña($email, $mysqli);
        
        if ($resultado['success']) {
            $pregunta = $resultado['pregunta'];
            $id_usuario = $resultado['id_usuario'];
            $_SESSION['recovery_id'] = $id_usuario;
            $_SESSION['recovery_question_index'] = $resultado['index'] ?? 1;
            $paso = 2;
        } else {
            $error = $resultado['error'];
        }
    } 
    elseif (isset($_POST['respuesta']) && isset($_SESSION['recovery_id'])) {
        // Paso 2: Verificar respuesta
        $respuesta = trim($_POST['respuesta']);
        $id_usuario = $_SESSION['recovery_id'];
        $index = $_SESSION['recovery_question_index'] ?? 1;
        
        if (verificarRespuesta($id_usuario, $respuesta, $index, $mysqli)) {
            $paso = 3;
        } else {
            $error = 'Respuesta incorrecta';
            $paso = 2;
            
            // Recuperar la misma pregunta (no cambiar)
            $sql = "SELECT pregunta_seguridad, pregunta_seguridad_2, pregunta_seguridad_3 FROM usuarios WHERE id_usuario = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("s", $id_usuario);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            
            $preguntas = [];
            if (!empty($row['pregunta_seguridad'])) $preguntas[1] = $row['pregunta_seguridad'];
            if (!empty($row['pregunta_seguridad_2'])) $preguntas[2] = $row['pregunta_seguridad_2'];
            if (!empty($row['pregunta_seguridad_3'])) $preguntas[3] = $row['pregunta_seguridad_3'];
            
            $index = $_SESSION['recovery_question_index'] ?? 1;
            $pregunta = $preguntas[$index] ?? ($preguntas[1] ?? '');
            $stmt->close();
        }
    }
   elseif (isset($_POST['nueva_password']) && isset($_SESSION['recovery_id'])) {
    // Paso 3: Cambiar contraseña
    $nueva_password = $_POST['nueva_password'];
    $confirm_password = $_POST['confirm_password'];
    $id_usuario = $_SESSION['recovery_id'];
    
    if ($nueva_password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
        $paso = 3;
    } elseif (strlen($nueva_password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
        $paso = 3;
    } else {
        if (actualizarPassword($id_usuario, $nueva_password, $mysqli)) {
            $mensaje = '¡Contraseña actualizada exitosamente!';
            unset($_SESSION['recovery_id']);
            unset($_SESSION['recovery_question_index']);
            
            // Redireccionar después de 3 segundos
            header("refresh:3;url=login.php");
        } else {
            // Verificar si fue porque es la misma contraseña
            $sql_check = "SELECT password_hash FROM usuarios WHERE id_usuario = ?";
            $stmt_check = $mysqli->prepare($sql_check);
            $stmt_check->bind_param("s", $id_usuario);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $user = $result_check->fetch_assoc();
            
            if (password_verify($nueva_password, $user['password_hash'])) {
                $error = 'La nueva contraseña no puede ser igual a la actual';
            } else {
                $error = 'Error al actualizar la contraseña';
            }
            $stmt_check->close();
            $paso = 3;
        }
    }
}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - UNEFA Postgrado</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo $base_url; ?>public/images/logo-unefa.png" type="image/png">
    
    <style>
        /* ========================================
           ESTILOS UNEFA - RECUPERAR CONTRASEÑA
        ======================================== */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #8B1E3F 0%, #6a1730 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }
        
        /* Fondo animado */
        .pulse-circles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }
        
        .pulse-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(242, 169, 0, 0.1);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.5); opacity: 0.1; }
            100% { transform: scale(1); opacity: 0.3; }
        }
        
        .floating-logo {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            height: 400px;
            background-image: url('<?php echo $base_url; ?>public/images/logo-unefa-white1.png');
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.03;
            z-index: 0;
            animation: slowRotate 20s linear infinite;
            pointer-events: none;
        }
        
        @keyframes slowRotate {
            from { transform: translate(-50%, -50%) rotate(0deg); }
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        /* Contenedor principal */
        .recovery-wrapper {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 30px 60px rgba(139, 30, 63, 0.3);
            overflow: hidden;
            position: relative;
            z-index: 10;
            animation: slideUpFade 0.8s ease-out;
        }
        
        @keyframes slideUpFade {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Header */
        .recovery-header {
            background: linear-gradient(135deg, #8B1E3F 0%, #6a1730 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .recovery-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(242, 169, 0, 0.15) 0%, transparent 70%);
            animation: slowSpin 20s linear infinite;
        }
        
        @keyframes slowSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .recovery-header h1 {
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }
        
        .recovery-header p {
            color: rgba(255,255,255,0.9);
            font-size: 0.95rem;
            position: relative;
            z-index: 2;
        }
        
        .recovery-header i {
            color: #F2A900;
            margin: 0 5px;
        }
        
        /* Cuerpo */
        .recovery-body {
            padding: 40px;
            background: white;
        }
        
        /* Indicador de pasos */
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            transform: translateY(-50%);
            z-index: 1;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #adb5bd;
            position: relative;
            z-index: 2;
            background: white;
        }
        
        .step.active {
            border-color: #8B1E3F;
            background: #8B1E3F;
            color: white;
            box-shadow: 0 0 0 4px rgba(139, 30, 63, 0.2);
        }
        
        .step.completed {
            border-color: #28a745;
            background: #28a745;
            color: white;
        }
        
        .step-label {
            position: absolute;
            top: 45px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            white-space: nowrap;
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
            font-size: 0.95rem;
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
        
        /* Grupos de input */
        .input-group {
            margin-bottom: 25px;
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
            transition: all 0.3s ease;
        }
        
        input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
            background: #f8f9fa;
        }
        
        input:focus {
            outline: none;
            border-color: #8B1E3F;
            background: white;
            box-shadow: 0 0 0 4px rgba(139, 30, 63, 0.1);
        }
        
        input:focus + i {
            color: #8B1E3F;
        }
        
        /* Pregunta de seguridad */
        .security-question {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 4px solid #F2A900;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .security-question i {
            color: #F2A900;
            font-size: 1.2rem;
            margin-right: 8px;
        }
        
        .security-question strong {
            color: #8B1E3F;
            font-size: 1rem;
            display: block;
            margin-bottom: 10px;
        }
        
        .security-question p {
            color: #495057;
            font-size: 1.1rem;
            font-weight: 500;
            padding: 10px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        /* Botones */
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #8B1E3F 0%, #6a1730 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(139, 30, 63, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(139, 30, 63, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: transparent;
            border: 2px solid #8B1E3F;
            color: #8B1E3F;
            box-shadow: none;
            margin-top: 15px;
        }
        
        .btn-secondary:hover {
            background: #8B1E3F;
            color: white;
        }
        
        /* Enlaces */
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #8B1E3F;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .back-link a:hover {
            color: #F2A900;
            gap: 10px;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .recovery-body {
                padding: 30px 20px;
            }
            
            .recovery-header {
                padding: 30px 20px;
            }
            
            .recovery-header h1 {
                font-size: 1.8rem;
            }
            
            .step-label {
                font-size: 0.65rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animaciones de fondo -->
    <div class="pulse-circles">
        <div class="pulse-circle" style="width: 300px; height: 300px; top: 10%; left: 5%;"></div>
        <div class="pulse-circle" style="width: 200px; height: 200px; bottom: 20%; right: 10%;"></div>
        <div class="pulse-circle" style="width: 150px; height: 150px; top: 40%; right: 30%;"></div>
    </div>
    
    <div class="floating-logo"></div>
    
    <div class="recovery-wrapper">
        <div class="recovery-header">
            <h1>Recuperar Contraseña</h1>
            <p><i class="fas fa-lock"></i> UNEFA Postgrado <i class="fas fa-lock"></i></p>
        </div>
        
        <div class="recovery-body">
            <!-- Indicador de pasos -->
            <div class="step-indicator">
                <div class="step <?php echo $paso >= 1 ? 'active' : ''; ?>" style="left: 0;">
                    1
                    <span class="step-label" style="left: -10px;">Email</span>
                </div>
                <div class="step <?php echo $paso >= 2 ? 'active' : ''; ?>" style="left: 0;">
                    2
                    <span class="step-label" style="left: -15px;">Pregunta</span>
                </div>
                <div class="step <?php echo $paso >= 3 ? 'active' : ''; ?>" style="left: 0;">
                    3
                    <span class="step-label" style="left: -10px;">Nueva clave</span>
                </div>
            </div>
            
            <?php if ($mensaje): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($paso == 1): ?>
                <!-- Paso 1: Solicitar email -->
                <form method="POST" action="">
                    <div class="input-group">
                        <label for="email">CORREO ELECTRÓNICO</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" required 
                                   placeholder="ejemplo@unefa.edu.ve"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-arrow-right"></i>
                        CONTINUAR
                    </button>
                    
                    <a href="login.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        VOLVER AL LOGIN
                    </a>
                </form>
                
            <?php elseif ($paso == 2): ?>
                <!-- Paso 2: Pregunta de seguridad -->
                <div class="security-question">
                    <i class="fas fa-shield-alt"></i>
                    <strong>Pregunta de seguridad:</strong>
                    <p><?php echo htmlspecialchars($pregunta); ?></p>
                </div>
                
                <form method="POST" action="">
                    <div class="input-group">
                        <label for="respuesta">TU RESPUESTA</label>
                        <div class="input-wrapper">
                            <i class="fas fa-pencil-alt"></i>
                            <input type="text" id="respuesta" name="respuesta" required 
                                   placeholder="Escribe tu respuesta"
                                   autocomplete="off">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-check"></i>
                        VERIFICAR RESPUESTA
                    </button>
                    
                    <a href="recuperar-password.php" class="btn btn-secondary">
                        <i class="fas fa-undo"></i>
                        VOLVER A INTENTAR
                    </a>
                </form>
                
            <?php elseif ($paso == 3): ?>
                <!-- Paso 3: Nueva contraseña -->
                <form method="POST" action="" id="passwordForm">
                    <div class="input-group">
                        <label for="nueva_password">NUEVA CONTRASEÑA</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="nueva_password" name="nueva_password" required 
                                   placeholder="Mínimo 6 caracteres" minlength="6">
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="confirm_password">CONFIRMAR CONTRASEÑA</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   placeholder="Repite tu contraseña" minlength="6">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i>
                        CAMBIAR CONTRASEÑA
                    </button>
                    
                    <a href="login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i>
                        IR AL LOGIN
                    </a>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Validación de contraseñas en paso 3
        document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('nueva_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('❌ Las contraseñas no coinciden');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('❌ La contraseña debe tener al menos 6 caracteres');
                return false;
            }
            
            return true;
        });
        
        // Efecto de foco en inputs
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>