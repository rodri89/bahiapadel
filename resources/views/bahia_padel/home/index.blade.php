@extends('bahia_padel/home/plantilla')

@section('title_header', 'Bahía Pádel')

@section('contenedor')
<section class="page-header-img mb-4">
    <div class="page-header-img-inner">
        <img src="{{ asset('images/home/reglamento.webp') }}" alt="Inicio" class="img-fluid w-100">
        <div class="page-header-img-overlay"></div>
        <h1 class="page-header-title">Inicio</h1>
    </div>
</section>

{{-- Video a ancho completo (solo el video, sin comentarios). Archivo: public/videos/home-video.mp4 --}}
<section class="home-video-fullwidth">
    <video class="home-video" autoplay muted loop playsinline>
        <source src="{{ asset('videos/home-video.mp4') }}" type="video/mp4">
    </video>
</section>

<section class="py-4 page-content-home">
   
</section>
@endsection