/**
 * NAVIGATION.JS - Sistema de navegación y rutas
 * UNEFA Postgrado - Nueva Esparta
 */

// Configuración de rutas
const routes = {
    public: {
        home: '/',
        programas: '/programas',
        profesores: '/profesores',
        investigacion: '/investigacion',
        contacto: '/contacto'
    },
    auth: {
        login: '/auth/login.html',
        register: '/auth/register.html',
        forgot: '/auth/forgot-password.html'
    },
    user: {
        dashboard: '/src/user/dashboard.html',
        perfil: '/src/user/perfil.html',
        cursos: '/src/user/mis-cursos.html',
        mensajes: '/src/user/mensajes.html'
    },
    admin: {
        dashboard: '/src/admin/dashboard.html',
        usuarios: '/src/admin/usuarios.html',
        profesores: '/src/admin/profesores.html',
        cursos: '/src/admin/cursos.html',
        config: '/src/admin/configuracion.html'
    }
};

// Clase de navegación
class Navigation {
    constructor() {
        this.currentPath = window.location.pathname;
        this.userRole = this.getUserRole();
        this.init();
    }
    
    init() {
        this.checkAuth();
        this.setupEventListeners();
        this.updateUIForUser();
        this.loadUserData();
    }
    
    // Obtener rol del usuario del localStorage
    getUserRole() {
        const user = JSON.parse(localStorage.getItem('unefa_user')) || null;
        return user ? user.rol : null;
    }
    
    // Verificar autenticación
    checkAuth() {
        const publicPaths = Object.values(routes.public);
        const authPaths = Object.values(routes.auth);
        
        // Si está en ruta de admin y no es admin
        if (this.currentPath.includes('/src/admin/') && this.userRole !== 'admin') {
            this.redirect('/auth/login.html');
        }
        
        // Si está en ruta de usuario y no está autenticado
        if (this.currentPath.includes('/src/user/') && !this.userRole) {
            this.redirect('/auth/login.html');
        }
    }
    
    // Redirigir a otra página
    redirect(path) {
        window.location.href = path;
    }
    
    // Configurar event listeners
    setupEventListeners() {
        // Botones de logout
        document.querySelectorAll('.logout-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.logout();
            });
        });
        
        // Navegación por teclado
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'h') {
                e.preventDefault();
                this.goToHome();
            }
        });
        
        // Breadcrumb dinámico
        this.updateBreadcrumb();
    }
    
    // Actualizar UI según el rol
    updateUIForUser() {
        if (this.userRole) {
            // Ocultar botones de auth
            document.querySelectorAll('.auth-buttons .btn-outline, .auth-buttons .btn-primary')
                .forEach(btn => btn.style.display = 'none');
            
            // Mostrar menú de usuario
            const userMenu = this.createUserMenu();
            const authButtons = document.querySelector('.auth-buttons');
            if (authButtons) {
                authButtons.innerHTML = '';
                authButtons.appendChild(userMenu);
            }
        }
    }
    
    // Crear menú de usuario
    createUserMenu() {
        const user = JSON.parse(localStorage.getItem('unefa_user')) || {};
        
        const menu = document.createElement('div');
        menu.className = 'user-menu';
        
        menu.innerHTML = `
            <button class="user-menu-btn">
                <img src="${user.foto || '/public/images/default-avatar.png'}" alt="Avatar">
                <span>${user.nombre || 'Usuario'}</span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="user-dropdown">
                <a href="/src/user/perfil.html">
                    <i class="fas fa-user"></i> Mi Perfil
                </a>
                <a href="/src/user/mis-cursos.html">
                    <i class="fas fa-book"></i> Mis Cursos
                </a>
                <a href="/src/user/mensajes.html">
                    <i class="fas fa-envelope"></i> Mensajes
                    <span class="badge" id="mensajes-no-leidos">0</span>
                </a>
                ${this.userRole === 'admin' ? `
                <div class="dropdown-divider"></div>
                <a href="/src/admin/dashboard.html">
                    <i class="fas fa-cog"></i> Admin Panel
                </a>
                ` : ''}
                <div class="dropdown-divider"></div>
                <a href="#" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        `;
        
        // Estilos del menú
        this.addUserMenuStyles();
        
        // Event listeners
        setTimeout(() => {
            const btn = menu.querySelector('.user-menu-btn');
            const dropdown = menu.querySelector('.user-dropdown');
            
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('show');
            });
            
            document.addEventListener('click', () => {
                dropdown.classList.remove('show');
            });
        }, 100);
        
        return menu;
    }
    
    // Agregar estilos del menú de usuario
    addUserMenuStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .user-menu {
                position: relative;
            }
            
            .user-menu-btn {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 5px 15px;
                background: var(--primary-color);
                color: white;
                border: none;
                border-radius: 30px;
                cursor: pointer;
                font-size: 14px;
            }
            
            .user-menu-btn img {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                object-fit: cover;
            }
            
            .user-dropdown {
                position: absolute;
                top: 100%;
                right: 0;
                width: 250px;
                background: white;
                border-radius: 5px;
                box-shadow: var(--shadow-lg);
                opacity: 0;
                visibility: hidden;
                transform: translateY(10px);
                transition: all 0.3s ease;
                z-index: 1000;
            }
            
            .user-dropdown.show {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }
            
            .user-dropdown a {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 12px 20px;
                color: var(--dark-color);
                text-decoration: none;
                transition: var(--transition);
            }
            
            .user-dropdown a:hover {
                background: var(--light-color);
                color: var(--primary-color);
            }
            
            .user-dropdown i {
                width: 20px;
                color: var(--gray-color);
            }
            
            .dropdown-divider {
                height: 1px;
                background: #e1e1e1;
                margin: 5px 0;
            }
            
            .badge {
                background: var(--primary-color);
                color: white;
                padding: 2px 6px;
                border-radius: 10px;
                font-size: 11px;
                margin-left: auto;
            }
        `;
        
        document.head.appendChild(style);
    }
    
    // Cerrar sesión
    logout() {
        localStorage.removeItem('unefa_user');
        localStorage.removeItem('unefa_token');
        
        // Mostrar mensaje
        showNotification('Sesión cerrada correctamente', 'success');
        
        // Redirigir al home
        setTimeout(() => {
            window.location.href = '/';
        }, 1500);
    }
    
    // Ir al home
    goToHome() {
        window.location.href = '/';
    }
    
    // Actualizar breadcrumb
    updateBreadcrumb() {
        const breadcrumb = document.querySelector('.breadcrumb');
        if (!breadcrumb) return;
        
        const pathParts = this.currentPath.split('/').filter(p => p);
        let html = '<a href="/">Inicio</a>';
        
        pathParts.forEach((part, index) => {
            if (part.includes('.html')) {
                const name = part.replace('.html', '').replace(/-/g, ' ');
                html += ` <span class="separator">/</span> <span>${name}</span>`;
            }
        });
        
        breadcrumb.innerHTML = html;
    }
    
    // Cargar datos del usuario
    loadUserData() {
        if (this.userRole) {
            // Simular carga de mensajes no leídos
            setInterval(() => {
                const random = Math.floor(Math.random() * 5);
                const badge = document.getElementById('mensajes-no-leidos');
                if (badge) {
                    badge.textContent = random;
                    badge.style.display = random > 0 ? 'inline' : 'none';
                }
            }, 10000);
        }
    }
}

// Inicializar navegación
document.addEventListener('DOMContentLoaded', () => {
    window.navigation = new Navigation();
});

// Función para proteger rutas
function protectRoute(allowedRoles = []) {
    const user = JSON.parse(localStorage.getItem('unefa_user')) || null;
    
    if (!user) {
        window.location.href = '/auth/login.html';
        return false;
    }
    
    if (allowedRoles.length > 0 && !allowedRoles.includes(user.rol)) {
        window.location.href = '/';
        showNotification('No tienes permiso para acceder a esta página', 'error');
        return false;
    }
    
    return true;
}

// Exportar funciones
window.protectRoute = protectRoute;
window.routes = routes;