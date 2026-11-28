const formularioSorteo = document.getElementById('form-sorteo-categoria');
const resultadoSorteo = document.getElementById('resultado-sorteo');
const botonTematico = document.getElementById('btn-jugar-categoria');

if (formularioSorteo && resultadoSorteo && botonTematico) {
    const clave = 'lobby.sorteoPendiente';
    const animar = sessionStorage.getItem(clave) === '1';
    sessionStorage.removeItem(clave);

    const categoriaFinal = resultadoSorteo.textContent.trim();
    const categorias = Array.from(
        document.querySelectorAll('#categorias-animacion span'),
        elemento => elemento.textContent.trim()
    ).filter(nombre => nombre && nombre !== categoriaFinal);

    const movimientoReducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let intervalo;

    function mostrarResultado() {
        resultadoSorteo.textContent = categoriaFinal;
        resultadoSorteo.classList.remove('sorteo-pendiente');

        if (categoriaFinal) {
            botonTematico.textContent = `Jugar ${categoriaFinal}`;
            botonTematico.disabled = false;
        }
    }

    formularioSorteo.addEventListener('submit', () => {
        clearInterval(intervalo);
        sessionStorage.setItem(clave, '1');
        botonTematico.textContent = 'Jugar categoría';
        botonTematico.disabled = true;
    });

    if (animar && categoriaFinal && categorias.length && !movimientoReducido) {
        const inicio = Math.floor(Math.random() * categorias.length);
        let paso = 0;

        resultadoSorteo.textContent = categorias[inicio];
        resultadoSorteo.classList.remove('sorteo-pendiente');

        intervalo = setInterval(() => {
            paso++;

            if (paso === 8) {
                clearInterval(intervalo);
                mostrarResultado();
                return;
            }

            resultadoSorteo.textContent = categorias[(inicio + paso) % categorias.length];
        }, 100);
    } else {
        mostrarResultado();
    }
}