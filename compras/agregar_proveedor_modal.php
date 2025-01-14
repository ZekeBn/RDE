<?php
/*--------------------------------------------
Datos para agregar proveedor modal
30/5/2023
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

require_once("../proveedores/preferencias_proveedores.php");
//buscando moneda nacional
$consulta = "SELECT idtipo FROM `tipo_moneda` WHERE nacional='S' ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];


//buscando pais defecto
$consulta = "SELECT idpais FROM paises_propio WHERE defecto=1 ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_pais_nacional = $rs_guarani->fields["idpais"];

if ($id_pais_nacional == 0) {
    $errores = "Pais por defecto no seleccionado favor verificarlo <a  style='color:white;' href ='../paises/paises.php' > ¡Click Aqui! </a>";
}
//buscando origenes importacion y locales
$consulta = "SELECT idtipo_origen FROM tipo_origen WHERE UPPER(tipo)='LOCAL'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_tipo_origen_local = intval($rs_guarani->fields["idtipo_origen"]);
if ($id_tipo_origen_local == 0) {
    $errores = "- Por favor cree el Origen LOCAL.<br />";
}
$consulta = "SELECT idtipo_origen FROM tipo_origen WHERE  UPPER(tipo)='IMPORTACION'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_tipo_origen_importacion = intval($rs_guarani->fields["idtipo_origen"]);
if ($id_tipo_origen_importacion == 0) {
    $errores = "- Por favor cree el Origen IMPORTACON.<br />";
}

?>
<script>
 if ($("#erroresEditarArticulo").is(':empty')) {
    $('#boxErroresEditarArticulo').hide();
  }
  function detalles_pais(){
	$("#titulov").html("Detalles Pais-Monedas ");
	$('#form_agregar_proveedor').removeClass('show');
	$("#form_agregar_proveedor").addClass('hide');
	$('#moneda_pais').removeClass('hide');
	
	$("#moneda_pais #idpais").val($("#form_agregar_proveedor #idpais").val());
	$("#moneda_pais").addClass('show');
	
  }
  function cerrar_detalles_pais(){
	$("#titulov").html("Agregar Proveedores");
	$('#form_agregar_proveedor').removeClass('hide');
	$("#form_agregar_proveedor").addClass('show');
	$('#moneda_pais').removeClass('show');
	$("#moneda_pais").addClass('hide');
  }

function verificar_pais(selectElement){
	
	const selectedOption = selectElement.options[selectElement.selectedIndex];
	//seleccion de origen local o importacion 
	if(selectedOption.value==<?php echo $id_pais_nacional?>) {
		$("#idtipo_origen").val(<?php echo $id_tipo_origen_local?>);
	}else{
		$("#idtipo_origen").val(<?php echo $id_tipo_origen_importacion?>);
	}
  	const idMoneda = selectedOption.dataset.hiddenValue;
	if (idMoneda){
		$("#idmoneda").val(idMoneda);
	}else{
		alerta("info","- El país seleccionado no cuenta con una moneda asociada. Se establecerá la moneda nacional como opción predeterminada.<br> Si lo deseas, puedes asignar manualmente una moneda haciendo uso del botón en forma de lupa ubicado junto al campo de ingreso del país.<br>","Alerta");
		$("#idmoneda").val(<?php echo $id_moneda_nacional ?>);
		
	}
}
function IsJsonString(str) {
	try {
		JSON.parse(str);
	} catch (e) {
		return false;
	}
	return true;
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

function agregar_proveedor_post(event){
	event.preventDefault();
	var valido = "S"; 
	var error="";
	
	if($("#form_agregar_proveedor #idpais").val() == ""){
		valido = "N";
		error +=("- Favor seleccione un País.<br>");
	}
	if($("#form_agregar_proveedor #ruc").val() == ""){
		valido = "N";
		error +=(" - El RUC no puede estar vacío.<br>");
	}
	if($("#form_agregar_proveedor #nombre").val() == ""){
		valido = "N";
		error +=(" - No se envio ninguna Razon Social.<br>");
	}
	<?php if ($proveedores_sin_factura == "S") {?>
		if($("#form_agregar_proveedor #incrementa").val() == ""){
			valido = "N";
			error +=(" - Sin Factura no puede estar vacío.<br>");
		}
	<?php } ?>
	if($("#form_agregar_proveedor #acuerdo_comercial").val() == ""){
		valido = "N";
		error +=(" - Acuerdo comercial no puede estar vacío.<br>");
	}
	if (valido == "N"){
		alerta("danger",error,"ERROR");
	}
	if(valido == "S"){
		
		var parametros_array = {
			"ruc"						: $("#form_agregar_proveedor #ruc").val(), 
			"idpais"					: $("#form_agregar_proveedor #idpais").val(), 
			"fantasia"					: $("#form_agregar_proveedor #fantasia").val(), 
			"idmoneda"					: $("#form_agregar_proveedor #idmoneda").val(), 
			"agente_retencion"			: $("#form_agregar_proveedor #agente_retencion").val(), 
			"idtipo_servicio"			: $("#form_agregar_proveedor #idtipo_servicio").val(), 
			"idtipo_origen"				: $("#form_agregar_proveedor #idtipo_origen").val(), 
			"nombre"					: $("#form_agregar_proveedor #nombre").val(), 
			"diasvence"					: $("#form_agregar_proveedor #diasvence").val(), 
			"incrementa"				: $("#form_agregar_proveedor #incrementa").val(), 
			"acuerdo_comercial"			: $("#form_agregar_proveedor #acuerdo_comercial").val(),
			"acuerdo_comercial_coment"	: $("#form_agregar_proveedor #acuerdo_comercial_coment").val(),
			"telefono"					: $("#form_agregar_proveedor #telefono").val(), 
			"email"						: $("#form_agregar_proveedor #email").val(),
			"idtipocompra" 				: $("#form_agregar_proveedor #idtipocompra").val(),
			"cuenta_cte_mercaderia" 	: $("#form_agregar_proveedor #cuenta_cte_mercaderia").val(),
			"cuenta_cte_deuda" 			: $("#form_agregar_proveedor #cuenta_cte_deuda").val(),
			"agregar_proveedor"			: 1
		};
	  $.ajax({		  
		data:  parametros_array,
		url:   'agregar_proveedor.php',
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 5000
		crossDomain: true,
		beforeSend: function () {
		  $("#submitAgregarProveedor").text('Cargando...');
		},
		success:  function (response) {
			// console.log(response);
			if (JSON.parse(response)["success"]==true) {
				cerrar_pop();
				location.reload();
			}else {
				alerta("danger",JSON.parse(response)["errores"],"Error:");
				$("#submitAgregarProveedor").text('Guardar');
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
		  errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
		}
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
		});
	}
}
function editar_pais_moneda(event){
	event.preventDefault();
	var parametros_array = {
			"idpais"					: $("#moneda_pais #idpais").val(), 
			"idmoneda"					: $("#moneda_pais #idmoneda").val(),
			"agregar_pais"				: 1
		};
	  $.ajax({		  
		data:  parametros_array,
		url:   'paises_dropdown.php',
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 5000
		crossDomain: true,
		beforeSend: function () {
		  $("#submitEditarPais").text('Cargando...');
		},
		success:  function (response) {
			$("#dropdown_pais").html(response);
			$("#form_agregar_proveedor #idmoneda").val($("#moneda_pais #idmoneda").val());
			cerrar_detalles_pais();
		},
		error: function(jqXHR, textStatus, errorThrown) {
		  errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
		}
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
		});
}

$(document).ready(function() {
	$('#boxErroresProveedor').on('closed.bs.alert', function () {
        $('#boxErroresProveedor').removeClass('show');
        $('#boxErroresProveedor').addClass('hide');
      });
	  <?php

      if (intval($id_moneda_nacional) == 0) {
          echo "
			
			
			alerta('info','- No cuenta con una moneda por defecto asociada. Realicelo en monedas_extranjeras si asi lo deseas.<br>','Alerta');
			";
      }
if (intval($id_pais_nacional) == 0) {
    echo "
			alerta('info','- No cuenta con un pais por defecto asociado. Realicelo en el modulo de paises si asi lo deseas.<br>','Alerta');
			";
}
?>
});
function cerrar_errores_proveedor(event){
	event.preventDefault();
	$('#boxErroresProveedor').removeClass('show');
	$('#boxErroresProveedor').addClass('hide');
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
<br />
<div class="alert  alert-dismissible fade in hide" role="alert" id="boxErroresProveedor">
	<button type="button" class="close" onclick="cerrar_errores_proveedor(event)" aria-label="Close">
		<span aria-hidden="true">×</span>
	</button>
	<strong id="tituloErroresProveedor">Errores:</strong><br /><p id="erroresProveedor"></p>
</div>
<div id="form_agregar_proveedor">
	<div class="col-md-12 col-sm-12  " >
		<h2 style="font-size: 1.3rem;">Datos Personales</h2>
		<hr>
		<div class="col-md-6 col-xs-12 form-group" >
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Email</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="email" id="email"  placeholder="" class="form-control" />                    
			</div>
		</div>

		<div class="col-md-6 col-xs-12 form-group" >
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="telefono" id="telefono"  placeholder="" class="form-control" />                    
			</div>
		</div>

		<!--  preferencias   -->
		<?php if ($proveedores_importacion == "S") {?>
		
			<div id="dropdown_pais"><?php require_once("./paises_dropdown.php") ?></div>

			<div class="col-md-6 col-xs-12 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Moneda *</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
					<?php

             // consulta

             $consulta = "
					SELECT idtipo, descripcion
					FROM tipo_moneda
					where
					estado = 1
					order by descripcion asc
					";

		    // valor seleccionado
		    if (isset($_POST['idmoneda'])) {
		        $value_selected = htmlentities($_POST['idmoneda']);
		    } else {
		        $value_selected = $id_moneda_nacional;
		    }



		    // parametros
		    $parametros_array = [
		        'nombre_campo' => 'idmoneda',
		        'id_campo' => 'idmoneda',

		        'nombre_campo_bd' => 'descripcion',
		        'id_campo_bd' => 'idtipo',

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
		<?php } ?>
		<!--  hasta aca la preferencias -->
		<!-- preferencias proveedor   -->
		<?php if ($proveedores_importacion == "S") {?>
			

			<div class="col-md-6 col-xs-12 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Origen Proveedor*</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
					<?php

		    // consulta

		    $consulta = "
					SELECT idtipo_origen, tipo
					FROM tipo_origen
					order by tipo asc
					";

		    // valor seleccionado
		    if (isset($_POST['idtipo_origen'])) {
		        $value_selected = htmlentities($_POST['idtipo_origen']);
		    } else {
		        $value_selected = null;
		    }



		    // parametros
		    $parametros_array = [
		        'nombre_campo' => 'idtipo_origen',
		        'id_campo' => 'idtipo_origen',

		        'nombre_campo_bd' => 'tipo',
		        'id_campo_bd' => 'idtipo_origen',

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
		<?php } ?>
		<!--  hasta aca la preferencias -->
		<!-- //////fin  -->
	</div>


	<div class="col-md-12 col-sm-12  " >
		<h2 style="font-size: 1.3rem;">Datos Tributarios</h2>
		<hr>

		<div class="col-md-6 col-xs-12 form-group" >
			<label class="control-label col-md-3 col-sm-3 col-xs-12 ">RUC *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="ruc" id="ruc" class="form-control"   />
				<span id="medidanombre" style="color: red;"></span>
			</div>
		</div>
		<div class="col-md-6 col-xs-12 form-group"  >
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon Social *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="nombre" id="nombre" class="form-control"   />                      
			</div>
		</div>
		
		<div class="col-md-6 col-xs-12 form-group"  >
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre Fantasia</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="fantasia" id="fantasia" class="form-control"   />                      
			</div>
		</div>
		
		<?php if ($proveedores_cta_cte == "S") {?>
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Cuenta Contable Mercaderia</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="cuenta_cte_mercaderia" id="cuenta_cte_mercaderia" value="<?php  if (isset($_POST['cuenta_cte_mercaderia'])) {
				    echo htmlentities($_POST['cuenta_cte_mercaderia']);
				} else {
				    echo htmlentities($rs->fields['cuenta_cte_mercaderia']);
				}?>" placeholder="Cuenta Cte. Mercaderia" class="form-control"   />                    
				</div>
			</div>
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Cuenta Contable Deuda Proveedor</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="cuenta_cte_deuda" id="cuenta_cte_deuda" value="<?php  if (isset($_POST['cuenta_cte_deuda'])) {
				    echo htmlentities($_POST['cuenta_cte_deuda']);
				} else {
				    echo htmlentities($rs->fields['cuenta_cte_deuda']);
				}?>" placeholder="Cuenta Cte. Deuda Proveedor" class="form-control"   />                    
				</div>
			</div>
		<?php } ?>


		<?php if ($proveedores_sin_factura == "S") {?>
			<div class="col-md-6 col-xs-12 form-group" >
				<label class="control-label col-md-3 col-sm-3 col-xs-12" >Sin Factura *</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<select class="custom-select form-control" name="incrementa" id="incrementa">
					<option selected value>Seleccionar...</option>
					<option value="S">Si</option>
					<option value="N">No</option>
				</select>
				</div>
			</div>
		<?php } ?>



		
		<!--  SOLO PARA RDE  preferencias agente de retencion y proveedor de mercaderias-->
		<?php if ($proveedores_agente_retencion == "S") { ?>
			<div class="col-md-6 col-xs-12 form-group" >
				<label class="control-label col-md-3 col-sm-3 col-xs-12" >Agente Retencion *</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<select class="custom-select form-control" name="agente_retencion" id="agente_retencion">
					<option value="S">Si</option>
					<option selected value="N">No</option>
				</select>
				</div>
			</div>
		<?php } ?>
		
		

		<!-- FIN DE TRIBUTARIOS -->
	</div>



	<div class="col-md-12 col-sm-12  " >
		<h2 style="font-size: 1.3rem;">Acuerdos Comerciales</h2>
		<hr>

		<div class="col-md-6 col-xs-12 form-group" >
			<label class="control-label col-md-3 col-sm-3 col-xs-12" >Acuerdo comercial *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<select class="custom-select form-control" name="acuerdo_comercial" id="acuerdo_comercial">
				<option selected value>Seleccionar...</option>
				<option value="S">Si</option>
				<option value="N">No</option>
			</select>
			</div>
		</div>

		<div class="col-md-6 col-xs-12 form-group" >
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Acuerdo comercial Detalle</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="acuerdo_comercial_coment" id="acuerdo_comercial_coment"  placeholder="" class="form-control" />                    
			</div>
		</div>

		<!-- FIN ACUERDOS COMERCIALES  -->
	</div>

	<div class="col-md-12 col-sm-12  " >
		<h2 style="font-size: 1.3rem;">Datos Compra</h2>
		<hr>

		<div class="col-md-6 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo compra</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php
                    // consulta
                    $consulta = "
					SELECT idtipocompra, tipocompra
					FROM tipocompra
					order by tipocompra asc
					";

// valor seleccionado
if (isset($_POST['idtipocompra'])) {
    $value_selected = htmlentities($_POST['idtipocompra']);
} else {
    $value_selected = htmlentities($rs->fields['idtipocompra']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtipocompra',
    'id_campo' => 'idtipocompra',

    'nombre_campo_bd' => 'tipocompra',
    'id_campo_bd' => 'idtipocompra',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="tipo_compra(this.value);" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
			</div>
		</div>

		<!-- ///////////////////////////////////////////// -->
		<?php if ($tipo_servicio == "S") { ?>
			<div class="col-md-6 col-xs-12 form-group" >
				<label class="control-label col-md-3 col-sm-3 col-xs-12" >Tipo Servicio</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
					<?php

    // consulta

    $consulta = "
					SELECT idtipo_servicio, tipo
					FROM tipo_servicio
					order by tipo asc
					";

		    // valor seleccionado
		    if (isset($_POST['idtipo_servicio'])) {
		        $value_selected = htmlentities($_POST['idtipo_servicio']);
		    } else {
		        $value_selected = null;
		    }



		    // parametros
		    $parametros_array = [
		        'nombre_campo' => 'idtipo_servicio',
		        'id_campo' => 'idtipo_servicio',

		        'nombre_campo_bd' => 'tipo',
		        'id_campo_bd' => 'idtipo_servicio',

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
		<?php } ?>
		<!--  -->
		
	
		<div class="col-md-6 col-xs-12 form-group"  >
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Dias de Credito *</label>   <!--/*number*/-->
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="number" name="diasvence" value="0" id="diasvence" class="form-control"  />                      
			</div>
		</div>
		
	</div>

	<div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 col-md-offset-5">
			<button type="submit" id="submitAgregarProveedor" class="btn btn-success" onclick="agregar_proveedor_post(event);" ><span class="fa fa-check-square-o"></span> Guardar</button>
			<button type="button" class="btn btn-primary" onMouseUp="cerrar_pop();"><span class="fa fa-ban"></span> Cancelar</button>
		</div>
	</div>
	<div class="clearfix form-group"></div>
	<br />
</div>

<div id="moneda_pais" class="hide" >
	<a href="javascript:void(0);" onclick="cerrar_detalles_pais()" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
	<br />
	
	<div class="col-md-6 col-xs-12 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">
			Pais *
		</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<?php

            // consulta

            $consulta = "
			SELECT p.idpais, p.nombre, p.idmoneda FROM paises_propio p
			WHERE p.estado = 1
			order by nombre asc;
			";

// valor seleccionado
if (isset($_POST['idpais'])) {
    $value_selected = htmlentities($_POST['idpais']);
} else {
    $value_selected = htmlentities($_GET['idpais']);
}

if ($_GET['idpais'] > 0) {
    $add = "disabled";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idpais',
    'id_campo' => 'idpais',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idpais',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'data_hidden' => 'idmoneda',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" disabled '.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
		</div>
	</div>
		<div class="col-md-6 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Moneda *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php

    // consulta

    $consulta = "
				SELECT idtipo, descripcion
				FROM tipo_moneda
				where
				estado = 1
				order by moneda asc
				";

// valor seleccionado
if (isset($_POST['idmoneda'])) {
    $value_selected = htmlentities($_POST['idmoneda']);
} else {
    $value_selected = $id_moneda_nacional;
}

if ($_GET['idmoneda'] > 0) {
    $add = "disabled";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idmoneda',
    'id_campo' => 'idmoneda',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idtipo',

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

		<div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 col-md-offset-5">
			<button type="submit" id="submitEditarPais" class="btn btn-success" onclick="editar_pais_moneda(event);" ><span class="fa fa-check-square-o"></span> Guardar</button>
		</div>
	</div>
	
	<div class="clearfix form-group"></div>
	<br />
</div>





