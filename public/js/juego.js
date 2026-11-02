window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
        window.location.reload();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const mainElement = document.querySelector('.pantalla-juego');
    const tiempoInicial = parseInt(mainElement.getAttribute('data-tiempo'));

    let tiempoRestante = tiempoInicial;
    const temporizadorElement = document.getElementById('temporizador');
    const formTimeout = document.getElementById('formTimeout');

    const intervalo = setInterval(function () {
        tiempoRestante--;
        temporizadorElement.textContent = tiempoRestante;

        if (tiempoRestante <= 0) {
            clearInterval(intervalo);
            formTimeout.submit();
        }
    }, 1000);

    let salidaLegitima = false;

    document.querySelectorAll('form').forEach(formulario => {
        formulario.addEventListener('submit', function () {
            salidaLegitima = true;
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
