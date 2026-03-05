/**
 * MAIN.JS - Funcionalidades principales
 * UNEFA Postgrado - Nueva Esparta
 */

// Esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Página de Postgrado UNEFA cargada');
    
    // Inicializar todas las funcionalidades
    initNavigation();
    initScrollEffects();
    initSmoothScroll();
    initCounters();
    initFormValidation();
    initAnimations();
    initProfesoresCards();
});

/**
 * Navegación y menú responsive
 */
function initNavigation() {
    const header = document.querySelector('.header');
    const navMenu = document.querySelector('.nav-menu');
    const mobileMenuBtn = createMobileMenuButton();
    
    // Agregar botón de menú móvil
    if (window.innerWidth <= 768) {
        header.querySelector('.container').appendChild(mobileMenuBtn);
    }
    
    // Toggle menú móvil
    mobileMenuBtn.addEventListener('click', function() {
        navMenu.classList.toggle('show');
        this.classList.toggle('active');
    });
    
    // Marcar enlace activo según la sección visible
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-menu a');
    
    window.addEventListener('scroll', function() {
        let current = '';
        const scrollY = window.scrollY;
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop - 100;
            const sectionHeight = section.clientHeight;
            
            if (scrollY >= sectionTop && scrollY < sectionTop + sectionHeight) {
                current = section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').includes(current)) {
                link.classList.add('active');
            }
        });
    });
}

/**
 * Crear botón de menú móvil
 */
function createMobileMenuButton() {
    const btn = document.createElement('button');
    btn.className = 'mobile-menu-btn';
    btn.innerHTML = `
        <span></span>
        <span></span>
        <span></span>
    `;
    
    // Estilos dinámicos
    const style = document.createElement('style');
    style.textContent = `
        .mobile-menu-btn {
            display: none;
            flex-direction: column;
            justify-content: space-around;
            width: 30px;
            height: 30px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0;
            z-index: 1001;
        }
        
        .mobile-menu-btn span {
            width: 30px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 10px;
            transition: all 0.3s linear;
        }
        
        .mobile-menu-btn.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        
        .mobile-menu-btn.active span:nth-child(2) {
            opacity: 0;
        }
        
        .mobile-menu-btn.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }
        
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: flex;
            }
            
            .nav-menu {
                position: fixed;
                top: 80px;
                left: -100%;
                width: 100%;
                height: calc(100vh - 80px);
                background: white;
                flex-direction: column;
                transition: 0.3s;
                box-shadow: var(--shadow-lg);
            }
            
            .nav-menu.show {
                left: 0;
            }
            
            .nav-menu ul {
                flex-direction: column;
                padding: 40px;
            }
        }
    `;
    
    document.head.appendChild(style);
    return btn;
}

/**
 * Efectos de scroll
 */
function initScrollEffects() {
    const header = document.querySelector('.header');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
}

/**
 * Smooth scroll para enlaces internos
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Contadores animados
 */
function initCounters() {
    const counters = document.querySelectorAll('.stat-item h3');
    const speed = 200;
    
    const animateCounter = (counter) => {
        const target = parseInt(counter.innerText.replace('+', ''));
        let count = 0;
        
        const updateCount = () => {
            const increment = target / speed;
            
            if (count < target) {
                count += increment;
                counter.innerText = Math.ceil(count) + '+';
                setTimeout(updateCount, 10);
            } else {
                counter.innerText = target + '+';
            }
        };
        
        updateCount();
    };
    
    // Usar Intersection Observer para iniciar animación cuando sea visible
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    counters.forEach(counter => observer.observe(counter));
}

/**
 * Validación de formularios
 */
function initFormValidation() {
    const contactForm = document.querySelector('.contacto-form form');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Obtener datos del formulario
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            // Validar campos
            let isValid = true;
            const errors = [];
            
            if (!data.nombre || data.nombre.length < 3) {
                isValid = false;
                errors.push('El nombre debe tener al menos 3 caracteres');
            }
            
            if (!data.email || !isValidEmail(data.email)) {
                isValid = false;
                errors.push('Ingresa un email válido');
            }
            
            if (!data.mensaje || data.mensaje.length < 10) {
                isValid = false;
                errors.push('El mensaje debe tener al menos 10 caracteres');
            }
            
            if (isValid) {
                // Enviar formulario (simulado)
                showNotification('Mensaje enviado correctamente', 'success');
                this.reset();
            } else {
                showNotification(errors.join('<br>'), 'error');
            }
        });
    }
}

/**
 * Validar email
 */
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Mostrar notificación
 */
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = message;
    
    // Estilos
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        padding: 15px 25px;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        color: white;
        border-radius: 5px;
        box-shadow: var(--shadow-lg);
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Remover después de 5 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

/**
 * Animaciones al hacer scroll
 */
function initAnimations() {
    const animatedElements = document.querySelectorAll(
        '.programa-card, .profesor-card, .linea-card, .stat-item'
    );
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });
    
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });
}

/**
 * Interacción con tarjetas de profesores
 */
function initProfesoresCards() {
    const profesorCards = document.querySelectorAll('.profesor-card');
    
    profesorCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}