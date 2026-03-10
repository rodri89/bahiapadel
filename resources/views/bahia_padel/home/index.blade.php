@extends('bahia_padel/home/plantilla')

@section('title_header', 'Bahía Pádel')

@section('contenedor')
{{-- Video a ancho completo ocupando el lugar del header. Archivo: public/videos/home-video.mp4 --}}
<section class="home-video-fullwidth">
    <video class="home-video" autoplay muted loop playsinline preload="metadata">
        <source src="{{ asset('videos/home-video.mp4') }}" type="video/mp4">
    </video>
</section>

<section class="py-4 page-content-home">
   
</section>
@endsection