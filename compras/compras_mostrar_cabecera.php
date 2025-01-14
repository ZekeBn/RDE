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

require_once("../compras_ordenes/preferencias_compras_ordenes.php");
require_once("./preferencias_compras.php");


$consulta = "SELECT idtipo_origen FROM tipo_origen WHERE  UPPER(tipo)='IMPORTACION'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_tipo_origen_importacion = intval($rs_guarani->fields["idtipo_origen"]);
if ($id_tipo_origen_importacion == 0) {
    $errores = "- Por favor cree el Origen IMPORTACON.<br />";
}

$consulta = "SELECT idtipo_origen FROM tipo_origen WHERE  UPPER(tipo)='LOCAL'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_tipo_origen_local = intval($rs_guarani->fields["idtipo_origen"]);
if ($id_tipo_origen_local == 0) {
    $errores = "- Por favor cree el Origen IMPORTACON.<br />";
}

//buscando moneda nacional
$consulta = "SELECT tipo_moneda.idtipo, tipo_moneda.descripcion as nombre FROM tipo_moneda WHERE nacional='S'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];
$nombre_moneda_nacional = $rs_guarani->fields["nombre"];

$idtran = intval($_POST['idtransaccion']);

if ($idtran == 0) {
    $idtran = intval($_GET['idtransaccion']);

}

$consulta = "
SELECT tmpcompras.*,
cotizaciones.cotizacion, tmpcompras.moneda as idmoneda_select, tipo_moneda.descripcion as moneda_nombre
FROM tmpcompras
LEFT JOIN cotizaciones on cotizaciones.idcot = tmpcompras.idcot
LEFT JOIN tipo_moneda on tipo_moneda.idtipo = cotizaciones.tipo_moneda 
where
idtran  = $idtran
and tmpcompras.estado = 1
";
$rstran = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//echo $consulta;
$idproveedor = intval($rstran->fields['proveedor']);
$idtran = intval($rstran->fields['idtran']);
$cotizacion = $rstran->fields['cotizacion'];
$idcot = intval($rstran->fields['idcot']);
$moneda_nombre = $rstran->fields['moneda_nombre'];
$idmoneda_select = intval($rstran->fields['idmoneda_select']);
if ($idmoneda_select == 0) {
    $idmoneda_select = $id_moneda_nacional;
}
if ($idtran == 0) {
    header("location: compras.php");
    exit;
}
//echo $idproveedor;exit;
$consulta = "
select tipocompra from preferencias limit 1
";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tipocompra = intval($rspref->fields['tipocompra']);

$consulta = "
select obliga_cdc, tipocomprobante_def from preferencias_compras limit 1
";
$rsprefcompra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$obliga_tipocomprobante = 'S';
$obliga_cdc = trim($rsprefcompra->fields['obliga_cdc']);
$tipocomprobante_def = trim($rsprefcompra->fields['tipocomprobante_def']);


// si se selecciono proveedor
if ($idproveedor > 0) {

    // busca si existe en la bd
    $consulta = "
	select * from proveedores where estado = 1 and idproveedor = $idproveedor
	";
    $rsprov = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $incrementa = $rsprov->fields['incrementa'];
    $diasvence = $rsprov->fields['diasvence'];
    // si no existe redirecciona
    if (intval($rsprov->fields['idproveedor']) == 0) {
        header("location: tmpcompras_add.php");
        exit;
    }
    // si es incremental
    if ($incrementa == 'S') {
        $consulta = "
		SELECT fact_num 
		FROM facturas_proveedores 
		where 
		estado <> 6
		 and id_proveedor = $idproveedor
		 order by fact_num desc
		 limit 1
		";
        $rsfac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $facturacompra = "001-001-".agregacero($rsfac->fields['fact_num'] + 1, 7);
        $tipocomprobante_def = 1;
    }

    // actualiza numeracion proveedor
    $consulta = "
	update facturas_proveedores 
	set 
	fact_num = CAST(substring(factura_numero from 7 for 9) as UNSIGNED)
	where 
	fact_num is null
	and id_proveedor=$idproveedor;
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // habilita compras
    $consulta = "
	select * from compras_habilita where estado = 1 order by idcomprahab asc limit 1
	";
    $rscomhab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $fechadesde_habilita = $rscomhab->fields['fechadesde'];
    $fechahasta_habilita = $rscomhab->fields['fechahasta'];
    $fechadesde_txt = date("d/m/Y", strtotime($rscomhab->fields['fechadesde']));
    $fechahasta_txt = date("d/m/Y", strtotime($rscomhab->fields['fechahasta']));

    // buscar en la base el timbrado
    $consulta = "
	Select * 
	from compras 
	where 
	idproveedor=$idproveedor
	and estado=1 
	order by fechacompra desc 
	limit 1
	";
    //echo $consulta;exit;
    $rstimb = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $timbrado_bd = $rstimb->fields['timbrado'];
    $vto_timbrado_bd = $rstimb->fields['vto_timbrado'];
    //echo $timbrado_bd;exit;


}


$consulta = "
SELECT * 
FROM tipo_comprobante
where 
estado = 1
and vence_timbrado = 'S'
";

$rstipcompv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tipos_comprobantes_vence = "";
while (!$rstipcompv->EOF) {
    $tipos_comprobantes_vence .= $rstipcompv->fields['idtipocomprobante'].',';
    $rstipcompv->MoveNext();
}
$tipos_comprobantes_vence = substr($tipos_comprobantes_vence, 0, -1);
$tipos_comprobantes_vence_ar = explode(',', $tipos_comprobantes_vence);

function limpiacdc($cdc)
{
    $cdc = trim($cdc);
    $cdc = str_replace(' ', '', $cdc);
    $cdc = htmlentities($cdc);
    $cdc = solonumeros($cdc);
    return $cdc;
}


?>
<script>
	function regresar_cabecera(){
		$("#form_cabecera_compras").css("display","block");
		$("#cotizacion_tabla").css("display","none");
	}
	function buscar_cotizacion_moneda(){
		var idmoneda= $("#idmoneda").val();
		var parametros = {
		"idmoneda"   : idmoneda
		};
		var id_moneda_nacional = <?php echo $id_moneda_nacional; ?>;
		if ( id_moneda_nacional != idmoneda){
			$.ajax({
				data:  parametros,
				url:   './buscar_cotizaciones_modal.php',
				type:  'post',
				beforeSend: function () {
					
				},
				success:  function (response) {
						// alerta_modal("Cotizaciones disponibles",response);
						$("#form_cabecera_compras").css("display","none");
						$("#cotizacion_tabla").css("display","block");
						$("#cotizacion_tabla_box").html(response);
				}
			});
		}

	}
	function moneda_div(valor){
		var multimoneda_local = <?php echo $multimoneda_local == "S" ? "'$multimoneda_local'" : "'N'" ?>;
		
		if(valor == 1){
			if(multimoneda_local == "S"){
				$("#box_cotizacion").css("display", "block");
				$("#box_monto_moneda").css("display", "none");
			}else{
				$("#box_cotizacion").css("display", "none");
				$("#box_monto_moneda").css("display", "none");
			}
			ocultar_tipo_comprobante(false);   
		}
		if(valor == 2 ){
			$("#box_cotizacion").css("display", "block");
			$("#box_monto_moneda").css("display", "block");
			verificar_cotizacion_moneda();
			ocultar_tipo_comprobante(true);
		}

	}
	function ocultar_tipo_comprobante(ocultar){
		if(ocultar) {
			$("#box_tipo_comprobante").css("display","none");
			$("#idtipocomprobante").val("");
		}else{
			$("#box_tipo_comprobante").css("display","block");
		}
	}
	function transformar_precio(nacional){
		var monto_factura = $("#form_cabecera_compras #monto_factura").val();
		if(monto_factura ==NaN || monto_factura == undefined || monto_factura == "NaN"){
			monto_factura = <?php echo floatval($rstran->fields['monto_factura']); ?>;
		}
		if(nacional==false){
			var cotizacion = ($("#idcot option:selected").text());
			cotizacion = parseFloat(cotizacion);
			monto_factura = parseFloat(monto_factura);
			if(cotizacion != 0){
				monto_factura = monto_factura/cotizacion;
				$("#form_cabecera_compras #monto_factura").val(monto_factura.toFixed(7));
			}
		}else{
			$("#form_cabecera_compras #monto_factura").val(monto_factura);
		}
	}
	if ($('#errores').is(':empty')) {
		$('#boxErrores').hide();
	}
  function autocompletar_monto(){
	var urlParams = new URLSearchParams(window.location.search);

	var parametros_array = {
			"idtransaccion"		: urlParams.get('id')
	  };
	//   console.log(parametros_array);
	$.ajax({		  
	  data:  parametros_array,
	  url:   'compras_mostrar_cabeza_search_monto.php',
	  type:  'post',
	  cache: false,
	  timeout: 3000,  // I chose 3 secs for kicks: 5000
	  crossDomain: true,
	  beforeSend: function () {
	  },
	  success:  function (response) {

		///////////////////////
		// console.log(response);
				if(IsJsonString(response)){
					var obj = jQuery.parseJSON(response);
					console.log (obj.monto_factura);
					if(obj.success == true){
						$("#monto_factura").val(obj.monto_factura);
						var idmoneda = $("#idmoneda").val();
						var id_moneda_nacional = <?php echo $id_moneda_nacional;?>;
						
						if(idmoneda != undefined) {
							if(idmoneda !=  id_moneda_nacional){
								  transformar_precio(false);
							  }else{
								  transformar_precio(true);
							}
						}
					}
				}
			/////////////////////////////



	  },
	  error: function(jqXHR, textStatus, errorThrown) {
		errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
	  }
	}).fail( function( jqXHR, textStatus, errorThrown ) {
	  errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
	});
  }

  
	function IsJsonString(str) {
		try {
			JSON.parse(str);
		} catch (e) {
			return false;
		}
		return true;
	}
	function update_cabecera_compra(){
		var urlParams = new URLSearchParams(window.location.search);
		var idtipo_origen_valor = $("#form_cabecera_compras #idtipo_origen").val();
		var valido = "S"; 
		var error="";
		var idmoneda=$("#form_cabecera_compras #idmoneda").val();
		var id_moneda_elegida = $('#form_cabecera_compras input[name="radio_moneda_form"]').filter(':checked').val();
		var nacional = $('#form_cabecera_compras input[name="radio_moneda_form"]').filter(':checked').attr('data-hidden-nacional');
		if($("#form_cabecera_compras #fecha_compra").val() == ""){
			valido = "N";
			error +=("- La Fecha compra no puede estar vacia.<br>");
		}
		
		if($("#form_cabecera_compras #idtipocompra").val() == ""){
			valido = "N";
			error +=("- Debe indicar si la compra fue al contado o a credito.<br>");
		}
		if( ( $("#form_cabecera_compras #timbrado").val() == ""|| $("#form_cabecera_compras #timbrado").val() == 0 ) && $("#form_cabecera_compras #idtipo_origen").val() != <?php echo $id_tipo_origen_importacion?>){
			valido = "N";
			var a = $("#form_cabecera_compras #idtipo_origen").val();
			error +=("- El campo timbrado no puede ser cero o nulo.<br>");
		}
		if($("#form_cabecera_compras #facturacompra").val() == ""|| $("#form_cabecera_compras #facturacompra").val() == 0){
			valido = "N";
			error +=("- El campo Factura Nro * no puede ser cero o nulo.<br>");
		}
		if($('#form_cabecera_compras #idproveedor').val() == ""){
			valido = "N";
			error +=("- Debe indicar el Proveedor *.<br>");
		}
		if(!id_moneda_elegida && idtipo_origen_valor == <?php echo $id_tipo_origen_importacion; ?> ){
			valido="N";
			error +=("- Por favor, selecciona un tipo de moneda.<br>");
		}
		
		if(idtipo_origen_valor == <?php echo $id_tipo_origen_importacion; ?> && idmoneda == "" ){
			valido="N";
			error +=("- Por favor, selecciona una moneda.<br>");
		}
		if (valido == "N"){
			alerta_error(error);
		}
		var monto_factura = parseFloat($("#form_cabecera_compras #monto_factura").val().replace(',', '.'));
		if (nacional == "false"){
			var cotizacion = <?php echo floatval($cotizacion); ?>;
			cotizacion = cotizacion ==0 ? parseFloat($('#idcot option:selected').text()): cotizacion;
			monto_factura = cotizacion*monto_factura;
		}


		
		var nombre = urlParams.get('id');
		if(valido == "S"){

			var parametros_array = {
					"idproveedor"		: $('#form_cabecera_compras #idproveedor').val(),
					"vto_timbrado"		: $('#form_cabecera_compras #vto_timbrado').val(),
					"fecha_compra"		: $("#form_cabecera_compras #fecha_compra").val(), 
					"facturacompra"		: $("#form_cabecera_compras #facturacompra").val(), 
					"monto_factura"		: monto_factura, 
					"idtipocomprobante"	: $("#form_cabecera_compras #idtipocomprobante").val(), 
					"cdc"				: $("#form_cabecera_compras #cdc").val(), 
					"timbrado"			: $("#form_cabecera_compras #timbrado").val(), 
					"ocnum"				: $("#form_cabecera_compras #ocnum").val(), 
					"sucursal"			: $("#form_cabecera_compras #sucursal").val(), 
					"idtipocompra"		: $("#form_cabecera_compras #idtipocompra").val(),
					"descripcion"		: $("#form_cabecera_compras #descripcion").val(),
					"vencimiento"		: $("#form_cabecera_compras #vencimiento").val(),
					"idtipo_origen"		: idtipo_origen_valor,
					"idcot"				: $("#form_cabecera_compras #idcot").val(),
					"idmoneda"			: idmoneda,
					"idtransaccion"		: urlParams.get('id'),
					"idusu"				: <?php echo $idusu?>,
					"update_cabecera"		: 1
			};
			//   console.log(parametros_array);
			$.ajax({		  
			data:  parametros_array,
			url:   'compras_carrito.php',
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 5000
			crossDomain: true,
			beforeSend: function () {
				$("#submit1").text('Cargando...');
			},
			success:  function (response) {
				// console.log(response);
				cerrar_pop();
				$("#carritocompras").html(response);
				window.location.reload();
			},
			error: function(jqXHR, textStatus, errorThrown) {
				errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
			}
			}).fail( function( jqXHR, textStatus, errorThrown ) {
			errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
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
		$('#boxErroresCabecera').on('closed.bs.alert', function () {
			$('#boxErroresCabecera').removeClass('show');
			$('#boxErroresCabecera').addClass('hide');
		});
		
		<?php if ($idproveedor && $preferencias_importacion == "S") { ?>
			verificar_cotizacion_moneda(true);

		<?php } ?>

		moneda_div($('#idtipo_origen').val());
		

	});


		function verificar_proveedor_importacion(){
			var selectElement = document.getElementById('idproveedor');
			var selectedOption = selectElement.options[selectElement.selectedIndex];
			var idmoneda = selectedOption.getAttribute('data-hidden-value');
			var idtipo_origen = selectedOption.getAttribute('data-hidden-value2');
			if (parseInt(idmoneda) ==0){
				idmoneda = <?php echo $id_moneda_nacional;?>
			}
			$("#idtipo_origen").val(idtipo_origen);
			$("#idmoneda").val(idmoneda);



			verificar_cotizacion_moneda();
		}
		function verificar_cotizacion_moneda(primera_carga = false){
			const idmoneda = $("#idmoneda").val();
			var parametros = {
			"idmoneda"   : idmoneda,
			"fecha_compra" : $("#fecha_compra").val(),
			"idcot"	: <?php echo $idcot; ?>
			};
			// console.log(parametros);
			$.ajax({
				data:  parametros,
				url:   '../cotizaciones/cotizaciones_hoy.php',
				type:  'post',
				beforeSend: function () {
					
				},
				success:  function (response) {
					// console.log(response);
					if(JSON.parse(response)['success']==false){
						alerta_error(JSON.parse(response)['error']);
						$("#idcot").css('border', '1px solid red');
						$('#idcot').prop('readonly', true);
					}else{
						
						var cotiza = JSON.parse(response)['cotiza'];
						if(cotiza == true){
							var idcot = JSON.parse(response)['idcot'];
							var cotizacion = JSON.parse(response)['cotizacion'];
							$('#idcot').html($('<option>', {
								value: idcot,
								text: cotizacion
							}));
							$("#cotizacion_fecha_text").html("Fecha Cotizacion: "+ JSON.parse(response)['fecha_format']);
						
							// Seleccionar opción
							$('#idcot').val(idcot);
							$('#idcotizacion').val(idcot);
							$('#idcot').prop('readonly', true);
							$("#radio_moneda_extranjera_form").click();
							// console.log("primera carga ",primera_carga);
							if (primera_carga == true) {
								// console.log("entre  ");
								id_moneda_nacional = <?php echo $id_moneda_nacional;?>;
								if(idmoneda !=  id_moneda_nacional){
									transformar_precio(false);
								}
							}
						}else{
							$('#idcot').html("");
							$('#idcot').prop('readonly', true);
							$("#idcot").css('border', '1px solid #ccc');
						}
						$("#box_monto_moneda").css("display", "none");
						
					
					}

					$("#idcot").css('background', '#EEE');
					$('#idcot').on('mousedown', function(event) {
						// Evitar que el select se abra
						event.preventDefault();
					});


				}
			});
			
			var id_moneda_nacional=<?php echo $id_moneda_nacional; ?>;
			if (id_moneda_nacional != idmoneda){
				$("#box_monto_moneda").css("display", "none");
				$("#radio_moneda_extranjera").val(idmoneda);
				$("#form_cabecera_compras #label_moneda_extranjera").html($("#idmoneda option:selected").text());
			}else{
				$("#box_monto_moneda").css("display", "none");
				// $('#radio_moneda_extranjera').data('hidden-nacional', true);
				$("#radio_moneda_nacional_form").click();
			}
		}


	function cerrar_errores_cabecera(event){
		event.preventDefault();
		$('#boxErroresCabecera').removeClass('show');
		$('#boxErroresCabecera').addClass('hide');
	}
	function validar_fecha(fecha){
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
		//alert(f1); 
		//alert(ano+'-'+mes+'-'+dia);
		var f2 = new Date(<?php echo date("Y"); ?>, <?php echo date("m") - 1; ?>, <?php echo date("d"); ?>);
		var fdesde = new Date(<?php echo date("Y", strtotime($fechadesde_habilita)); ?>, <?php echo date("m", strtotime($fechadesde_habilita)) - 1; ?>, <?php echo date("d", strtotime($fechadesde_habilita)); ?>);
		var fhasta = new Date(<?php echo date("Y", strtotime($fechahasta_habilita)); ?>, <?php echo date("m", strtotime($fechahasta_habilita)) - 1; ?>, <?php echo date("d", strtotime($fechahasta_habilita)); ?>);
		// fecha no puede estar en el futuro
		if (f1 > f2){
			valido = 'N';
			errores = 'La Fecha de compra ('+dia+'/'+meshtml+'/'+ano+') no puede estar en el futuro.';
		}
		// la fecha no puede ser menor a la fecha desde
		if(f1 < fdesde){
			valido = 'N';
			errores = 'La Fecha de compra ('+dia+'/'+meshtml+'/'+ano+') no puede ser menor al periodo habilitado entre: <?php echo $fechadesde_txt; ?> y <?php echo $fechahasta_txt; ?>.';	
		}
		// la fecha no puede ser mayor a la fecha hasta
		if(f1 > fhasta){
			valido = 'N';	
			errores = 'La Fecha de compra ('+dia+'/'+meshtml+'/'+ano+') no puede ser mayor al periodo habilitado entre: <?php echo $fechadesde_txt; ?> y <?php echo $fechahasta_txt; ?>.';	
		}
		if(valido == 'N'){
			//alert(f1); 
			//alert(f2); 
			//alert(fdesde); 
			//alert(fhasta); 
			//alerta_modal('Incorrecto','Fecha de compra ('+dia+'/'+meshtml+'/'+ano+') incorrecta, habilitado entre: <?php echo $fechadesde_txt; ?> y <?php echo $fechahasta_txt; ?> y no pude ser mayor a hoy <?php echo date("d/m/Y", strtotime($ahora)); ?>.');
			alerta_error(errores);
			$("#form_cabecera_compras #fecha_compra").val('');
		}else{
			//cargavto();
		}

		
	}
	function validar_fecha_timbrado(fecha){
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
		$("#erroresCabecera").html(error);
		$('#boxErroresCabecera').addClass('show');
	}
	function tipo_comprobantes(tipo){
		// CDC si es factura electronica
		if(tipo == 2){
			$("#vencimiento_box").show();
			var fecha_compra = $("#fecha_compra").val();
			// si se indico fecha de compra
			if(fecha_compra != ''){
				var parametros = {
						"idproveedor"    : <?php echo $idproveedor ?>,
						"fechacompra"    : fecha_compra,
						"tipocompra"     : tipo
				};
				$.ajax({
					data:  parametros,
					url:   'cargavto_new.php',
					type:  'post',
					beforeSend: function () {
						$("#vencimiento").val('Cargando...');
					},
					success:  function (response) {
						//alert(response);
						if(IsJsonString(response)){
							var obj = jQuery.parseJSON(response);
							$("#vencimiento").val(obj.vencimiento);
						}else{
							alert(response);	
						}
					}
				});
			}
			
		}else{
			$("#vencimiento_box").hide();
		}
	}

	$("#form_cabecera_compras  input").keydown(function(event) {
	// Verifica si la tecla presionada es "Enter"
	if (event.keyCode === 13) {
		// Cancela el comportamiento predeterminado del formulario
		event.preventDefault();
		// Envía el formulario
		// $(this).closest("form").submit();
		$("#submit1").click();
	}
	});

</script>
<div class="clearfix"></div>
<br />

<div class="alert alert-danger alert-dismissible fade in hide" role="alert" id="boxErroresCabecera">
	<button type="button" class="close" onclick="cerrar_errores_cabecera(event)" aria-label="Close">
		<span aria-hidden="true">×</span>
	</button>
	<strong>Errores:</strong><br /><p id="erroresCabecera"></p>
</div>
<div id="form_cabecera_compras">

	<div class="col-md-12 col-sm-12  " >
		<h2 style="font-size: 1.3rem;">Datos del proveedor</h2>
		<hr>

		<div class="col-md-6 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">(1) Proveedor *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<?php


                // consulta
                $consulta = "
				SELECT idproveedor, nombre, idmoneda, idtipo_origen
				FROM proveedores
				where
				estado = 1
				order by nombre asc
				";

// valor seleccionado
if (isset($_POST['idproveedor'])) {
    $value_selected = htmlentities($_POST['idproveedor']);
} else {
    $value_selected = htmlentities($idproveedor);
}

if ($idproveedor > 0) {
    $add = " ";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idproveedor',
    'id_campo' => 'idproveedor',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idproveedor',

    'value_selected' => $value_selected,
    'data_hidden' => 'idmoneda',
    'data_hidden2' => 'idtipo_origen',

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' onchange="actualizar_cabecera(this.value);" '.$add,
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
			</div>
		</div>

		<?php if ($preferencias_importacion == "S") { ?>
			<div class="col-md-6 col-xs-12 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Origen*</label>
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
		        $value_selected = $rstran->fields['idtipo_origen'];
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
		        'acciones' => ' required="required" onchange="moneda_div(this.value)" ',
		        'autosel_1registro' => 'N'

		    ];

		    // construye campo
		    echo campo_select($consulta, $parametros_array);

		    ?>
				</div>
			</div>
		<?php } ?>


	</div>


	<?php if ($preferencias_importacion == "S") { ?>
		<div class="col-md-12 col-sm-12  " id="box_cotizacion" >
			<h2 style="font-size: 1.3rem;">Cotizacion</h2>
			<hr>

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
	        $value_selected = intval($rstran->fields['idmoneda_select']);
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
	        'acciones' => '  onchange="verificar_cotizacion_moneda()" "'.$add,
	        'autosel_1registro' => 'N'

	    ];

	    // construye campo
	    echo campo_select($consulta, $parametros_array);

	    ?>
				</div>
			</div>

			<div class="col-md-6 col-sm-12 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Cotizacion </label>
				<div class="col-md-9 col-sm-9 col-xs-12">
					<select  name="idcot" id="idcot" class="form-control" onclick="buscar_cotizacion_moneda()">
					</select>
					<small id="cotizacion_fecha_text"></small>
					<input type="hidden" name="idcotizacion" id="idcotizacion" value="" />   
				</div>
			</div>


		</div>
	<?php } ?>

	<?php if (intval($idproveedor) > 0) { ?>
		<div class="col-md-12 col-sm-12" >
			<h2 style="font-size: 1.3rem;">Datos Factura</h2>
			<hr>
		</div>

	<?php } ?>

	<?php if (intval($idproveedor) > 0) { ?>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">(2) Fecha compra *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="date" name="fecha_compra" id="fecha_compra" onBlur="validar_fecha(this.value);" value="<?php  if (isset($_POST['fecha_compra']) && $_POST['fecha_compra'] != "0000-00-00") {
				    echo htmlentities($_POST['fecha_compra']);
				} else {
				    echo htmlentities($rstran->fields['fecha_compra']);
				}?>" placeholder="Fecha compra" class="form-control" required onBlur="actualizar_cabecera();" />                    
			</div>
		</div>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">(3) Factura Nro *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="facturacompra" id="facturacompra" value="<?php  if (isset($_POST['facturacompra'])) {
			    echo htmlentities($_POST['facturacompra']);
			} else {
			    echo htmlentities($rstran->fields['facturacompra_guion']);
			}?>" placeholder="Ej: 001-001-0000123" class="form-control" onchange="actualizar_cabecera();" required <?php if ($incrementa == 'S') { ?> readonly<?php } ?> />                    
			</div>
		</div>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0)" class="btn btn-sm btn-default" title="Monto Total" data-toggle="tooltip" data-placement="right"  data-original-title="Monto Total" onclick="autocompletar_monto()"><span class="fa fa-search"></span></a> (4) Monto factura *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="monto_factura" id="monto_factura" value="<?php  if (isset($_POST['monto_factura'])) {
			    echo floatval($_POST['monto_factura']);
			} else {
			    echo floatval($rstran->fields['monto_factura']);
			}?>" placeholder="Monto factura" class="form-control"  required onchange="actualizar_cabecera();"/>                    
			</div>
		</div>

		<div class="col-md-6" id="box_monto_moneda">
			<?php if ($preferencias_importacion == "S") { ?>
				<div class="row" style="margin:0px;">
					<div class="form-group">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">Monto Moneda</label>
						<div style="display: flex;justify-content: space-around;">
							<div class="form-check " id="box_radio_moneda_extranjera_form" class="col-md-6" style="display:inline-block;">
								<input class="form-check-input" data-hidden-nacional="false" value="<?php echo $idmoneda_select; ?>" type="radio" name="radio_moneda_form" id="radio_moneda_extranjera_form" >
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
			<?php } ?>
		</div>

		


	<div class="col-md-6 col-sm-6 form-group" id="box_tipo_comprobante">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">(5) Tipo Comp. *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<?php
            if ($incrementa == 'S') {
                $readonly_tipocomp = " readonly ";
                $whereadd_tipocom = " and idtipocomprobante = 1 ";
            } else {
                $readonly_tipocomp = "";
                $whereadd_tipocom = "";
            }

	    // consulta
	    $consulta = "
			SELECT idtipocomprobante, tipocomprobante
			FROM tipo_comprobante
			where
			estado = 1
			$whereadd_tipocom
			order by tipocomprobante asc
			";

	    // valor seleccionado
	    if (isset($_POST['idtipocomprobante'])) {
	        $value_selected = htmlentities($_POST['idtipocomprobante']);
	    } else {
	        $value_selected = htmlentities($rstran->fields['idtipocomprobante']);
	        if (intval($rstran->fields['idtipocomprobante']) == 0) {
	            $value_selected = $tipocomprobante_def;
	        }
	    }

	    // parametros
	    $parametros_array = [
	        'nombre_campo' => 'idtipocomprobante',
	        'id_campo' => 'idtipocomprobante',

	        'nombre_campo_bd' => 'tipocomprobante',
	        'id_campo_bd' => 'idtipocomprobante',

	        'value_selected' => $value_selected,

	        'pricampo_name' => 'Seleccionar...',
	        'pricampo_value' => '',
	        'style_input' => 'class="form-control"',
	        'acciones' => '  required="required" onchange="actualizar_cabecera(this.value);" '.$readonly_tipocomp,
	        'autosel_1registro' => 'S'

	    ];

	    // construye campo
	    echo campo_select($consulta, $parametros_array);

	    ?>                
		</div>
	</div>

	<?php
	    if ($obliga_cdc == 'S') {
	        $cdc_req = '';
	        $cdc_ast = "*";
	    } else {
	        $cdc_req = '';
	        $cdc_ast = "";
	    }


	    // si envio  post
	    if (isset($_POST['idtipocomprobante'])) {
	        // si es electronica
	        if (intval($_POST['idtipocomprobante']) == 4) {
	            $muesta_cdc = 'style="display:display;"';
	        } else {
	            $muesta_cdc = 'style="display:none;"';
	        }
	    } else {
	        // si es electronica
	        if (intval($rstran->fields['idtipocomprobante']) == 4) {
	            $muesta_cdc = 'style="display:display;"';
	        } else {
	            $muesta_cdc = 'style="display:none;"';
	        }
	    }
	    ?>
	<div class="col-md-6 col-sm-6 form-group" id="cdc_box" <?php echo $muesta_cdc; ?>>
		<label class="control-label col-md-3 col-sm-3 col-xs-12">CDC <?php $cdc_ast; ?></label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="text" name="cdc" id="cdc" value="<?php  if (isset($_POST['cdc'])) {
		    echo limpiacdc($_POST['cdc']);
		} else {
		    echo limpiacdc($rstran->fields['cdc']);
		}?>" placeholder="CDC" class="form-control" <?php echo $cdc_req; ?> onchange="this.value = get_numbers(this.value)" />                    
		</div>
	</div>	
	<div class="clearfix"></div>
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12"> Timbrado *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="text" name="timbrado" id="timbrado" value="<?php

        if ($incrementa != 'S') {
            if (isset($_POST['timbrado'])) {
                echo intval($_POST['timbrado']);
            } else {
                echo intval($rstran->fields['timbrado']);
                //echo $timbrado_bd;
            }
        } else {
            echo 1;
        }

	    ?>" placeholder="Timbrado" class="form-control" required <?php if ($incrementa == 'S') { ?> readonly<?php } ?> />                    
		</div>
	</div>

	<?php
	    // si envio  post
	    if (isset($_POST['idtipocomprobante'])) {
	        // si es con vencimiento
	        if (in_array($_POST['idtipocomprobante'], $tipos_comprobantes_vence_ar)) {
	            $muesta_ven = 'style="display:display;"';
	        } else {
	            $muesta_ven = 'style="display:none;"';
	        }
	    } else {
	        // si es con vencimiento
	        if (in_array($rstran->fields['idtipocomprobante'], $tipos_comprobantes_vence_ar)) {
	            $muesta_ven = 'style="display:display;"';
	        } else {
	            $muesta_ven = 'style="display:none;"';
	        }
	    }
	    ?>
	<div class="col-md-6 col-sm-6 form-group" id="vto_timbrado_box" <?php echo $muesta_ven;  ?>>
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Vto timbrado </label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="date" name="vto_timbrado" id="vto_timbrado" onBlur="validar_fecha_timbrado(this.value);" value="<?php
	        if (isset($_POST['vto_timbrado'])) {
	            echo htmlentities($_POST['vto_timbrado']);
	        } else {
	            echo htmlentities($rstran->fields['vto_timbrado']);
	            //echo $vto_timbrado_bd;
	        }
	    ?>" placeholder="Vto timbrado" class="form-control" <?php if ($incrementa == 'S') { ?> readonly<?php } ?>  />                    
		</div>
	</div>


	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12"> Orden Compra</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<?php
	    if ($_POST['idproveedor'] > 0) {
	        $idproveedor = intval($_POST['idproveedor']);
	    }
	    $idproveedor = intval($idproveedor);
	    // consulta


	    $preferencia_add = null;
	    if ($preferencias_facturas_multiples == "S") {
	        // $preferencia_add=" carga_completa='N' ";
	        //por el momento el multiorden no funcionara para varias facturas
	        $preferencia_add = " AND compras_ordenes.estado_orden = 1 
				and  ocnum_ref is not NULL ";

	    } else {
	        $preferencia_add = " and ocnum not in (select ocnum from compras where ocnum is not null and estado <> 6) ";
	    }


	    $consulta = "
			select 
			ocnum,
			CONCAT('Orden N.: ',ocnum,' | Fecha: ',DATE_FORMAT(fecha,\"%d/%m/%Y\")) as ocdesc,
			(select nombre from proveedores where idproveedor = compras_ordenes.idproveedor ) as proveedor
			from compras_ordenes 
			where 
			estado = 2 
			$preferencia_add
			and compras_ordenes.idproveedor = $idproveedor
			
			order by fecha desc
			limit 50
			";
	    //echo $consulta;
	    // valor seleccionado
	    if (isset($_POST['ocnum'])) {
	        $value_selected = htmlentities($_POST['ocnum']);
	    } else {
	        $value_selected = htmlentities($rstran->fields['ocnum']);
	    }

	    // parametros
	    $parametros_array = [
	        'nombre_campo' => 'ocnum',
	        'id_campo' => 'ocnum',

	        'nombre_campo_bd' => 'ocdesc',
	        'id_campo_bd' => 'ocnum',

	        'value_selected' => $value_selected,

	        'pricampo_name' => 'Seleccionar...',
	        'pricampo_value' => '',
	        'style_input' => 'class="form-control"',
	        'acciones' => ' onchange="actualizar_cabecera(this.value);" ',
	        'autosel_1registro' => 'N'

	    ];

	    // construye campo
	    // echo $consulta;
	    echo campo_select($consulta, $parametros_array);

	    ?>
		</div>
	</div>


	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12"> Sucursal *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<?php
	    // consulta
	    $consulta = "
		SELECT idsucu, nombre
		FROM sucursales
		where
		estado = 1
		order by nombre asc
		";

	    // valor seleccionado
	    if (isset($_POST['sucursal'])) {
	        $value_selected = htmlentities($_POST['sucursal']);
	    } else {
	        $value_selected = htmlentities($rstran->fields['sucursal']);
	    }

	    // parametros
	    $parametros_array = [
	        'nombre_campo' => 'sucursal',
	        'id_campo' => 'sucursal',

	        'nombre_campo_bd' => 'nombre',
	        'id_campo_bd' => 'idsucu',

	        'value_selected' => $value_selected,

	        'pricampo_name' => 'Seleccionar...',
	        'pricampo_value' => '',
	        'style_input' => 'class="form-control"',
	        'acciones' => ' required="required" onchange="actualizar_cabecera(this.value);" ',
	        'autosel_1registro' => 'S'

	    ];

	    // construye campo
	    echo campo_select($consulta, $parametros_array);

	    ?>
		</div>
	</div>
	<div class="clearfix"></div>

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12"> Tipo compra *</label>
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
	        $value_selected = htmlentities($rstran->fields['tipocompra']);
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
	        'acciones' => ' required="required" onchange="tipo_comprobantes(this.value)" ',
	        'autosel_1registro' => 'S'

	    ];

	    // construye campo
	    echo campo_select($consulta, $parametros_array);

	    ?>
		</div>
	</div>

	<div id="vencimiento_box" class="col-md-6 col-sm-6 form-group" <?php

	    if (isset($_POST['idtipocompra'])) {
	        $idtipocompra = $_POST['idtipocompra'];
	    } else {
	        $idtipocompra = $rstran->fields['tipocompra'];
	    }

	    if ($idtipocompra != 2) {


	        ?>style="display:none;"<?php } ?>>
		<label class="control-label col-md-3 col-sm-3 col-xs-12"> Vencimiento Factura *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="date" name="vencimiento" id="vencimiento" value="<?php  if (isset($_POST['vencimiento'])) {
		    echo htmlentities($_POST['vencimiento']);
		} else {
		    echo htmlentities($rstran->fields['vencimiento']);
		}?>" placeholder="Vencimiento" class="form-control"  />                    
		</div>
	</div>

	<div class="col-md-6 col-sm-6 form-group" >
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Comentario</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<textarea name="descripcion" id="descripcion" style="width:100%;resize: none;" rows="4" cols="50" ><?php  if (isset($_POST['descripcion'])) {
		    echo htmlentities($_POST['descripcion']);
		} else {
		    echo htmlentities($rstran->fields['descripcion']);
		}?></textarea>
		</div>
	</div>



	

	<div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 col-md-offset-5">
			<button type="submit" id="submit1" class="btn btn-success" onclick="update_cabecera_compra();" ><span class="fa fa-check-square-o"></span> Guardar</button>
			<button type="button" class="btn btn-primary" onMouseUp="cerrar_pop();"><span class="fa fa-ban"></span> Cancelar</button>
		</div>
	</div>
	<div class="clearfix"></div>
	<br />
	<?php } ?>
</div>
<div id="cotizacion_tabla" style="display:none">
<a type="submit" id="submit1" class="btn btn-small btn-default" onclick="regresar_cabecera();" ><span class="fa fa-reply"></span> Volver</a>
<div id="cotizacion_tabla_box"></div>
</div>
<div id="updatecabeza"></div>





