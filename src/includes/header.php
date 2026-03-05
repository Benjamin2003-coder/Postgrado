<?php
// src/user/includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generar token CSRF para formularios sensibles (logout, etc.)
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}
// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /posgrado/auth/login.php');
    exit();
}

// Obtener datos del usuario
$nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$apellido = $_SESSION['usuario_apellido'] ?? '';
$foto = $_SESSION['usuario_foto'] ?? 'default-avatar.png';

// Determinar la URL pública correcta de la foto de perfil buscando en rutas conocidas
$foto_candidates = [
    '/POSGRADO/auth/assets/uploads/perfil/' . $foto,
    '/POSGRADO/assets/uploads/perfil/' . $foto,
    '/POSGRADO/src/assets/uploads/perfil/' . $foto,
    '/POSGRADO/public/images/' . $foto
];
$foto_url = '/POSGRADO/public/images/default-avatar.png';
foreach ($foto_candidates as $candidate_url) {
    $candidate_path = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . $candidate_url;
    if (file_exists($candidate_path)) {
        $foto_url = $candidate_url;
        break;
    }
}
// Guardar URL en sesión para que otras vistas la reutilicen
$_SESSION['usuario_foto_url'] = $foto_url;
$rol = $_SESSION['usuario_rol'] ?? 'estudiante';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Estudiante - UNEFA Postgrado</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" href="/POSGRADO/public/images/logo-unefa.png" type="image/png">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-x: hidden;
        }

        /* Header superior */
        .top-header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 15px 30px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-mini {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-mini img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .logo-mini h2 {
            font-size: 1.2rem;
            color: #8B1E3F;
            font-weight: 700;
        }

        .logo-mini p {
            font-size: 0.7rem;
            color: #F2A900;
            margin-top: -5px;
        }

        .page-title {
            font-size: 1.1rem;
            color: #495057;
            font-weight: 500;
            border-left: 2px solid #e1e1e1;
            padding-left: 20px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #8B1E3F;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .user-avatar:hover {
            transform: scale(1.05);
        }

        .user-details {
            text-align: right;
        }

        .user-details h4 {
            font-size: 0.9rem;
            color: #8B1E3F;
            font-weight: 600;
        }

        .user-details p {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .notification-badge {
            position: relative;
            cursor: pointer;
        }

        .notification-badge i {
            font-size: 1.2rem;
            color: #6c757d;
            transition: color 0.3s ease;
        }

        .notification-badge:hover i {
            color: #8B1E3F;
        }

        .badge-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #F2A900;
            color: #8B1E3F;
            font-size: 0.6rem;
            font-weight: 700;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Dropdown de usuario */
        .user-dropdown {
            position: relative;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            width: 250px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            z-index: 1000;
            margin-top: 10px;
        }

        .user-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-header {
            padding: 20px;
            border-bottom: 1px solid #e1e1e1;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .dropdown-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .dropdown-header h4 {
            font-size: 0.95rem;
            color: #8B1E3F;
            margin-bottom: 5px;
        }

        .dropdown-header p {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 20px;
            color: #495057;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
            color: #8B1E3F;
        }

        .dropdown-item i {
            width: 20px;
            color: #adb5bd;
            transition: color 0.3s ease;
        }

        .dropdown-item:hover i {
            color: #8B1E3F;
        }

        .dropdown-divider {
            height: 1px;
            background: #e1e1e1;
            margin: 5px 0;
        }

        .logout-item {
            color: #dc3545;
        }

        .logout-item i {
            color: #dc3545;
        }

        .logout-item:hover {
            background: #fee;
        }

        /* Contenido principal */
        .main-content {
            flex: 1;
            margin-top: 80px;
            margin-left: 100px;
            padding: 30px;
            transition: margin-left 0.3s ease;
        }

        /* Footer */
        .footer {
            background: white;
            padding: 20px 30px;
            margin-top: auto;
            border-top: 1px solid #e1e1e1;
            margin-left: 100px;
            transition: margin-left 0.3s ease;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #6c757d;
            font-size: 0.85rem;
        }

        .footer-links {
            display: flex;
            gap: 20px;
        }

        .footer-links a {
            color: #6c757d;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #8B1E3F;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .top-header {
                padding: 15px;
            }

            .page-title {
                display: none;
            }

            .user-details {
                display: none;
            }

            .main-content, .footer {
                margin-left: 0;
                padding: 20px;
            }

            .footer-content {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header superior -->
    <header class="top-header">
        <div class="header-left">
            <div class="logo-mini">
                <img src="/posgrado/public/images/logo-unefa.png" alt="UNEFA">
                <div>
                    <h2>UNEFA Postgrado</h2>
                    <p>Núcleo Nueva Esparta</p>
                </div>
            </div>
            <div class="page-title" id="pageTitle">
                Panel de Estudiante
            </div>
        </div>

        <div class="header-right">
            <div class="notification-badge">
                <i class="far fa-bell"></i>
                <span class="badge-count">3</span>
            </div>

            <div class="user-dropdown">
                <div class="user-info">
                    <img src="<?php echo $foto_url; ?>" 
                         alt="Avatar" 
                         class="user-avatar"
                        onerror="this.src='/POSGRADO/public/images/default-avatar.png'">
                    <div class="user-details">
                        <h4><?php echo $nombre . ' ' . $apellido; ?></h4>
                        <p><?php echo ucfirst($rol); ?></p>
                    </div>
                </div>

                <div class="dropdown-menu">
                    <div class="dropdown-header">
                                <img src="<?php echo $foto_url; ?>" 
                             alt="Avatar"
                                    onerror="this.src='/POSGRADO/public/images/default-avatar.png'">
                        <div>
                            <h4><?php echo $nombre . ' ' . $apellido; ?></h4>
                            <p><?php echo $rol; ?></p>
                        </div>
                    </div>
                    <a href="/POSGRADO/src/user/modules/perfil/perfil.php" class="dropdown-item">
                        <i class="fas fa-user"></i> Mi Perfil
                    </a>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-cog"></i> Configuración
                    </a>
                    <div class="dropdown-divider"></div>
                    <form method="post" action="/POSGRADO/config/logout.php" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <button type="submit" class="dropdown-item logout-item" style="background:none;border:none;padding:12px 20px;width:100%;text-align:left;cursor:pointer;">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>