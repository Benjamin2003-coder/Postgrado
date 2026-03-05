<?php
// src/user/includes/footer.php
?>
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-copyright">
                &copy; <?php echo date('Y'); ?> UNEFA Núcleo Nueva Esparta - Dirección de Investigación y Postgrado
            </div>
            <div class="footer-links">
                <a href="#">Términos y condiciones</a>
                <a href="#">Política de privacidad</a>
                <a href="/posgrado/index.html">Volver al inicio</a>
            </div>
            <div class="footer-version">
                v1.0.0
            </div>
        </div>
    </footer>

    <script>
        // Actualizar título de página según la sección
        function updatePageTitle(title) {
            const pageTitle = document.getElementById('pageTitle');
            if (pageTitle) {
                pageTitle.textContent = title;
            }
        }

        // Notificaciones simuladas
        setInterval(() => {
            const badge = document.querySelector('.badge-count');
            if (badge) {
                const random = Math.floor(Math.random() * 5);
                badge.textContent = random;
            }
        }, 30000);

        // Efecto de hover en cards
        document.querySelectorAll('.info-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>