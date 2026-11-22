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
