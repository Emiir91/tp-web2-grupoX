document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modal-reporte');
    const botonesReportar = document.querySelectorAll('.btn-reportar');
    const btnCancelar = document.getElementById('btn-cancelar-reporte');
    const btnEnviar = document.getElementById('btn-enviar-reporte');
    const motivoInput = document.getElementById('reporte-motivo');
    const tipoSelect = document.getElementById('reporte-tipo');
    const errorReporte = document.getElementById('reporte-error');
    const confirmacion = document.getElementById('reporte-confirmacion');
    const textoPregunta = document.getElementById('reporte-texto-pregunta');

    let idPreguntaActual = null;

    botonesReportar.forEach(function (boton) {
        boton.addEventListener('click', function () {
            idPreguntaActual = this.dataset.idPregunta;
            textoPregunta.textContent = this.dataset.texto;
            motivoInput.value = '';
            tipoSelect.value = 'respuesta_incorrecta';
            errorReporte.classList.add('confirmacion-oculta');
            confirmacion.classList.add('confirmacion-oculta');
            modal.classList.remove('modal-oculto');
        });
    });

    tipoSelect.addEventListener('change', function () {
        motivoInput.placeholder = tipoSelect.value === 'otro'
            ? 'Describí el problema con esta pregunta...'
            : 'Contanos más (opcional)...';
        errorReporte.classList.add('confirmacion-oculta');
    });

    btnCancelar.addEventListener('click', function () {
        modal.classList.add('modal-oculto');
    });

    btnEnviar.addEventListener('click', function () {
        const motivo = motivoInput.value.trim();
        const tipo = tipoSelect.value;

        if (!idPreguntaActual) {
            return;
        }

        // Si elige "Otro", tiene que escribir el detalle
        if (tipo === 'otro' && !motivo) {
            errorReporte.classList.remove('confirmacion-oculta');
            return;
        }
        errorReporte.classList.add('confirmacion-oculta');

        btnEnviar.disabled = true;

        fetch(BASE_URL + '/resultados/reportarPregunta', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_pregunta: idPreguntaActual,
                tipo: tipo,
                motivo: motivo
            })
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                confirmacion.classList.remove('confirmacion-oculta');
                setTimeout(function () {
                    modal.classList.add('modal-oculto');
                }, 1500);
            }
        })
        .catch(function () {
            alert('No se pudo enviar el reporte. Intentá de nuevo.');
        })
        .finally(function () {
            btnEnviar.disabled = false;
        });
    });
});

// Ventanita "Cambiar modo": se abre desde el link de abajo de "Nueva partida"
document.addEventListener('DOMContentLoaded', function () {
    const modalModo = document.getElementById('modal-modo');
    const btnAbrir = document.getElementById('btn-cambiar-modo');
    const btnCancelar = document.getElementById('btn-cancelar-modo');
    if (!modalModo || !btnAbrir) return;

    btnAbrir.addEventListener('click', function () {
        modalModo.classList.remove('modal-oculto');
    });

    btnCancelar.addEventListener('click', function () {
        modalModo.classList.add('modal-oculto');
    });

    // Elegir un modo: queda marcado, se actualiza "Modo: ..." y se cierra la ventanita.
    // La partida arranca recién cuando el usuario toca "Nueva partida".
    const inputModo = document.getElementById('modo-nueva-partida');
    const nombreModo = document.getElementById('nombre-modo-elegido');
    const opciones = modalModo.querySelectorAll('.opcion-modo');

    opciones.forEach(function (opcion) {
        opcion.addEventListener('click', function () {
            opciones.forEach(function (o) { o.classList.remove('activo'); });
            opcion.classList.add('activo');
            inputModo.value = opcion.dataset.modo;
            nombreModo.textContent = opcion.textContent.trim();
            modalModo.classList.add('modal-oculto');
        });
    });

    // Tocar afuera de la caja también la cierra
    modalModo.addEventListener('click', function (e) {
        if (e.target === modalModo) {
            modalModo.classList.add('modal-oculto');
        }
    });
});

// Final de partida: el puntaje cuenta de 0 al valor real y suena la fanfarria o el bajón
document.addEventListener('DOMContentLoaded', function () {
    const puntaje = document.getElementById('puntaje-final');
    if (puntaje) {
        const final = parseInt(puntaje.dataset.puntaje, 10) || 0;
        const duracion = 1000;
        const inicio = performance.now();
        puntaje.textContent = '0';

        function contar(ahora) {
            const avance = Math.min((ahora - inicio) / duracion, 1);
            // Arranca rápido y frena al final, como un marcador
            const suavizado = 1 - Math.pow(1 - avance, 3);
            puntaje.textContent = Math.round(final * suavizado);
            if (avance < 1) {
                requestAnimationFrame(contar);
            }
        }
        requestAnimationFrame(contar);
    }

    const pantalla = document.querySelector('.pantalla-resultados');
    const sonido = pantalla ? pantalla.dataset.sonidoFinal : '';
    if (typeof Sonido !== 'undefined') {
        if (sonido === 'victoria') Sonido.victoria();
        if (sonido === 'derrota') Sonido.derrota();
    }
});
