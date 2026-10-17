document.addEventListener('DOMContentLoaded', function() {
    const contenedorMapa = document.getElementById('mapa-registro');

    // Si estamos en la página de registro y el contenedor existe, arrancamos el mapa
    if (contenedorMapa) {
        // Vaciamos el texto temporal "(Acá irá el mapa interactivo)"
        contenedorMapa.innerHTML = '';

        // Inicializamos el mapa centrado en Argentina (Latitud, Longitud) y nivel de zoom (4)
        const map = L.map('mapa-registro').setView([-34.6037, -58.3816], 4);

        // Cargamos los "azulejos" (el dibujo del mapa) desde OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        let marcador = null;

        // Evento: ¿Qué pasa cuando el usuario hace clic en el mapa?
        map.on('click', function(evento) {
            const latitud = evento.latlng.lat;
            const longitud = evento.latlng.lng;

            // Si ya había un marcador, lo movemos. Si no, creamos uno nuevo.
            if (marcador) {
                marcador.setLatLng(evento.latlng);
            } else {
                marcador = L.marker(evento.latlng).addTo(map);
            }

            // Llamamos a la API gratuita de Nominatim para traducir coordenadas a texto (Reverse Geocoding)
            const urlApi = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitud}&lon=${longitud}`;

            fetch(urlApi)
                .then(respuesta => respuesta.json())
                .then(datos => {
                    // Buscamos los inputs donde vamos a escribir
                    const inputPais = document.getElementById('pais');
                    const inputCiudad = document.getElementById('ciudad');

                    // Nominatim a veces devuelve 'city', otras 'town', o 'village' dependiendo del tamaño del lugar
                    const direccion = datos.address;
                    if (direccion) {
                        const pais = direccion.country || '';
                        const ciudad = direccion.city || direccion.town || direccion.village || direccion.state || '';

                        // Autocompletamos los campos readonly del formulario
                        inputPais.value = pais;
                        inputCiudad.value = ciudad;
                    } else {
                        // Si hace clic en el medio del océano
                        inputPais.value = 'Desconocido';
                        inputCiudad.value = 'Desconocida';
                    }
                })
                .catch(error => {
                    console.error("Hubo un problema obteniendo la ubicación: ", error);
                });
        });
    }
});