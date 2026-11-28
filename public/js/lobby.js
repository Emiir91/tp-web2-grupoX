const formularioSorteo = document.getElementById('form-sorteo-categoria');
const resultadoSorteo = document.getElementById('resultado-sorteo');
const botonTematico = document.querySelector(
    '.botones-iniciar button[name="alcance"][value="categoria"]'
);

if (formularioSorteo && resultadoSorteo) {
    const clave = 'lobby.sorteoPendiente';
    const animar = sessionStorage.getItem(clave) === '1';
    sessionStorage.removeItem(clave);

    formularioSorteo.addEventListener('submit', () => {
        sessionStorage.setItem(clave, '1');
    });

    const categoriaFinal = resultadoSorteo.textContent.trim();
    const categorias = Array.from(
        document.querySelectorAll('#categorias-animacion span'),
        elemento => elemento.textContent.trim()
    ).filter(nombre => nombre && nombre !== categoriaFinal);

    const movimientoReducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (animar && categoriaFinal && categorias.length && !movimientoReducido) {
        const inicio = Math.floor(Math.random() * categorias.length);
        let paso = 0;

        resultadoSorteo.textContent = categorias[inicio];
        resultadoSorteo.classList.remove('sorteo-pendiente');

        const intervalo = setInterval(() => {
            paso++;

            if (paso === 8) {
                clearInterval(intervalo);
                resultadoSorteo.textContent = categoriaFinal;
                botonTematico?.classList.remove('sorteo-pendiente');
                return;
            }

            resultadoSorteo.textContent = categorias[(inicio + paso) % categorias.length];
        }, 100);
    } else {
        resultadoSorteo.classList.remove('sorteo-pendiente');
        botonTematico?.classList.remove('sorteo-pendiente');
    }
}