<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

$idtransaccion = intval($_POST['tran']);
$idempresa = intval($_POST['idempresa']);
$idusu = intval($_POST["idusu"]);

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
function guardar_faltantes(guardar){
    var idtransaccion=<?php echo $idtransaccion ?>;
	var idempresa=<?php echo $idempresa ?>;
	var idusu=<?php echo $idusu?>;
	
    var parametros_array = {
        "tran"			: idtransaccion,
        "idempresa"		: idempresa,
        "idusu"			: idusu,
        "faltante"      : guardar
	};
	
  $.ajax({		  
	data:  parametros_array,
	url:   'registrar_compra.php',
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
		if(JSON.parse(response)["success"] == true) {
            $("#generar_compra").text("Exito");
            document.location.href='gest_reg_compras_resto_new.php';

        }else{
            // console.log(JSON.parse(response)["errores"]);
            $('#titulovError').html('Error');
            $('#cuerpovError').html(JSON.parse(response)["errores"]);	
            $('#ventanamodalError').modal('show');
            $("#generar_compra").text("Finalizar Compra");
        }
		
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




</script>
<div class="clearfix"></div>
<br />
<div class="alert alert-danger alert-dismissible fade in hide" role="alert" id="boxErroresArticulosModal">
	<button type="button" class="close" onclick="cerrar_errores_articulos_modal(event)" aria-label="Close">
		<span aria-hidden="true">×</span>
	</button>
	<strong>Errores:</strong><br /><p id="erroresArticulosModal"></p>
</div>
<div id="form_editar_articulo">
        <p style="text-align: center;font-size: x-large;">
            ¿Desea almacenar en un historial los artículos faltantes 
            de la orden de compra asociada a la factura?
        </p>


		<div class="form-group">
			<div class="col-md-12 col-sm-12 col-xs-12 col-md-offset-5">
				<button type="button" class="btn btn-success" onclick="guardar_faltantes(1)"><span class="fa fa-check-square-o"></span> SI</button>
				<button type="button" class="btn btn-primary" onclick="guardar_faltantes(2);"><span class="fa fa-ban"></span> NO</button>
			</div>
		</div>
		<div class="clearfix form-group"></div>
		<br />
	</div>





