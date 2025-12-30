@extends('admin_lumen/plantilla')

@section('title_header','Base Componentes')

@section('contenedor')

<div class="row">
	<div class="col-md-4">
		<label for="uso_select">Uso:</label>
		<select class="form-control margin_top_menos_5px" id="uso_select" onchange="actualizarListadoEspecialidad()">
			<option value="1">Interno</option>
			<option value="2">Externo</option>
		</select>
	</div>
</div>

<div class="row">
	<label for="np_imagen">Imagen</label><br>
	<input type="file" id="np_imagen" name="np_imagen" class="form-control" placeholder="foto"  />
</div>

@endsection