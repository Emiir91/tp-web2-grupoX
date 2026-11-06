document.addEventListener("DOMContentLoaded", function() {
    const btnPerfil = document.getElementById("btn-perfil");
    const menuDesplegable = document.getElementById("menu-desplegable");

    if (btnPerfil) {
        btnPerfil.addEventListener("click", function(e) {
            e.stopPropagation(); // Evita que el clic cierre el menú inmediatamente
            menuDesplegable.classList.toggle("mostrar");
        });

        // Cierra el menú si hacés clic en cualquier otra parte de la pantalla
        document.addEventListener("click", function(e) {
            if (!menuDesplegable.contains(e.target)) {
                menuDesplegable.classList.remove("mostrar");
            }
        });
    }
});

document.addEventListener("DOMContentLoaded", function() {
    const botonesModo = document.querySelectorAll(".btn-modo");
    const inputModoOculto = document.getElementById("input_modo_juego");

    if (!botonesModo.length || !inputModoOculto) return;

    botonesModo.forEach(boton => {
        boton.addEventListener("click", function() {
            // Quitar la clase activo de todos los botones
            botonesModo.forEach(b => b.classList.remove("activo"));

            // Agregar la clase activo al botón presionado
            this.classList.add("activo");

            // Asignar el valor del atributo data-modo al input oculto del formulario
            inputModoOculto.value = this.getAttribute("data-modo");
        });
    });
});