<?php
/*--------------------------------------------
Datos de cabecera para edicion durante
la carga / registro de las compras
---------------------------------------------*/
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

$idtran = intval($_POST['idtransaccion']);
$idunico = intval($_POST['idunico']);
$editar_vencimiento = intval($_POST["editar_vencimiento"]);
$idvencimiento = intval($_POST["idvencimiento"]);
if (intval($idtran) == 0) {
    header("location: gest_reg_compras_resto.php");
    exit;
}
if ($idvencimiento > 0) {
    $consulta = "
	select * from tmpcompravenc where idvencimiento=$idvencimiento order by vencimiento asc
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
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
function editar_vencimiento_compras_post(){
	var parametros_array = {
          "monto_cuota"					: $("#form_compras_vencimiento #monto_cuota").val(), 
          "vencimiento"					: $("#form_compras_vencimiento #vencimiento").val(), 
          "idtransaccion"				: <?php echo $idtran?>, 
          "idvencimiento"				: <?php echo $idvencimiento?>, 
		  "vencimientos_compra_editar"	: 1
	};
	// console.log(parametros_array);
	$.ajax({		  
		data:  parametros_array,
		url:   'compras_carrito.php',
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 5000
		crossDomain: true,
		beforeSend: function () {
		$("#submitEditarArticulo").text('Cargando...');
		},
		success:  function (response) {
			cerrar_pop();
			$("#carritocompras").html(response);
		},
		error: function(jqXHR, textStatus, errorThrown) {
		errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
		}
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
	});
}
function crear_vencimiento_compras_post(){
	var parametros_array = {
          "monto_cuota"		: $("#form_compras_vencimiento #monto_cuota").val(), 
          "vencimiento"	: $("#form_compras_vencimiento #vencimiento").val(), 
          "idtransaccion"	: <?php echo $idtran?>, 
		  "vencimientos_compra"	: 1
	};
	// console.log(parametros_array);
	$.ajax({		  
		data:  parametros_array,
		url:   'compras_carrito.php',
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
			$("#carritocompras").html(response);
		},
		error: function(jqXHR, textStatus, errorThrown) {
		errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
		}
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
	});
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
	$('#boxErroresArticulosModal').removeClass('show');
	$('#boxErroresArticulosModal').addClass('hide');
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

$("#form_compras_vencimiento  input").keydown(function(event) {
  // Verifica si la tecla presionada es "Enter"
  if (event.keyCode === 13) {
    // Cancela el comportamiento predeterminado del formulario
    event.preventDefault();
    // Envía el formulario
    // $(this).closest("form").submit();
	$("#submitEditarArticulo").click();
  }
});


</script>
<div class="clearfix"></div>
<br />
<div class="alert alert-danger alert-dismissible fade in hide" role="alert" id="boxErroresArticulosModal">
	<button type="button" class="close" onclick="cerrar_errores_articulos_modal(event)" aria-label="Close">
		<span aria-hidden="true">×</span>
	</button>
	<strong>Errores:</strong><br /><p id="erroresArticulosModal"></p>
</div>
<div id="form_compras_vencimiento">
		
		
		<div class="col-md-6 col-xs-12 form-group" >
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Monto</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="monto_cuota" id="monto_cuota" value="<?php  if (isset($_POST['monto_cuota'])) {
				    echo htmlentities($_POST['monto_cuota']);
				} else {
				    echo htmlentities($rs->fields['monto_cuota']);
				}?>" placeholder="" class="form-control" />                    
			</div>
		</div>
		
		<div class="col-md-6 col-xs-12 form-group" >
			<label class="control-label col-md-3 col-sm-3 col-xs-12" >Vencimiento</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="date" name="vencimiento" id="vencimiento" onBlur="validar_fecha_articulo_vencimiento(this.value)" value="<?php  if (isset($_POST['vencimiento'])) {
				    echo htmlentities($_POST['vencimiento']);
				} else {
				    echo htmlentities($rs->fields['vencimiento']);
				}?>" placeholder="" class="form-control" />                    
			</div>
		</div>

	


		<div class="form-group">
			<div class="col-md-12 col-sm-12 col-xs-12 col-md-offset-5">
				<button type="submit" id="submitEditarArticulo" class="btn btn-success" onclick="<?php if ($idvencimiento > 0) {
				    echo "editar_vencimiento_compras_post();";
				} else {
				    echo "crear_vencimiento_compras_post();";
				}?>" ><span class="fa fa-check-square-o"></span> Guardar</button>
				<button type="button" class="btn btn-primary" onMouseUp="cerrar_pop();"><span class="fa fa-ban"></span> Cancelar</button>
			</div>
		</div>
		<div class="clearfix form-group"></div>
		<br />
	</div>
<div id="updatecabeza"></div>





