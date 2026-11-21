// Estadísticas del administrador: pide los datos por AJAX según el período y arma tablas con barras.
document.addEventListener('DOMContentLoaded', function () {
    const nombresPeriodo = { dia: 'Hoy', semana: 'Esta semana', mes: 'Este mes', anio: 'Este año', todo: 'Todo' };
    const botones = document.querySelectorAll('.btn-periodo');
    const estadoCarga = document.getElementById('estado-carga');

    document.getElementById('fecha-reporte').textContent = new Date().toLocaleDateString('es-AR');
    document.getElementById('btn-imprimir').addEventListener('click', function () {
        window.print();
    });

    botones.forEach(function (boton) {
        boton.addEventListener('click', function () {
            botones.forEach(b => b.classList.remove('activo'));
            boton.classList.add('activo');
            cargar(boton.dataset.periodo);
        });
    });

    // Evita que un texto de la base se interprete como HTML
    function escapar(texto) {
        const div = document.createElement('div');
        div.textContent = texto ?? '';
        return div.innerHTML;
    }

    function filaVacia(tbody) {
        tbody.innerHTML = '<tr><td colspan="4" class="sin-datos">Sin datos para este período</td></tr>';
    }

    // Tabla de cantidades (país, sexo, edad): barra proporcional al mayor valor y % sobre el total
    function llenarTablaCantidades(idTabla, datos) {
        const tbody = document.querySelector('#' + idTabla + ' tbody');
        const total = datos.reduce((suma, fila) => suma + fila.cantidad, 0);

        if (total === 0) {
            filaVacia(tbody);
            return;
        }

        const maximo = Math.max(...datos.map(fila => fila.cantidad));
        tbody.innerHTML = datos.map(function (fila) {
            const ancho = maximo > 0 ? (fila.cantidad / maximo) * 100 : 0;
            const porcentaje = ((fila.cantidad / total) * 100).toFixed(1);
            return '<tr>' +
                '<td>' + escapar(fila.etiqueta) + '</td>' +
                '<td class="col-barra"><div class="barra"><span style="width:' + ancho + '%"></span></div></td>' +
                '<td class="numero">' + fila.cantidad + '</td>' +
                '<td class="numero">' + porcentaje + '%</td>' +
                '</tr>';
        }).join('');
    }

    // Tabla de aciertos por usuario: la barra es el % de respuestas correctas
    function llenarTablaAciertos(datos) {
        const tbody = document.querySelector('#tabla-aciertos tbody');

        if (datos.length === 0) {
            filaVacia(tbody);
            return;
        }

        tbody.innerHTML = datos.map(function (fila) {
            return '<tr>' +
                '<td>' + escapar(fila.etiqueta) + '</td>' +
                '<td class="col-barra"><div class="barra"><span style="width:' + fila.porcentaje + '%"></span></div></td>' +
                '<td class="numero">' + fila.correctas + ' / ' + fila.respondidas + '</td>' +
                '<td class="numero">' + fila.porcentaje + '%</td>' +
                '</tr>';
        }).join('');
    }

    async function cargar(periodo) {
        estadoCarga.textContent = 'Cargando estadísticas...';
        estadoCarga.style.display = '';
        document.getElementById('texto-periodo').textContent = nombresPeriodo[periodo];

        try {
            const respuesta = await fetch(BASE_URL + '/admin/datosEstadisticas?periodo=' + encodeURIComponent(periodo));
            if (!respuesta.ok) {
                throw new Error('HTTP ' + respuesta.status);
            }
            const datos = await respuesta.json();

            Object.entries(datos.indicadores).forEach(function ([clave, valor]) {
                const elemento = document.getElementById('ind-' + clave);
                if (elemento) {
                    elemento.textContent = valor;
                }
            });

            llenarTablaAciertos(datos.aciertos_por_usuario);
            llenarTablaCantidades('tabla-pais', datos.usuarios_por_pais);
            llenarTablaCantidades('tabla-sexo', datos.usuarios_por_sexo);
            llenarTablaCantidades('tabla-edad', datos.usuarios_por_edad);

            estadoCarga.style.display = 'none';
        } catch (error) {
            estadoCarga.textContent = 'No se pudieron cargar las estadísticas. Intentá de nuevo.';
        }
    }

    cargar('todo');
});
