<?php
/*--------------------------------------------
Datos de cabecera para edicion durante
la carga / registro de las compras
---------------------------------------------*/
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$ocnum = intval($_POST['ocnum']);


if ($ocnum == 0) {
    echo "Registro inexistente!";
    exit;
}



?>
<script>
 if ($('#erroresEditarArticulo').is(':empty')) {
    $('#boxErroresEditarArticulo').hide();
  }
	function IsJsonString(str) {
		try {
			JSON.parse(str);
		} catch (e) {
			return false;
		}
		return true;
	}
	function editar_articulo_post(){


		var valido = "S"; 
		var error="";

		if($("#form_agregar_embarque #idpuerto").val() == ""){
			valido = "N";
			error +=(" - El campo idpuerto no puede ser cero o nulo.<br />");
		}
		if($("#form_agregar_embarque #idtransporte").val() == ""){
			valido = "N";
			error +=(" - El campo idtransporte no puede ser cero o nulo.<br />");
		}
		if($("#form_agregar_embarque #idvias_embarque").val() == ""){
			valido = "N";
			error +=(" - El campo idvias_embarque no puede ser cero o nulo.<br />");
		}
		if($("#form_agregar_embarque #fecha_embarque").val() == ""){
			valido = "N";
			error +=(" - El campo fecha_embarque no puede estar vacio.<br />");
		}
		if($("#form_agregar_embarque #fecha_llegada").val() == ""){
			valido = "N";
			error +=(" - El campo fecha_llegada no puede estar vacio.<br />");
		}
		if (valido == "N"){
			alerta("danger",error,"ERROR");
		}
		if (valido == "S"){
			var parametros_array = {
				"ocnum"					: <?php echo $ocnum?>, 
				"idpuerto"				: $("#form_agregar_embarque #idpuerto").val(), 
				"idtransporte"			: $("#form_agregar_embarque #idtransporte").val(), 
				"idvias_embarque"		: $("#form_agregar_embarque #idvias_embarque").val(), 
				"estado_embarque"		: $("#form_agregar_embarque #estado_embarque").val(), 
				"descripcion"			: $("#form_agregar_embarque #descripcion").val(),
				"fecha_embarque"		: $("#form_agregar_embarque #fecha_embarque").val(),
				"fecha_llegada"			: $("#form_agregar_embarque #fecha_llegada").val(),
				"agregar_embarque"		: 1
			};
			// console.log(parametros_array);
			var url_send = 'compras_ordenes_det.php?id=<?php echo $ocnum?>'
			$.ajax({		  
				data:  parametros_array,
				url:   url_send,
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 5000
				crossDomain: true,
				beforeSend: function () {
				$("#submitEditarArticulo").text('Cargando...');
				},
				success:  function (response) {
					// console.log(response);
					cerrar_pop();
					
				},
				error: function(jqXHR, textStatus, errorThrown) {
				errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
				}
			}).fail( function( jqXHR, textStatus, errorThrown ) {
				errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
			});
		}
	}
	function alerta( clase ,error,titulo){
		var alertaClase = 'alert-' + clase;
		if (clase == "info"){
			$('#boxErroresProveedor').removeClass('alert-danger');
		}else{
			$('#boxErroresProveedor').removeClass('alert-info');
		}
		$('#tituloErroresProveedor').html(titulo);
		$('#boxErroresProveedor').addClass(alertaClase);
		$('#boxErroresProveedor').removeClass('hide');
		$("#erroresProveedor").html(error);
		$('#boxErroresProveedor').addClass('show');
		
	}
	function nl2br (str, is_xhtml) {
		var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>'; // Adjust comment to avoid issue on phpjs.org display
		return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
	}
	function errores_ajax_manejador(jqXHR, textStatus, errorThrown, tipo){
		// error
		if(tipo == 'error'){
			if(jqXHR.status == 404){
			alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
			alert('Se ha rechazado la conexión.');
			}else{
			alert(jqXHR.status+' '+errorThrown);
			}
		// fail
		}else{
			if (jqXHR.status === 0) {
			alert('No conectado: verifique la red.');
			} else if (jqXHR.status == 404) {
			alert('Pagina no encontrada [404]');
			} else if (jqXHR.status == 500) {
			alert('Internal Server Error [500].');
			} else if (textStatus === 'parsererror') {
			alert('Requested JSON parse failed.');
			} else if (textStatus === 'timeout') {
			alert('Tiempo de espera agotado, time out error.');
			} else if (textStatus === 'abort') {
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
			} else {
			alert('Uncaught Error: ' + jqXHR.responseText);
			}
		}
	}

	$(document).ready(function() {
		$('#boxErroresArticulosModal').on('closed.bs.alert', function () {
			$('#boxErroresArticulosModal').removeClass('show');
			$('#boxErroresArticulosModal').addClass('hide');
		});
	});
	function cerrar_errores_articulos_modal(event){
		event.preventDefault();
		$('#boxErroresProveedor').removeClass('show');
		$('#boxErroresProveedor').addClass('hide');
	}

	function validar_fecha_articulo_vencimiento(fecha){
		/*
		Note: JavaScript counts months from 0 to 11.
		January is 0. December is 11.
		*/
		var errores = '';
		// var fecha = $("#fecha_compra").val();
		// var vencimiento_timbrado = $("#vto_timbrado").val()
		var valido = 'S';
		var fe=fecha.split("-");
		var ano=fe[0];
		var mes=fe[1]-1;
		var meshtml= fe[1];
		var dia=fe[2];
		var f1 = new Date(ano, mes, dia);
		var f2 = new Date(<?php echo date("Y"); ?>, <?php echo date("m") - 1; ?>, <?php echo date("d"); ?>);
		
		//alert(f1); 
		//alert(ano+'-'+mes+'-'+dia);
		
		if (f1 < f2){
			valido = 'N';
			errores = 'La Fecha del timbrado ('+dia+'/'+meshtml+'/'+ano+') esta vencida.';
		}
		// la fecha no puede ser menor a la fecha desde
		if(valido == 'N'){
			//alert(f1); 
			//alert(f2); 
			//alert(fdesde); 
			//alert(fhasta); 
			//alerta_modal('Incorrecto','Fecha de compra ('+dia+'/'+meshtml+'/'+ano+') incorrecta, habilitado entre: <?php echo $fechadesde_txt; ?> y <?php echo $fechahasta_txt; ?> y no pude ser mayor a hoy <?php echo date("d/m/Y", strtotime($ahora)); ?>.');
			alerta_error(errores);
		}else{
			//cargavto();
		}

	}
	function alerta_error(error){
		$("#erroresArticulosModal").html(error);
		$('#boxErroresArticulosModal').addClass('show');
	}

	$("#form_agregar_embarque  input").keydown(function(event) {
		// Verifica si la tecla presionada es "Enter"
		if (event.keyCode === 13) {
			// Cancela el comportamiento predeterminado del formulario
			event.preventDefault();
			// Envía el formulario
			// $(this).closest("form").submit();
			$("#submitEditarArticulo").click();
		}
	});

	function cambiar_vias_embarque(selectElement){
      const selectedOption = selectElement.options[selectElement.selectedIndex];
      const idViasEmbarque = selectedOption.dataset.hiddenValue;
      // console.log(idViasEmbarque);
      $("#idvias_embarque").val(idViasEmbarque)

    }


</script>
<div class="clearfix"></div>
<br />
<div class="alert  alert-dismissible fade in hide" role="alert" id="boxErroresProveedor">
	<button type="button" class="close" onclick="cerrar_errores_articulos_modal(event)" aria-label="Close">
		<span aria-hidden="true">×</span>
	</button>
	<strong id="tituloErroresProveedor">Errores:</strong><br /><p id="erroresProveedor"></p>
</div>
<div id="form_agregar_embarque">
	<div class="col-md-6 col-xs-12 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Puerto *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<?php

                    // consulta

                    $consulta = "
					SELECT idpuerto, descripcion
					FROM puertos
					where
					estado = 1
					order by descripcion asc
					";

// valor seleccionado
if (isset($_POST['idpuerto'])) {
    $value_selected = htmlentities($_POST['idpuerto']);
} else {
    $value_selected = $rs->fields['idpuerto'];
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idpuerto',
    'id_campo' => 'idpuerto',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idpuerto',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'data_hidden' => 'idvias_embarque',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="cambiar_vias_embarque(this)" "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
		</div>
	</div>




	<div class="col-md-6 col-xs-12 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Transporte *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<?php

        // consulta

        $consulta = "
					SELECT idtransporte, idvias_embarque, descripcion
					FROM transporte
					where
					estado = 1
					order by descripcion asc
					";

// valor seleccionado
if (isset($_POST['idtransporte'])) {
    $value_selected = htmlentities($_POST['idtransporte']);
} else {
    $value_selected = $rs->fields['idtransporte'];
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtransporte',
    'id_campo' => 'idtransporte',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idtransporte',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
            'data_hidden' => 'idvias_embarque',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="cambiar_vias_embarque(this)" "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
		</div>
	</div>






	<div class="col-md-6 col-xs-12 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Vias embarque *</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
					<?php

        // consulta

        $consulta = "
					SELECT idvias_embarque, descripcion
					FROM vias_embarque
					where
					estado = 1
					order by descripcion asc
					";

// valor seleccionado
if (isset($_POST['idvias_embarque'])) {
    $value_selected = htmlentities($_POST['idvias_embarque']);
} else {
    $value_selected = $rs->fields['idvias_embarque'];
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idvias_embarque',
    'id_campo' => 'idvias_embarque',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idvias_embarque',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
		</div>
	</div>



	<div class="col-md-6 col-xs-12 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Estado Embarque *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<?php

if (isset($_POST['estado_embarque'])) {
    $value_selected = htmlentities($_POST['estado_embarque']);
} else {
    $value_selected = '1';
}
// opciones
$opciones = [
'Activo' => '1',
'Inactivo' => '2'
];
// parametros
$parametros_array = [
'nombre_campo' => 'estado_embarque',
'id_campo' => 'estado_embarque',

'value_selected' => $value_selected,

'pricampo_name' => 'Seleccionar...',
'pricampo_value' => '',
'style_input' => 'class="form-control"',
'acciones' => ' required="required" ',
'autosel_1registro' => 'S',
'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);

?>
		</div>
	</div>



	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Descripcion </label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
			    echo htmlentities($_POST['descripcion']);
			} else {
			    echo htmlentities($rs->fields['descripcion']);
			}?>" placeholder="Descripcion" class="form-control" required="required" />                    
		</div>
	</div>




	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha embarque *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="date" name="fecha_embarque" id="fecha_embarque" value="<?php  if (isset($_POST['fecha_embarque'])) {
		    echo htmlentities($_POST['fecha_embarque']);
		} else {
		    echo htmlentities($rs->fields['fecha_embarque']);
		}?>" placeholder="Fecha embarque" class="form-control" required="required" />                    
		</div>
	</div>

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha llegada *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="date" name="fecha_llegada" id="fecha_llegada" value="<?php  if (isset($_POST['fecha_llegada'])) {
		    echo htmlentities($_POST['fecha_llegada']);
		} else {
		    echo htmlentities($rs->fields['fecha_llegada']);
		}?>" placeholder="Fecha llegada" class="form-control" required="required" />                    
		</div>
	</div>



	<div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 col-md-offset-5">
			<button type="submit" id="submitEditarArticulo" class="btn btn-success" onclick="editar_articulo_post();" ><span class="fa fa-check-square-o"></span> Guardar</button>
			<button type="button" class="btn btn-primary" onMouseUp="cerrar_pop();"><span class="fa fa-ban"></span> Cancelar</button>
		</div>
	</div>
	<div class="clearfix form-group"></div>
	<br />
</div>





