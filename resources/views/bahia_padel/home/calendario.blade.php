@extends('bahia_padel.home.plantilla')

@section('title_header', 'Calendario - Bahía Pádel')

@section('contenedor')
<section class="page-header-img mb-4">
    <div class="page-header-img-inner">
        <img src="{{ asset('images/home/reglamento.webp') }}" alt="Calendario" class="img-fluid w-100">
        <div class="page-header-img-overlay"></div>
        <h1 class="page-header-title">Calendario</h1>
    </div>
</section>
<section class="py-4 page-content-home">
    @if(!isset($eventos) || $eventos->isEmpty())
        <p class="text-secondary">No hay eventos programados aún.</p>
    @else
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Categoría</th>
                        <th>Tipo</th>
                        <th>Nombre</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($eventos as $e)
                    @if($e)
                    <tr>
                        <td>{{ $e->fecha ? $e->fecha->format('d/m/Y') : '-' }}</td>
                        <td>{{ $e->categoria }}ª</td>
                        <td>{{ $e->tipo_label }}</td>
                        <td>{{ $e->nombre ?? '-' }}</td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
