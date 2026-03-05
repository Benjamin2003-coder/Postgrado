<?php
// auth/register.php
require_once '../config/config.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/funciones.php';
    
    // Recoger datos
    $datos = [
        'id_usuario' => trim($_POST['id_usuario'] ?? ''),
        'tipo_documento' => $_POST['tipo_documento'] ?? '',
        'email' => trim($_POST['email'] ?? ''),
        'nombre' => trim($_POST['nombre'] ?? ''),
        'apellido' => trim($_POST['apellido'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        // Manejo de pregunta personalizada si se selecciona
        'pregunta1' => (($_POST['pregunta_seguridad_1'] ?? '') === '__personalizada__' && trim($_POST['pregunta_personalizada_1'] ?? '') !== '') ? trim($_POST['pregunta_personalizada_1']) : ($_POST['pregunta_seguridad_1'] ?? ''),
        'respuesta1' => trim($_POST['respuesta_seguridad_1'] ?? ''),
        'pregunta2' => (($_POST['pregunta_seguridad_2'] ?? '') === '__personalizada__' && trim($_POST['pregunta_personalizada_2'] ?? '') !== '') ? trim($_POST['pregunta_personalizada_2']) : ($_POST['pregunta_seguridad_2'] ?? ''),
        'respuesta2' => trim($_POST['respuesta_seguridad_2'] ?? ''),
        'pregunta3' => (($_POST['pregunta_seguridad_3'] ?? '') === '__personalizada__' && trim($_POST['pregunta_personalizada_3'] ?? '') !== '') ? trim($_POST['pregunta_personalizada_3']) : ($_POST['pregunta_seguridad_3'] ?? ''),
        'respuesta3' => trim($_POST['respuesta_seguridad_3'] ?? ''),
        'telefono' => trim($_POST['telefono'] ?? '')
    ];
    
    // Validar contraseñas
    if ($datos['password'] !== $datos['confirm_password']) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($datos['password']) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        // Registrar usuario
        $resultado = registrarUsuario($datos, $mysqli);
        
        if ($resultado['success']) {
            $id_usuario = $resultado['id_usuario'];
            
            // Subir foto de perfil si se envió
            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                $upload_result = subirImagenPerfil($_FILES['foto_perfil'], $id_usuario);
                
                if ($upload_result['success']) {
                    $sql = "UPDATE usuarios SET foto_perfil = ? WHERE id_usuario = ?";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param("ss", $upload_result['nombre_archivo'], $id_usuario);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
            // Actualizar teléfono si se proporcionó
            if (!empty($datos['telefono'])) {
                $sql = "UPDATE usuarios SET telefono = ? WHERE id_usuario = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("ss", $datos['telefono'], $id_usuario);
                $stmt->execute();
                $stmt->close();
            }
            
            $mensaje = '¡Registro exitoso! Ahora puedes iniciar sesión.';
            header("refresh:3;url=login.php");
        } else {
            $error = $resultado['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - UNEFA Postgrado</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo $base_url; ?>public/images/logo-unefa.png" type="image/png">
    
    <style>
        /* ========================================
           ESTILOS UNEFA - ROJO Y AMARILLO
           VERSIÓN COMPACTA CON ANIMACIONES
        ======================================== */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #8B1E3F 0%, #6a1730 100%);
            position: relative;
            overflow-x: hidden;
            padding: 20px;
        }
        
        /* ========================================
           ANIMACIONES DE FONDO
        ======================================== */
        
        /* Círculos pulsantes */
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
            0% {
                transform: scale(1);
                opacity: 0.3;
            }
            50% {
                transform: scale(1.5);
                opacity: 0.1;
            }
            100% {
                transform: scale(1);
                opacity: 0.3;
            }
        }
        
        /* Líneas móviles */
        .moving-lines {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
        
        .moving-line {
            position: absolute;
            width: 200%;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(242, 169, 0, 0.2), transparent);
            animation: moveLine 8s linear infinite;
        }
        
        @keyframes moveLine {
            0% {
                transform: translateX(-100%) rotate(45deg);
            }
            100% {
                transform: translateX(100%) rotate(45deg);
            }
        }
        
        /* Partículas flotantes */
        .floating-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }
        
        .floating-particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(242, 169, 0, 0.3);
            border-radius: 50%;
            animation: float 15s linear infinite;
        }
        
        @keyframes float {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            20% {
                opacity: 1;
            }
            80% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) translateX(100px);
                opacity: 0;
            }
        }
        
        /* Logo flotante de fondo */
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
        
        /* ========================================
           CONTENEDOR PRINCIPAL - MÁS COMPACTO
        ======================================== */
        
        .register-wrapper {
            width: 100%;
            max-width: 1000px; /* Reducido de 1300px a 1000px */
            background: white;
            border-radius: 30px;
            box-shadow: 0 30px 60px rgba(139, 30, 63, 0.3);
            overflow: hidden;
            display: flex;
            position: relative;
            z-index: 10;
            animation: slideUpFade 0.8s ease-out;
        }
        
        @keyframes slideUpFade {
            0% {
                opacity: 0;
                transform: translateY(50px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* ========================================
           PANEL IZQUIERDO - ROJO UNEFA
        ======================================== */
        
        .left-panel {
            flex: 1.1; /* Proporción ajustada */
            background: linear-gradient(145deg, #8B1E3F 0%, #6a1730 100%);
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        
        .left-panel::before {
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
        
        .logo-area {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
            animation: slideInLeft 0.6s ease-out;
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .logo-area img {
            width: 60px;
            height: 60px;
            object-fit: contain;

            transition: transform 0.3s ease;
        }
        
        .logo-area img:hover {
            transform: rotate(10deg) scale(1.1);
        }
        
        .logo-text h2 {
            color: white;
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .logo-text p {
            color: #F2A900;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .info-content {
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .info-content h1 {
            color: white;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.3;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }
        
        .info-content h1 span {
            color: #F2A900;
            display: block;
            font-size: 2rem;
        }
        
        .info-content > p {
            color: rgba(255,255,255,0.9);
            font-size: 0.9rem;
            margin-bottom: 25px;
            line-height: 1.6;
            animation: fadeInUp 0.8s ease-out 0.3s both;
        }
        
        .benefits {
            background: rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid rgba(242,169,0,0.3);
            margin-bottom: 25px;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            color: white;
            font-size: 0.9rem;
            transition: transform 0.3s ease;
        }
        
        .benefit-item:hover {
            transform: translateX(10px);
        }
        
        .benefit-item:last-child {
            margin-bottom: 0;
        }
        
        .benefit-item i {
            color: #F2A900;
            font-size: 1.1rem;
            width: 22px;
        }
        
        /* Frase de Chávez */
        .chavez-quote {
            background: rgba(0,0,0,0.2);
            padding: 20px;
            border-radius: 15px;
            border-left: 4px solid #F2A900;
            position: relative;
            z-index: 2;
            margin-top: auto;
            animation: fadeInUp 0.8s ease-out 0.5s both;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .chavez-quote:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        
        .quote-icon {
            color: #F2A900;
            font-size: 1.2rem;
            margin-bottom: 10px;
            opacity: 0.7;
        }
        
        .chavez-quote p {
            color: white;
            font-size: 0.85rem;
            line-height: 1.6;
            font-style: italic;
            margin-bottom: 12px;
        }
        
        .quote-author {
            color: #F2A900;
            font-weight: 600;
            text-align: right;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .quote-author i {
            margin-left: 5px;
            animation: starPulse 2s infinite;
        }
        
        @keyframes starPulse {
            0%, 100% { opacity: 0.5; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.2); }
        }
        
        /* ========================================
           PANEL DERECHO - FORMULARIO
        ======================================== */
        
        .right-panel {
            flex: 1.3; /* Proporción ajustada */
            background: white;
            padding: 30px;
            overflow-y: auto;
            max-height: 700px;
        }
        
        .right-panel::-webkit-scrollbar {
            width: 6px;
        }
        
        .right-panel::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .right-panel::-webkit-scrollbar-thumb {
            background: #8B1E3F;
            border-radius: 10px;
        }
        
        /* Header del formulario - BIEN ACOMODADO */
        .form-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            position: relative;
        }
        
        .form-header h2 {
            color: #8B1E3F;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }
        
        .form-header p {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .form-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: #F2A900;
            border-radius: 2px;
        }
        
        /* Mensajes */
        .message {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            font-size: 0.9rem;
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
        
        /* Grid del formulario - 2 COLUMNAS */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .full-width {
            grid-column: span 2;
        }
        
        /* Grupos de input */
        .input-group {
            margin-bottom: 0;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 0.75rem;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .input-wrapper {
            position: relative;
            transition: transform 0.3s ease;
        }
        
        .input-wrapper:focus-within {
            transform: scale(1.02);
        }
        
        .input-wrapper i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        input, select {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
            background: #f8f9fa;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #8B1E3F;
            background: white;
            box-shadow: 0 0 0 3px rgba(139, 30, 63, 0.1);
        }
        
        input:focus + i {
            color: #8B1E3F;
        }
        
        /* Foto de perfil - COMPACTA */
        .photo-upload {
            border: 2px dashed #8B1E3F;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #fff5f5;
        }
        
        .photo-upload:hover {
            border-color: #F2A900;
            background: #fff9e6;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 30, 63, 0.1);
        }
        
        .photo-upload label {
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            margin: 0;
            text-transform: none;
            font-weight: 500;
            color: #495057;
        }
        
        .photo-upload i {
            font-size: 2.5rem;
            color: #8B1E3F;
            transition: all 0.3s ease;
        }
        
        .photo-upload:hover i {
            color: #F2A900;
            transform: scale(1.1) rotate(5deg);
        }
        
        .photo-upload span {
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .photo-upload small {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .preview-container {
            position: relative;
            display: inline-block;
            margin-top: 10px;
        }
        
        .preview-foto {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #8B1E3F;
            display: none;
            animation: popIn 0.3s ease;
        }
        
        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .btn-remove-photo {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #dc3545;
            color: white;
            border: none;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        
        .btn-remove-photo:hover {
            transform: scale(1.1);
            background: #c82333;
        }
        
        /* Preguntas de seguridad - COMPACTAS */
        .security-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 5px;
        }
        
        .security-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .security-item {
            background: white;
            padding: 12px;
            border-radius: 8px;
            border-left: 3px solid #8B1E3F;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        
        .security-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(139,30,63,0.1);
        }
        
        .security-item select,
        .security-item input {
            padding-left: 10px;
            font-size: 0.85rem;
        }
        
        /* Botón de envío */
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #8B1E3F 0%, #6a1730 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            box-shadow: 0 4px 10px rgba(139, 30, 63, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-submit:hover::before {
            left: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(139, 30, 63, 0.4);
        }
        
        .btn-submit i {
            transition: transform 0.3s ease;
        }
        
        .btn-submit:hover i {
            transform: translateX(5px);
        }
        
        /* Enlace de login */
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .login-link a {
            color: #8B1E3F;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .login-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #F2A900;
            transition: width 0.3s ease;
        }
        
        .login-link a:hover::after {
            width: 100%;
        }
        
        .login-link a:hover {
            color: #F2A900;
        }
        
        /* Validación */
        .field-feedback {
            font-size: 0.7rem;
            margin-top: 3px;
            color: #dc3545;
        }
        
        /* Fortaleza de contraseña */
        .password-strength {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            margin-top: 5px;
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
        
        /* Responsive */
        @media (max-width: 992px) {
            .register-wrapper {
                flex-direction: column;
                max-width: 600px;
            }
            
            .left-panel {
                padding: 30px;
            }
            
            .right-panel {
                padding: 30px;
            }
            
            .security-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .full-width {
                grid-column: span 1;
            }
            
            .info-content h1 {
                font-size: 1.4rem;
            }
            
            .info-content h1 span {
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .left-panel {
                padding: 25px 20px;
            }
            
            .right-panel {
                padding: 25px 20px;
            }
            
            .logo-area img {
                width: 50px;
                height: 50px;
            }
            
            .logo-text h2 {
                font-size: 1.4rem;
            }
            
            .form-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- ANIMACIONES DE FONDO -->
    <div class="pulse-circles">
        <div class="pulse-circle" style="width: 300px; height: 300px; top: 10%; left: 5%;"></div>
        <div class="pulse-circle" style="width: 200px; height: 200px; bottom: 20%; right: 10%;"></div>
        <div class="pulse-circle" style="width: 150px; height: 150px; top: 40%; right: 30%;"></div>
    </div>
    
    <div class="moving-lines">
        <div class="moving-line" style="top: 20%;"></div>
        <div class="moving-line" style="top: 50%; animation-delay: 2s;"></div>
        <div class="moving-line" style="top: 80%; animation-delay: 4s;"></div>
    </div>
    
    <div class="floating-particles">
        <script>
            for(let i = 0; i < 20; i++) {
                document.write('<div class="floating-particle" style="left: ' + Math.random()*100 + '%; animation-delay: ' + Math.random()*5 + 's;"></div>');
            }
        </script>
    </div>
    
    <div class="floating-logo"></div>
    
    <!-- CONTENEDOR PRINCIPAL -->
    <div class="register-wrapper">
        <!-- Panel izquierdo - Rojo UNEFA -->
        <div class="left-panel">
            <div class="logo-area">
                <img src="<?php echo $base_url; ?>public/images/logo-unefa.png" alt="UNEFA">
                <div class="logo-text">
                    <h2>UNEFA</h2>
                    <p>Núcleo Nueva Esparta</p>
                </div>
            </div>
            
            <div class="info-content">
                <h1>Dirección de <span>Investigación y Postgrado</span></h1>
                <p>Regístrate para acceder al sistema de gestión académica</p>
                
                <div class="benefits">
                    <div class="benefit-item">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Gestión de maestrías y doctorados</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-book"></i>
                        <span>Inscripción a materias por créditos</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-mobile-alt"></i>
                        <span>Pagos por cuotas con pago móvil</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-history"></i>
                        <span>Historial académico en línea</span>
                    </div>
                </div>
                
                <!-- Frase de Chávez -->
                <div class="chavez-quote">
                    <div class="quote-icon">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p>"El mundo militar tiene mucho que aportar al mundo civil y mucho que aprender de él. Y la inversa, el mundo civil tiene mucho que aportarle y aprender del mundo militar. Esa es una relación no sólo necesaria e inevitable, sino incluso de perfecta complementariedad"</p>
                    <div class="quote-author">
                        <span>Comandante Hugo Chávez</span>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Panel derecho - Formulario -->
        <div class="right-panel">
            <div class="form-header">
                <h2>Crear Cuenta</h2>
                <p>Completa el formulario para registrarte</p>
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
            
            <form method="POST" action="" enctype="multipart/form-data" id="registroForm">
                <div class="form-grid">
                    <!-- Tipo de Documento -->
                    <div class="input-group">
                        <label for="tipo_documento">TIPO DE DOCUMENTO *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-id-card"></i>
                            <select id="tipo_documento" name="tipo_documento" required>
                                <option value="">Selecciona</option>
                                <option value="V" <?php echo ($_POST['tipo_documento'] ?? '') == 'V' ? 'selected' : ''; ?>>V - Venezolano</option>
                                <option value="E" <?php echo ($_POST['tipo_documento'] ?? '') == 'E' ? 'selected' : ''; ?>>E - Extranjero</option>
                                <option value="P" <?php echo ($_POST['tipo_documento'] ?? '') == 'P' ? 'selected' : ''; ?>>P - Pasaporte</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Número de Documento -->
                    <div class="input-group">
                        <label for="id_usuario">NÚMERO DE DOCUMENTO *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-hashtag"></i>
                            <input type="text" id="id_usuario" name="id_usuario" required 
                                   placeholder="Ej: 12345678"
                                   value="<?php echo htmlspecialchars($_POST['id_usuario'] ?? ''); ?>"
                                   maxlength="9">
                            <div id="idFeedback" class="field-feedback"></div>
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <div class="input-group">
                        <label for="email">CORREO ELECTRÓNICO *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" required 
                                   placeholder="ejemplo@unefa.edu.ve"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            <div id="emailFeedback" class="field-feedback"></div>
                        </div>
                    </div>
                    
                    <!-- Teléfono -->
                    <div class="input-group">
                        <label for="telefono">TELÉFONO</label>
                        <div class="input-wrapper">
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="telefono" name="telefono" 
                                   placeholder="0412-1234567"
                                   value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Nombre -->
                    <div class="input-group">
                        <label for="nombre">NOMBRE *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="nombre" name="nombre" required 
                                   placeholder="Tu nombre"
                                   value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                                   maxlength="20">
                        </div>
                    </div>
                    
                    <!-- Apellido -->
                    <div class="input-group">
                        <label for="apellido">APELLIDO *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="apellido" name="apellido" required 
                                   placeholder="Tu apellido"
                                   value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>"
                                   maxlength="20">
                        </div>
                    </div>
                    
                    <!-- Contraseña -->
                    <div class="input-group">
                        <label for="password">CONTRASEÑA *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" required 
                                   placeholder="Mínimo 6 caracteres" minlength="6">
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar" id="passwordStrength"></div>
                        </div>
                    </div>
                    
                    <!-- Confirmar Contraseña -->
                    <div class="input-group">
                        <label for="confirm_password">CONFIRMAR CONTRASEÑA *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   placeholder="Repite tu contraseña" minlength="6">
                        </div>
                    </div>
                    
                    <!-- Foto de Perfil -->
                    <div class="full-width">
                        <label>FOTO DE PERFIL</label>
                        <div class="photo-upload" id="photoUploadArea">
                            <input type="file" id="foto_perfil" name="foto_perfil" 
                                   accept="image/jpeg,image/png,image/gif,image/webp" 
                                   onchange="previewImage(this)" hidden>
                            <label for="foto_perfil">
                                <i class="fas fa-camera"></i>
                                <span>Haz clic para subir tu foto</span>
                                <small>JPG, PNG, GIF, WEBP - Máx. 10MB</small>
                            </label>
                            <div class="preview-container">
                                <img id="fotoPreview" class="preview-foto" src="#" alt="Vista previa">
                                <button type="button" class="btn-remove-photo" id="btnRemovePhoto" onclick="removePhoto()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preguntas de Seguridad -->
                    <div class="full-width">
                        <label>PREGUNTAS DE SEGURIDAD</label>
                        <div class="security-section">
                            <div class="security-grid">
                                <!-- Pregunta 1 -->
                                <div class="security-item">
                                    <label for="pregunta_seguridad_1">Pregunta 1 *</label>
                                     <select id="pregunta_seguridad_1" name="pregunta_seguridad_1" required>
                                         <option value="">Selecciona</option>
                                         <option value="¿Cuál es el nombre de tu madre?" <?php echo ($_POST['pregunta_seguridad_1'] ?? '') == '¿Cuál es el nombre de tu madre?' ? 'selected' : ''; ?>>¿Nombre de tu madre?</option>
                                         <option value="¿Cuál es el nombre de tu primera mascota?" <?php echo ($_POST['pregunta_seguridad_1'] ?? '') == '¿Cuál es el nombre de tu primera mascota?' ? 'selected' : ''; ?>>¿Nombre de tu primera mascota?</option>
                                         <option value="¿En qué ciudad naciste?" <?php echo ($_POST['pregunta_seguridad_1'] ?? '') == '¿En qué ciudad naciste?' ? 'selected' : ''; ?>>¿Ciudad de nacimiento?</option>
                                         <option value="__personalizada__" <?php echo ($_POST['pregunta_seguridad_1'] ?? '') == '__personalizada__' ? 'selected' : ''; ?>>Personalizada</option>
                                     </select>
                                     <input type="text" name="pregunta_personalizada_1" id="pregunta_personalizada_1" placeholder="Escribe tu pregunta personalizada"
                                         value="<?php echo htmlspecialchars($_POST['pregunta_personalizada_1'] ?? ''); ?>"
                                         style="margin-top:8px; display: <?php echo (($_POST['pregunta_seguridad_1'] ?? '') === '__personalizada__') ? 'block' : 'none'; ?>;">
                                     <input type="text" name="respuesta_seguridad_1" placeholder="Tu respuesta" required
                                         value="<?php echo htmlspecialchars($_POST['respuesta_seguridad_1'] ?? ''); ?>"
                                         style="margin-top: 8px;">
                                </div>
                                
                                <!-- Pregunta 2 -->
                                <div class="security-item">
                                    <label for="pregunta_seguridad_2">Pregunta 2 (opcional)</label>
                                     <select id="pregunta_seguridad_2" name="pregunta_seguridad_2">
                                         <option value="">-- Ninguna --</option>
                                         <option value="¿Cuál es tu comida favorita?" <?php echo ($_POST['pregunta_seguridad_2'] ?? '') == '¿Cuál es tu comida favorita?' ? 'selected' : ''; ?>>¿Comida favorita?</option>
                                         <option value="¿Cuál es tu libro favorito?" <?php echo ($_POST['pregunta_seguridad_2'] ?? '') == '¿Cuál es tu libro favorito?' ? 'selected' : ''; ?>>¿Libro favorito?</option>
                                         <option value="¿Cuál es tu película favorita?" <?php echo ($_POST['pregunta_seguridad_2'] ?? '') == '¿Cuál es tu película favorita?' ? 'selected' : ''; ?>>¿Película favorita?</option>
                                         <option value="__personalizada__" <?php echo ($_POST['pregunta_seguridad_2'] ?? '') == '__personalizada__' ? 'selected' : ''; ?>>Personalizada</option>
                                     </select>
                                     <input type="text" name="pregunta_personalizada_2" id="pregunta_personalizada_2" placeholder="Escribe tu pregunta personalizada"
                                         value="<?php echo htmlspecialchars($_POST['pregunta_personalizada_2'] ?? ''); ?>"
                                         style="margin-top:8px; display: <?php echo (($_POST['pregunta_seguridad_2'] ?? '') === '__personalizada__') ? 'block' : 'none'; ?>;">
                                     <input type="text" name="respuesta_seguridad_2" placeholder="Respuesta (opcional)"
                                         value="<?php echo htmlspecialchars($_POST['respuesta_seguridad_2'] ?? ''); ?>"
                                         style="margin-top: 8px;">
                                </div>
                                
                                <!-- Pregunta 3 -->
                                <div class="security-item" style="grid-column: span 2;">
                                    <label for="pregunta_seguridad_3">Pregunta 3 (opcional)</label>
                                     <select id="pregunta_seguridad_3" name="pregunta_seguridad_3">
                                         <option value="">-- Ninguna --</option>
                                         <option value="¿Cuál es tu deporte favorito?" <?php echo ($_POST['pregunta_seguridad_3'] ?? '') == '¿Cuál es tu deporte favorito?' ? 'selected' : ''; ?>>¿Deporte favorito?</option>
                                         <option value="¿En qué año te graduaste?" <?php echo ($_POST['pregunta_seguridad_3'] ?? '') == '¿En qué año te graduaste?' ? 'selected' : ''; ?>>¿Año de graduación?</option>
                                         <option value="¿Cuál es tu canción favorita?" <?php echo ($_POST['pregunta_seguridad_3'] ?? '') == '¿Cuál es tu canción favorita?' ? 'selected' : ''; ?>>¿Canción favorita?</option>
                                         <option value="__personalizada__" <?php echo ($_POST['pregunta_seguridad_3'] ?? '') == '__personalizada__' ? 'selected' : ''; ?>>Personalizada</option>
                                     </select>
                                     <input type="text" name="pregunta_personalizada_3" id="pregunta_personalizada_3" placeholder="Escribe tu pregunta personalizada"
                                         value="<?php echo htmlspecialchars($_POST['pregunta_personalizada_3'] ?? ''); ?>"
                                         style="margin-top:8px; display: <?php echo (($_POST['pregunta_seguridad_3'] ?? '') === '__personalizada__') ? 'block' : 'none'; ?>;">
                                     <input type="text" name="respuesta_seguridad_3" placeholder="Respuesta (opcional)"
                                         value="<?php echo htmlspecialchars($_POST['respuesta_seguridad_3'] ?? ''); ?>"
                                         style="margin-top: 8px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-user-plus"></i>
                    CREAR CUENTA
                </button>
                
                <div class="login-link">
                    ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Previsualización de imagen
        function previewImage(input) {
            const preview = document.getElementById('fotoPreview');
            const removeBtn = document.getElementById('btnRemovePhoto');
            const uploadArea = document.getElementById('photoUploadArea');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                if (file.size > 10 * 1024 * 1024) {
                    alert('La imagen no puede superar los 10MB');
                    input.value = '';
                    return;
                }
                
                if (!file.type.match('image.*')) {
                    alert('Solo se permiten imágenes');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    removeBtn.style.display = 'flex';
                    uploadArea.style.borderColor = '#28a745';
                    
                    const span = uploadArea.querySelector('label span');
                    if (span) span.textContent = 'Foto seleccionada';
                }
                reader.readAsDataURL(file);
            }
        }
        
        // Eliminar foto
        function removePhoto() {
            const input = document.getElementById('foto_perfil');
            const preview = document.getElementById('fotoPreview');
            const removeBtn = document.getElementById('btnRemovePhoto');
            const uploadArea = document.getElementById('photoUploadArea');
            
            input.value = '';
            preview.src = '#';
            preview.style.display = 'none';
            removeBtn.style.display = 'none';
            uploadArea.style.borderColor = '#8B1E3F';
            
            const span = uploadArea.querySelector('label span');
            if (span) span.textContent = 'Haz clic para subir tu foto';
        }
        
        // Fortaleza de contraseña
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrength');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 6) strength += 25;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
            if (password.match(/\d/)) strength += 25;
            if (password.match(/[^a-zA-Z\d]/)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            if (strength < 50) {
                strengthBar.className = 'strength-bar strength-weak';
            } else if (strength < 75) {
                strengthBar.className = 'strength-bar strength-fair';
            } else if (strength < 100) {
                strengthBar.className = 'strength-bar strength-good';
            } else {
                strengthBar.className = 'strength-bar strength-strong';
            }
        });
        
        // Validaciones
        document.getElementById('id_usuario').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });
        
        document.getElementById('telefono').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });

        // Validación en tiempo real (email y número de documento)
        (function() {
            function debounce(fn, delay) {
                let t;
                return function(...args) {
                    clearTimeout(t);
                    t = setTimeout(() => fn.apply(this, args), delay);
                };
            }

            const emailInput = document.getElementById('email');
            const idInput = document.getElementById('id_usuario');
            const emailFeedback = document.getElementById('emailFeedback');
            const idFeedback = document.getElementById('idFeedback');
            const submitBtn = document.querySelector('#registroForm button[type="submit"]');

            async function checkUnique(field, value) {
                if (!value) return { success: true, exists: false };
                try {
                    const res = await fetch('check_unique.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ field, value })
                    });
                    return await res.json();
                } catch (e) {
                    console.error('checkUnique error', e);
                    return { success: false, error: 'Error de conexión' };
                }
            }

            function setFeedback(el, msg, ok) {
                if (!el) return;
                el.textContent = msg;
                el.className = 'field-feedback ' + (ok ? 'success' : 'error');
            }

            const handleEmail = debounce(async function() {
                const value = this.value.trim();
                if (!value) { setFeedback(emailFeedback, '', true); submitBtn.disabled = false; return; }
                const data = await checkUnique('email', value);
                if (data && data.success) {
                    if (data.exists) {
                        setFeedback(emailFeedback, 'Este correo electrónico ya está siendo usado por otro usuario.', false);
                        submitBtn.disabled = true;
                    } else {
                        setFeedback(emailFeedback, 'Correo disponible.', true);
                        submitBtn.disabled = false;
                    }
                } else {
                    setFeedback(emailFeedback, '', true);
                }
            }, 450);

            const handleId = debounce(async function() {
                let value = this.value.trim();
                if (!value) { setFeedback(idFeedback, '', true); submitBtn.disabled = false; return; }
                // quitar no dígitos
                value = value.replace(/\D/g, '');
                const data = await checkUnique('id_usuario', value);
                if (data && data.success) {
                    if (data.exists) {
                        setFeedback(idFeedback, 'Este número de documento ya está registrado.', false);
                        submitBtn.disabled = true;
                    } else {
                        setFeedback(idFeedback, 'Documento disponible.', true);
                        submitBtn.disabled = false;
                    }
                } else {
                    setFeedback(idFeedback, '', true);
                }
            }, 350);

            emailInput.addEventListener('input', handleEmail);
            emailInput.addEventListener('blur', handleEmail);
            idInput.addEventListener('input', handleId);
            idInput.addEventListener('blur', handleId);
        })();
        // Mostrar campos de pregunta personalizada cuando se selecciona la opción
        function togglePersonalizada(selectId, customId) {
            const sel = document.getElementById(selectId);
            const cust = document.getElementById(customId);
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
            // inicializar
            update();
        }

        togglePersonalizada('pregunta_seguridad_1', 'pregunta_personalizada_1');
        togglePersonalizada('pregunta_seguridad_2', 'pregunta_personalizada_2');
        togglePersonalizada('pregunta_seguridad_3', 'pregunta_personalizada_3');
    </script>
</body>
</html>