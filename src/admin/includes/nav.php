<?php
// src/admin/includes/nav.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* ========================================
       MENÚ SEMICIRCULAR PRINCIPAL
    ======================================== */
    .semi-circle-nav {
        position: fixed;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        width: 70px;
        height: 450px;
        background: white;
        border-radius: 40px;
        box-shadow: 0 10px 30px rgba(139, 30, 63, 0.15);
        z-index: 999;
        transition: all 0.3s ease;
        overflow: visible;
        border: 2px solid #8B1E3F;
    }

    .semi-circle-nav:hover {
        width: 250px;
        box-shadow: 0 15px 40px rgba(139, 30, 63, 0.25);
    }

    .nav-items {
        display: flex;
        flex-direction: column;
        height: 100%;
        padding: 20px 0;
        position: relative;
    }

    /* Items principales */
    .nav-item {
        position: relative;
        display: flex;
        align-items: center;
        padding: 15px 20px;
        color: #495057;
        text-decoration: none;
        transition: all 0.3s ease;
        white-space: nowrap;
        border-left: 4px solid transparent;
    }

    .nav-item:hover {
        background: rgba(242, 169, 0, 0.1);
        color: #8B1E3F;
        border-left-color: #F2A900;
    }

    .nav-item.active {
        background: rgba(139, 30, 63, 0.1);
        color: #8B1E3F;
        border-left-color: #8B1E3F;
        font-weight: 600;
    }

    .nav-item i {
        font-size: 1.3rem;
        min-width: 30px;
        color: inherit;
        transition: transform 0.3s ease;
    }

    .nav-item:hover i {
        transform: scale(1.1);
    }

    .nav-item span {
        margin-left: 15px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .semi-circle-nav:hover .nav-item span {
        opacity: 1;
    }

    .nav-divider {
        height: 1px;
        background: #e1e1e1;
        margin: 10px 0;
    }

    /* Tooltips */
    .nav-item::after {
        content: attr(data-tooltip);
        position: absolute;
        left: 80px;
        background: #8B1E3F;
        color: white;
        padding: 8px 12px;
        border-radius: 5px;
        font-size: 0.8rem;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        pointer-events: none;
        z-index: 1000;
    }

    .nav-item:hover::after {
        opacity: 1;
        visibility: visible;
        left: 90px;
    }

    .semi-circle-nav:hover .nav-item::after {
        opacity: 0;
        visibility: hidden;
    }

    /* Indicador de página activa */
    .nav-indicator {
        position: absolute;
        right: 10px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #F2A900;
        display: none;
    }

    .nav-item.active .nav-indicator {
        display: block;
    }

    /* ========================================
       SUBMENÚS DESPLEGABLES
    ======================================== */
    .nav-item.has-submenu {
        position: relative;
    }

    .submenu {
        position: absolute;
        left: 100%;
        top: 0;
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(139, 30, 63, 0.2);
        min-width: 280px;
        opacity: 0;
        visibility: hidden;
        transform: translateX(10px);
        transition: all 0.3s ease;
        border: 2px solid #8B1E3F;
        z-index: 1000;
        pointer-events: none;
    }

    .nav-item.has-submenu:hover .submenu,
    .submenu:hover {
        opacity: 1;
        visibility: visible;
        transform: translateX(0);
        pointer-events: auto;
    }

    .submenu-header {
        padding: 15px;
        background: linear-gradient(135deg, #8B1E3F 0%, #6a1730 100%);
        color: white;
        border-radius: 13px 13px 0 0;
        font-weight: 600;
        font-size: 0.9rem;
        border-bottom: 2px solid #F2A900;
    }

    .submenu-header i {
        margin-right: 8px;
        color: #F2A900;
    }

    .submenu-items {
        padding: 10px;
    }

    .submenu-item {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        color: #495057;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s ease;
        margin: 5px 0;
        border-left: 3px solid transparent;
    }

    .submenu-item i {
        width: 25px;
        color: #8B1E3F;
        font-size: 1rem;
        transition: transform 0.3s ease;
    }

    .submenu-item span {
        flex: 1;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .submenu-item .badge {
        background: #F2A900;
        color: #8B1E3F;
        padding: 3px 8px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .submenu-item:hover {
        background: rgba(242, 169, 0, 0.1);
        border-left-color: #F2A900;
    }

    .submenu-item:hover i {
        transform: scale(1.1);
    }

    .submenu-divider {
        height: 1px;
        background: #e1e1e1;
        margin: 8px 0;
    }

    .submenu-note {
        padding: 10px 15px;
        font-size: 0.75rem;
        color: #6c757d;
        background: #f8f9fa;
        border-radius: 0 0 13px 13px;
        border-top: 1px dashed #e1e1e1;
    }

    .submenu-note i {
        color: #F2A900;
        margin-right: 5px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .semi-circle-nav {
            bottom: 20px;
            top: auto;
            left: 50%;
            transform: translateX(-50%);
            width: auto;
            height: 60px;
            flex-direction: row;
            border-radius: 30px;
        }

        .semi-circle-nav:hover {
            width: auto;
        }

        .nav-items {
            flex-direction: row;
            padding: 0 10px;
        }

        .nav-item {
            padding: 15px;
        }

        .nav-item span {
            display: none;
        }

        .semi-circle-nav:hover .nav-item span {
            display: none;
        }

        .nav-item::after {
            left: 50%;
            transform: translateX(-50%);
            bottom: 70px;
            top: auto;
        }

        .nav-divider {
            width: 1px;
            height: 30px;
            margin: 0 5px;
        }

        .submenu {
            left: 50%;
            transform: translateX(-50%) translateY(-10px);
            top: auto;
            bottom: 100%;
            margin-bottom: 10px;
        }

        .nav-item.has-submenu:hover .submenu {
            transform: translateX(-50%) translateY(0);
        }
    }
</style>

<nav class="semi-circle-nav">
    <div class="nav-items">
        <!-- INICIO / DASHBOARD -->
        <a href="/POSGRADO/src/admin/index.php" 
           class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>"
           data-tooltip="Inicio">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
            <span class="nav-indicator"></span>
        </a>

        <!-- PROGRAMAS (con submenú) -->
        <div class="nav-item has-submenu <?php echo strpos($_SERVER['REQUEST_URI'], 'programas') !== false || strpos($_SERVER['REQUEST_URI'], 'especializacion') !== false || strpos($_SERVER['REQUEST_URI'], 'maestria') !== false || strpos($_SERVER['REQUEST_URI'], 'doctorado') !== false ? 'active' : ''; ?>"
             data-tooltip="Programas">
            <i class="fas fa-graduation-cap"></i>
            <span>Programas</span>
            <span class="nav-indicator"></span>
            
            <div class="submenu">
                <div class="submenu-header">
                    <i class="fas fa-graduation-cap"></i> Programas de Posgrado
                </div>
                
                <div class="submenu-items">
                    <!-- ESPECIALIZACIÓN -->
                    <a href="/POSGRADO/src/admin/modules/especializacion/especializacion.php" class="submenu-item">
                        <i class="fas fa-certificate" style="color: #17a2b8;"></i>
                        <span>Especializaciones</span>
                        <span class="badge" style="background: #17a2b8;">1 año</span>
                    </a>
                    
                    <!-- MAESTRÍA -->
                    <a href="/POSGRADO/src/admin/modules/maestria/maestria.php" class="submenu-item">
                        <i class="fas fa-graduation-cap" style="color: #8B1E3F;"></i>
                        <span>Maestrías</span>
                        <span class="badge" style="background: #8B1E3F;">2 años</span>
                    </a>
                    
                    <!-- DOCTORADO -->
                    <a href="/POSGRADO/src/admin/modules/doctorado/doctorado.php" class="submenu-item">
                        <i class="fas fa-microscope" style="color: #F2A900;"></i>
                        <span>Doctorados</span>
                        <span class="badge" style="background: #F2A900; color: #8B1E3F;">3-4 años</span>
                    </a>
                    
                    <div class="submenu-divider"></div>
                    
                    <!-- NUEVO PROGRAMA (para crear) -->
                    <a href="/POSGRADO/src/admin/modules/programas/agregar.php" class="submenu-item">
                        <i class="fas fa-plus-circle"></i>
                        <span>Nuevo Programa</span>
                        <span class="badge">crear</span>
                    </a>
                </div>
                
                <div class="submenu-note">
                    <i class="fas fa-info-circle"></i> Especializaciones · Maestrías · Doctorados
                </div>
            </div>
        </div>

        <!-- PAGOS (con submenú) -->
        <div class="nav-item has-submenu <?php echo strpos($_SERVER['REQUEST_URI'], 'pagos') !== false ? 'active' : ''; ?>"
             data-tooltip="Pagos">
            <i class="fas fa-credit-card"></i>
            <span>Pagos</span>
            <span class="nav-indicator"></span>
            
            <div class="submenu">
                <div class="submenu-header">
                    <i class="fas fa-credit-card"></i> Gestión de Pagos
                </div>
                
                <div class="submenu-items">
                    <a href="/POSGRADO/src/admin/modules/pagos/historial.php" class="submenu-item">
                        <i class="fas fa-history"></i>
                        <span>Historial de Pagos</span>
                        <span class="badge">todos</span>
                    </a>
                    
                    <a href="/POSGRADO/src/admin/modules/pagos/pendientes.php" class="submenu-item">
                        <i class="fas fa-clock"></i>
                        <span>Pagos Pendientes</span>
                        <span class="badge" style="background: #dc3545; color: white;">3</span>
                    </a>
                    
                    <a href="/POSGRADO/src/admin/modules/pagos/verificar.php" class="submenu-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Verificar Pagos</span>
                        <span class="badge">pago móvil</span>
                    </a>
                    
                    <div class="submenu-divider"></div>
                    
                    <a href="/POSGRADO/src/admin/modules/configuracion/creditos.php" class="submenu-item">
                        <i class="fas fa-coins"></i>
                        <span>Valor del Crédito</span>
                        <span class="badge">config</span>
                    </a>
                </div>
                
                <div class="submenu-note">
                    <i class="fas fa-info-circle"></i> Gestiona pagos y transacciones
                </div>
            </div>
        </div>

        <!-- REPORTES (con submenú) -->
        <div class="nav-item has-submenu <?php echo strpos($_SERVER['REQUEST_URI'], 'reportes') !== false ? 'active' : ''; ?>"
             data-tooltip="Reportes">
            <i class="fas fa-chart-bar"></i>
            <span>Reportes</span>
            <span class="nav-indicator"></span>
            
            <div class="submenu">
                <div class="submenu-header">
                    <i class="fas fa-chart-bar"></i> Reportes y Estadísticas
                </div>
                
                <div class="submenu-items">
                    <a href="/POSGRADO/src/admin/modules/reportes/estudiantes.php" class="submenu-item">
                        <i class="fas fa-users"></i>
                        <span>Estudiantes</span>
                        <span class="badge">matriculados</span>
                    </a>
                    
                    <a href="/POSGRADO/src/admin/modules/reportes/inscripciones.php" class="submenu-item">
                        <i class="fas fa-pencil-alt"></i>
                        <span>Inscripciones</span>
                        <span class="badge">por período</span>
                    </a>
                    
                    <a href="/POSGRADO/src/admin/modules/reportes/pagos.php" class="submenu-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Ingresos</span>
                        <span class="badge">financiero</span>
                    </a>
                    
                    <div class="submenu-divider"></div>
                    
                    <a href="/POSGRADO/src/admin/modules/reportes/rendimiento.php" class="submenu-item">
                        <i class="fas fa-trophy"></i>
                        <span>Rendimiento</span>
                        <span class="badge">académico</span>
                    </a>
                </div>
                
                <div class="submenu-note">
                    <i class="fas fa-info-circle"></i> Reportes y análisis de datos
                </div>
            </div>
        </div>

        <div class="nav-divider"></div>

        <!-- PERFIL (sin submenú) -->
        <a href="/POSGRADO/src/admin/modules/perfil/perfil.php" 
           class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'perfil') !== false ? 'active' : ''; ?>"
           data-tooltip="Mi Perfil">
            <i class="fas fa-user-cog"></i>
            <span>Mi Perfil</span>
            <span class="nav-indicator"></span>
        </a>
    </div>
</nav>