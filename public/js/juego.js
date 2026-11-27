window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
        window.location.reload();
    }
});

// El objeto Sonido está en sonido.js (se carga antes que este archivo)

function prepararBotonSonido() {
    const boton = document.getElementById('btn-sonido');
    if (!boton) return;

    function actualizar() {
        const silenciado = Sonido.estaSilenciado();
        boton.textContent = silenciado ? '🔇' : '🔊';
        boton.title = silenciado ? 'Activar sonido' : 'Silenciar sonido';
        boton.setAttribute('aria-label', boton.title);
        boton.classList.toggle('silenciado', silenciado);
    }

    boton.addEventListener('click', function () {
        Sonido.alternar();
        Sonido.desbloquear();
        actualizar();
    });
    actualizar();

    // Si el navegador bloqueó el audio al cargar, se habilita con el primer toque o tecla
    ['pointerdown', 'keydown'].forEach(function (evento) {
        document.addEventListener(evento, Sonido.desbloquear, { once: true });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const mainElement = document.querySelector('.pantalla-juego');

    prepararBotonSonido();

    // Pantalla de feedback (después de responder): se ve un momento y sigue sola.
    // No tiene temporizador ni respuestas para elegir.
    if (mainElement.dataset.feedback) {
        if (mainElement.dataset.acerto === '1') {
            Sonido.acierto();
        } else {
            Sonido.error();
        }
        setTimeout(function () {
            window.location.replace('mostrar');
        }, 1600);
        return;
    }
    const tiempoInicial = parseInt(mainElement.getAttribute('data-tiempo'));

    let tiempoRestante = tiempoInicial;
    const temporizadorElement = document.getElementById('temporizador');
    // Si se recarga con poco tiempo, ya arranca en alerta
    temporizadorElement.classList.toggle('alerta', tiempoRestante <= 3);
    const formTimeout = document.getElementById('formTimeout');

    const intervalo = setInterval(function () {
        tiempoRestante--;
        temporizadorElement.textContent = tiempoRestante;

        if (tiempoRestante > 0) {
            Sonido.tic(tiempoRestante <= 3);
        }
        temporizadorElement.classList.toggle('alerta', tiempoRestante <= 3);

        if (tiempoRestante <= 0) {
            clearInterval(intervalo);
            salidaLegitima = true;
            formTimeout.submit();
        }
    }, 1000);

    let salidaLegitima = false;

    document.querySelectorAll('form').forEach(formulario => {
        formulario.addEventListener('submit', function () {
            salidaLegitima = true;
        });
    });

    // Al elegir una respuesta: se frena el temporizador y se bloquean los botones.
    // Así no se manda también el "tiempo agotado" ni se puede responder dos veces.
    let yaRespondio = false;
    document.querySelectorAll('.form-respuesta').forEach(formulario => {
        formulario.addEventListener('submit', function (e) {
            if (yaRespondio) {
                e.preventDefault();
                return;
            }
            yaRespondio = true;
            clearInterval(intervalo);
            document.querySelectorAll('.btn-respuesta').forEach(boton => boton.disabled = true);
        });
    });

    history.pushState(null, null, location.href);

    const modalAbandono = document.getElementById('modal-abandonar');
    const btnConfirmar = document.getElementById('btn-confirmar-abandono');
    const btnCancelar = document.getElementById('btn-cancelar-abandono');

    window.addEventListener('popstate', function (event) {
        if (!salidaLegitima) {
            modalAbandono.classList.remove('modal-oculto');
        }
    });

    if (btnCancelar) {
        btnCancelar.addEventListener('click', function () {
            modalAbandono.classList.add('modal-oculto');
            history.pushState(null, null, location.href);
        });
    }

    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', function () {
            salidaLegitima = true;
            clearInterval(intervalo);
            btnConfirmar.disabled = true;
            // Suena el "apagón" y después se va (si está silenciado, se va enseguida)
            Sonido.abandonar();
            setTimeout(function () {
                window.location.href = "abandonar";
            }, Sonido.estaSilenciado() ? 0 : 650);
        });
    }

    window.addEventListener('beforeunload', function (e) {
        if (!salidaLegitima) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'F5' || (e.ctrlKey && e.key.toLowerCase() === 'r') || (e.metaKey && e.key.toLowerCase() === 'r')) {
            e.preventDefault();
        }
    });

});


