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
    <p class="text-secondary">Contenido de la sección Calendario. (Por definir)</p>
</section>
@endsection
