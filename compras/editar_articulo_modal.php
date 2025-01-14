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
require_once("../proveedores/preferencias_proveedores.php");

require_once("../insumos/preferencias_insumos_listas.php");
require_once("../compras_ordenes/preferencias_compras_ordenes.php");
require_once("./preferencias_compras.php");

$idtran = intval($_POST['idtransaccion']);
$idunico = intval($_POST['idunico']);
if ($idunico == 0) {
    echo "Registro inexistente!";
    exit;
}


$buscar = "SELECT id_medida FROM medidas WHERE nombre like '%UNIDAD%' ";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$id_unidad = intval($rsd->fields['id_medida']);

$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%DESPACHO\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_despacho = intval($rs_conceptos->fields['idconcepto']);

$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%FLETE\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_flete = intval($rs_conceptos->fields['idconcepto']);


//buscando moneda nacional
$consulta = "SELECT tipo_moneda.idtipo, tipo_moneda.descripcion as nombre FROM tipo_moneda WHERE nacional='S'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];
$nombre_moneda_nacional = $rs_guarani->fields["nombre"];

// consulta a la tabla
$buscar = "SELECT id_medida FROM medidas WHERE nombre like '%EDI' ";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$id_cajas_edi = intval($rsd->fields['id_medida']);


$consulta = "
select tmpcompradeta.*,insumos_lista.maneja_lote, insumos_lista.descripcion,insumos_lista.idmedida as idmedida1, 
insumos_lista.idmedida2, insumos_lista.idmedida3,
insumos_lista.cant_medida2, insumos_lista.cant_medida3, insumos_lista.cant_caja_edi,
(select nombre from medidas where medidas.id_medida = insumos_lista.idmedida) as medida_nombre,
(select nombre from medidas where medidas.id_medida = insumos_lista.idmedida2) as medida2,
(select nombre from medidas where medidas.id_medida = insumos_lista.idmedida3) as medida3,
tmpcompradetaimp.ivaml,
cotizaciones.cotizacion, cotizaciones.tipo_moneda as idmoneda_select, tipo_moneda.descripcion as moneda_nombre
from tmpcompradeta 
inner join tmpcompras on tmpcompras.idtran = tmpcompradeta.idt
inner join insumos_lista on insumos_lista.idinsumo = tmpcompradeta.idprod
left Join tmpcompradetaimp on tmpcompradeta.idt=tmpcompradetaimp.idtran
and tmpcompradeta.idregcc=tmpcompradetaimp.idtrandet
LEFT JOIN cotizaciones on cotizaciones.idcot = tmpcompras.idcot
LEFT JOIN tipo_moneda on tipo_moneda.idtipo = cotizaciones.tipo_moneda 
where 
idregcc = $idunico
limit 1
";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idunico = intval($rs->fields['idregcc']);
$idtransaccion = intval($rs->fields['idt']);
$idtipoiva = intval($rs->fields['idtipoiva']);
$idinsumo = intval($rs->fields['idprod']);

$cotizacion = $rs->fields['cotizacion'];
$moneda_nombre = $rs->fields['moneda_nombre'];
$idmoneda_select = $rs->fields['idmoneda_select'];

$medida = $rs->fields['medida_nombre'];
$medida2 = $rs->fields['medida2'];
$medida3 = $rs->fields['medida3'];
$idmedida = $rs->fields['idmedida'];
$subtotal = $rs->fields['subtotal'];
$idmedida1 = $rs->fields['idmedida1'];
$idmedida2 = $rs->fields['idmedida2'];
$idmedida3 = $rs->fields['idmedida3'];
$cant_caja_edi = floatval($rs->fields['cant_caja_edi']);
$cant_medida2 = floatval($rs->fields['cant_medida2']); //cantidad de unidades por caja
$cant_medida3 = floatval($rs->fields['cant_medida3']); //cantidad de pallets por caja
$maneja_lote = intval($rs->fields['maneja_lote']);
$idmedida = floatval($rs->fields['idmedida']);
$idconcepto = floatval($rs->fields['idconcepto']);
$cantidad_medida_inicial = floatval($rs->fields['cantidad']);
$ivaml = floatval($rs->fields['ivaml']);
$cantidad_medida2_inicial = 0;
$cantidad_medida3_inicial = 0;
$cantidad_medida_edi_inicial = 0;
$costo_input = $subtotal / $cantidad_medida_inicial;

// echo $idmedida." ".$idmedida1." ".$idmedida2." ".$idmedida3." ".$cant_caja_edi." ".$cant_mededida2." ".$cant_medida3;exit;
if ($idmedida != $idmedida1 && $idmedida != $idmedida2 && $idmedida != $idmedida3 && $idmedida == $id_cajas_edi) {
    $cantidad_medida_edi_inicial = ($cantidad_medida_inicial / $cant_caja_edi); // cantidad de medida edi
    $costo_input = $subtotal / $cantidad_medida_edi_inicial;
}
// echo "idmedida:  ".$cantidad_medida_inicial;
// echo "cantmedida1:  ".$cant_medida1;
// echo "cantmedida2:  ".$cant_medida2;
// echo "cantmedida3:  ".$cant_medida3;
// exit;
if ($idmedida != $idmedida1 && $idmedida != $idmedida2 && $idmedida == $idmedida3 && $idmedida != $id_cajas_edi) {
    $cantidad_medida3_inicial = $cantidad_medida_inicial / ($cant_medida3 * $cant_medida2); // cantidad de pallets
    $cantidad_medida2_inicial = $cantidad_medida_inicial / ($cant_medida2);
    $costo_input = $subtotal / $cantidad_medida3_inicial;
}
if ($idmedida != $idmedida1 && $idmedida == $idmedida2 && $idmedida != $idmedida3 && $idmedida != $id_cajas_edi) {
    $cantidad_medida2_inicial = $cantidad_medida_inicial / $cant_medida2; // cantidad de cajas
    $cantidad_medida3_inicial = $cantidad_medida_inicial / ($cant_medida3 * $cant_medida2); // cantidad de pallets
    $costo_input = $subtotal / $cantidad_medida2_inicial;
}

if ($idunico == 0) {
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

function transformar_precio(nacional){
	var costo_unitario = $("#costo_unitario").val();
	if(nacional==false){
		var cotizacion = <?php echo $cotizacion ? $cotizacion : 1 ; ?>;
		cotizacion = parseFloat(cotizacion);
		costo_unitario = costo_unitario/cotizacion;
		$("#form_editar_articulo #costo_unitario").val(costo_unitario);
	}else{
		$("#form_editar_articulo #costo_unitario").val(costo_unitario);
	}
}

function editar_articulo_post(){
	var urlParams = new URLSearchParams(window.location.search);
  	var nombre = urlParams.get('id');
	var lote =$("#form_editar_articulo #lote").val();
	var vencimiento =$("#form_editar_articulo #vencimiento").val();
	var costo_unitario = parseFloat($("#form_editar_articulo #costo_unitario").val().replace(',', '.'));
	var id_moneda_elegida = $('#form_editar_articulo input[name="radio_moneda_form"]').filter(':checked').val();
	var nacional = $('#form_editar_articulo input[name="radio_moneda_form"]').filter(':checked').attr('data-hidden-nacional');
	var valido = "S";
	var cantidad = $("#form_editar_articulo #cantidad").val();
	var cantidad_ref =0;
	var medida_elegida = 1;
	var errores=""; 
	var iva_variable =$("#form_editar_articulo #iva_variable").val();


	<?php if ($preferencias_medidas_referenciales == "S" || $preferencias_medidas_edi == "S") { ?>
			//preferencias para medidas referenciales y edi
		var medida_elegida = $('input[name="radio_medida_form1"]').filter(':checked').val();
		var id_elegido= $('input[name="radio_medida_form1"]').filter(':checked').attr('data-hidden-value');
		console.log(id_elegido);
		if(medida_elegida){
			if(medida_elegida == 1 ){
				cantidad_elegida = cantidad;
			}
			else if(medida_elegida == 2 ){
				cantidad_elegida = parseInt($("#form_editar_articulo #bulto").val());
			}
			else if(medida_elegida == 3 ){
				cantidad_elegida = parseInt($("#form_editar_articulo #pallet").val());
			}
			else if(medida_elegida == 4 ){
				cantidad_elegida = parseInt($("#form_editar_articulo #bulto_edi").val());
			}
			else{
				cantidad_elegida = cantidad;
			}
			
			
				cantidad_ref=cantidad_elegida;
				var tipoUnidad =medida_elegida;
				var idmedida = id_elegido;
				
		}
		if(!medida_elegida){
			valido="N";
			errores=errores+'- Debe indicar a que unidad corresponde el precio de compra acordado en las opciones ubicadas por debajo del campo Precio compra *. \n<br>';		
		}
		<?php } else { ?>
			var tipoUnidad = 0;
			var idmedida = <?php echo $id_unidad?>;
			cantidad_ref= cantidad;
		<?php } ?>

		<?php if ($maneja_lote == "1") { ?>
		if(lote =="" && vencimiento ==""){
			valido="N";
			errores=errores+'- Debe indicar Vencimiento o Lote . \n<br>';
		}
		<?php } ?>




		////////////////////////////////////////////////
	<?php if ($preferencias_importacion == "S" && intval($idmoneda_select) != $id_moneda_nacional && intval($idmoneda_select) != 0) {
	    ?>
		if(!id_moneda_elegida ){
			valido="N";
			errores=errores+("Por favor, selecciona un tipo de moneda.<br>")
		}
	<?php } ?>
	
		if (cantidad==0 || cantidad == '' || isNaN(cantidad)){
			valido="N";
			errores=errores+'- Debe indicar cantidad a comprar. \n<br>';	
		}
		if(valido=="N"){
			alerta_error(errores); //
		}

	if(valido=="S"){
		if (nacional == "false"){
			var cotizacion = <?php echo $cotizacion ? $cotizacion : 1 ; ?>;
			costo_unitario = cotizacion*costo_unitario;

		}
		var parametros_array = {
			"idregcc"			: <?php echo $idunico?>, 
			"idinsumo"		: <?php echo $idinsumo?>, 
			"cantidad"		: cantidad, 
			"costo_unitario"	: costo_unitario, 
			"idtransaccion"	: <?php echo $idtransaccion?>, 
			"idtipoiva"		: <?php echo $idtipoiva?>,
			"vencimiento"		: vencimiento,
			"lote"			: lote,
			"idmedida"		: idmedida,
			"cantidad_ref"		: cantidad_ref,
			"iddeposito"			: $("#form_editar_articulo #iddeposito").val(),
			"iva_variable"	: iva_variable,
			"editar"	: 1
		
		};
		console.log(parametros_array);
			

			$.ajax({
					data:  parametros_array,
					url:   'verificar_carrito.php',
					type:  'post',
					beforeSend: function () {
						 
					},
					success:  function (response) {
						// console.log(response);
						  if(JSON.parse(response)["success"]==true){

							  errores=errores+"Un producto idéntico en términos de lote, vencimiento y costo ya se encuentra en el carrito. Si desea incrementar la cantidad, por favor, modifique la cantidad en carrito. <br/>";
							  alerta_error(errores);
						  }else{
							

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
									var string = "Lote:"+ lote + "-Vencimiento:" + vencimiento;
									$("#carritocompras").html(response);
									
								},
								error: function(jqXHR, textStatus, errorThrown) {
								errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
								}
							}).fail( function( jqXHR, textStatus, errorThrown ) {
								errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
							});


						  }
					}
			});

		}
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
	transformar_precio(false);
	$("#form_editar_articulo #radio_moneda_extranjera_form").click();
	$("#box_monedas_select_producto").css("display","none");


	
});

function verificar_cotizacion_moneda(primera_carga = false){
		var parametros = {
		"idmoneda"   :1
		};
		$.ajax({
			data:  parametros,
			url:   '../cotizaciones/cotizaciones_hoy.php',
			type:  'post',
			beforeSend: function () {
				
			},
			success:  function (response) {
				if(JSON.parse(response)['success']==false){
					alerta_error(JSON.parse(response)['error']);
					$("#idcot").css('border', '1px solid red');
					$('#idcot').prop('disabled', true);
				}else{
					var cotiza = JSON.parse(response)['cotiza'];
					if(cotiza == true){
						var idcot = JSON.parse(response)['idcot'];
						var cotizacion = JSON.parse(response)['cotizacion'];
						$('#idcot').html($('<option>', {
							value: idcot,
							text: cotizacion
						}));
						
					
						// Seleccionar opción
						$('#idcot').val(idcot);
						$('#idcotizacion').val(idcot);
						$('#idcot').prop('disabled', true);
						$("#radio_moneda_extranjera_form").click(); 
						if (primera_carga) {
							id_moneda_nacional = <?php echo $id_moneda_nacional;?>;
							if($("#idmoneda").val() !=  id_moneda_nacional){
								transformar_precio(false);
							}else{
								transformar_precio(true);
							}
						}
					}else{
						$('#idcot').html("");
						$('#idcot').prop('disabled', true);
						$("#idcot").css('border', '1px solid #ccc');
					}
					$("#box_monto_moneda").css("display", "none");
					
				
				}
			}
		});

		var id_moneda_nacional=<?php echo $id_moneda_nacional; ?>;
		if (id_moneda_nacional != idmoneda){
			$("#box_monto_moneda").css("display", "block");
			$("#radio_moneda_extranjera").val(idmoneda);
			$("#label_moneda_extranjera").html($("#idmoneda option:selected").text());
		}else{
			$("#box_monto_moneda").css("display", "none");
		}
	}


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

$("#form_editar_articulo  input").keydown(function(event) {
  // Verifica si la tecla presionada es "Enter"
  if (event.keyCode === 13) {
    // Cancela el comportamiento predeterminado del formulario
    event.preventDefault();
    // Envía el formulario
    // $(this).closest("form").submit();
	$("#submitEditarArticulo").click();
  }
});




//////////////////////////////////////////////////////////////////////////////////
	function cargarMedidaModal(){
		$('#form_editar_articulo #bulto').val(0);
		$('#form_editar_articulo #bulto_edi').val(0);
		$('#form_editar_articulo #pallet').val(0);
		var cant_unidad = $("#cant").val();


		var medida2_input = document.getElementById('bulto');
		var cant_medida2 = medida2_input.getAttribute('data-hidden-cant');

		var medida3_input = document.getElementById('pallet');
		var cant_medida3 = medida3_input.getAttribute('data-hidden-cant');

		if(  cant_medida2 != undefined && cant_unidad%cant_medida2 == 0 ){
			medida2_input.value = (cant_unidad/cant_medida2);
		}

		if(  cant_medida3 != undefined && (cant_unidad/cant_medida2)%cant_medida3 == 0 ){
			medida3_input.value = ((cant_unidad/cant_medida2)/cant_medida3);
		}
	}

	function cargarMedida2Modal(value,limpiar){
		var medida2_input = document.querySelector('#form_editar_articulo #bulto');
		var cant_medida = <?php echo $cant_medida2 ? $cant_medida2 : 1; ?>;
		$("#form_editar_articulo #cantidad").val(value*cant_medida)
		if(limpiar == true){
			$('#form_editar_articulo #pallet').val(0);
			$('#form_editar_articulo #bulto_edi').val(0);
		}
		var medida3_input = document.getElementById('pallet');
		var cant_medida3 = medida3_input.getAttribute('data-hidden-cant');

	
		if(  cant_medida3 != undefined && value%cant_medida3 == 0 ){
			medida3_input.value = (value/cant_medida3);
		}
	}

	function cargarMedida3Modal(value){
		var medida2_input = document.querySelector('#form_editar_articulo #pallet');
		var cant_medida = <?php echo $cant_medida3 ? $cant_medida3 : 1; ?>;
		$("#form_editar_articulo #bulto").val(value*cant_medida);
		$("#form_editar_articulo #bulto_edi").val(0);
		cargarMedida2Modal(value*cant_medida,false);
	}

	function cargarMedidaEDIModal(value){
		var medida2_input = document.querySelector('#form_editar_articulo #bulto_edi');
		var cant_medida = <?php echo $cant_caja_edi ? $cant_caja_edi : 1; ?>;
		$('#form_editar_articulo #bulto').val(0);
		$('#form_editar_articulo #pallet').val(0);
		$("#form_editar_articulo #cantidad").val(value*cant_medida)
	}

</script>
<style>
	.radios_box{
		border-radius: 8px;
		border:1px solid #c2c2c2;
		width: 30%;

	}

	.radio_div{
		background-color: #fff;
		padding: 5px;
		margin: 2px;
		border-radius: 8px;
		color: #6789A9;
		cursor: pointer;

		/* border:1px solid #c2c2c2; */
		
	}
	
	.radio_div:hover{
		background-color: #cecece;
		/* border:1px solid #c2c2c2; */
		color: black !important;
		opacity: 0.8;
		/* color: #486b7a !important; */
		

	}
	.radio_div input{
		width: 20%;
		cursor: pointer;
	}
	.even{
		background: #F7F7F7 !important;
	}

	.radio_div label{
		cursor: pointer;
	}
	.radio_div input:focus{
		border:1px solid hsl(210, 50%, 70%);
		cursor: pointer;
	}
</style>
<div class="clearfix"></div>
<br />
<div class="alert alert-danger alert-dismissible fade in hide" role="alert" id="boxErroresArticulosModal">
	<button type="button" class="close" onclick="cerrar_errores_articulos_modal(event)" aria-label="Close">
		<span aria-hidden="true">×</span>
	</button>
	<strong>Errores:</strong><br /><p id="erroresArticulosModal"></p>
</div>

<div id="form_editar_articulo">
		<div class="col-md-6 col-xs-12 form-group"  >
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Seleccionado</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="seleccionado" id="seleccionado" class="form-control" disabled value="<?php  echo htmlentities($rs->fields['descripcion']); ?>" />                      
			</div>
		</div>
		<?php if ($maneja_lote == 1) { ?>
			<div class="col-md-6 col-xs-12 form-group" >
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Lote</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
					<input type="text" name="lote" id="lote" value="<?php  if (isset($_POST['lote"'])) {
					    echo floatval($_POST['lote"']);
					} else {
					    echo str_replace("'", "", antixss($rs->fields['lote'])) ;
					}?>" placeholder="" class="form-control" />                    
				</div>
			</div>
	
			<div class="col-md-6 col-xs-12 form-group" >
				<label class="control-label col-md-3 col-sm-3 col-xs-12" >Vto</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
					<input type="date" name="vencimiento" id="vencimiento" onBlur="validar_fecha_articulo_vencimiento(this.value)" value="<?php  if (isset($_POST['vencimiento"'])) {
					    echo floatval($_POST['vencimiento"']);
					} else {
					    echo($rs->fields['vencimiento']);
					}?>" placeholder="" class="form-control" />                    
				</div>
			</div>
		<?php } ?>

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
    $value_selected = htmlentities($rs->fields['iddeposito_tmp']);
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

<div class="col-md-6 col-xs-12 form-group" >
	<label class="control-label col-md-3 col-sm-3 col-xs-12 ">Cantidad </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="text" onkeyup="cargarMedidaModal()" name="cantidad" id="cantidad" class="form-control" value="<?php  if (isset($_POST['cantidad_modif"'])) {
		    echo floatval($_POST['cantidad_modif"']);
		} else {
		    echo floatval($rs->fields['cantidad']);
		}?>"  />
		<span id="medidanombre" style="color: red;"></span>
	</div>
</div>

		
<?php if ($preferencias_medidas_referenciales == "S") { ?>

								
<!-- MEDIDAS 2  -->
<div class="col-md-6 col-xs-12 form-group" >
	<label class="control-label col-md-3 col-sm-3 col-xs-12">
		<a id="caja_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
			<span class="fa fa-plus"></span>
		</a>
		<?php if ($medida2) { ?>
			<div id="medida2"><?php echo $medida2; ?>:</div>
		<?php } else { ?>
			<div id="medida2">Medida2:</div>
			<?php } ?>
	</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php if ($cant_medida2 > 0) { ?>
		<input  class="form-control" onchange="cargarMedida2Modal(this.value,true)"  aria-describedby="cajaHelp" type="text" name="bulto" id="bulto" value="<?php echo $cantidad_medida2_inicial;?>" size="10" />	
		<?php } else { ?>
			<input disabled class="form-control" onchange="cargarMedida2Modal(this.value,true)"  aria-describedby="cajaHelp" type="text" name="bulto" id="bulto" value="0" size="10" />	
	<?php } ?>
		<small id="cajaHelp"  style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Medida2</strong> asignadas,favor agregar en insumos.</small>
	</div>
</div>


<!-- MEDIDAS INICIO 3 -->
<div class="col-md-6 col-xs-12 form-group" >
	<label class="control-label col-md-3 col-sm-3 col-xs-12">
		<a id="pallet_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
			<span class="fa fa-plus"></span>
		</a>	
		<?php if ($medida3) { ?>
			<div id="medida3"><?php echo $medida3; ?>:</div>
		<?php } else { ?>
			<div id="medida3">Medida3:</div>
			<?php } ?>
	</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
    //echo $cant_medida3; exit;
    if ($cant_medida3 > 0) { ?>
		<input aria-describedby="palletHelp" onchange="cargarMedida3Modal(this.value)"  type="text" class="form-control" name="pallet" id="pallet" value="<?php echo $cantidad_medida3_inicial = number_format($cantidad_medida3_inicial, 2, '.', '');?>" size="10" />
	<?php } else { ?>
		<input disabled aria-describedby="palletHelp" onchange="cargarMedida3Modal(this.value)"  type="text" class="form-control" name="pallet" id="pallet" value="0" size="10" />
	<?php } ?>
		<small id="palletHelp" style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Medida3</strong>  asignadas,favor agregar en insumos.</small>
	
	</div>
</div>

<?php } ?>
<?php if ($preferencias_medidas_edi == "S") { ?>

<!-- MEDIDAS EDI  -->
<div class="col-md-6 col-xs-12 form-group" style="display:none;" >
	<label class="control-label col-md-3 col-sm-3 col-xs-12">
		<a id="caja_edi_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
			<span class="fa fa-plus"></span>
		</a>
		<div id="medida4">Cajas EDI:</div>
	</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php if ($cant_caja_edi > 0) { ?>
		<input  class="form-control" onchange="cargarMedidaEDIModal(this.value)"  aria-describedby="cajaEdiHelp" type="text" name="bulto_edi" id="bulto_edi" value="<?php echo $cantidad_medida_edi_inicial;?>" size="10" />	
	<?php } else { ?>
		<input disabled class="form-control" onchange="cargarMedidaEDIModal(this.value)"  aria-describedby="cajaEdiHelp" type="text" name="bulto_edi" id="bulto_edi" value="0" size="10" />	
	<?php } ?>
		<small id="cajaEdiHelp"  style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Cant. Cajas EDI</strong> asignadas,favor agregar en insumos.</small>
	</div>
</div>
<!-- FIN DE MEDIDAS -->
<?php } ?>




		<div class="row" style="margin:0;">
			<div class="col-md-6 col-xs-12 form-group" >
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Precio</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
					<input type="text" name="costo_unitario" id="costo_unitario" value="<?php  if (isset($_POST['costo_modif"'])) {
					    echo floatval($_POST['costo_modif"']);
					} else {
					    echo floatval($costo_input);
					}?>" placeholder="" class="form-control" />
				</div>
			</div>
			<?php if ($idconcepto == $idconcepto_despacho || $idconcepto == $idconcepto_flete) { ?>
				<div class="col-md-6 col-xs-12 form-group" >
				<label class="control-label col-md-3 col-sm-3 col-xs-12">IVA</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
					<input type="text" name="iva_variable" id="iva_variable" value="<?php  if (isset($_POST['iva_variable"'])) {
					    echo floatval($_POST['iva_variable"']);
					} else {
					    echo floatval($ivaml);
					}?>" placeholder="" class="form-control" />
				</div>
			</div>
			<?php } ?>
			<?php if (($moneda_nombre != null || $moneda_nombre != "") && $idmoneda_select != $id_moneda_nacional && $preferencias_importacion == "S") { ?>
				<div class="col-md-6" id="box_monedas_select_producto">
					<div class="row" style="margin:0px;">
						<div class="form-group">
							<label class="control-label col-md-3 col-sm-3 col-xs-12">Moneda</label>
							<div style="display: flex;justify-content: space-around;">
								<div class="form-check " id="box_radio_moneda_extranjera_form" class="col-md-6" style="display:inline-block;">
									<input class="form-check-input" data-hidden-nacional="false"  value="<?php echo $idmoneda_select; ?>" type="radio" name="radio_moneda_form" id="radio_moneda_extranjera_form" >
									<label class="form-check-label" id="label_moneda_extranjera" for="radio_moneda_extranjera_form">
										<?php echo $moneda_nombre; ?>
									</label>
								</div>
								<div class="form-check " id="box_radio_moneda_nacional_form" class="col-md-6" style="display:inline-block;">
									<input  checked class="form-check-input" data-hidden-nacional="true"  value="<?php echo $id_moneda_nacional; ?>" type="radio" name="radio_moneda_form" id="radio_moneda_nacional_form" >
									<label class="form-check-label" id="label_moneda_nacional" for="radio_moneda_nacional_form">
									<?php echo $nombre_moneda_nacional; ?>
									</label>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>


		<?php if ($preferencias_medidas_referenciales == "S" || $preferencias_medidas_edi == "S") { ?>
							<!-- //////////////////////////////////////////////////////////////////////// -->
							<!-- //////////radio medida comienza////////////////////// -->

							<div class="col-md-12 col-xs-12 form-group" >
								<label class="control-label col-md-3 col-sm-3 col-xs-12">Medida de Compra</label>
								<div class="col-md-9 col-sm-9 col-xs-12">
								<div class="row radios_box" style="display: flex;flex-direction: column;">
									<div onclick="click_radio(this)" class="form-check radio_div" id="box_radio_unidad">
											<input class="form-check-input"  <?php if ($idmedida == $idmedida1) { ?>checked<?php } ?>  data-hidden-value=<?php echo $idmedida1; ?> value="1" type="radio" name="radio_medida_form1" id="radio_unidad_form1" >
											<label class="form-check-label" id="label_unidad" for="radio_unidad_form1">
												UNIDAD
											</label>
										</div>
										<?php if ($cant_medida2 > 0) { ?>
											<div onclick="click_radio(this)" class="form-check radio_div" id="box_radio_bulto" >
												<input class="form-check-input" <?php if ($idmedida == $idmedida2) { ?>checked<?php } ?>   data-hidden-value=<?php echo $idmedida2; ?> value="2" type="radio" name="radio_medida_form1" id="radio_bulto_form1" >
												<label class="form-check-label" id="label_bulto" for="radio_bulto_form1">
													CAJA
												</label>
											</div>
										<?php } ?>
										

										<?php if ($cant_medida3 > 0) { ?>
											<div onclick="click_radio(this)" class="form-check radio_div" id="box_radio_pallet" >
												<input class="form-check-input" <?php if ($idmedida == $idmedida3) { ?>checked<?php } ?>   data-hidden-value=<?php echo $idmedida3; ?> value="3" type="radio" name="radio_medida_form1" id="radio_pallet_form1" >
												<label class="form-check-label" id="label_pallet" for="radio_pallet_form1">
													PALLET
												</label>
											</div>
										<?php } ?>

										<?php if ($cant_caja_edi > 0) { ?>
										<div onclick="click_radio(this)" class="form-check radio_div" id="box_radio_edi" >
											<input class="form-check-input" <?php if ($idmedida == $id_cajas_edi) {?> checked <?php } ?> data-hidden-value=<?php echo $id_cajas_edi; ?> value="4" type="radio" name="radio_medida_form1" id="radio_edi_form1" >
											<label class="form-check-label"  id="label_EDI" for="radio_edi_form1">
												CAJA EDI
											</label>
										</div>
										<?php }?>

									</div>
								</div>
							</div>
							
							<!-- ////////////////////////////////////////////////////////////////////////// -->
							<!-- ////////////////////////////radio medida fin ///////////////////////////// -->
							<?php } ?>

		



		<div class="form-group">
			<div class="col-md-12 col-sm-12 col-xs-12 col-md-offset-5">
				<button type="submit" id="submitEditarArticulo" class="btn btn-success" onclick="editar_articulo_post();" ><span class="fa fa-check-square-o"></span> Guardar</button>
				<button type="button" class="btn btn-primary" onMouseUp="cerrar_pop();"><span class="fa fa-ban"></span> Cancelar</button>
			</div>
		</div>
		<div class="clearfix form-group"></div>
		<br />
	</div>





