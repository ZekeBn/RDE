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
$idcompra = antisqlinyeccion(intval($_POST['idcompra']), "int");
$idregs = antisqlinyeccion(intval($_POST['idregs']), "int");
$idempresa = antisqlinyeccion(intval($_POST['idempresa']), "int");
$nombre_producto = antisqlinyeccion($_POST['nombre_producto'], 'text');
$nombre_producto = str_replace("'", "", $nombre_producto);

if ($idcompra == 0) {
    echo "Registro inexistente!";
    exit;
}

// consulta a la tabla
$consulta = "
SELECT * FROM compras_detalles
WHERE idcompra = $idcompra and idregs = $idregs and idempresa = $idempresa ORDER BY `idcompra` DESC
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idunico = intval($rs->fields['idregcc']);
$idtransaccion = intval($rs->fields['idt']);
$idtipoiva = intval($rs->fields['idtipoiva']);
$idinsumo = intval($rs->fields['idprod']);



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
function editar_deposito_compra_formulario(){
	var parametros_array = {
		  "iddeposito"			: $("#form_editar_articulo #iddeposito").val(),
		  "idcompra"			: <?php echo $idcompra ?>,
		  "idregs"				: <?php echo $idregs?>,
		  "idempresa"			: <?php echo $idempresa?>,
		  "editar_deposito"		: 1
	};
  $.ajax({		  
	data:  parametros_array,
	url:   'gest_adm_depositos_compras_lista.php',
	type:  'post',
	cache: false,
	timeout: 3000,  // I chose 3 secs for kicks: 5000
	crossDomain: true,
	beforeSend: function () {
	  $("#submitEditarArticulo").text('Cargando...');
	},
	success:  function (response) {
		$("#gest_admin_deposito_compra_listas").html(response);
		cerrar_pop();
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


</script>
<div class="clearfix"></div>
<br />
<div id="form_editar_articulo">
		
		<div class="col-md-6 col-xs-12 form-group" >
			<label class="control-label col-md-3 col-sm-3 col-xs-12 ">Producto </label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="cantidad" id="cantidad" class="form-control" value="<?php  echo $nombre_producto ?>" disabled  />
				<span id="medidanombre" style="color: red;"></span>
			</div>
		</div>
		

		<div class="col-md-6 col-xs-12 form-group" >
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Deposito</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
// consulta
$consulta = "
SELECT iddeposito, descripcion
FROM gest_depositos
where
estado = 1
and tiposala <> 3
order by descripcion asc
 ";

// valor seleccionado
if (isset($_POST['iddeposito'])) {
    $value_selected = htmlentities($_POST['iddeposito']);
} else {
    $value_selected = htmlentities($rs->fields['iddeposito_compra']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'iddeposito',
    'id_campo' => 'iddeposito',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'iddeposito',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>



		<div class="form-group">
			<div class="col-md-12 col-sm-12 col-xs-12 col-md-offset-5">
				<button type="submit" id="submitEditarArticulo" class="btn btn-success" onclick="editar_deposito_compra_formulario();" ><span class="fa fa-check-square-o"></span> Guardar</button>
				<button type="button" class="btn btn-primary" onMouseUp="cerrar_pop();"><span class="fa fa-ban"></span> Cancelar</button>
			</div>
		</div>
		<div class="clearfix form-group"></div>
		<br />
	</div>
<div id="updatecabeza"></div>





