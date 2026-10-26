<?php
// Los scripts de validación y mapa solo son necesarios en el registro
if (strtolower($view) === 'registro'):
    ?>
    <script src="<?= BASE_URL ?>/public/js/Validaciones.js"></script>
    <script src="<?= BASE_URL ?>/public/js/mapa.js"></script>
<?php endif; ?>

<!-- Script global de la barra de navegación -->
<script src="<?= BASE_URL ?>/public/js/header.js"></script>

</body>
</html>