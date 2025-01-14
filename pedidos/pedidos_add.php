<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");

require_once("../insumos/preferencias_insumos_listas.php");
require_once("../proveedores/preferencias_proveedores.php");
require_once("../compras_ordenes/preferencias_compras_ordenes.php");

if ($agregar_embarque == 1) {

    // echo "$agregar_embarque";exit;
    $ocnum = antisqlinyeccion($_POST['ocnum'], "int");
    $idpuerto = antisqlinyeccion($_POST['idpuerto'], "int");
    $idtransporte = antisqlinyeccion($_POST['idtransporte'], "int");
    $idvias_embarque = antisqlinyeccion($_POST['idvias_embarque'], "int");
    $estado_embarque = antisqlinyeccion($_POST['estado_embarque'], "int");
    $descripcion = antisqlinyeccion($_POST['descripcion'], "text");
    $fecha_embarque = antisqlinyeccion($_POST['fecha_embarque'], "date");
    $fecha_llegada = antisqlinyeccion($_POST['fecha_llegada'], "date");
    $estado = 1;

    $valido = "S";
    $errores = "";
    if (intval($_POST['idpuerto']) == 0) {
        $valido = "N";
        $errores .= " - El campo idpuerto no puede ser cero o nulo.<br />";
    }
    if (intval($_POST['idtransporte']) == 0) {
        $valido = "N";
        $errores .= " - El campo idtransporte no puede ser cero o nulo.<br />";
    }
    if (intval($_POST['idvias_embarque']) == 0) {
        $valido = "N";
        $errores .= " - El campo idvias_embarque no puede ser cero o nulo.<br />";
    }

    if (trim($_POST['fecha_embarque']) == '') {
        $valido = "N";
        $errores .= " - El campo fecha_embarque no puede estar vacio.<br />";
    }
    if (trim($_POST['fecha_llegada']) == '') {
        $valido = "N";
        $errores .= " - El campo fecha_llegada no puede estar vacio.<br />";
    }


    if ($valido == "S") {
        $registrado_por = $idusu;
        $registrado_el = antisqlinyeccion($ahora, "text");

        $idembarque = select_max_id_suma_uno("embarque", "idembarque")["idembarque"];
        $consulta = "
		insert into embarque
		(idembarque, estado_embarque, idpuerto, idtransporte, idvias_embarque, descripcion, fecha_embarque, fecha_llegada, registrado_por, registrado_el, estado,ocnum)
		values
		($idembarque, $estado_embarque, $idpuerto, $idtransporte, $idvias_embarque, $descripcion, $fecha_embarque, $fecha_llegada, $registrado_por, $registrado_el, $estado, $ocnum)
		";
        // echo $consulta;exit;
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    }
}

// consulta a la tabla
$consulta = "
select *,
(select nombre from proveedores where idproveedor=compras_ordenes.idproveedor) as nombre_proveedor,
(select fantasia from proveedores where idproveedor=compras_ordenes.idproveedor) as fantasia_proveedor,
(select ruc from proveedores where idproveedor=compras_ordenes.idproveedor) as ruc_proveedor 
from compras_ordenes 
where 
estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ocnum = intval($rs->fields['ocnum']);
$idproveedor = intval($rs->fields['idproveedor']);
$ruc_proveedor = intval($rs->fields['ruc_proveedor']);
$nombre_proveedor = ($rs->fields['nombre_proveedor']);
$fantasia_proveedor = ($rs->fields['fantasia_proveedor']);
$ocnum_ref = intval($rs->fields['ocnum_ref']);
if ($ocnum == 0) {
    header("location: compras_ordenes.php");
    exit;
}
//echo $idproveedor; exit;



if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

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

    $consulta = "
	select * from compras_ordenes_detalles where marca_borra = 1 and ocnum = $ocnum limit 1
	";
    $rsmb = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsmb->fields['ocnum'] > 0) {
        $errores .= "- No se puee finalizar por que hay articulos marcados como borrados sin borrar.<br />";
        $valido = "N";
    }


    // si todo es correcto inserta
    if ($valido == "S") {
        $consulta = "SELECT 
		SUM(descuento) AS descuento_total 
		FROM compras_ordenes_detalles 
		WHERE ocnum = $ocnum";
        $resultado_desc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $desc = $resultado_desc->fields['descuento_total'];

        $consulta = "
		update compras_ordenes
		set
			estado = 2,
			finalizado_por = $idusu,
			finalizado_el = '$ahora',
			descuento = $desc
		where
			ocnum = $ocnum
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        $consulta = "
		select ocnum_ref from compras_ordenes where ocnum = $ocnum and estado = 2 limit 1
		";
        $rs_orden_compra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $ocnum_ref = intval($rs_orden_compra->fields['ocnum_ref']);
        if ($ocnum_ref > 0 && $preferencias_facturas_multiples == "S") {
            header("location: compras_ordenes_det_finalizado.php?id=$ocnum_ref");
        } else {
            header("location: compras_ordenes_det_finalizado.php?id=$ocnum");
        }
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());


?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>


ajustarAnchoCelda();

	function editar_cabecera(){
		
		//////////////////////////////////////////////
		var ocnum = <?php echo $ocnum; ?>;
		var direccionurl='editar_compras_ordenes_cabecera_modal.php';	
		var parametros = {
		"ocnum"        : ocnum
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
						
			},
			success:  function (response, textStatus, xhr) {
				alerta_modal("Editar cabecera", response);
				verificar_tipo($("#idtipo_origen").val());
				$('#idproveedor').on('mousedown', function(event) {
					// Evitar que el select se abra
					event.preventDefault();
				});
				// verificar_cotizacion_moneda();
				$('#form1_cabecera #idcot').on('mousedown', function(event) {
						// Evitar que el select se abra
						event.preventDefault();
					});
					$("#form1_cabecera #idcot").css('background', '#EEE');
					$("#form1_cabecera #idcot").css('cursor', 'pointer');
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
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
			
		});
		

	}
	function click_radio(div) {
		var input = div.querySelector('input');
		input.click();
		console.log("Valor Input " + input.value);
		return input.value;				
	}

	function verificar_precio(value){
		const newValue = value.replace(',', '.');
		if(parseFloat(newValue) > 0){
			$('#porcentaje').prop('disabled', false);
			$('#monto').prop('disabled', false);
		}
	}

	function cargarPorcentaje(value){
		total = $("#precio").val();
		
		var texto = "";
		if(parseFloat(value) > 0){
			

			var tipo = determinarUnidadCompra()['tipo'];
			if (tipo != 1){
				texto = "Precio con descuento "+(total - value);
			}else{
				var cantidad = $("#cant").val();
				total = total*cantidad;
				texto = "Precio con descuento "+(total - value);
			}
			$("#porcentaje").val(parseFloat((value*100)/total).toFixed(6));
		}
		$("#precio_descuento").html(texto);
	}
	function cargarMonto(value){
		total = $("#precio").val();
		$("#monto").val(parseFloat((value*total)/100).toFixed(6));
		if(parseFloat(value) > 0){
			var tipo = determinarUnidadCompra()['tipo'];
			if (tipo != 1){
				texto = "Precio con descuento "+ ( total - ((value*total)/100).toFixed(6));
			}else{
				var cantidad = $("#cant").val();
				total = total*cantidad;
				texto = "Precio con descuento "+ ( total - ((value*total)/100).toFixed(6));
			}
		}
		$("#precio_descuento").html(texto);

	}

	function cerrar_pop(){
			$("#modal_ventana").modal("hide");
	}
	function agregar_embarcacion(ocnum){
		var parametros = {
				"ocnum"   : ocnum
		};

		$("#modal_titulo").html("Agregar Embarcacion");
		$.ajax({		  
			data:  parametros,
			url:   'agregar_embarcacion_modal.php',
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {	
								
			},
			success:  function (response) {
				// console.log(response);
				$("#modal_cuerpo").html(response);	
				$("#modal_ventana").modal("show");
				
			}
		});

	}
	function cargarMedidaEDI(value){
		var medida2_input = document.getElementById('bulto_edi');
		var cant_medida = medida2_input.getAttribute('data-hidden-cant');
		$('#bulto').val(0);
		$('#pallet').val(0);
		$("#cant").val(value*cant_medida)
	}
	function cargarMedida() {
    $('#bulto').val(0);
    $('#bulto_edi').val(0);
    $('#pallet').val(0);
    
    var cant_unidad = parseFloat($("#cant").val());
    var medida2_input = document.getElementById('bulto');
    var cant_medida2 = parseFloat(medida2_input.getAttribute('data-hidden-cant'));
    var medida3_input = document.getElementById('pallet');
    var cant_medida3 = parseFloat(medida3_input.getAttribute('data-hidden-cant'));
    
    if (!isNaN(cant_medida2) && cant_medida2 != 0 && cant_unidad % cant_medida2 == 0) {
        medida2_input.value = cant_unidad / cant_medida2;
		$("#bulto").val(cant_unidad / cant_medida2);
		$("#pallet").val(((cant_unidad/cant_medida2)/cant_medida3).toFixed(2));
    }

    if (!isNaN(cant_medida3) && cant_medida3 != 0 && cant_unidad % cant_medida2 == 0 && (cant_unidad / cant_medida2) % cant_medida3 == 0) {
        medida3_input.value = (cant_unidad / cant_medida2) / cant_medida3;
    }
}

function cargarMedida2(value, limpiar) {
    var medida2_input = document.getElementById('bulto');
    var cant_medida = parseFloat(medida2_input.getAttribute('data-hidden-cant'));
    
    if (!isNaN(cant_medida)) {
        $("#cant").val(value * cant_medida);
    }
    
    if (limpiar == true) {
        $('#pallet').val(0);
        $('#bulto_edi').val(0);
    }
    
    var medida3_input = document.getElementById('pallet');
    var cant_medida3 = parseFloat(medida3_input.getAttribute('data-hidden-cant'));  
    
    if (!isNaN(cant_medida3)) {
        medida3_input.value = (value / cant_medida3).toFixed(2);
    }   
}

function cargarMedida3(value) {
    var medida3_input = document.getElementById('pallet');
    var cant_medida3 = parseFloat(medida3_input.getAttribute('data-hidden-cant'));
    
    if (!isNaN(cant_medida3)) {
        var resultado = value * cant_medida3;
        $("#bulto").val(resultado);
        $("#bulto_edi").val(0);
        cargarMedida2(resultado, false);
    }
}
	
	function verificar_insumo(id){
		$('input[name="radio_medida"]').filter(':checked').prop('checked', false);
		const selectElement = document.getElementById('lprod');
		const selectedOption = selectElement.options[selectElement.selectedIndex];

		const medida = selectedOption.getAttribute('data-hidden-medida');
		const idmedida = selectedOption.getAttribute('data-hidden-idmedida');
		const medida2 = selectedOption.getAttribute('data-hidden-medida2');
		const idmedida2 = selectedOption.getAttribute('data-hidden-idmedida2');
		const cant_medida2 = selectedOption.getAttribute('data-hidden-cant-medida2');
		const medida3 = selectedOption.getAttribute('data-hidden-medida3');
		const idmedida3 = selectedOption.getAttribute('data-hidden-idmedida3');
		const cant_medida3 = selectedOption.getAttribute('data-hidden-cant-medida3');
		const cant_cajas_edi = selectedOption.getAttribute('data-hidden-cant-edi');
		const id_cajas_edi = selectedOption.getAttribute('data-hidden-id-edi');

		// alert(medida); // Valor de data-hidden-bulto del elemento seleccionado
		// alert(medida3 ); // Valor de data-hidden-pallet del elemento seleccionado

		$("#medida1").html(medida);
		$('#cant').attr('data-hidden-id', idmedida);

		if( cant_cajas_edi == 0 ){
		// $('#caja_plus').css('display', 'inline-block');
		$('#bulto_edi').prop('disabled', true);
		$('#cajaEdiHelp').css('display', 'inline');
		$('#bulto_edi').val(0);
		$('#box_radio_edi').css('display', 'none');

		}else{
		$('#bulto_edi').attr('data-hidden-id', id_cajas_edi);
		$('#bulto_edi').prop('disabled', false);
		$('#bulto_edi').attr('data-hidden-cant', cant_cajas_edi );
		$('#cajaEdiHelp').css('display', 'none');
		$('#box_radio_edi').css('display', 'block');
		}
		

		if( medida2 == "" ){
			// $('#caja_plus').css('display', 'inline-block');
			$('#bulto').prop('disabled', true);
			$('#cajaHelp').css('display', 'inline');
			$('#bulto').val(0);
			$('#box_radio_bulto').css('display', 'none');
		}else{
			$("#medida2").html(medida2);
			$('#bulto').attr('data-hidden-id', idmedida2);
			$('#caja_plus').attr('data-hidden-id', id);
			$('#caja_plus').css('display', 'none');
			$('#bulto').prop('disabled', false);
			$('#bulto').attr('data-hidden-cant',cant_medida2 );
			$('#cajaHelp').css('display', 'none');
			$('#box_radio_bulto').css('display', 'block');
		}
		if( medida3 == ""  ){
			$('#pallet_plus').attr('data-hidden-id', id);
			$('#pallet').prop('disabled', true);
			$('#palletHelp').css('display', 'inline');
			$('#pallet').val(0);
			$('#box_radio_pallet').css('display', 'none');
		}else{
			$('#pallet').attr('data-hidden-id', idmedida3);
			$("#medida3").html(medida3);
			$('#pallet_plus').attr('data-hidden-id', id);
			$('#pallet_plus').css('display', 'none');
			$('#pallet').prop('disabled', false);
			$('#pallet').attr('data-hidden-cant',cant_medida3 );
			$('#palletHelp').css('display', 'none');
			$('#box_radio_pallet').css('display', 'block');
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
	function nl2br (str, is_xhtml) {
		var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>'; 
		return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
	}
	/*function busprod(valor){

			var buscar=valor;
			var parametros='bus='+buscar;
			
			OpenPage('gest_lprodv2.php',parametros,'POST','lprodbus','pred');
	}*/
	function genera_automatico(){
		////var idproveedor = <?php echo $idproveedor; ?>;
		////if(window.confirm('Se generara automaticamente en base al stock minimo, esta accion no se puede deshacer, esta seguro?')){	
			//document.location.href='teso_orden_compras_gen.php?ocn=<?php echo $ocnum; ?>&idproveedor='+idproveedor;
			document.location.href='compras_ordenes_genauto.php?id=<?php echo $ocnum; ?>';
			
		////}
	}
	function busprod(valor){
		
		var direccionurl='gest_lprodv2.php';	
		var parametros = {
		"bus" : valor,
		"ocn": <?php echo $ocnum; ?>
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#lprodbus").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				if(xhr.status === 200){
					$("#lprodbus").html(response);
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
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
			
		});	
		
	}
	function busprod_cbar(e){
		tecla = (document.all) ? e.keyCode : e.which;
		// tecla enter
		if (tecla==13){
			
			var fcbar = $("#fcbar").val();	
			
			var direccionurl='gest_lprodv2.php';	
			var parametros = {
			"fcbar" : fcbar
			};
			$.ajax({		  
				data:  parametros,
				url:   direccionurl,
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function () {
					$("#lprodbus").html('Cargando...');				
				},
				success:  function (response, textStatus, xhr) {
					if(xhr.status === 200){
						$("#lprodbus").html(response);
						$("#cant").val('');	
						$("#precio").val('');
						$("#fcbar").val('');	
						$("#cant").focus();	
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					if(jqXHR.status == 404){
						alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
					}else if(jqXHR.status == 0){
						alert('Se ha rechazado la conexión.');
					}else{
						alert(jqXHR.status+' '+errorThrown);
					}
				}
				
				
			}).fail( function( jqXHR, textStatus, errorThrown ) {
				
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
				
			});	
			
		}
		
	}
	function agregaprod_e(e){
		tecla = (document.all) ? e.keyCode : e.which;
		// tecla enter
		if (tecla==13){
			agregaprod();
		}
	}
	function determinarUnidadCompra(){
		var tipos_carga ={"unidad": 1,"bulto":2,"pallet":3, "bulto_edi":4}
		var tipo = tipos_carga["unidad"];
		var cantidad_cargada;
		var medida = document.getElementById('cant');
		var medida_elegida=1;
		var id_tipo = medida.getAttribute('data-hidden-id');
		var medida2 =document.getElementById('bulto');
		var medida3 = document.getElementById('pallet');
		var medida_edi = document.getElementById('bulto_edi');
		var cantidad_medida = parseFloat(medida?.value);
		var cantidad_medida2 = parseFloat(medida2?.value);
		var cantidad_medida3 = parseFloat(medida3?.value);
		var cantidad_edi = parseFloat(medida_edi?.value);
		var cantidad_ref=0;
		var medida_elegida = $('input[name="radio_medida"]').filter(':checked').val();
		console.log("Medida Elegida "+medida_elegida);
		//console.log("Valor medida "+medida3);
		//console.log("Valor cantidad_medida3 "+cantidad_medida3);
		if(cantidad_medida != 0 && medida_elegida == 1){
			tipo = tipos_carga["unidad"];
			id_tipo = medida?.getAttribute('data-hidden-id');
			cantidad_cargada = cantidad_medida;
		}
		if (cantidad_medida3 != 0 && medida_elegida == 3) {
    		tipo = tipos_carga["pallet"];
    		id_tipo = medida3?.getAttribute('data-hidden-id');
    		cantidad_cargada = cantidad_medida3;
		}
		if(cantidad_medida2 != 0 && medida_elegida == 2){
			tipo = tipos_carga["bulto"];
			id_tipo = medida2?.getAttribute('data-hidden-id');
			cantidad_cargada = cantidad_medida2;
		}
		if(cantidad_edi != 0){
			tipo = tipos_carga["bulto_edi"];
			id_tipo = medida_edi?.getAttribute('data-hidden-id');
			cantidad_cargada = cantidad_edi;
		}
		//console.log("Tipo de unidad de DeterminarUnidadCompr "+tipo);
		return {"tipo": tipo, "id_tipo": id_tipo};
	}

	function agregaprod(){
		var errores='';
		var idprod=$("#lprod").val();
		var cantidad=parseFloat(document.getElementById('cant').value);
		var precio=parseFloat(document.getElementById('precio').value.replace(',', '.'));		
		var cantidad_ref =0;
		var medida_elegida = 1;
		<?php if ($preferencias_medidas_referenciales == "S" || $preferencias_medidas_edi == "S") { ?>
			//preferencias para medidas referenciales y edi
		var medida_elegida = $('input[name="radio_medida"]').filter(':checked').val();
		if (medida_elegida) {
            if (medida_elegida == 1) {
                cantidad_elegida = cantidad;
            } else if (medida_elegida == 2) {
                cantidad_elegida = parseInt($("#bulto").val());
                console.log("Entre en Caja ");
            } else if (medida_elegida == 3) {
                cantidad_elegida = parseInt($("#pallet").val());
            } else if (medida_elegida == 4) {
                cantidad_elegida = parseInt($("#bulto_edi").val());
            } else {}
				var resp = determinarUnidadCompra();
				cantidad_ref=cantidad_elegida;
				var tipoUnidad =medida_elegida;
				var idmedida = resp['id_tipo'];
				var medida = document.getElementById('pallet');
				console.log("idmedida:  "+idmedida);
		}
		<?php } else { ?>
			// var tipoUnidad = 0;
			// var medida = document.getElementById('cant');
			// var idmedida = medida.getAttribute('data-hidden-id');
			// cantidad_ref= cantidad;
			// console.log("Medida Leida: "+medida);
		<?php } ?>
				
				
			// preferencia de descuento orden de compra
	
			var descuento=parseFloat($("#descuentos_form #monto").val());

		if (idprod==''){
			errores=errores+'- Debe indicar producto a comprar. \n<br>';	
		}
		if(!medida_elegida){
			errores=errores+'- Debe indicar a que unidad corresponde el precio de compra acordado en las opciones ubicadas al costado del campo Precio. \n<br>';		
		}
		if (cantidad==0 || cantidad == '' || isNaN(cantidad)){
			errores=errores+'- Debe indicar cantidad a comprar. \n<br>';	
		}
		if (precio==0 || precio == '' || isNaN(precio)){
			errores=errores+'- Debe indicar precio de compra acordado. \n<br>';	
		}
		if (errores==''){
			var direccionurl='add_prodtmp_new.php';	
			var parametros = {
			"ocn"         : <?php echo $ocnum ?>,
			"idp"         : idprod,
			"precio"      : precio,
			"cant"        : cantidad,
			"cant_ref"    : cantidad_ref,
			"tipoUnidad"  : tipoUnidad,
			"descuento"   : descuento,
			"idmedida"    : idmedida
			};
			console.log(parametros);
			$.ajax({		  
				data:  parametros,
				url:   direccionurl,
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function () {
					$("#lprodbus").html('Cargando...');				
				},
				success:  function (response, textStatus, xhr) {

					if(xhr.status === 200){
						$("#lprodbus").html(response);
						if(response == 'ok'){
							actualiza_carrito();
							$("#precio").val('');
							$("#cant").val('');
							$("#bulto").val('');
							$("#pallet").val('');
							$("#bulto_edi").val('');
							$("#fprod").val('');
							busprod('');
						
						}else if(response == 'yaexiste'){
							$("#lprodbus").html('El Articulo ya existe en la lista, editelo.');
							$("#precio").val('');
							$("#cant").val('');
							$("#fprod").val('');
							alert('El Articulo ya existe en la lista, editelo.');
						}else{
							alert(response);	
						}
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					if(jqXHR.status == 404){
						alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
					}else if(jqXHR.status == 0){
						alert('Se ha rechazado la conexión.');
					}else{
						alert(jqXHR.status+' '+errorThrown);
					}
				}
				
				
			}).fail( function( jqXHR, textStatus, errorThrown ) {
				
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
				
			});
		} else {
			alerta_modal('Errores:\n',errores);	
		}
	}
	function actualiza_carrito(){
		var direccionurl='compras_ordenes_grillaprod.php';	
		var parametros = {
		"ocn"      : <?php echo $ocnum ?>
		};
		
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#grilla_box").html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				
				if(xhr.status === 200){
					$("#grilla_box").html(response);
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
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
			
		});
	}
	function precio_costo(tipocosto){
		var idprod =$("#lprod").val();
		if(idprod > 0){
			var direccionurl='compras_ordenes_costo.php';	
			var parametros = {
			"idprod"      : idprod,
			"tipocosto"   : tipocosto, // 1 ultimo 2 contrato
			"idproveedor" : <?php echo $idproveedor ?>
			};
			$.ajax({		  
				data:  parametros,
				url:   direccionurl,
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function () {
					//$("#grilla_box").html('Cargando...');				
				},
				success:  function (response, textStatus, xhr) {
					if(xhr.status === 200){
						if(parseInt(response) > 0){
							$("#precio").val(response);
						}else{
							$("#precio").val(0);
							alert('Sin costo cargado: '+response);	
						}
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					if(jqXHR.status == 404){
						alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
					}else if(jqXHR.status == 0){
						alert('Se ha rechazado la conexión.');
					}else{
						alert(jqXHR.status+' '+errorThrown);
					}
				}
				
				
			}).fail( function( jqXHR, textStatus, errorThrown ) {
				
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
				
			});	
		}else{
			alert("No selecciono ningun producto.");	
		}
	}

	function marca_borrado(ocseria){
		var direccionurl='compras_ordenes_det_del_marca.php';	
		var parametros = {
		"ocseria" : ocseria,
		"accion"  : 'M',
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#bor_"+ocseria).html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				if(xhr.status === 200){
					$("#bor_"+ocseria).html(response);	
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
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
			
		});
	}
	function desmarca_borrado(ocseria){
		var direccionurl='compras_ordenes_det_del_marca.php';	
		var parametros = {
		"ocseria" : ocseria,
		"accion"  : 'D',
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
				$("#bor_"+ocseria).html('Cargando...');				
			},
			success:  function (response, textStatus, xhr) {
				if(xhr.status === 200){
					$("#bor_"+ocseria).html(response);	
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
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
			
		});
	}
	function editar_oc(id){

		var direccionurl='compras_ordenes_det_edit.php';	
		var parametros = {
		"id" : id
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
					$('#modal_ventana').modal('show');
					$("#modal_titulo").html('Editar');
					$("#modal_cuerpo").html('Cargando...');			
			},
			success:  function (response, textStatus, xhr) {
				console.log(response);
				if(xhr.status === 200){
					$("#modal_cuerpo").html(response);	
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
			
			
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			
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
			
		});

	}
	function abrir_alert(event,mensaje){
		event.preventDefault();
		$("#form1Edit #error_box").css('display','block');
		$("#form1Edit #error_box_msg").html(mensaje);
	}
	function editar_oc_reg(id){


		///////////////////////////////////////////////////////////////////////////////////////////////////////////
		var errores='';
		var cantidad=parseFloat($('#form1Edit #cantidad').val());
		var precio=parseFloat($('#form1Edit #precio_compra').val());
		var cantidad_ref =0;
		var medida_elegida = 1;
		<?php if ($preferencias_medidas_referenciales == "S" || $preferencias_medidas_edi == "S") { ?>
			//preferencias para medidas referenciales y edi
		var medida_elegida = $('input[name="radio_medida_form1"]').filter(':checked').val();
		var id_elegido= $('input[name="radio_medida_form1"]').filter(':checked').attr('data-hidden-value');
		if(medida_elegida){
			if(medida_elegida == 1 ){
				cantidad_elegida = cantidad;
			}
			else if(medida_elegida == 2 ){
				cantidad_elegida = parseInt($("#form1Edit #bulto").val());
			}
			else if(medida_elegida == 3 ){
				cantidad_elegida = parseInt($("#form1Edit #pallet").val());
			}
			else if(medida_elegida == 4 ){
				cantidad_elegida = parseInt($("#form1Edit #bulto_edi").val());
			}
			else{}
			
			
				cantidad_ref=cantidad_elegida;
				var tipoUnidad =medida_elegida;
				var idmedida = id_elegido;
		}
		if(!medida_elegida){
			errores=errores+'- Debe indicar a que unidad corresponde el precio de compra acordado en las opciones ubicadas por debajo del campo Precio compra *. \n<br>';		
		}
		<?php } else { ?>
			var tipoUnidad = 0;
			var medida = document.getElementById('cant');
			var idmedida = medida.getAttribute('data-hidden-id');
			cantidad_ref= cantidad;
		<?php } ?>
				
				
			

		
		
		if (cantidad==0 || cantidad == '' || isNaN(cantidad)){
			errores=errores+'- Debe indicar cantidad a comprar. \n<br>';	
		}
		if (precio==0 || precio == '' || isNaN(precio)){
			errores=errores+'- Debe indicar precio de compra acordado. \n<br>';	
		}
		///////////////////////////////////////////////////////////////////////////////////////////////////////

		var direccionurl='compras_ordenes_det_edit.php';
		////////////////////////////////
		
		////////////////////////////////	
		if(errores==''){
			var parametros = {
			"id"              : id,
			"MM_update"       : 'form1',
			"precio_compra"   : precio,
			"cantidad"        : cantidad,
			"almacenar"       : $('#form1Edit #almacenar').val(),
			"cant_ref"        : cantidad_ref,
			"tipoUnidad" 	  : tipoUnidad,
			"idmedida" 		  : idmedida
			
			};
			console.log(parametros);
			$.ajax({		  
				data:  parametros,
				url:   direccionurl,
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function () {
						//$('#modal_ventana').modal('show');
						//$("#modal_titulo").html('Editar');
						//$("#modal_cuerpo").html('Cargando...');			
				},
				success:  function (response, textStatus, xhr) {
					if(xhr.status === 200){
						if(IsJsonString(response)){
							var obj = jQuery.parseJSON(response);
							if(obj.valido == 'S'){
								$('#modal_ventana').modal('hide');
								actualiza_carrito();	
							}else{
								//alert('Errores: '+obj.errores);	
								$("#error_box_msg").html(nl2br(obj.errores));
								$("#error_box").show();
								actualiza_carrito();	
							}
						}else{
							$("#modal_cuerpo").html(response);
							actualiza_carrito();		
						}
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					if(jqXHR.status == 404){
						alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
					}else if(jqXHR.status == 0){
						alert('Se ha rechazado la conexión.');
					}else{
						alert(jqXHR.status+' '+errorThrown);
					}
				}
				
				
			}).fail( function( jqXHR, textStatus, errorThrown ) {
				
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
				
			});
		} else {
			abrir_alert(event,errores);
		}

	}
	function alerta_modal(titulo,mensaje){
		$('#modal_ventana').modal('show');
		$("#modal_titulo").html(titulo);
		$("#modal_cuerpo").html(mensaje);
	}
</script>
<style>
	.radios_box{
		border-radius: 8px;
		border:1px solid #c2c2c2;
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
		background-color: #c2c2c2;
		/* border:1px solid #c2c2c2; */
		color: #fff !important;
		/* color: #486b7a !important; */
		

	}
	.radio_div input{
		width: 20%;
		cursor: pointer;
	}

	.radio_div label{
		cursor: pointer;
	}
	.radio_div input:focus{
		border:1px solid hsl(210, 50%, 70%);
		cursor: pointer;
	}
	input:focus, select:focus {
        border: #add8e6 solid 3px !important; /* Este es un tono de azul pastel */
    }
    input,select{
		border:  1px solid #c2c2c2;
		border-radius: 3px !important;
	}
	.even{
		background: #F7F7F7 !important;
	}
	#lprodbus select option{
		padding: 1.6vh;
	}
	#lprodbus select option:hover{
		background-color: #ccc;
	}
	#lprod option{
		padding: 1.4vh;
		position: relative;
		cursor: pointer;
		/* border-bottom: 1px solid #c2c2c2; */
	}
	#lprod option:hover{
		background: #cecece; 
		/* #4BA0E2 */
		font-weight: bold;
		color: black ;
		opacity: 0.7;
		box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);

	}
	#lprod option + option:after{

		content: "";
		background: #c2c2c2;
		position: absolute;
		bottom: 100%;
		left: 2%;
		height: 1px;
		width: 96%;
	}
    #lprod{
        border: 0.5px solid lightgray;
        border-radius: 8px;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
    }
	.label-cell {
           width: 150px;
      }
</style>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>

            <div class="clearfix"></div>
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Carga de Pedidos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>

<a href="pedidos.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
<a href="../insumos/insumos_lista_add.php" class="btn btn-sm btn-default" target="_blank" ><span class="fa fa-plus"></span> Crear Insumos</a>
<a href="../insumos/productos_add.php" class="btn btn-sm btn-default" target="_blank" ><span class="fa fa-plus"></span> Crear Productos</a>
<a href="../compras_ordenes/buscar_cliente.php" class="btn btn-sm btn-default" target="_blank" ><span class="fa fa-plus"></span> Buscar Cliente</a>
<a href="../clientes/cliente.php" class="btn btn-sm btn-default" target="_blank" ><span class="fa fa-plus"></span> Editar Cliente</a>

<hr />
<div class="clearfix"></div>
<!-- //////////////////////////////////////////////////////////////////////////// -->
<div class="col-md-12 col-xs-12">
  <div class="table-responsive">
  <table class="table table-striped jambo_table bulk_action" style="table-layout: fixed">
  <tbody>
  	<tr>
      <th class="label-cell" width="auto" style="max-width: 600px;" align="center">Cliente: </th>
    <th class="text-cell" style="background:#eee;" align="center"><?php echo $codigo_cliente, " - ",$nombre_cliente; ?></th>
		  <th class="label-cell" width="auto" style="max-width: 600px;" align="center">Ruc: </th>
 		<th style="background:#eee;" align="center"><?php echo $ruc_cliente; ?></th>
	</tr>
  <tr>
    <th width="auto" style="max-width: 600px;" align="center">Direccion: </th>
  <th style="background:#eee;" align="left"><?php echo $direccion; ?></th>
    <th width="auto" style="max-width: 600px;" align="center">Telefono: </th>
 	<th style="background:#eee;" align="center"><?php echo $telefono; ?></th>
  </tr>
  <tr>
    <th width="auto" style="max-width: 600px;" align="center">Vendedor: </th>
  <th style="background:#eee;" align="left"><?php echo $vendedor; ?></th>
    <th width="auto" style="max-width: 600px;" align="center">Forma de Pago: </th>
 	<th style="background:#eee;" align="center"><?php echo $telefono; ?></th>
  </tr>
  </tbody>
</table>
  </div>
</div>

<!-- /////////////////////////////////////////////////////////////// -->

<div class="resumenmini">
<?php if ($ocnum_ref > 0) { ?>
	<h2> Agregar Mas Articulos (Proforma)</h2>
<?php } ?>
                <div class="col-md-12 col-xs-12 col-sm-12" style="margin-bottom:10px !important;">
					<input type="text" placeholder="Filtrar Producto" name="fprod" id="fprod" style="width:100%; height:40px;margin-bottom:10px !important;"  onkeyup="busprod(this.value);" /><br />
					<input type="text" placeholder="Codigo de Barras" name="fcbar" id="fcbar" style="width:100%; height:40px;"  onkeyup="busprod_cbar(event);" />
				</div>
                <div>
                      <div class="col-md-6 col-sm-12 col-xs-12" style="margin-bottom:10px !important;" rowspan="3" id="lprodbus">
                        <?php require_once("gest_lprodv2.php") ?>
                      </div>
						<div class="col-md-6">
							
								<!-- MEDIDA -->
								<div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
									<label class="control-label col-md-3 col-sm-3 col-xs-12">
										<div id="medida1">Unidades:</div>
									</label>
									<div class="col-md-9 col-sm-9 col-xs-12">
										<input  onchange="cargarMedida(this.value,true)"  class="form-control" type="text" name="cant" id="cant" value="0" size="10" />
									</div>
								</div>
								<?php if ($preferencias_medidas_referenciales == "S") { ?>			
							
									<!-- MEDIDAS 2  -->
								<div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
									<label class="control-label col-md-3 col-sm-3 col-xs-12">
										<a id="caja_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
											<span class="fa fa-plus"></span>
										</a>
										<div id="medida2">Medida2:</div>
									</label>
									<div class="col-md-9 col-sm-9 col-xs-12">
										<input disabled class="form-control" onchange="cargarMedida2(this.value,true)"  aria-describedby="cajaHelp" type="text" name="bulto" id="bulto" value="0" size="10" />	
										<small id="cajaHelp"  style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Medida2</strong> asignadas,favor agregar en insumos.</small>
									</div>
								</div>

								<?php } ?>
								<?php if ($preferencias_medidas_edi == "S") { ?>

								<!-- MEDIDAS EDI  -->
								<div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;display:none;">
									<label class="control-label col-md-3 col-sm-3 col-xs-12">
										<a id="caja_edi_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
											<span class="fa fa-plus"></span>
										</a>
										<div id="medida4">Cajas EDI:</div>
									</label>
									<div class="col-md-9 col-sm-9 col-xs-12">
										<input disabled class="form-control" onchange="cargarMedidaEDI(this.value)"  aria-describedby="cajaEdiHelp" type="text" name="bulto_edi" id="bulto_edi" value="0" size="10" />	
										<small id="cajaEdiHelp"  style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Cant. Cajas EDI</strong> asignadas,favor agregar en insumos.</small>
									</div>
								</div>
								<!-- FIN DE MEDIDAS -->
								<?php } ?>


								
							<div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
								<label class="control-label col-md-3 col-sm-3 col-xs-12">Precio:</label>
								<div class="col-md-9 col-sm-9 col-xs-12">
									<input  type="text" class="form-control" onkeyup="verificar_precio(this.value)"  name="precio" id="precio" value="0" size="10" onKeyPress="agregaprod_e(event);" />                
								</div>
							</div>
							<?php if ($preferencias_medidas_referenciales == "S" || $preferencias_medidas_edi == "S") { ?>
							<!-- //////////////////////////////////////////////////////////////////////// -->
							<!-- //////////radio medida comienza////////////////////// -->
							<div class="clearfix"></div>
							<div  class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
								<label class="control-label col-md-3 col-sm-3 col-xs-12">Medida Precio:</label>
								<div class="col-md-9 col-sm-9 col-xs-12 radios_box " style="display: flex;flex-direction: column;padding:0;">
									<div  onclick="click_radio(this)" class="form-check radio_div" id="box_radio_unidad">
										<input class="form-check-input" value="1" type="radio" name="radio_medida" id="radio_unidad" checked>
										<label class="form-check-label" id="label_unidad" for="radio_unidad">
											UNIDAD
										</label>
									</div>
									<div  onclick="click_radio(this)" class="form-check radio_div" id="box_radio_bulto" style="display:none;">
										<input class="form-check-input" value="2" type="radio" name="radio_medida" id="radio_bulto" >
										<label class="form-check-label" id="label_bulto" for="radio_bulto">
											CAJA
										</label>
									</div>
									
								</div>
								<div class="clearfix"></div>
							</div>
							<div class="clearfix"></div>
							<!-- ////////////////////////////////////////////////////////////////////////// -->
							<!-- ////////////////////////////radio medida fin ///////////////////////////// -->
							<?php } ?>
							<div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
								
								<div class="col-md-9 col-sm-9 col-xs-12" id="precio_descuento">
								</div>
							</div>
							
							
								<!-- DESCUENTO PREFERENCIA -->
								<?php if ($descuento == "S") { ?>
									<div id ="descuentos_form"  class="col-md-12 col-xs-12">
										<div>Descuento</div>
										<hr>
										<div class="col-md-12 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
											<label class="control-label col-md-3 col-sm-3 col-xs-12">Monto:</label>
											<div class="col-md-9 col-sm-9 col-xs-12">
												<input disabled type text name="monto" id="monto" onkeyup="cargarPorcentaje(this.value)" value="" class="form-control" />
											</div>
										</div>
										<div class="col-md-12 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
											<label class="control-label col-md-3 col-sm-3 col-xs-12">Porcentaje:</label>
											<div class="col-md-9 col-sm-9 col-xs-12">
												<input  disabled type text name="porcentaje"  aria-describedby="basic-addon3"  class="form-control" aria-describedby="montoHelp" id="porcentaje" onkeyup="cargarMonto(this.value)" value=""  class="form-control" />
												<small  class="form-text text-muted">Los valores equivalen al porcenaje ejemplo 10 %.</small>
											</div>
										</div>
									</div>
								<?php } ?>
								<!-- fin preferencias  -->


							<div class="col-md-12 col-xs-12" style="display:flex;justify-content:space-between;margin-top: 10px;">
								<a href="javascript:void(0);" class="btn btn-sm btn-default"  name="agregar" id="agregar" value="Agregar Producto" onclick="agregaprod();">Agregar Producto</a>
								<a href="javascript:void(0);" class="btn btn-sm btn-default" name="button" id="button" value="Ult Costo" onMouseUp="precio_costo(1);">Ult Costo</a>
								<a href="javascript:void(0);" class="btn btn-sm btn-default" name="button" id="button" value="Costo Contrato" onMouseUp="precio_costo(2);">Costo Contrato</a>
							</div>
						</div>
              
			</div>
                
        </div>

<div class="clearfix"></div>
<br />
<?php if ($ocnum_ref > 0) { ?>
	<h2> Productos Cargados</h2>
<?php } ?>
<div id="grilla_box"><?php require("../pedidos/pedidos_add_grillaprod.php"); ?></div>
<div class="clearfix"></div>
<br />

<form id="form1" name="form1" method="post" action="">

    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12" style="display:flex;justify-content:center;">
			<button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Finalizar Orden</button>
        </div>
    </div>
  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>


<div class="clearfix"></div>
<br />
<br /><br /><br />


                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            





            
          </div>
        </div>
        <!-- /page content -->
        
        <!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
            	<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
           		<h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
            	Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
            	<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        
        </div>
    </div>
</div>
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
