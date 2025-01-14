<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");





if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots


    // recibe parametros
    $idproveedor = antisqlinyeccion($_POST['idproveedor'], "int");
    $codigo_articulo = antisqlinyeccion($_POST['codigo_articulo'], "text");
    $precio = antisqlinyeccion($_POST['precio'], "float");
    $fecha = antisqlinyeccion($_POST['fecha'], "text");
    $registrado_el = antisqlinyeccion($ahora, "text");
    $registrado_por = $idusu;
    $estado = 1;




    if (intval($_POST['idproveedor']) == 0) {
        $valido = "N";
        $errores .= " - El campo idproveedor no puede ser cero o nulo.<br />";
    }
    if (trim($_POST['codigo_articulo']) == '') {
        $valido = "N";
        $errores .= " - El campo codigo_articulo no puede estar vacio.<br />";
    }
    if (floatval($_POST['precio']) <= 0) {
        $valido = "N";
        $errores .= " - El campo precio no puede ser cero o negativo.<br />";
    }
    if (trim($_POST['fecha']) == '') {
        $valido = "N";
        $errores .= " - El campo fecha no puede estar vacio.<br />";
    }

    // si todo es correcto inserta
    if ($valido == "S") {
        $idfob = select_max_id_suma_uno("proveedores_fob", "idfob")["idfob"];
        $consulta = "
		insert into proveedores_fob
		(idfob, idproveedor, codigo_articulo, precio, fecha, registrado_el, registrado_por, estado)
		values
		($idfob, $idproveedor, $codigo_articulo, $precio, $fecha, $registrado_el, $registrado_por, $estado)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: codigo_origen.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());



?><script>
	function agregar_codigo_origen_post(event){
	event.preventDefault();
	var valido = "S"; 
	var error="";
	
	if($("#form_codigo_origen #idproveedor").val() == "" || $("#form_codigo_origen #idproveedor").val() == undefined){
		valido = "N";
		error +=("- El campo idproveedor no puede ser cero o nulo.<br />");
	}
	if($("#form_codigo_origen #codigo_articulo").val() == "" || $("#form_codigo_origen #codigo_articulo").val() == undefined) {
		valido = "N";
		error +=("- El campo codigo_articulo no puede estar vacio.<br />");
	}
	if($("#form_codigo_origen #precio").val() == "" || $("#form_codigo_origen #precio").val() == undefined) {
		valido = "N";
		error +=(" - El campo precio no puede ser cero o negativo.<br />");
	}
	
	if($("#form_codigo_origen #fecha").val() == "" || $("#form_codigo_origen #fecha").val() == undefined) {
		valido = "N";
		error +=(" - El campo fecha no puede estar vacio.<br />");
	}
	if (valido == "N"){
		alerta("danger",error,"ERROR");
	}
	if(valido == "S"){
		
		var parametros_array = {
			"idproveedor"						: $("#form_codigo_origen #idproveedor").val(), 
			"codigo_articulo"						: $("#form_codigo_origen #codigo_articulo").val(), 
			"precio"						: $("#form_codigo_origen #precio").val(), 
			"fecha"						: $("#form_codigo_origen #fecha").val(), 
			"agregar_cod_fob"			: 1
		};
		console.log(parametros_array);

	  $.ajax({		  
		data:  parametros_array,
		url:   'dropdown_proveedor_fob.php',
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 5000
		crossDomain: true,
		beforeSend: function () {
		  $("#submit_codigo_origen").text('Cargando...');
		},
		success:  function (response) {
			console.log(response);

			$("#box_cod_fob").html(response);
				cerrar_pop();
				// location.reload();
			
		},
		error: function(jqXHR, textStatus, errorThrown) {
		  errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
		}
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
		});
	}
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
function cerrar_errores_proveedor(event){
	event.preventDefault();
	$('#boxErroresProveedor').removeClass('show');
	$('#boxErroresProveedor').addClass('hide');
}
</script>

 
                  
<div class="alert  alert-dismissible fade in hide" role="alert" id="boxErroresProveedor">
	<button type="button" class="close" onclick="cerrar_errores_proveedor(event)" aria-label="Close">
		<span aria-hidden="true">×</span>
	</button>
	<strong id="tituloErroresProveedor">Errores:</strong><br /><p id="erroresProveedor"></p>
</div>
<div id="form_codigo_origen">
	
	<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Porveedor *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php
                    // consulta
                    $consulta = "SELECT idproveedor ,nombre
					FROM proveedores
					where estado = 1
					";

// valor seleccionado
if (isset($_POST['idproveedor'])) {
    $value_selected = htmlentities($_POST['idproveedor']);
} else {
    $value_selected = htmlentities($rs->fields['idproveedor']);
}
// parametros
$parametros_array = [
    'nombre_campo' => 'idproveedor',
    'id_campo' => 'idproveedor',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idproveedor',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' readonly required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
			</div>
	</div>
	
	
	
	
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo articulo *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="text" name="codigo_articulo" id="codigo_articulo" value="<?php  if (isset($_POST['codigo_articulo'])) {
		    echo htmlentities($_POST['codigo_articulo']);
		} else {
		    echo htmlentities($rs->fields['codigo_articulo']);
		}?>" placeholder="Codigo articulo" class="form-control" required="required" />
		</div>
	</div>
	
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Precio *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="text" name="precio" id="precio" value="<?php  if (isset($_POST['precio'])) {
		    echo floatval($_POST['precio']);
		} else {
		    echo floatval($rs->fields['precio']);
		}?>" placeholder="Precio" class="form-control" required="required" />
		</div>
	</div>
	
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="date" name="fecha" id="fecha" value="<?php  if (isset($_POST['fecha'])) {
		    echo htmlentities($_POST['fecha']);
		} else {
		    echo htmlentities($rs->fields['fecha']);
		}?>" placeholder="Fecha" class="form-control" required="required" />
		</div>
	</div>
	
	
	<br />
	
		<div class="form-group">
			<div class="col-md-12 col-sm-12 col-xs-12 text-center">
	
		   <button type="submit" onclick="agregar_codigo_origen_post(event)" id="submit_codigo_origen" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
			</div>
		</div>
	
	  <input type="hidden" name="MM_insert" value="form1" />
	  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
	<br />
	<div class="clearfix"></div>
</div>
<div class="clearfix"></div>

<div class="clearfix"></div>
<br /><br />







