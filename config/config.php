<?php
// config/config.php

// Incluir la conexión
require_once __DIR__ . '/conexion.php';

// Protecciones globales: evitar edición de footers desde la app
if (!defined('FOOTER_PROTECTED')) {
    define('FOOTER_PROTECTED', true);
}

// Verificar si el archivo proteger_archivos.php existe antes de incluirlo
$proteger_archivos_path = __DIR__ . '/proteger_archivos.php';
if (file_exists($proteger_archivos_path)) {
    require_once $proteger_archivos_path;
}

// Configuración de rutas - CORREGIDO para posgrado
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/posgrado/';

// Directorio para fotos de perfil
$upload_dir = __DIR__ . '/../assets/uploads/perfil/';

// Crear directorio si no existe
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Configuración de zona horaria
date_default_timezone_set('America/Caracas');

// Configuración de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Constantes útiles
define('SITE_NAME', 'UNEFA Postgrado - Nueva Esparta');
define('SITE_VERSION', '1.0.0');
?>