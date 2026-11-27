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
        // Abandonar: "apagón" eléctrico, un tono que cae de golpe (como un cortocircuito)
        abandonar: function () {
            if (silenciado) return;
            const ctx = obtenerContexto();
            if (!ctx) return;

            const inicio = ctx.currentTime;
            const oscilador = ctx.createOscillator();
            const ganancia = ctx.createGain();

            oscilador.type = 'sawtooth';
            oscilador.frequency.setValueAtTime(600, inicio);
            oscilador.frequency.exponentialRampToValueAtTime(40, inicio + 0.6);
            ganancia.gain.setValueAtTime(0.15, inicio);
            ganancia.gain.exponentialRampToValueAtTime(0.001, inicio + 0.65);

            oscilador.connect(ganancia);
            ganancia.connect(ctx.destination);
            oscilador.start(inicio);
            oscilador.stop(inicio + 0.65);

            // Chispazo corto al final
            tono(90, 0.08, 'square', 0.12, 0.55);
        },
        // Final de partida: fanfarria si ganaste, tono bajón si perdiste
        victoria: function () {
            tono(523, 0.15, 'triangle', 0.18);
            tono(659, 0.15, 'triangle', 0.18, 0.15);
            tono(784, 0.15, 'triangle', 0.18, 0.30);
            tono(1047, 0.45, 'triangle', 0.20, 0.45);
        },
        derrota: function () {
            tono(392, 0.3, 'triangle', 0.16);
            tono(330, 0.3, 'triangle', 0.16, 0.3);
            tono(262, 0.6, 'triangle', 0.16, 0.6);
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
