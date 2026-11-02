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