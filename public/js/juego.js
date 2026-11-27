window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
        window.location.reload();
    }
});

// ===================== Sonido (grilla 7.2) =====================
// Los sonidos se generan con la Web Audio API del navegador: no hace falta ningún archivo de audio.
// El silencio se guarda en localStorage para que se mantenga entre preguntas y partidas.
const Sonido = (function () {
    const CLAVE = 'cortocircuito_silencio';
    let silenciado = false;
    try {
        silenciado = localStorage.getItem(CLAVE) === '1';
    } catch (e) { /* sin localStorage: arranca con sonido */ }

    let contexto = null;

    function obtenerContexto() {
        if (!contexto) {
            const ClaseAudio = window.AudioContext || window.webkitAudioContext;
            if (!ClaseAudio) return null;
            contexto = new ClaseAudio();
        }
        // El navegador puede dejarlo "suspendido" hasta que el usuario toque la página
        if (contexto.state === 'suspended') {
            contexto.resume();
        }
        return contexto;
    }

    // Un tono corto: frecuencia (Hz), duración (s), forma de onda, volumen y demora (s)
    function tono(frecuencia, duracion, tipo, volumen, demora) {
        if (silenciado) return;
        const ctx = obtenerContexto();
        if (!ctx) return;

        const inicio = ctx.currentTime + (demora || 0);
        const oscilador = ctx.createOscillator();
        const ganancia = ctx.createGain();

        oscilador.type = tipo;
        oscilador.frequency.setValueAtTime(frecuencia, inicio);
        ganancia.gain.setValueAtTime(volumen, inicio);
        ganancia.gain.exponentialRampToValueAtTime(0.001, inicio + duracion);

        oscilador.connect(ganancia);
        ganancia.connect(ctx.destination);
        oscilador.start(inicio);
        oscilador.stop(inicio + duracion);
    }

    let esTic = true;

    return {
        // Tic-tac del temporizador; en los últimos segundos suena más agudo
        tic: function (urgente) {
            if (urgente) {
                tono(1400, 0.06, 'square', 0.10);
            } else {
                tono(esTic ? 950 : 750, 0.04, 'square', 0.05);
                esTic = !esTic;
            }
        },
        acierto: function () {
            tono(880, 0.12, 'sine', 0.2);
            tono(1320, 0.25, 'sine', 0.2, 0.1);
        },
        error: function () {
            tono(180, 0.35, 'sawtooth', 0.12);
            tono(130, 0.4, 'sawtooth', 0.10, 0.08);
        },
        estaSilenciado: function () {
            return silenciado;
        },
        alternar: function () {
            silenciado = !silenciado;
            try {
                localStorage.setItem(CLAVE, silenciado ? '1' : '0');
            } catch (e) { /* no se pudo guardar: vale solo para esta página */ }
            return silenciado;
        },
        desbloquear: function () {
            if (!silenciado) obtenerContexto();
        }
    };
})();

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
    const formTimeout = document.getElementById('formTimeout');

    const intervalo = setInterval(function () {
        tiempoRestante--;
        temporizadorElement.textContent = tiempoRestante;

        if (tiempoRestante > 0) {
            Sonido.tic(tiempoRestante <= 3);
        }

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
            window.location.href = "abandonar";
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


