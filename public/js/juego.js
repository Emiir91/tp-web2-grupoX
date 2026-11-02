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

    window.addEventListener('beforeunload', function (e) {
        e.preventDefault();
        e.returnValue = ''; 
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'F5' || (e.ctrlKey && e.key.toLowerCase() === 'r') || (e.metaKey && e.key.toLowerCase() === 'r')) {
            e.preventDefault();
        }
    });

});
