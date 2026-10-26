<?php
// Scripts específicos para el Registro
if (strtolower($view) === 'registro'):
    ?>
    <script src="<?= BASE_URL ?>/public/js/Validaciones.js"></script>
    <script src="<?= BASE_URL ?>/public/js/mapa.js"></script>
<?php endif; ?>

<?php
// Scripts específicos para el Perfil
if (strtolower($view) === 'perfil'):
    ?>
    <script src="<?= BASE_URL ?>/public/js/perfil.js"></script>
<?php endif; ?>

<!-- Script global de la barra de navegación -->
<script src="<?= BASE_URL ?>/public/js/header.js"></script>

</body>
</html>