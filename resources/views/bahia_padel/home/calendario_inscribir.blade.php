@extends('bahia_padel.home.plantilla')

@section('title_header', 'Inscripción - Calendario - Bahía Pádel')

@section('contenedor')
@php
    $tituloTorneo = $evento->nombre ?: ($evento->categoria.'ª categoría · '.$evento->tipo_label);
    $txtFechas = $evento->textoFechasTorneo();
    $valorInscr = ($evento->valor_inscripcion !== null && $evento->valor_inscripcion !== '')
        ? '$'.number_format((float) $evento->valor_inscripcion, 0, ',', '.')
        : null;
@endphp
<style>
  .inscribir-resumen {
    border: 1px solid rgba(148, 163, 184, 0.35);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    background: rgba(255, 255, 255, 0.04);
  }
  body.dark-mode .inscribir-resumen {
    background: rgba(45, 45, 45, 0.5);
    border-color: rgba(148, 163, 184, 0.25);
  }
  .inscribir-resumen h2 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
  }
  .inscribir-form label {
    font-weight: 500;
    font-size: 0.9rem;
  }
  .inscribir-form .form-section-title {
    font-size: 1rem;
    font-weight: 600;
    margin-top: 1.25rem;
    margin-bottom: 0.75rem;
    padding-bottom: 0.35rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.35);
  }
</style>

<section class="page-header-img mb-4">
    <div class="page-header-img-inner">
        <img src="{{ asset('images/home/reglamento.webp') }}" alt="Inscripción" class="img-fluid w-100">
        <div class="page-header-img-overlay"></div>
        <h1 class="page-header-title">Inscripción</h1>
    </div>
</section>

<section class="py-3 page-content-home">
    <div class="inscribir-resumen">
        <h2 class="mb-1">{{ $tituloTorneo }}</h2>
        @if($evento->nombre)
            <p class="text-secondary small mb-2">{{ $evento->categoria }}ª · {{ $evento->tipo_label }}</p>
        @endif
        @if($txtFechas !== '')
            <p class="mb-0"><strong>Fecha:</strong> {{ $txtFechas }}</p>
        @endif
        @if($valorInscr)
            <p class="mb-0 mt-2"><strong>Valor inscripción por jugador:</strong> {{ $valorInscr }}</p>
        @endif
    </div>

    <form method="post" action="{{ route('home.calendario.inscribir.guardar', $evento) }}" class="inscribir-form">
        @csrf

        <div class="form-section-title">Jugador 1</div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="jugador1_nombre">Nombre</label>
                <input type="text" class="form-control @error('jugador1_nombre') is-invalid @enderror" id="jugador1_nombre" name="jugador1_nombre" value="{{ old('jugador1_nombre') }}" required maxlength="120">
                @error('jugador1_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group col-md-6">
                <label for="jugador1_apellido">Apellido</label>
                <input type="text" class="form-control @error('jugador1_apellido') is-invalid @enderror" id="jugador1_apellido" name="jugador1_apellido" value="{{ old('jugador1_apellido') }}" required maxlength="120">
                @error('jugador1_apellido')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="form-group">
            <label for="jugador1_telefono">Teléfono</label>
            <input type="text" class="form-control @error('jugador1_telefono') is-invalid @enderror" id="jugador1_telefono" name="jugador1_telefono" value="{{ old('jugador1_telefono') }}" required maxlength="40" inputmode="tel" autocomplete="tel">
            @error('jugador1_telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-section-title">Jugador 2</div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="jugador2_nombre">Nombre</label>
                <input type="text" class="form-control @error('jugador2_nombre') is-invalid @enderror" id="jugador2_nombre" name="jugador2_nombre" value="{{ old('jugador2_nombre') }}" required maxlength="120">
                @error('jugador2_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group col-md-6">
                <label for="jugador2_apellido">Apellido</label>
                <input type="text" class="form-control @error('jugador2_apellido') is-invalid @enderror" id="jugador2_apellido" name="jugador2_apellido" value="{{ old('jugador2_apellido') }}" required maxlength="120">
                @error('jugador2_apellido')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="form-group">
            <label for="jugador2_telefono">Teléfono <span class="text-muted font-weight-normal">(opcional)</span></label>
            <input type="text" class="form-control @error('jugador2_telefono') is-invalid @enderror" id="jugador2_telefono" name="jugador2_telefono" value="{{ old('jugador2_telefono') }}" maxlength="40" inputmode="tel" autocomplete="tel">
            @error('jugador2_telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="disponibilidad_horaria">Disponibilidad horaria</label>
            <textarea class="form-control @error('disponibilidad_horaria') is-invalid @enderror" id="disponibilidad_horaria" name="disponibilidad_horaria" rows="4" required maxlength="5000" placeholder="Ej.: Sábados por la tarde, entre semana después de las 19 hs…">{{ old('disponibilidad_horaria') }}</textarea>
            @error('disponibilidad_horaria')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary px-4">Guardar</button>
        <a href="{{ route('home.calendario') }}" class="btn btn-link text-secondary">Volver al calendario</a>
    </form>
</section>
@endsection
