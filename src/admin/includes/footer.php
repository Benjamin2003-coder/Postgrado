<?php
// src/admin/includes/footer.php
?>
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-copyright">
                &copy; <?php echo date('Y'); ?> UNEFA Núcleo Nueva Esparta - Dirección de Investigación y Posgrado
            </div>
            <div class="footer-links">
                <a href="#">Términos y condiciones</a>
                <a href="#">Política de privacidad</a>
                <a href="/POSGRADO/index.html">Volver al inicio</a>
            </div>
            <div class="footer-version">
                v2.0.0 Administración
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
                const random = Math.floor(Math.random() * 8);
                badge.textContent = random;
            }
        }, 45000);
    </script>
</body>
</html>