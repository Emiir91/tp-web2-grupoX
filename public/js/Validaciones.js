document.addEventListener('DOMContentLoaded', function() {
    // Capturamos los elementos del HTML a través de sus IDs
    const formRegistro = document.getElementById('form-registro');
    const contrasenia = document.getElementById('contrasenia');
    const repetirContrasenia = document.getElementById('repetir_contrasenia');
    const errorContainer = document.getElementById('error-contrasenia');

    // Nos aseguramos de que el formulario exista en esta página antes de hacer nada
    if (formRegistro) {
        formRegistro.addEventListener('submit', function(evento) {

            // Comparamos lo que el usuario escribió en ambos campos
            if (contrasenia.value !== repetirContrasenia.value) {

                // ESTA ES LA MAGIA: evitamos que el formulario viaje al servidor y quedarte la página
                evento.preventDefault();

                // Inyectamos el texto de error y lo hacemos visible
                errorContainer.textContent = "Las contraseñas no coinciden. Por favor, verificalas.";
                errorContainer.style.display = 'block';

                // Le vaciamos el campo de repetir contraseña y le ponemos el cursor ahí
                repetirContrasenia.value = '';
                repetirContrasenia.focus();

            } else {
                // Si coinciden, ocultamos el error (por si estaba visible de un intento anterior)
                // y dejamos que el formulario siga su curso natural hacia el Controlador PHP
                errorContainer.style.display = 'none';
            }
        });
    }
});