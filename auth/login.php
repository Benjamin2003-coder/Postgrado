<?php
// auth/login.php
session_start();
require_once '../config/conexion.php';

$error = '';

// Verificar si ya está logueado
if (isset($_SESSION['usuario_id'])) {
    $rol = $_SESSION['usuario_rol'] ?? 'estudiante';
    
    if ($rol === 'admin') {
        header('Location: /posgrado/src/admin/index.php');
    } else {
        header('Location: /posgrado/src/user/index.php');
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/funciones.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $resultado = iniciarSesion($email, $password, $mysqli);
    
    if ($resultado['success']) {
        $_SESSION['usuario_id'] = $resultado['usuario']['id_usuario'];
        $_SESSION['usuario_email'] = $resultado['usuario']['email'];
        $_SESSION['usuario_nombre'] = $resultado['usuario']['nombre'];
        $_SESSION['usuario_rol'] = $resultado['usuario']['rol'];
        
        if ($resultado['usuario']['rol'] === 'admin') {
            header('Location: ../src/admin/index.php');
        } else {
            header('Location: ../src/user/index.php');
        }
        exit();
    } else {
        $error = $resultado['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - UNEFA Postgrado</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" href="../public/images/logo-unefa.png" type="image/png">
    
    <style>
        /* ========================================
           ESTILOS UNEFA - MISMOS DEL INDEX.HTML
        ======================================== */
        
        :root {
            --primary-color: #8B1E3F;     /* Rojo UNEFA */
            --secondary-color: #F2A900;    /* Amarillo UNEFA */
            --dark-color: #1E1E1E;
            --light-color: #F5F5F5;
            --gray-color: #6C757D;
            
            --font-primary: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --shadow-xl: 0 20px 25px rgba(0,0,0,0.15);
            
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color) 0%, #6a1730 100%);
            position: relative;
            overflow-x: hidden;
            padding: 20px;
        }
        
        /* ANIMACIONES DE FONDO - CÍRCULOS PULSANTES */
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
            background-image: url('../public/images/logo-unefa-white1.png');
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
        
        /* Fondo con logo de UNEFA (como en hero) */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('../public/images/logo-unefa-white1.png');
            background-repeat: repeat;
            background-position: center;
            background-size: 200px;
            opacity: 0.03;
            z-index: -1;
        }
        
        /* Decoración de círculos (como en el hero) */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 30%, rgba(242, 169, 0, 0.1) 0%, transparent 30%),
                        radial-gradient(circle at 80% 70%, rgba(139, 30, 63, 0.1) 0%, transparent 30%);
            z-index: -1;
        }
        
        .login-container {
            width: 100%;
            max-width: 1000px;
            display: flex;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            animation: fadeInUp 0.8s ease;
            position: relative;
            z-index: 10;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Panel izquierdo - Info UNEFA (CON ANIMACIÓN DE ROTACIÓN) */
        .info-panel {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color) 0%, #6a1730 100%);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        
        .info-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(242, 169, 0, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .logo-unefa {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }
        
        .logo-unefa img {
            width: 70px;
            height: 70px;
            object-fit: contain;
           
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .logo-unefa h2 {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .logo-unefa p {
            color: var(--secondary-color);
            font-size: 0.9rem;
            margin-top: -5px;
        }
        
        .info-content {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .info-content h1 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .info-content h1 span {
            color: var(--secondary-color);
        }
        
        .info-content p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
            max-width: 400px;
        }
        
        .benefits {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            color: white;
            transition: transform 0.3s ease;
        }
        
        .benefit-item:hover {
            transform: translateX(5px);
        }
        
        .benefit-item:last-child {
            margin-bottom: 0;
        }
        
        .benefit-item i {
            color: var(--secondary-color);
            font-size: 1.2rem;
            width: 25px;
            transition: transform 0.3s ease;
        }
        
        .benefit-item:hover i {
            transform: scale(1.2) rotate(5deg);
        }
        
        /* Panel derecho - Formulario */
        .form-panel {
            flex: 1;
            padding: 50px 45px;
            background: white;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .form-header h2 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: var(--gray-color);
            font-size: 1rem;
        }
        
        .error-message {
            background: #fee;
            color: #c53030;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid #c53030;
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
        
        .input-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-color);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .input-container {
            position: relative;
            transition: transform 0.3s ease;
        }
        
        .input-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-color);
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            font-family: inherit;
        }
        
        input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(139, 30, 63, 0.1);
        }
        
        input:focus + i {
            color: var(--primary-color);
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary-color) 0%, #6a1730 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            width: 100%;
            padding: 16px;
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            margin-bottom: 15px;
        }
        
        .btn-secondary:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .links {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .links a {
            color: var(--primary-color);
            text-decoration: none;
            display: block;
            margin: 8px 0;
            transition: var(--transition);
            font-size: 0.95rem;
        }
        
        .links a:hover {
            color: var(--secondary-color);
            transform: translateX(5px);
            text-decoration: none;
        }
        
        .links a i {
            margin-right: 8px;
            width: 20px;
            transition: transform 0.3s ease;
        }
        
        .links a:hover i {
            transform: scale(1.2);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .login-container {
                max-width: 500px;
                flex-direction: column;
            }
            
            .info-panel {
                padding: 40px;
            }
            
            .info-content h1 {
                font-size: 2rem;
            }
            
            .form-panel {
                padding: 40px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .info-panel {
                padding: 30px 20px;
            }
            
            .form-panel {
                padding: 30px 20px;
            }
            
            .logo-unefa img {
                width: 50px;
                height: 50px;
            }
            
            .logo-unefa h2 {
                font-size: 1.4rem;
            }
            
            .info-content h1 {
                font-size: 1.6rem;
            }
            
            .form-header h2 {
                font-size: 1.8rem;
            }
            
            .benefits {
                padding: 20px;
            }
        }
        
        /* CHATBOT FLOTANTE - Excelencia Educativa */
        .chatbot-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .chatbot-btn {
            width: 65px;
            height: 65px;
            background: linear-gradient(135deg, var(--primary-color) 0%, #6a1730 100%);
            border-radius: 50%;
            border: 3px solid var(--secondary-color);
            box-shadow: 0 4px 15px rgba(139, 30, 63, 0.4);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .chatbot-btn::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30% 30%, rgba(242, 169, 0, 0.4), transparent 70%);
            animation: pulse 2s infinite;
        }
        
        .chatbot-btn:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 6px 20px rgba(139, 30, 63, 0.6);
        }
        
        .chatbot-btn-pulse {
            animation: btnPulse 2s infinite;
        }
        
        @keyframes btnPulse {
            0% { box-shadow: 0 0 0 0 rgba(242, 169, 0, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(242, 169, 0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(242, 169, 0, 0); }
        }
        
        .chatbot-window {
            position: absolute;
            bottom: 75px;
            right: 0;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            display: none;
            border: 2px solid var(--primary-color);
            animation: slideUpFade 0.3s ease;
        }
        
        @keyframes slideUpFade {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .chatbot-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #6a1730 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 3px solid var(--secondary-color);
        }
        
        .chatbot-avatar {
            width: 45px;
            height: 45px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 2px solid var(--secondary-color);
        }
        
        .chatbot-avatar img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .chatbot-title h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
        }
        
        .chatbot-title p {
            margin: 0;
            font-size: 0.8rem;
            opacity: 0.9;
        }
        
        .chatbot-close {
            margin-left: auto;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .chatbot-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .chatbot-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        .chatbot-message {
            max-width: 80%;
            padding: 12px 16px;
            margin-bottom: 10px;
            border-radius: 15px;
            font-size: 0.95rem;
            line-height: 1.4;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .chatbot-message-bot {
            background: white;
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
        }
        
        .chatbot-message-user {
            background: var(--primary-color);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
            margin-left: auto;
            border-right: 4px solid var(--secondary-color);
        }
        
        .chatbot-input-area {
            padding: 15px;
            border-top: 2px solid #eee;
            display: flex;
            gap: 10px;
            background: white;
        }
        
        .chatbot-input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 25px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .chatbot-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(139, 30, 63, 0.1);
        }
        
        .chatbot-send {
            background: linear-gradient(135deg, var(--primary-color) 0%, #6a1730 100%);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .chatbot-send:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 10px rgba(139, 30, 63, 0.3);
        }
        
        .chatbot-typing {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 12px 16px;
            background: white;
            border-radius: 15px;
            margin-bottom: 10px;
            width: fit-content;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
            animation: fadeIn 0.3s ease;
        }
        
        .chatbot-typing span {
            width: 8px;
            height: 8px;
            background: var(--primary-color);
            border-radius: 50%;
            animation: typing 1.4s infinite;
            opacity: 0.5;
        }
        
        .chatbot-typing span:nth-child(2) { animation-delay: 0.2s; }
        .chatbot-typing span:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
            30% { transform: translateY(-10px); opacity: 1; }
        }
        
        .chatbot-suggestions {
            padding: 10px 15px;
            background: #f8f9fa;
            border-top: 1px solid #e1e1e1;
        }
        
        .chatbot-suggestions-title {
            font-size: 0.8rem;
            color: var(--gray-color);
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .chatbot-suggestion-btns {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .chatbot-suggestion-btn {
            background: white;
            border: 2px solid #e1e1e1;
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--dark-color);
            font-weight: 500;
        }
        
        .chatbot-suggestion-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        @media (max-width: 480px) {
            .chatbot-window {
                width: 300px;
                height: 450px;
                right: -10px;
            }
            
            .chatbot-container {
                bottom: 10px;
                right: 10px;
            }
        }
        
        /* Loader overlay */
        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(139, 30, 63, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }
        
        .loader-content {
            text-align: center;
            color: white;
        }
        
        .loader-content img {
            width: 120px;
            height: 120px;
            margin-bottom: 20px;
            filter: brightness(0) invert(1);
            animation: pulse 2s infinite;
        }
        
        .loader-content h2 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .loader-content p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <!-- ANIMACIONES DE FONDO - CÍRCULOS PULSANTES Y LOGO FLOTANTE -->
    <div class="pulse-circles">
        <div class="pulse-circle" style="width: 300px; height: 300px; top: 10%; left: 5%;"></div>
        <div class="pulse-circle" style="width: 200px; height: 200px; bottom: 20%; right: 10%;"></div>
        <div class="pulse-circle" style="width: 150px; height: 150px; top: 40%; right: 30%;"></div>
    </div>
    
    <div class="floating-logo"></div>
    
    <!-- Loader (opcional) -->
    <?php if (isset($_GET['welcome']) && $_GET['welcome'] == 1): ?>
    <div class="loader-overlay" id="welcomeLoader">
        <div class="loader-content">
            <img src="../public/images/logo-unefa.png" alt="UNEFA">
            <h2>Bienvenido a UNEFA Postgrado</h2>
            <p>Dirección de Investigación y Postgrado - Nueva Esparta</p>
        </div>
    </div>
    <script>
        setTimeout(() => {
            document.getElementById('welcomeLoader').style.display = 'none';
        }, 2000);
    </script>
    <?php endif; ?>
    
    <div class="login-container">
        <!-- Panel izquierdo - UNEFA (CON ANIMACIÓN DE ROTACIÓN) -->
        <div class="info-panel">
            <div class="logo-unefa">
                <img src="../public/images/logo-unefa.png" alt="UNEFA Logo">
                <div>
                    <h2>UNEFA</h2>
                    <p>Núcleo Nueva Esparta</p>
                </div>
            </div>
            
            <div class="info-content">
                <h1>Dirección de <span>Investigación</span> y Postgrado</h1>
                <p>Accede al sistema de gestión académica para estudiantes de postgrado</p>
                
                <div class="benefits">
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Gestión de maestrías y doctorados</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Inscripción a materias por créditos</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Pagos por cuotas con pago móvil</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Historial académico en línea</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Panel derecho - Login -->
        <div class="form-panel">
            <div class="form-header">
                <h2>Iniciar Sesión</h2>
                <p>Ingresa tus credenciales para continuar</p>
            </div>
            
            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-group">
                    <label for="email">CORREO ELECTRÓNICO</label>
                    <div class="input-container">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" required 
                               placeholder="ejemplo@unefa.edu.ve"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="password">CONTRASEÑA</label>
                    <div class="input-container">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required 
                               placeholder="••••••••">
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    INGRESAR AL SISTEMA
                </button>
                
                <a href="../index.html" class="btn-secondary">
                    <i class="fas fa-home"></i>
                    VOLVER AL INICIO
                </a>
            </form>
            
            <div class="links">
                <a href="register.php">
                    <i class="fas fa-user-plus"></i>
                    ¿No tienes cuenta? Regístrate
                </a>
                <a href="recuperar-password.php">
                    <i class="fas fa-key"></i>
                    ¿Olvidaste tu contraseña?
                </a>
            </div>
        </div>
    </div>
    
    <!-- CHATBOT FLOTANTE - Excelencia Educativa -->
    <div class="chatbot-container">
        <div class="chatbot-window" id="chatbotWindow">
            <div class="chatbot-header">
                <div class="chatbot-avatar">
                    <img src="../public/images/logo-unefa.png" alt="Excelencia Educativa">
                </div>
                <div class="chatbot-title">
                    <h3>Excelencia Educativa</h3>
                    <p>Asistente Virtual UNEFA</p>
                </div>
                <button class="chatbot-close" onclick="toggleChatbot()">×</button>
            </div>
            
            <div class="chatbot-messages" id="chatbotMessages">
                <!-- Mensajes se cargarán aquí -->
            </div>
            
            <div class="chatbot-suggestions">
                <div class="chatbot-suggestions-title">Preguntas frecuentes:</div>
                <div class="chatbot-suggestion-btns">
                    <button class="chatbot-suggestion-btn" onclick="sendSuggestion('¿Qué maestrías ofrecen?')">
                        <i class="fas fa-graduation-cap"></i> Maestrías
                    </button>
                    <button class="chatbot-suggestion-btn" onclick="sendSuggestion('¿Cómo me inscribo?')">
                        <i class="fas fa-pencil-alt"></i> Inscripción
                    </button>
                    <button class="chatbot-suggestion-btn" onclick="sendSuggestion('¿Valor del crédito?')">
                        <i class="fas fa-dollar-sign"></i> Créditos
                    </button>
                    <button class="chatbot-suggestion-btn" onclick="sendSuggestion('¿Horarios de atención?')">
                        <i class="fas fa-clock"></i> Horarios
                    </button>
                </div>
            </div>
            
            <div class="chatbot-input-area">
                <input type="text" class="chatbot-input" id="chatbotInput" 
                       placeholder="Escribe tu pregunta..." onkeypress="handleChatbotKeyPress(event)">
                <button class="chatbot-send" onclick="sendChatbotMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
        
        <button class="chatbot-btn chatbot-btn-pulse" onclick="toggleChatbot()" id="chatbotBtn">
            <img src="../public/images/logo-unefa.png" alt="Excelencia Educativa" style="width: 40px; height: 40px;">
        </button>
    </div>
    
    <script>
        // Variables globales
        let chatbotOpen = false;
        
        // Alternar visibilidad del chatbot
        function toggleChatbot() {
            const window = document.getElementById('chatbotWindow');
            const btn = document.getElementById('chatbotBtn');
            
            chatbotOpen = !chatbotOpen;
            
            if (chatbotOpen) {
                window.style.display = 'flex';
                btn.classList.remove('chatbot-btn-pulse');
                if (document.getElementById('chatbotMessages').children.length === 0) {
                    addBotMessage("🎓 <strong>Excelencia Educativa</strong><br><br>¡Bienvenido a la Dirección de Investigación y Postgrado de la UNEFA Núcleo Nueva Esparta!<br><br>Soy tu asistente virtual y puedo ayudarte con información sobre maestrías, inscripciones, créditos, pagos y más. ¿En qué puedo asistirte hoy?");
                }
                document.getElementById('chatbotInput').focus();
            } else {
                window.style.display = 'none';
            }
        }
        
        // Agregar mensaje del usuario
        function addUserMessage(text) {
            const messagesDiv = document.getElementById('chatbotMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chatbot-message chatbot-message-user';
            messageDiv.innerHTML = text;
            messagesDiv.appendChild(messageDiv);
            scrollToBottom();
        }
        
        // Agregar mensaje del bot
        function addBotMessage(text) {
            const messagesDiv = document.getElementById('chatbotMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chatbot-message chatbot-message-bot';
            messageDiv.innerHTML = text;
            messagesDiv.appendChild(messageDiv);
            scrollToBottom();
        }
        
        // Mostrar indicador de "escribiendo"
        function showTyping() {
            const messagesDiv = document.getElementById('chatbotMessages');
            const typingDiv = document.createElement('div');
            typingDiv.className = 'chatbot-typing';
            typingDiv.id = 'typingIndicator';
            typingDiv.innerHTML = `
                <span></span>
                <span></span>
                <span></span>
                <small style="margin-left: 5px; color: #666;">Excelencia Educativa está escribiendo...</small>
            `;
            messagesDiv.appendChild(typingDiv);
            scrollToBottom();
        }
        
        // Remover indicador de "escribiendo"
        function removeTyping() {
            const typingDiv = document.getElementById('typingIndicator');
            if (typingDiv) {
                typingDiv.remove();
            }
        }
        
        // Enviar mensaje
        async function sendChatbotMessage() {
            const input = document.getElementById('chatbotInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            addUserMessage(message);
            input.value = '';
            showTyping();
            
            try {
                const response = await fetch('chatbot.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ pregunta: message })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                removeTyping();
                
                if (data.success) {
                    addBotMessage(data.respuesta);
                } else {
                    addBotMessage('Lo siento, hubo un error: ' + (data.respuesta || 'Intenta nuevamente.'));
                }
                
            } catch (error) {
                console.error('Error detallado:', error);
                removeTyping();
                addBotMessage('Error de conexión. Verifica que el archivo chatbot.php exista en la carpeta auth/.');
            }
        }
        
        // Enviar sugerencia
        function sendSuggestion(text) {
            document.getElementById('chatbotInput').value = text;
            sendChatbotMessage();
        }
        
        // Manejar tecla Enter
        function handleChatbotKeyPress(event) {
            if (event.key === 'Enter') {
                sendChatbotMessage();
            }
        }
        
        // Scroll al final
        function scrollToBottom() {
            const messagesDiv = document.getElementById('chatbotMessages');
            setTimeout(() => {
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            }, 100);
        }
        
        // Cerrar chatbot al hacer clic fuera
        document.addEventListener('click', function(event) {
            const chatbotWindow = document.getElementById('chatbotWindow');
            const chatbotBtn = document.getElementById('chatbotBtn');
            
            if (chatbotOpen && 
                !chatbotWindow.contains(event.target) && 
                !chatbotBtn.contains(event.target)) {
                toggleChatbot();
            }
        });
        
        // Inicializar con mensaje de bienvenida después de 3 segundos
        setTimeout(() => {
            if (!chatbotOpen) {
                const btn = document.getElementById('chatbotBtn');
                btn.classList.add('chatbot-btn-pulse');
            }
        }, 3000);
        
        // Efecto de foco en inputs
        const inputs = document.querySelectorAll('input');
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
</body>
</html>