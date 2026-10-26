document.addEventListener('DOMContentLoaded', function() {
    const contenedorMapa = document.getElementById('mapa-perfil');

    if (contenedorMapa) {
        contenedorMapa.innerHTML = '';

        // Leemos los datos de país y ciudad inyectados desde el Mustache
        const pais = contenedorMapa.getAttribute('data-pais');
        const ciudad = contenedorMapa.getAttribute('data-ciudad');

        if (pais && ciudad && pais.trim() !== '' && ciudad.trim() !== 'Desconocido') {
            // Buscamos las coordenadas exactas de la ciudad y país guardados
            const busqueda = encodeURIComponent(`${ciudad}, ${pais}`);
            const urlApi = `https://nominatim.openstreetmap.org/search?format=json&q=${busqueda}&limit=1`;

            fetch(urlApi)
                .then(respuesta => respuesta.json())
                .then(datos => {
                    if (datos && datos.length > 0) {
                        const lat = parseFloat(datos[0].lat);
                        const lng = parseFloat(datos[0].lon);

                        // Inicializamos Leaflet igual que en el registro
                        const map = L.map('mapa-perfil').setView([lat, lng], 13);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '© OpenStreetMap'
                        }).addTo(map);

                        // Añadimos el marcador en la ubicación del usuario
                        L.marker([lat, lng]).addTo(map)
                            .bindPopup(`<b>${ciudad}, ${pais}</b>`)
                            .openPopup();

                        // Aseguramos que el mapa recalcule su tamaño dentro del panel
                        setTimeout(() => {
                            map.invalidateSize();
                        }, 200);

                    } else {
                        contenedorMapa.innerHTML = '<p style="text-align:center; padding-top:100px; color:#999; font-size:0.9rem;">No se encontró la ubicación en el mapa.</p>';
                    }
                })
                .catch(error => {
                    console.error("Error al consultar la API de mapas: ", error);
                });
        } else {
            contenedorMapa.innerHTML = '<p style="text-align:center; padding-top:100px; color:#999; font-size:0.9rem;">Ubicación no disponible.</p>';
        }
    }
});