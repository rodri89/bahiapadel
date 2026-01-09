@extends('bahia_padel.mobile.plantilla_mobile')

@section('content')
<div class="container-fluid px-3">
    <!-- Header -->
    <div class="header-mobile text-center">
        <h1><i class="fas fa-camera"></i> Subir Foto de Jugador</h1>
        <p class="mb-0" style="opacity: 0.9; font-size: 14px;">Busca un jugador y sube su foto</p>
    </div>

    <!-- Buscador -->
    <div class="search-container">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search"></i></span>
            <input type="text" 
                   class="form-control border-start-0" 
                   id="buscador-jugadores" 
                   placeholder="Buscar por nombre o apellido..."
                   autocomplete="off">
            <button class="btn btn-outline-secondary" type="button" id="btn-limpiar-busqueda" style="display: none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Lista de jugadores -->
    <div id="lista-jugadores">
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <p>Busca un jugador para comenzar</p>
        </div>
    </div>

    <!-- Sección de subida de foto (fixed bottom) -->
    <div class="upload-section" id="upload-section" style="display: none;">
        <div class="selected-jugador-info mb-3 p-3 bg-light rounded">
            <div class="d-flex align-items-center">
                <img id="selected-jugador-foto" src="" class="jugador-foto-mobile me-3" alt="Foto jugador">
                <div>
                    <h6 class="mb-0" id="selected-jugador-nombre"></h6>
                    <small class="text-muted">ID: <span id="selected-jugador-id"></span></small>
                </div>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold">Seleccionar nueva foto:</label>
            <div class="file-input-wrapper">
                <input type="file" 
                       id="input-foto" 
                       name="foto" 
                       accept="image/*" 
                       class="form-control">
                <div class="file-input-button">
                    <i class="fas fa-cloud-upload-alt mb-2" style="font-size: 32px; display: block;"></i>
                    <span id="file-input-text">Toca para seleccionar una imagen</span>
                </div>
            </div>
            <small class="text-muted">Formatos: JPG, PNG, GIF. Tamaño máximo recomendado: 2MB</small>
        </div>
        
        <div id="preview-container" style="display: none;" class="text-center mb-3">
            <img id="preview-foto" src="" class="preview-foto" alt="Vista previa">
        </div>
        
        <button type="button" 
                class="btn btn-upload" 
                id="btn-subir-foto" 
                disabled>
            <i class="fas fa-upload"></i> Subir Foto
        </button>
    </div>
</div>

<!-- Toast para mensajes -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="fas fa-info-circle me-2"></i>
            <strong class="me-auto">Bahía Padel</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toast-message"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let jugadorSeleccionado = null;
let timeoutBusqueda = null;

$(document).ready(function() {
    // Buscador en tiempo real
    $('#buscador-jugadores').on('input', function() {
        const busqueda = $(this).val().trim();
        
        // Mostrar/ocultar botón limpiar
        if (busqueda.length > 0) {
            $('#btn-limpiar-busqueda').show();
        } else {
            $('#btn-limpiar-busqueda').hide();
        }
        
        // Debounce para evitar demasiadas peticiones
        clearTimeout(timeoutBusqueda);
        timeoutBusqueda = setTimeout(function() {
            buscarJugadores(busqueda);
        }, 300);
    });
    
    // Limpiar búsqueda
    $('#btn-limpiar-busqueda').on('click', function() {
        $('#buscador-jugadores').val('');
        $(this).hide();
        $('#lista-jugadores').html(`
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <p>Busca un jugador para comenzar</p>
            </div>
        `);
        ocultarSeccionUpload();
    });
    
    // Seleccionar jugador
    $(document).on('click', '.jugador-item', function() {
        const jugadorId = $(this).data('id');
        seleccionarJugador(jugadorId);
    });
    
    // Preview de imagen antes de subir
    $('#input-foto').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validar tamaño (2MB máximo)
            if (file.size > 2 * 1024 * 1024) {
                mostrarMensaje('La imagen es demasiado grande. Máximo 2MB.', 'error');
                $(this).val('');
                return;
            }
            
            // Validar tipo
            if (!file.type.match('image.*')) {
                mostrarMensaje('Por favor selecciona una imagen válida.', 'error');
                $(this).val('');
                return;
            }
            
            // Mostrar preview
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#preview-foto').attr('src', e.target.result);
                $('#preview-container').show();
                $('#file-input-text').text(file.name);
                $('#btn-subir-foto').prop('disabled', false);
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Subir foto
    $('#btn-subir-foto').on('click', function() {
        if (!jugadorSeleccionado) {
            mostrarMensaje('Por favor selecciona un jugador primero.', 'error');
            return;
        }
        
        const fileInput = $('#input-foto')[0];
        if (!fileInput.files || !fileInput.files[0]) {
            mostrarMensaje('Por favor selecciona una imagen.', 'error');
            return;
        }
        
        subirFoto(jugadorSeleccionado.id, fileInput.files[0]);
    });
    
    // Función para mostrar mensajes
    window.mostrarMensaje = function(mensaje, tipo) {
        const toastElement = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');
        const toastHeader = document.querySelector('#toast .toast-header');
        
        toastMessage.textContent = mensaje;
        
        // Cambiar color según el tipo
        toastHeader.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'text-white');
        if (tipo === 'success') {
            toastHeader.classList.add('bg-success', 'text-white');
            toastHeader.querySelector('i').className = 'fas fa-check-circle me-2';
        } else if (tipo === 'error') {
            toastHeader.classList.add('bg-danger', 'text-white');
            toastHeader.querySelector('i').className = 'fas fa-exclamation-circle me-2';
        } else {
            toastHeader.classList.add('bg-warning', 'text-white');
            toastHeader.querySelector('i').className = 'fas fa-info-circle me-2';
        }
        
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
    };
});

function buscarJugadores(busqueda) {
    if (busqueda.length < 2) {
        return;
    }
    
    $('#lista-jugadores').html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Buscando...</span>
            </div>
            <p class="mt-3 text-muted">Buscando jugadores...</p>
        </div>
    `);
    
    $.ajax({
        type: 'POST',
        url: '{{ route("buscar.jugadores.publico") }}',
        data: {
            busqueda: busqueda,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.jugadores && response.jugadores.length > 0) {
                mostrarJugadores(response.jugadores);
            } else {
                $('#lista-jugadores').html(`
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <p>No se encontraron jugadores</p>
                        <small class="text-muted">Intenta con otro nombre</small>
                    </div>
                `);
            }
        },
        error: function(xhr) {
            console.error('Error al buscar jugadores:', xhr);
            mostrarMensaje('Error al buscar jugadores. Por favor intenta de nuevo.', 'error');
            $('#lista-jugadores').html(`
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error al buscar jugadores</p>
                </div>
            `);
        }
    });
}

function mostrarJugadores(jugadores) {
    let html = '';
    jugadores.forEach(function(jugador) {
        let foto = jugador.foto || '{{ asset('images/jugador_img.png') }}';
        if (!foto.startsWith('http') && !foto.startsWith('/')) {
            foto = '{{ url('/') }}/' + foto;
        } else if (foto.startsWith('/') && !foto.startsWith('{{ url('/') }}')) {
            foto = '{{ url('/') }}' + foto;
        }
        
        html += `
            <div class="jugador-item" data-id="${jugador.id}">
                <div class="d-flex align-items-center">
                    <img src="${foto}" class="jugador-foto-mobile me-3" alt="${jugador.nombre} ${jugador.apellido}">
                    <div class="flex-grow-1">
                        <h6 class="mb-0">${jugador.nombre} ${jugador.apellido}</h6>
                        ${jugador.telefono ? `<small class="text-muted"><i class="fas fa-phone"></i> ${jugador.telefono}</small>` : ''}
                    </div>
                    <i class="fas fa-chevron-right text-muted"></i>
                </div>
            </div>
        `;
    });
    
    $('#lista-jugadores').html(html);
}

function seleccionarJugador(jugadorId) {
    // Obtener datos del jugador desde la lista
    const jugadorItem = $(`.jugador-item[data-id="${jugadorId}"]`);
    if (jugadorItem.length === 0) return;
    
    // Buscar el jugador en la lista actual
    let jugador = null;
    $('.jugador-item').each(function() {
        if ($(this).data('id') == jugadorId) {
            const img = $(this).find('img');
            const nombre = $(this).find('h6').text().trim();
            jugador = {
                id: jugadorId,
                nombre: nombre,
                foto: img.attr('src')
            };
            return false;
        }
    });
    
    if (!jugador) return;
    
    jugadorSeleccionado = jugador;
    
    // Marcar como seleccionado
    $('.jugador-item').removeClass('selected');
    jugadorItem.addClass('selected');
    
    // Mostrar sección de upload
    $('#selected-jugador-id').text(jugador.id);
    $('#selected-jugador-nombre').text(jugador.nombre);
    $('#selected-jugador-foto').attr('src', jugador.foto);
    $('#upload-section').slideDown();
    
    // Scroll a la sección de upload
    $('html, body').animate({
        scrollTop: $(document).height()
    }, 300);
}

function ocultarSeccionUpload() {
    $('#upload-section').slideUp();
    jugadorSeleccionado = null;
    $('.jugador-item').removeClass('selected');
    $('#input-foto').val('');
    $('#preview-container').hide();
    $('#btn-subir-foto').prop('disabled', true);
    $('#file-input-text').text('Toca para seleccionar una imagen');
}

function subirFoto(jugadorId, archivo) {
    const formData = new FormData();
    formData.append('id', jugadorId);
    formData.append('foto', archivo);
    formData.append('_token', '{{ csrf_token() }}');
    
    // Deshabilitar botón
    $('#btn-subir-foto').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Subiendo...');
    
    $.ajax({
        type: 'POST',
        url: '{{ route("subir.foto.jugador.publico") }}',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                mostrarMensaje('¡Foto subida correctamente!', 'success');
                
                // Actualizar foto en la lista
                const jugadorItem = $(`.jugador-item[data-id="${jugadorId}"]`);
                jugadorItem.find('img').attr('src', response.foto_url + '?t=' + new Date().getTime());
                $('#selected-jugador-foto').attr('src', response.foto_url + '?t=' + new Date().getTime());
                
                // Limpiar formulario
                $('#input-foto').val('');
                $('#preview-container').hide();
                $('#file-input-text').text('Toca para seleccionar una imagen');
                
                setTimeout(function() {
                    ocultarSeccionUpload();
                    $('#buscador-jugadores').val('');
                    $('#btn-limpiar-busqueda').hide();
                    $('#lista-jugadores').html(`
                        <div class="empty-state">
                            <i class="fas fa-check-circle text-success"></i>
                            <p class="text-success">Foto subida exitosamente</p>
                            <small class="text-muted">Busca otro jugador si deseas</small>
                        </div>
                    `);
                }, 1500);
            } else {
                mostrarMensaje(response.message || 'Error al subir la foto', 'error');
                $('#btn-subir-foto').prop('disabled', false).html('<i class="fas fa-upload"></i> Subir Foto');
            }
        },
        error: function(xhr) {
            let mensaje = 'Error al subir la foto';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                mensaje = xhr.responseJSON.message;
            }
            mostrarMensaje(mensaje, 'error');
            $('#btn-subir-foto').prop('disabled', false).html('<i class="fas fa-upload"></i> Subir Foto');
        }
    });
}
</script>
@endsection

