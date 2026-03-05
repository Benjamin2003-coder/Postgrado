<?php
// src/user/includes/nav.php
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
        height: 420px; /* Aumentado para dar espacio */
        background: white;
        border-radius: 40px;
        box-shadow: 0 10px 30px rgba(139, 30, 63, 0.15);
        z-index: 999; /* Alto para estar sobre todo */
        transition: all 0.3s ease;
        overflow: visible; /* ¡IMPORTANTE! Para que el submenú se vea fuera */
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

    /* ========================================
       ESTILOS DE LOS ITEMS PRINCIPALES
    ======================================== */
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

    /* Tooltips cuando el menú está cerrado */
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
       SUBMENÚ DESPLEGABLE (LA CLAVE)
    ======================================== */
    .nav-item.has-submenu {
        position: relative;
    }

    .submenu {
        position: absolute;
        left: 100%; /* Se posiciona a la derecha del item */
        top: 0;
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(139, 30, 63, 0.2);
        min-width: 220px;
        opacity: 0;
        visibility: hidden;
        transform: translateX(10px);
        transition: all 0.3s ease;
        border: 2px solid #8B1E3F;
        z-index: 1000;
        pointer-events: none;
    }

    /* Mostrar submenú al hacer hover sobre el item */
    .nav-item.has-submenu:hover .submenu {
        opacity: 1;
        visibility: visible;
        transform: translateX(0);
        pointer-events: auto;
    }

    /* También mantener visible cuando el mouse está sobre el submenú */
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

    /* ========================================
       RESPONSIVE
    ======================================== */
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

        /* En móvil, el submenú se despliega hacia arriba */
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

<?php
// Verificar si el usuario tiene maestría activa (para mostrar opciones)
// Esto deberías obtenerlo desde la sesión o base de datos
$tiene_maestria = false; // Cambiar según la lógica real
$id_usuario = $_SESSION['usuario_id'] ?? '';

// Aquí podrías consultar si el usuario tiene una maestría activa
// Por ahora lo dejamos como ejemplo
?>

<nav class="semi-circle-nav">
    <div class="nav-items">
        <!-- DASHBOARD / INICIO -->
        <a href="/posgrado/src/user/index.php" 
           class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>"
           data-tooltip="Inicio">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
            <span class="nav-indicator"></span>
        </a>

        <!-- INSCRIPCIÓN (con submenú) -->
        <div class="nav-item has-submenu <?php echo strpos($_SERVER['REQUEST_URI'], 'inscripcion') !== false ? 'active' : ''; ?>"
             data-tooltip="Inscripción">
            <i class="fas fa-pencil-alt"></i>
            <span>Inscripción</span>
            <span class="nav-indicator"></span>
            
            <!-- SUBMENÚ DESPLEGABLE -->
            <div class="submenu">
                <div class="submenu-header">
                    <i class="fas fa-graduation-cap"></i> Opciones de Inscripción
                </div>
                
                <div class="submenu-items">
                    <!-- Opción 1: Inscribir Maestría (Siempre visible) -->
                    <a href="/posgrado/src/user/modules/inscripcion/inscripcion_maestria.php" 
                       class="submenu-item">
                        <i class="fas fa-university"></i>
                        <span>Inscribir Maestría</span>
                        <span class="badge">1°</span>
                    </a>
                    
                    <div class="submenu-divider"></div>
                    
                    <!-- Opción 2: Inscribir Materias (Solo si tiene maestría) -->
                    <a href="/posgrado/src/user/modules/inscripcion/inscripcion_materias.php" 
                       class="submenu-item <?php echo !$tiene_maestria ? 'disabled' : ''; ?>"
                       <?php if (!$tiene_maestria): ?>
                       onclick="event.preventDefault(); alert('Primero debes inscribirte en una maestría');"
                       style="opacity: 0.6; cursor: not-allowed;"
                       <?php endif; ?>>
                        <i class="fas fa-book"></i>
                        <span>Inscribir Materias</span>
                        <span class="badge">2°</span>
                    </a>
                </div>
                
                <div class="submenu-note">
                    <i class="fas fa-info-circle"></i> 
                    Primero inscríbete en una maestría, luego en materias
                </div>
            </div>
        </div>

        <!-- PAGOS -->
        <a href="/posgrado/src/user/modules/pagos/pagos.php" 
           class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'pagos') !== false ? 'active' : ''; ?>"
           data-tooltip="Pagos">
            <i class="fas fa-credit-card"></i>
            <span>Pagos</span>
            <span class="nav-indicator"></span>
        </a>

        <!-- HORARIO -->
        <a href="/posgrado/src/user/modules/horario/horario.php" 
           class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'horario') !== false ? 'active' : ''; ?>"
           data-tooltip="Horario">
            <i class="fas fa-calendar-alt"></i>
            <span>Horario</span>
            <span class="nav-indicator"></span>
        </a>

        <div class="nav-divider"></div>

        <!-- PERFIL -->
        <a href="/posgrado/src/user/modules/perfil/perfil.php" 
           class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'perfil') !== false ? 'active' : ''; ?>"
           data-tooltip="Mi Perfil">
            <i class="fas fa-user"></i>
            <span>Mi Perfil</span>
            <span class="nav-indicator"></span>
        </a>
    </div>
</nav>

<script>
// ========================================
// VERIFICACIÓN DE MAESTRÍA ACTIVA
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    const submenuItemMaterias = document.querySelector('.submenu-item[href*="inscripcion_materias.php"]');
    const navItemInscripcion = document.querySelector('.nav-item.has-submenu');
    
    if (!submenuItemMaterias) return;
    
    // Mostrar indicador de carga
    submenuItemMaterias.innerHTML = `
        <i class="fas fa-spinner fa-spin"></i>
        <span>Verificando...</span>
    `;
    
    // Hacer petición AJAX para verificar maestría
    fetch('/posgrado/api/verificar_maestria.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                actualizarOpcionMaterias(data);
            } else {
                // Si hay error, mostrar opción deshabilitada por defecto
                deshabilitarOpcionMaterias('Error al verificar');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            deshabilitarOpcionMaterias('Error de conexión');
        });
    
    // Función para actualizar la opción según el estado
    function actualizarOpcionMaterias(data) {
        if (data.tiene_maestria) {
            // HABILITAR opción de materias
            habilitarOpcionMaterias(data.info_maestria);
        } else {
            // DESHABILITAR opción de materias
            deshabilitarOpcionMaterias('Primero inscríbete en una maestría');
        }
    }
    
    // Función para habilitar la opción de materias
    function habilitarOpcionMaterias(infoMaestria) {
        submenuItemMaterias.innerHTML = `
            <i class="fas fa-book"></i>
            <span>Inscribir Materias</span>
            <span class="badge">2°</span>
        `;
        
        submenuItemMaterias.style.opacity = '1';
        submenuItemMaterias.style.cursor = 'pointer';
        submenuItemMaterias.style.pointerEvents = 'auto';
        submenuItemMaterias.classList.remove('disabled');
        
        // Quitar el onclick que bloqueaba
        submenuItemMaterias.onclick = null;
        
        // Agregar atributo con información del programa (compatibilidad: usa programa_* o maestria_*)
        if (infoMaestria) {
            const nombreProg = infoMaestria.programa_nombre || infoMaestria.maestria_nombre || '';
            const codigoProg = infoMaestria.codigo_programa || infoMaestria.codigo_maestria || '';
            submenuItemMaterias.setAttribute('data-maestria', nombreProg);
            submenuItemMaterias.setAttribute('data-codigo', codigoProg);
            // Actualizar la nota del submenú
            const submenuNote = document.querySelector('.submenu-note');
            if (submenuNote) {
                submenuNote.innerHTML = `
                    <i class="fas fa-check-circle" style="color: #28a745;"></i> 
                    Maestría activa: <strong>${codigoProg}</strong>
                `;
            }
        }
    }
    
    // Función para deshabilitar la opción de materias
    function deshabilitarOpcionMaterias(mensaje) {
        submenuItemMaterias.innerHTML = `
            <i class="fas fa-book" style="opacity: 0.5;"></i>
            <span style="opacity: 0.7;">Inscribir Materias</span>
            <span class="badge" style="background: #6c757d;">bloqueado</span>
        `;
        
        submenuItemMaterias.style.opacity = '0.7';
        submenuItemMaterias.style.cursor = 'not-allowed';
        submenuItemMaterias.style.pointerEvents = 'auto'; // Para que el tooltip funcione
        
        // Agregar título con el mensaje
        submenuItemMaterias.setAttribute('title', mensaje);
        
        // Prevenir la navegación
        submenuItemMaterias.onclick = function(e) {
            e.preventDefault();
            mostrarNotificacion(mensaje, 'warning');
        };
        
        // Actualizar la nota del submenú
        const submenuNote = document.querySelector('.submenu-note');
        if (submenuNote) {
            submenuNote.innerHTML = `
                <i class="fas fa-info-circle" style="color: #F2A900;"></i> 
                ${mensaje}
            `;
        }
    }
    
    // Función para mostrar notificación flotante
    function mostrarNotificacion(mensaje, tipo = 'info') {
        // Crear elemento de notificación
        const notificacion = document.createElement('div');
        notificacion.className = 'floating-notification';
        
        // Estilos
        notificacion.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: ${tipo === 'warning' ? '#fff3cd' : '#d4edda'};
            color: ${tipo === 'warning' ? '#856404' : '#155724'};
            border-left: 4px solid ${tipo === 'warning' ? '#F2A900' : '#28a745'};
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            z-index: 9999;
            animation: slideInRight 0.3s ease;
            max-width: 350px;
            font-size: 0.9rem;
        `;
        
        notificacion.innerHTML = `
            <i class="fas ${tipo === 'warning' ? 'fa-exclamation-triangle' : 'fa-check-circle'}"></i>
            <span style="margin-left: 10px;">${mensaje}</span>
        `;
        
        document.body.appendChild(notificacion);
        
        // Remover después de 3 segundos
        setTimeout(() => {
            notificacion.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notificacion.remove(), 300);
        }, 3000);
    }
    
    // Agregar animaciones CSS si no existen
    if (!document.querySelector('#notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideInRight {
                from {
                    opacity: 0;
                    transform: translateX(100px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            @keyframes slideOutRight {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(100px);
                }
            }
            
            .floating-notification {
                display: flex;
                align-items: center;
            }
            
            .floating-notification i {
                font-size: 1.2rem;
            }
        `;
        document.head.appendChild(style);
    }
});

// ========================================
// ACTUALIZACIÓN EN TIEMPO REAL (opcional)
// ========================================
// Si quieres que se actualice automáticamente cada cierto tiempo
// (por si el usuario se inscribe en una maestría mientras navega)

let intervaloVerificacion;

document.addEventListener('DOMContentLoaded', function() {
    // Verificar cada 30 segundos
    intervaloVerificacion = setInterval(verificarMaestriaPeriodicamente, 30000);
});

function verificarMaestriaPeriodicamente() {
    fetch('/posgrado/api/verificar_maestria.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const submenuItem = document.querySelector('.submenu-item[href*="inscripcion_materias.php"]');
                const submenuNote = document.querySelector('.submenu-note');
                
                if (data.tiene_maestria) {
                    // Si tiene maestría, habilitar
                    if (submenuItem && submenuItem.style.opacity === '0.7') {
                        submenuItem.innerHTML = `
                            <i class="fas fa-book"></i>
                            <span>Inscribir Materias</span>
                            <span class="badge">2°</span>
                        `;
                        submenuItem.style.opacity = '1';
                        submenuItem.style.cursor = 'pointer';
                        submenuItem.onclick = null;
                        
                        if (submenuNote) {
                            const codigoProg = data.info_maestria?.codigo_programa || data.info_maestria?.codigo_maestria || '';
                            submenuNote.innerHTML = `
                                <i class="fas fa-check-circle" style="color: #28a745;"></i> 
                                Maestría activa: <strong>${codigoProg}</strong>
                            `;
                        }
                    }
                } else {
                    // Si no tiene maestría, deshabilitar
                    if (submenuItem && submenuItem.style.opacity !== '0.7') {
                        submenuItem.innerHTML = `
                            <i class="fas fa-book" style="opacity: 0.5;"></i>
                            <span style="opacity: 0.7;">Inscribir Materias</span>
                            <span class="badge" style="background: #6c757d;">bloqueado</span>
                        `;
                        submenuItem.style.opacity = '0.7';
                        submenuItem.style.cursor = 'not-allowed';
                        submenuItem.onclick = function(e) {
                            e.preventDefault();
                            mostrarNotificacion('Primero inscríbete en una maestría', 'warning');
                        };
                        
                        if (submenuNote) {
                            submenuNote.innerHTML = `
                                <i class="fas fa-info-circle" style="color: #F2A900;"></i> 
                                Primero inscríbete en una maestría
                            `;
                        }
                    }
                }
            }
        })
        .catch(error => console.error('Error en verificación periódica:', error));
}

// Limpiar intervalo cuando la página se descarga
window.addEventListener('beforeunload', function() {
    if (intervaloVerificacion) {
        clearInterval(intervaloVerificacion);
    }
});
</script>