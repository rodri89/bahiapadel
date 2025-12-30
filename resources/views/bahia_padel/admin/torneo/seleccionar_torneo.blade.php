<div class="container position-relative" style="padding-top:0 !important;">
    <button type="button"
            onclick="volverAtrasNuevoTorneo()"
            class="close"
            aria-label="Cerrar"
            style="position: absolute; top: 10px; right: 10px; font-size:2rem; color:#ff0264; z-index:10;">
        <span aria-hidden="true">&times;</span>
    </button>
    <h2 class="text-center mb-3" style="color:black; margin-top:0 !important;">Seleccionar Torneo</h2>
    <div class="d-flex align-items-center justify-content-center mb-4">
        <button class="btn btn-link" id="mes-anterior" style="font-size:2rem;color:#ff0264;">&#8592;</button>
        <h3 id="mes-actual" class="mx-3 my-0" style="color:black"></h3>
        <button class="btn btn-link" id="mes-siguiente" style="font-size:2rem;color:#ff0264;">&#8594;</button>
    </div>
    <div id="listado-torneos" class="row justify-content-center">
        <!-- Aquí se insertarán las tarjetas dinámicamente -->
    </div>
</div>

<script type="text/javascript">

    let torneos = [];
    let mesActual = new Date().getMonth();
    let anioActual = new Date().getFullYear();

    const meses = [
        "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
        "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
    ];

    function cargarTorneos() {
        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: '/get_torneos',
            data: { _token: '{{csrf_token()}}' },
            success: function (data) {
                torneos = data.torneos || [];
                mostrarTorneosMes();
            }
        });
    }

    function mostrarTorneosMes() {
        const contenedor = $("#listado-torneos");
        contenedor.empty();
        $("#mes-actual").text(meses[mesActual] + " " + anioActual);

        // Filtrar torneos por mes y año
        const torneosMes = torneos.filter(t => {
            const fecha = new Date(t.fecha_inicio);
            return fecha.getMonth() === mesActual && fecha.getFullYear() === anioActual;
        });

        if (torneosMes.length === 0) {
            contenedor.append('<div class="col-12 text-center text-muted">No hay torneos para este mes.</div>');
            return;
        }

        torneosMes.forEach(torneo => {
            // Obtener el tipo de torneo (puntuable, americano, suma) o usar 'puntuable' por defecto
            const tipoTorneo = torneo.tipo_torneo_formato || 'puntuable';
            const nombreTorneo = torneo.nombre || 'Sin nombre';
            
            // Función para capitalizar la primera letra
            const capitalizar = (str) => str.charAt(0).toUpperCase() + str.slice(1);
            
            contenedor.append(`
                <div class="col-12 mb-3">
                    <form action="{{ route('admintorneoselected') }}" method="POST" class="w-100">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="torneo_id" value="${torneo.id}"/>
                        <div class="card shadow bg-white w-100 p-3 d-flex align-items-center justify-content-between torneo-card"
                            style="cursor:pointer; border-radius: 12px; border: 1px solid #e3e6f0;" onclick="this.closest('form').submit();">
                            <div class="d-flex flex-column align-items-start flex-grow-1">
                                <div class="categoria display-4" style="font-size:2.2rem; font-weight:700; color:#4e73df;">${torneo.categoria}º Categoría</div>
                                <div class="fechas mt-2" style="font-size:0.85rem; color:#888;">
                                    <span style="font-weight:500;">${nombreTorneo}</span> - <span style="font-style:italic;">${capitalizar(tipoTorneo)}</span> - ${torneo.tipo}
                                </div>
                                <div class="fechas mt-2" style="font-size:1.1rem; color:#555;">Fecha: ${formatearRangoFechas(torneo.fecha_inicio, torneo.fecha_fin)}</div>
                            </div>
                            <div class="d-flex flex-column align-items-end premios" style="min-width:160px;">
                                <div class="premio1" style="font-size:1.5rem; font-weight:600; color:#1a8917;">1º Premio: $${formatearMiles(torneo.premio_1)}</div>
                                <div class="premio2" style="font-size:1.2rem; font-weight:500; color:#555;">2º Premio: $${formatearMiles(torneo.premio_2)}</div>
                            </div>
                        </div>
                    </form>
                </div>
            `);
        });
    }

    $("#mes-anterior").on("click", function() {
        if (mesActual === 0) {
            mesActual = 11;
            anioActual--;
        } else {
            mesActual--;
        }
        mostrarTorneosMes();
    });

    $("#mes-siguiente").on("click", function() {
        if (mesActual === 11) {
            mesActual = 0;
            anioActual++;
        } else {
            mesActual++;
        }
        mostrarTorneosMes();
    });

    // Inicializar
    $(document).ready(function() {
        cargarTorneos();
    });

    function getSeleccionarTorneo() {
            
            $.ajax({
                type: 'POST',
                dataType: 'JSON',
                url: '/get_torneos',
                data: {  _token: '{{csrf_token()}}' },
                success: function (data) {                
                    //showSnackbar("¡Torneo registrado exitosamente!");                
                }
            });
        }

    function volverAtrasNuevoTorneo() {        
        location.reload();
    }

    function formatearMiles(numero) {
        // Convierte a string y usa regex para poner puntos cada 3 dígitos desde la derecha
        return numero.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function formatearRangoFechas(fechaInicio, fechaFin) {
        const meses = [
            "enero", "febrero", "marzo", "abril", "mayo", "junio",
            "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"
        ];
        const [anioI, mesI, diaI] = fechaInicio.split("-");
        const [anioF, mesF, diaF] = fechaFin.split("-");
        // Si el mes es el mismo, muestra solo una vez el mes
        if (mesI === mesF) {
            return `${parseInt(diaI)} ${meses[parseInt(mesI)-1]} - ${parseInt(diaF)} ${meses[parseInt(mesF)-1]}`;
        } else {
            return `${parseInt(diaI)} ${meses[parseInt(mesI)-1]} - ${parseInt(diaF)} ${meses[parseInt(mesF)-1]}`;
        }
    }

</script>