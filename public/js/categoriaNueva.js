// Muestra los campos de "categoría nueva" solo cuando en el desplegable se elige la opción "__nueva__".
// Lo usan Sugerir pregunta (jugador) y Agregar pregunta (editor).
document.addEventListener('DOMContentLoaded', function () {
    const select = document.querySelector('.select-categoria');
    const campos = document.querySelector('.campos-categoria-nueva');

    if (!select || !campos) {
        return;
    }

    const inputNombre = campos.querySelector('input[name="categoria_nueva"]');

    function actualizar() {
        const esNueva = select.value === '__nueva__';
        campos.hidden = !esNueva;
        if (inputNombre) {
            inputNombre.required = esNueva;
            if (esNueva) {
                inputNombre.focus();
            }
        }
    }

    select.addEventListener('change', actualizar);
    actualizar();
});
