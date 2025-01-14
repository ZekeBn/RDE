<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");
require_once("../includes/funciones_iva.php");
require_once("../includes/funciones_compras.php");
require_once("./preferencias_compras.php");
require_once("../insumos/preferencias_insumos_listas.php");
$idtransaccion = intval($_GET['id']);
if ($idtransaccion == 0) {
    $idtransaccion = intval($_POST['idt']);
    if ($idtransaccion == 0) {
        header("location: gest_reg_compras_resto_new.php");
        exit;
    }
}
$idt = $idtransaccion;

//buscando moneda nacional
$consulta = "SELECT tipo_moneda.idtipo, tipo_moneda.descripcion as nombre FROM tipo_moneda WHERE nacional='S'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];
$nombre_moneda_nacional = $rs_guarani->fields["nombre"];

//Tipo de compra por defecto
$buscar = "select tipocompra from preferencias where idempresa=$idempresa";
$rstc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$tipoc = intval($rstc->fields['tipocompra']);

$hoy = $ahora;

$explota = explode("-", $hoy);
$an = $explota[0];
$me = $explota[1];
if ($me < 10) {
    $me = "$me";
}
$dd = intval($explota[2]);

if ($dd < 10) {
    $dd = "0$dd";
}


//Traemos los datos para mostrar
$buscar = "Select tmpcompras.*,
(select nombre from proveedores where idproveedor=tmpcompras.proveedor) as descproveedor,
cotizaciones.cotizacion, cotizaciones.tipo_moneda as idmoneda_select, tipo_moneda.descripcion as moneda_nombre
from tmpcompras
LEFT JOIN cotizaciones on cotizaciones.idcot = tmpcompras.idcot
LEFT JOIN tipo_moneda on tipo_moneda.idtipo = cotizaciones.tipo_moneda
where tmpcompras.idtran=$idtransaccion and tmpcompras.estado = 1 ";
$rscab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idprov = intval($rscab->fields['proveedor']);
$factura = trim($rscab->fields['facturacompra']);
$suc = substr($factura, 0, 3);
$pex = substr($factura, 3, 3);
$fa = substr($factura, 6, 15);
$fechacompra = $rscab->fields['fecha_compra'];
$monto_factura = $rscab->fields['monto_factura'];
$tipocompra = intval($rscab->fields['tipocompra']);
$vtofac = $rscab->fields['vencimiento'];
$timbrado = $rscab->fields['timbrado'];
$timvto = $rscab->fields['vto_timbrado'];
$ocnum = intval($rscab->fields['ocnum']);
$idproveedor = $idprov;
$idtransaccion = $rscab->fields['idtran'];
$idtipo_origen = $rscab->fields['idtipo_origen'];
$cotizacion = $rscab->fields['cotizacion'];
$moneda_nombre = $rscab->fields['moneda_nombre'];
$idmoneda_select = intval($rscab->fields['idmoneda_select']);
$idcompra_ref = intval($rscab->fields['idcompra_ref']);


if ($idmoneda_select == 0) {
    $idmoneda_select = $id_moneda_nacional;
}
$moneda_compra = $rscab->fields['moneda'];//Moneda origen seleccionada en la cabecera
$cotizacion_compra = floatval($rscab->fields['cambio']);//Cotizacion de la compra que puede
// venir de OC o de la seleccion en la cabecera
$proveedor_char = trim($rscab->fields['descproveedor']);//Nombre del proveedor, solo para mostrar en pantalla

//VERIFICANDO ESTADO DE LA ORDEN
$consulta = "SELECT idtipo_origen FROM tipo_origen WHERE  UPPER(tipo)='IMPORTACION'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_tipo_origen_importacion = intval($rs_guarani->fields["idtipo_origen"]);
if ($id_tipo_origen_importacion == 0) {
    $errores = "- Por favor cree el Origen IMPORTACON.<br />";
}
if ($ocnum > 0) {

    $consulta_ocnum = "SELECT 
	ocnum, estado, idtipo_origen
	from compras_ordenes 
	where 
	 ocnum = $ocnum
	";
    $ocdesc = $conexion->Execute($consulta_ocnum) or die(errorpg($conexion, $consulta_ocnum));
    $ocnum_estado = intval($ocdesc->fields['estado']);
    $idtipo_origen = intval($ocdesc->fields['idtipo_origen']);


    $consulta_embarque = "SELECT emb.idembarque
	FROM tmpcompras as tmp 
	INNER JOIN compras_ordenes as co on tmp.ocnum = co.ocnum
	LEFT JOIN embarque as emb on emb.ocnum = co.ocnum and emb.idcompra =tmp.idtran
	WHERE tmp.ocnum = $ocnum
	";
    $rsembarque = $conexion->Execute($consulta_embarque) or die(errorpg($conexion, $consulta_embarque));
    $idembarque = intval($rsembarque->fields['idembarque']);

    if ($id_tipo_origen_importacion == $idtipo_origen && $idembarque == 0) {
        $alerta_embarque = "<strong>Atencion es una orden de importacion sin Embarque.</strong></br>";
    }
}

// echo json_encode($idembarque);exit;


if ($idtransaccion == 0) {
    header("location: gest_reg_compras_resto_new.php");
    exit;
}

if ($listo == 'S') {
    $buscar = "Select max(numero) as mayor from transacciones_compras";
    $rsm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idtransaccion = intval($rsm->fields['mayor']) + 1;

}

$buscar = "Select * from proveedores where idempresa=$idempresa and estado = 1 and idproveedor = $idproveedor order by nombre ASC";
$rsprov = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tprov = $rsprov->RecordCount();

//Categorias
$buscar = "Select * from categorias where idempresa=$idempresa order by nombre ASC";
$rscate = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Unidades
$buscar = "Select * from medidas order by nombre ASC";
$rsmed = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

//Monedas
$buscar = "Select * from tipo_moneda order by idtipo asc";
$rsmo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$totmoneda = $rsmo->RecordCount();
$buscar = "Select * from insumos_lista where idempresa=$idempresa and estado = 'A' order by descripcion asc ";
$rsprod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<!----------------------------JS------------------------------------->
	<script>
		
		
//------------------------------------Funciones nuevas	------------------------------//
	function agregar_insumo(idmedida,tipo_medida,idmoneda,nacional){
		var lote=$("#lote").val();
		var vencimiento=$("#vencimiento").val();
		var iddeposito = $("#iddeposito").val();
		var iva = parseFloat($("#iva_articulo").val());
		var usa_iva=$("#iva_articulo").attr("data-hidden-iva");
		$("#erroresjs").hide();
		var errores="";
		var idtransaccion=<?php echo $idtransaccion ?>;
		var insumo=$("#ocinsumo").val();
		if (insumo==''){
			errores=errores+"Debe indicar el insumo a ser comprado. <br/>";
		}
		var cantidad=$("#cantidad").val();
		if (cantidad==''){
			errores=errores+"Debe indicar cantidad adquirida. <br/>";
		}
		var obliga_lote=$("#lote").attr("data-hidden-lote");
		if (obliga_lote==1 && (lote == '' || vencimiento =='')){
			errores=errores+"Debe indicar lote y vencimiento. <br/>";
		}
		<?php if ($id_tipo_origen_importacion == $idtipo_origen) {?>
		if( idmoneda==undefined  ){
			errores=errores+"Debe indicar el tipo de moneda para el precio elegido. <br/>";
		}<?php }?>
		var precio_compra=parseFloat($("#precio_compra").val().replace(',', '.'));
		var cotizacion = <?php echo $cotizacion ? $cotizacion : 1 ; ?>;
		if(nacional == "false"){
			precio_compra = precio_compra*cotizacion;
			iva = iva*cotizacion;
		}
		// console.log("este es nacional ",nacional);
		
		///////////////////////////////////
		var cantidad_ref =0;
		<?php if ($preferencias_medidas_referenciales == "S" || $preferencias_medidas_edi == "S") { ?>
			//preferencias para medidas referenciales y edi
		var medida_elegida = $('input[name="radio_medida"]').filter(':checked').val();
		if(medida_elegida){
			if(medida_elegida == 1 ){
				cantidad_elegida = cantidad;
			}
			else if(medida_elegida == 2 ){
				cantidad_elegida = parseInt($("#bulto").val());
			}
			else if(medida_elegida == 3 ){
				cantidad_elegida = parseInt($("#pallet").val());
			}
			else if(medida_elegida == 4 ){
				cantidad_elegida = parseInt($("#bulto_edi").val());
			}
			else{}
			
			
				var resp = determinarUnidadCompra();
				cantidad_ref=cantidad_elegida;
				var tipoUnidad =medida_elegida;
				var idmedida =idmedida;
		}
		if(!medida_elegida){
			errores=errores+'- Debe indicar a que unidad corresponde el precio de compra acordado en las opciones ubicadas por debajo del campo Precio compra *. \n<br>';		
			}
		<?php } else { ?>
			var tipoUnidad = 0;
			var idmedida = 0;
		<?php } ?>







		///////////////////////////////////
		if (errores==''){
			var parametros = {
                "idtransaccion"   : idtransaccion,
				"insumo"		  : insumo,
				"cantidad"		  : cantidad,
				"cantidad_ref"	  : cantidad_ref,
				"precio_compra"	  : precio_compra,
				"lote"			  : lote,
				"vencimiento"	  : vencimiento,
				"iddeposito"	  : iddeposito,
				"idmedida"		  : idmedida,
				"tipo_medida"	  : tipo_medida,
				"iva"			  : iva,
				"usa_iva" 		  : usa_iva,
				"agregar"		  : 1
			};
			// console.log(parametros);

			$.ajax({
					data:  parametros,
					url:   'verificar_carrito.php',
					type:  'post',
					beforeSend: function () {
						 
					},
					success:  function (response) {
						// console.log(response);
						  if(JSON.parse(response)["success"]==true){

							  errores=errores+"Un producto idéntico en términos de lote, vencimiento y costo ya se encuentra en el carrito. Si desea incrementar la cantidad, por favor, modifique la cantidad en el formulario inferior. <br/>";
							  $("#errorestxt").html(errores);
							  $("#erroresjs").show();
						  }else{
							$.ajax({
								data:  parametros,
								url:   'compras_carrito.php',
								type:  'post',
								beforeSend: function () {
									$("#carritocompras").html('Cargando...');  
								},
								success:  function (response) {
									$("#carritocompras").html(response);
								}
							});
						  }
					}
			});
			
			
			
		} else {
			$("#errorestxt").html(errores);
			$("#erroresjs").show();
		}
		
		
	}
	function eliminar_articulo(idunico){
		var idtransaccion=<?php echo $idtransaccion ?>;
		var idunico=idunico;
		var parametros = {
                "idtransaccion"   : idtransaccion,
				"idunico"		  : idunico
        };
		$.ajax({
                data:  parametros,
                url:   'compras_carrito.php',
                type:  'post',
                beforeSend: function () {
                      $("#carritocompras").html('Cargando...');  
                },
                success:  function (response) {
					  $("#carritocompras").html(response);
                }
        });

	}
	function agregar_cuenta(){
		var tipocompra=<?php echo $tipocompra ?>;
		var idtransaccion=<?php echo $idtransaccion ?>;
		var idformapago=$("#listapagos").val();
		var idcuentainterna=$("#listacuentas").val();
		var monto_seleccionado=$("#monto_abonado").val();
		var otros_valores=$("#obs").val();
		var registrar=1;
		var errores="";
		if (tipocompra!=1){
			errores=errores+"*OP automatica solo valida para compra al contado.<br />";
		}
		if (idformapago==''){
			errores=errores+"*Debe indicar la forma de pago<br />";
		}
		if (idcuentainterna==''){
			errores=errores+"*Debe indicar la cuenta interna de pago<br />";
		}
		if (monto_seleccionado==''){
			errores=errores+"*Debe indicar monto de pago<br />";
		}
		if (errores==''){
			$("#errorestxtpagos").html("");
			$("#erroresjspagos").hide();
			 var parametros = {
					"registrar"         : registrar,
					"tipocompra"  		: tipocompra,
					"idtransaccion"   	: idtransaccion,
					"idformapago"      	: idformapago,
					"idcuentainterna"  	: idcuentainterna,
					"monto_seleccionado": monto_seleccionado,
					"otros_valores"     : otros_valores
			};
			$.ajax({
					data:  parametros,
					url:   'compras_carrito_lista.php',
					type:  'post',
					beforeSend: function () {
						   $("#carrito_fpago").html("<br /><br /><br />Registrando...<br /><br />");
					},
					success:  function (response) {
						alert(response);
						$("#carrito_fpago").html(response);
						recargar();
					}
			});
		} else {
			
			$("#errorestxtpagos").html(errores);
			$("#erroresjspagos").show();
			
		}	
	}
	function eliminar_pago(valor){
		var idtransaccion=<?php echo $idtransaccion ?>;
		var parametros = {
						"registrar"         : 0,
						"chau"  : valor,
						"idtransaccion"   : idtransaccion
		};
		$.ajax({
				data:  parametros,
				url:   'compras_carrito_lista.php',
				type:  'post',
				beforeSend: function () {
					   $("#carrito_fpago").html("<br /><br /><br />Eliminando...<br /><br />");
				},
				success:  function (response) {
					$("#carrito_fpago").html(response);
					recargar();
				}
		});
		
		
	}



//------------------------------------Fin funciones nuevas	------------------------------//

//------------------------------------Agregar Compras	----------------------------------//
function cerrar_compra(){
	var rutaRelativa = "gest_reg_compras_resto_new.php";
	var urlActual = window.location.href;
	var ultimoSlash = urlActual.lastIndexOf("/");
	var nuevaURL = urlActual.substring(0, ultimoSlash + 1) + rutaRelativa;
	window.location.href = nuevaURL;
}
function generar_compra(){
	var idtransaccion=<?php echo $idtransaccion ?>;
	var idempresa=<?php echo $idempresa ?>;
	var idusu=<?php echo $idusu?>;
	var parametros = {
					"tran"			: idtransaccion,
					"idempresa"		: idempresa,
					"idusu"			: idusu
	};

	//////////////////////////////////////////////////////////////////////////////////////////

		$.ajax({
				data:  parametros,
				url:   'registrar_compra.php',
				type:  'post',
				beforeSend: function () {
						$("#generar_compra").text('Cargando...');
				},
				success:  function (response) {
					// console.log(response);
					if(JSON.parse(response)["success"] == true) {
						$("#generar_compra").text("Exito");
							<?php if ($idcompra_ref != 0) {?>
								document.location.href='<?php echo "gest_adm_depositos_compras_det.php?idcompra=$idcompra_ref"; ?>';
							<?php } else {?>
								document.location.href='gest_reg_compras_resto_new.php';
							<?php }?>
					}else{
						// console.log(JSON.parse(response)["errores"]);
						$('#titulovError').html('Error');
						$('#cuerpovError').html(JSON.parse(response)["errores"]);	
						$('#ventanamodalError').modal('show');
						$("#generar_compra").text("Finalizar Compra");
					}
					// recargar();
				}
		});
	<?php // }?>


	

}
//------------------------------------Fin Agregar Compras	------------------------------//
function mostrar_detalle(lote, vencimiento, deposito,nombre,cantidad,costo,subtotal){
	
	string = `
	<div class='detalles_articulo'>
		<h2> ${nombre}</h2>
		<p class='col-md-6 col-xs-12'><strong>Lote</strong>: ${lote} </p>
		<p class='col-md-6 col-xs-12'><strong>Vencimiento</strong>: ${vencimiento}</p>
		<p class='col-md-6 col-xs-12'> <strong>Deposito</strong>: ${deposito}</p>
		<p class='col-md-6 col-xs-12'><strong>Cantidad</strong>: ${cantidad} </p>
		<p class='col-md-6 col-xs-12'><strong>Costo</strong>: ${costo}</p>
		<p class='col-md-6 col-xs-12'><strong>Sub Total</strong>: ${subtotal}</p>
	</div>`;
	$('#titulovError').html('Detalles');
		$('#cuerpovError').html(string.trim());	
		$('#ventanamodalError').modal('show');
}
//------------------------------------Ajustar Auto ---------------------------------------//
function compras_ajuste_auto(diferencia, monto_factura){
	var idtransaccion=<?php echo $idtransaccion ?>;
	var idempresa=<?php echo $idempresa ?>;
	var idusu=<?php echo $idusu?>;
	
	// var idunico=idunico;//idregcc
	var parametros = {
					"idtransaccion"			: idtransaccion,
					"idempresa"		: idempresa,
					"idusu"			: idusu,
					"diferencia"	: diferencia,
					"monto_factura" : monto_factura,
					"ajustar"		: 1
	};
	$.ajax({
			data:  parametros,
			url:   'compras_carrito.php', //compras_ajustes_auto
			type:  'post',
			beforeSend: function () {
				
			},
			success:  function (response) {
				$("#carritocompras").html(response);
			}
	});
}
//------------------------------------Fin Ajustar ----------------------------------------//
//------------------------------Inicio para Calculo Porcentaje----------------------------//
function cargarPorcentaje(value,total_factura){
	var id_moneda_descuento = $('input[name="radio_moneda_form_descuento"]:checked').val();
	var id_moneda_nacional = <?php echo $id_moneda_nacional;?>;
	if (id_moneda_descuento != id_moneda_nacional){
		var nacional = false;
		total_factura=transformar_monto_factura(total_factura);
	} 
	$("#porcentaje").val(parseFloat((value*100)/total_factura).toFixed(6));
}
function cargarMonto(value,total_factura){
	$("#monto").val(parseFloat((value*total_factura)/100).toFixed(6));

}
//--------------------------------Fin ara Calculo porcentaje------------------------------//
//------------------------------------DESCUENTO-------------------------------------------//
function agregar_descuento(monto_factura){
	var idtransaccion=<?php echo $idtransaccion ?>;
	var idempresa=<?php echo $idempresa ?>;
	var idusu=<?php echo $idusu?>;
	var porcentaje = $("#descuentos_form #porcentaje").val();
	var descuento_valor = $("#descuentos_form #monto").val();
	var idmoneda_select = $('input[name="radio_moneda_form_descuento"]').filter(':checked').val();
	var moneda_nacional = <?php echo $id_moneda_nacional; ?>;
	var cotizacion = <?php echo $cotizacion ? $cotizacion : 1; ?>;
	cotizacion = parseFloat(cotizacion);
	if(idmoneda_select != parseInt(moneda_nacional)){
		descuento_valor = cotizacion*descuento_valor;
	}
	// var idunico=idunico;//idregcc
	var parametros = {
					"idtransaccion"			: idtransaccion,
					"idempresa"				: idempresa,
					"idusu"					: idusu,
					"porcentaje"			: porcentaje,
					"descuento_valor" 		: descuento_valor,
					"monto_factura"			: monto_factura,
					"descuento"				: 1
	};
	$.ajax({
			data:  parametros,
			url:   'compras_carrito.php', //compras_ajustes_auto
			type:  'post',
			beforeSend: function () {
				
			},
			success:  function (response) {
				$("#carritocompras").html(response);
			}
	});
}
//-------------------------------- FIN DESCUENTO------------------------------------------//
//------------------------------------Editar Articulo ------------------------------------//
function editar_articulo(idunico){

	var idtransaccion=<?php echo $idtransaccion ?>;
	var idunico=idunico;
	var parametros = {
			"idtransaccion"   : idtransaccion,
			"idunico"		  : idunico
	};

	$("#titulov").html("Datos de Articulo");
	$.ajax({		  
		data:  parametros,
		url:   'editar_articulo_modal.php',
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {	
							
		},
		success:  function (response) {
			$("#cuerpov").html(response);	
			$("#ventanamodal").modal("show");
			
		}
	});

	}
//------------------------------------Fin Editar Articulo --------------------------------//
//------------------------------------Vencimientos Personalizar Modal ------------------------------------//
function vencimientos_personalizar_modal(){

	var idtransaccion=<?php echo $idtransaccion ?>;
	var parametros = {
			"idtransaccion"   : idtransaccion
	};

	$("#titulov").html("Datos de Vencimientos");
	$.ajax({		  
		data:  parametros,
		url:   'vencimientos_compras_modal.php',
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {	
							
		},
		success:  function (response) {
			$("#cuerpov").html(response);	
			$("#ventanamodal").modal("show");
			
		}
	});

}
function vencimientos_personalizar_modal_editar(idvencimiento) {

	var idtransaccion=<?php echo $idtransaccion ?>;
	var parametros = {
			"idtransaccion"   	: idtransaccion,
			"idvencimiento"	  	: idvencimiento,
			"editar_vencimiento"	: 1
	};

	$("#titulov").html("Datos de Vencimientos");
	$.ajax({		  
		data:  parametros,
		url:   'vencimientos_compras_modal.php',
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {	
							
		},
		success:  function (response) {
			$("#cuerpov").html(response);	
			$("#ventanamodal").modal("show");
			
		}
	});

}

function vencimientos_personalizar_modal_eliminar(idvencimiento){
	var idtransaccion=<?php echo $idtransaccion ?>;
	var parametros = {
			"idtransaccion"   				: idtransaccion,
			"idvencimiento"	  				: idvencimiento,
			"vencimientos_compra_borrar"	: 1
	};
	
	$.ajax({
			data:  parametros,
			url:   'compras_carrito.php',
			type:  'post',
			beforeSend: function () {
				  $("#carritocompras").html('Cargando...');  
			},
			success:  function (response) {
				  $("#carritocompras").html(response);
			}
	});

}
//------------------------------------Fin Vencimientos Personalizar Modal --------------------------------//
function cancelar(transa){
	if (transa !=''){
		document.getElementById('chaucompra').submit();
		
	}
	
}
	
function agregatmp(){
		var errores='';
		var fecompra=document.getElementById('fechacompra').value;
		if (fecompra==''){
			errores=errores+'Debe indicar fecha de compra. \n'	;
		}
		var suc=document.getElementById('suc').value;
		if (suc==''){
			errores=errores+'Debe indicar encabezado(sucursal) para factura. \n';
		}
		
		var pe=document.getElementById('pex').value;
		if (pe==''){
			errores=errores+'Debe indicar encabezado(punto exp) para factura. \n';
		}
		var fc=document.getElementById('fa').value;
		if (fc==''){
			errores=errores+'Debe indicar numero para factura de compra. \n';
		}
		var tc=document.getElementById('tipocompra').value;
		if (tc==0){
			errores=errores+'Debe indicar tipo de compra. \n';
		}
		if (document.getElementById('proveedor').value=='0')	{
				errores=errores+'Debe indicar proveedor del producto. \n'	;
				
		}
		
		if (errores==''){
			var insu=document.getElementById('insuag').value;
			if (insu=='')	{
				errores=errores+'Debe indicar Insumo a comprar. \n'	;
				
			} else {
				document.getElementById('insuoc').value=insu;
				
			}
			if (document.getElementById('nombre').value==' ')	{
				errores=errores+'Debe indicar nombre del producto. \n'	;
				
			}
			
			//Producto seleccionado
			if (document.getElementById('cantidad').value=='')	{
				errores=errores+'Debe indicar cantidad comprada producto. \n'	;
				
			}
			if (document.getElementById('costobase').value=='')	{
				
				errores=errores+'Debe indicar precio del producto. \n'	;
			}
			
			if (document.getElementById('monto_factura').value=='')	{
				
				errores=errores+'Debe indicar monto de la factura. \n'	;
			}
			
			
			
			
			if (errores==''){
				  var cantidad=document.getElementById('cantidad').value;
		 		  var precom=document.getElementById('costobase').value;
		  		 document.getElementById('cantioc').value=cantidad;
		   		document.getElementById('pcom').value=precom
				
				
				
				document.getElementById('rc').submit();
			} else {
				alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');	
			}
	} else {
				alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');	
	}
}
function validar(){
	
	var fecha=document.getElementById('fechacompra').value;
	var valido = 'S';
	var fe=fecha.split("-");
	var ano=fe[0];
	var mes=fe[1];
	var dia=fe[2];
	var f1 = new Date(ano, mes, dia); 
	var f2 = new Date(<?php echo $an ?>, <?php echo $me ?>, <?php echo $dd ?>);
	var fdesde = new Date(<?php echo date("Y", strtotime($fechadesdebd)); ?>, <?php echo date("m", strtotime($fechadesdebd)); ?>, <?php echo date("d", strtotime($fechadesdebd)); ?>);
	var fhasta = new Date(<?php echo date("Y", strtotime($fechahastabd)); ?>, <?php echo date("m", strtotime($fechahastabd)); ?>, <?php echo date("d", strtotime($fechahastabd)); ?>);
    // fecha no puede estar en el futuro
	if (f1 > f2){
		valido = 'N';
	}
	// la fecha no puede ser menor a la fecha desde
	if(f1 < fdesde){
		valido = 'N';	
	}
	// la fecha no puede ser mayor a la fecha hasta
	if(f1 > fhasta){
		valido = 'N';	
	}
	if(valido == 'N'){
		alertar('ATENCION: Algo salió mal.','Fecha de compra incorrecta, habilitado entre: <?php echo date("d/m/Y", strtotime($fechadesdebd)); ?> y <?php echo date("d/m/Y", strtotime($fechahastabd)); ?> y no pude ser mayor a hoy <?php echo date("d/m/Y", strtotime($ahora)); ?>.','error','Lo entiendo!');
		document.getElementById('fechacompra').value='';
	}else{
		cargavto();
	}
	
}
function listar(que){
	//var parametros='idc='+que;
		var parametros = {
                "idc"   : que
        };
		$.ajax({
                data:  parametros,
                url:   'minilistaprod.php',
                type:  'post',
                beforeSend: function () {
                      $("#listaprodudiv").html('Cargando...');  
                },
                success:  function (response) {
					  $("#listaprodudiv").html(response);
                }
        });
	
	//OpenPage('minilistaprod.php',parametros,'POST','listaprodudiv','pred');
	setTimeout(function(){ controlar(); }, 200);
}
function eliminar(valor){
	document.getElementById('regse').value=valor;
	document.getElementById('deletar').submit();		
}
function cerrar(){
	var monto_factura = $("#monto_factura").val();
	var totcomp = $("#totcomp").val();
	if(monto_factura == totcomp && monto_factura > 0){
		$("#rpc").hide();
		document.getElementById('registracompra').submit();	
	}else{
		alert("El monto de factura con la sumatoria total de los montos de productos cargados.");
	}	
}
function controlar(){
  	if (document.getElementById('existep')){
	   var listo=parseInt(document.getElementById('existep').value);
	   if (listo==1){
		   var insumo=$("#insu").val();
		   var cantidad=document.getElementById('cantidad').value;
		   var precom=document.getElementById('costobase').value;
		   document.getElementById('insuoc').value=insumo;
		   document.getElementById('cantioc').value=cantidad;
		   document.getElementById('pcom').value=precom
		   $("#agp").show();
		   //document.getElementById('agp').hidden='';
	
	   } else {
		   //document.getElementById('agp').hidden='hidden';
		   $("#agp").hide();
		    document.getElementById('insuoc').value=0;
	   }
	} else {
		//document.getElementById('agp').hidden='hidden';
		$("#agp").hide();
		 document.getElementById('insuoc').value=0;
	}
}
function alertar(titulo,error,tipo,boton){
	swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
	}
function verifica_factura(){
	var suc = $("#suc").val();
	var pex = $("#pex").val();
	var fa = $("#fa").val();
	var prov = $("#proveedor").val();
	if(parseInt(suc) > 0 && parseInt(pex) > 0 && parseInt(fa) > 0 && parseInt(prov) > 0){	
		var parametros = {
                "suc"   : suc,
				"pex"   : pex,
				"fa"    : fa,
				"prov"  : prov
        };
		$.ajax({
                data:  parametros,
                url:   'verifica_factura_compra.php',
                type:  'post',
                beforeSend: function () {
                      //$("#adicio").html('');  
                },
                success:  function (response) {
						cargavto();
						if(response == 'error'){
							alertar('ATENCION: Algo salio mal.','Factura Duplicada para el proveedor seleccionado.','error','Lo entiendo!');
						}
                }
        });
	}else{
		cargavto();	
	}
	if(parseInt(prov) > 0){
		carga_timbrado();
	}
	
}
function cargavto(){
	var prov = $("#proveedor").val();
	var tipocompra= $("#tipocompra").val();
	var fechacompra = $("#fechacompra").val();
	var parametros='pp='+prov+'&tpc='+tipocompra+'&fcomp='+fechacompra;
    OpenPage('cargavto.php',parametros,'POST','vencefactu','pred');
	
}
function recalcular(){
	var prov = $("#proveedor").val();
	var tipocompra= $("#tipocompra").val();
	var fechacompra = $("#fechacompra").val();
	var parametros='pp='+prov+'&tpc='+tipocompra+'&fcomp='+fechacompra;
    OpenPage('cargavto.php',parametros,'POST','vencefactu','pred');
	
}
function cabeza(){
	
	var fec = $("#fechacompra").val();
	var suc = $("#suc").val();
	var pex = $("#pex").val();
	var tipocompra= $("#tipocompra").val();
	var fa = $("#fa").val();
	var prov = $("#proveedor").val();
	var timbrado=$("#timbrado").val();
	var vencetimbra=$("#timbrado_venc").val();
	var vencefactu=$("#factura_venc").val();
	var monto_factura = $("#monto_factura").val();
	
	if(parseInt(suc) > 0 && parseInt(pex) > 0 && parseInt(fa) > 0 && parseInt(prov) > 0  && parseInt(tipocompra) > 0 && (fec)!='' ){
		//var idt=<?php echo $idtransaccion?>;
		/*var parametros='idt='+idt+'&tpc='+tipocompra+'&fe='+fec+'&suc='+suc+'&pe='+pex+'&fa='+fa+'&prov='+prov+'&timb='+timbrado+'&vencefc='+vencefactu+'&vencetm='+vencetimbra;
   		 OpenPage('update_cabeza.php',parametros,'POST','updatecabeza','pred');*/
		 
		var parametros = {
                "idt"     : <?php echo $idtransaccion?>,
				"tpc"     : tipocompra,
				"fe"      : fec,
				"suc"     : suc,
				"pe"      : pex,
				"fa"      : fa,
				"prov"    : prov,
				"timb"    : timbrado,
				"vencefc" : vencefactu,
				"vencetm" : vencetimbra,
				"mfac"    : monto_factura
        };
		$.ajax({
                data:  parametros,
                url:   'update_cabeza.php',
                type:  'post',
                beforeSend: function () {
                	$("#updatecabeza").html('Actualizando...');  
                },
                success:  function (response) {
					$("#updatecabeza").html(response);
                }
        });
	
	}
}
function carga_timbrado(){
	var prov = $("#proveedor").val();
	var timbrado = $("#timbrado").val();
	var timbrado_venc = $("#timbrado_venc").val();
	// condicion de busqueda
	var cambia = "S";
	if(timbrado != ''){
		if(window.confirm('Existe un timbrado escrito en el campo, desea reemplazarlo?')){
			cambia = "S";	
		}else{
			cambia = "N";	
		}
	}
	if(cambia == 'S'){
		var parametros = {
				"prov"    : prov
        };
		$.ajax({
                data:  parametros,
                url:   'gest_compras_carga_timbrado.php',
                type:  'post',
                beforeSend: function () {
                	$("#timbrado").val('cargando...');  
					$("#timbrado_venc").val('');  
                },
                success:  function (response) {
					var datos = response.split(',');
					var timb = datos[0];
					var timbv = datos[1];
					var facincre = datos[2];
					var faactu = $("#fa").val();
					//alert(facincre);
                	$("#timbrado").val(timb);  
					$("#timbrado_venc").val(timbv);
					if(parseInt(facincre) > 0 && faactu == ''){
						$("#suc").val('1');
						$("#pex").val('1');
						$("#fa").val(facincre);					
					}
                }
        });
	}
}
function genera_auto(idt){
	<?php if (intval($ocnum) > 0) {?>
	var direccion= 'gest_reg_compras_resto_gen.php?ocnum=<?php echo $ocnum;?>&idt'+idt;
	var parametros = {
                "ocnum"   : <?php echo $ocnum;?>,
				"idt"   : idt
        };
	$.ajax({
                data:  parametros,
                url:   direccion,
                type:  'get',
                beforeSend: function () {
                },
                success:  function (response) {
					// console.log(response);
					window.location.reload();
                }
        });	
		<?php } else { ?>
		alert("Error! no indico el numero de orden de compra.");
	<?php } ?>
	
}
function validar_fecha_vencimiento(fecha){
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
		errores = 'La Fecha del insumo ('+dia+'/'+meshtml+'/'+ano+') esta vencida.';
	}
	// la fecha no puede ser menor a la fecha desde
	if(valido == 'N'){
		$('#boxErroresCompras').removeClass('hide');
		$("#erroresCompras").html(errores);
		$('#boxErroresCompras').addClass('show');
	}else{
		//cargavto();
	}

	
}
window.onload = function() {
       <?php if ($ocnum_estado == 1) { ?>
		$('#titulovError').html('Error');
		$('#cuerpovError').html("Por favor, finalice la orden en proceso de edición.");	
		$('#ventanamodalError').modal('show');
		$('#ventanamodalError').on('hidden.bs.modal', function() {
			var ocnum = <?php echo $ocnum; ?>;
			var url = '../compras_ordenes/compras_ordenes_det.php?id=' + ocnum;
			document.location.href = url;
		});

	   <?php } ?>
	   <?php if ($idmoneda_select != $id_moneda_nacional) { ?>
			$("#radio_moneda_extranjera").click();
			$("#radio2").css("display","none");
			$("#radio_moneda_form_descuento").click();
			$("#form_descuento_tipo_moneda").css("display","none");
			
		<?php } ?>

	   //////////////////////////////////////////////////////////////
};
let nacional_select_moneda=true;
function transformar_precio_descuento_moneda(event,nacional){
	const radio = event.target;
  // Si el radio button no está marcado (unchecked), ejecutamos la función
  if (radio.checked && nacional_select_moneda ==true) {
    transformar_precio_descuento(nacional);
	nacional_select_moneda=false;

  }
}
function transformar_precio_descuento(nacional){
	var descuento_valor = $("#descuentos_form #monto").val();
	var cotizacion = <?php echo $cotizacion ? $cotizacion : 1; ?>;
	cotizacion = parseFloat(cotizacion);
	if(nacional==false){
		descuento_valor = descuento_valor/cotizacion;
		$("#descuentos_form #monto").val(descuento_valor);
	}else{
		
		descuento_valor = descuento_valor*cotizacion;
		$("#descuentos_form #monto").val(descuento_valor);
	}
}
function transformar_monto_factura(monto_factura){
	var descuento_valor = monto_factura;
	var cotizacion = <?php echo $cotizacion ? $cotizacion : 1; ?>;
	cotizacion = parseFloat(cotizacion);
	descuento_valor = descuento_valor/cotizacion;
	return descuento_valor;
}
function transformar_precio_descuento_nacional(event,nacional){
	const radio = event.target;
  
  // Si el radio button no está marcado (unchecked), ejecutamos la función
  if (radio.checked && nacional_select_moneda ==false) {
    transformar_precio_descuento(nacional);
	nacional_select_moneda=true;
  }
}
</script>
<style>
	.color_gris_botones{
		color: #BDBDBD;
  		font-size: 1.4rem;
	}
	.color_azul_botones{
		color: #8CA6BE;
  		font-size: 1.4rem;
		  border: #8CA6BE solid 1px !important;

	}
	.alerta_color{
		background: #ce2d4fa8;
    	color: white;
		font-weight: bold;
	}
	.hover_embarque:hover{
		background-color: hsl(210, 50%, 70%) !important;
		color: #fff !important;
		border: hsl(210, 50%, 70%) solid 1px !important;
	}

	.hover_embarque:hover span{
		background-color: hsl(210, 50%, 70%) !important;
		color: #fff !important;
		border: hsl(210, 50%, 70%) solid 1px !important;
	}
	.detalles_articulo{
		/* width: 100%; */
		height: 300px !important;
	}
	.detalles_articulo p{
		display: inline-block;
	}
	.dropbtn {
	background-color: #04AA6D;
	color: white;
	padding: 16px;
	font-size: 16px;
	border: none;
	cursor: pointer;
	}

	.dropbtn:hover, .dropbtn:focus {
	background-color: #3e8e41;
	}

	#myInput {
	box-sizing: border-box;
	background-image: url('searchicon.png');
	background-position: 14px 12px;
	background-repeat: no-repeat;
	font-size: 16px;
	padding: 14px 20px 12px 45px;
	border: none;
	border-bottom: 1px solid #ddd;
	}

	#myInput:focus {outline: 3px solid #ddd;}

	.dropdown {
	position: relative;
	display: inline-block;
	}

	.dropdown-content {
	display: none;
	position: absolute;
	background-color: #f6f6f6;
	min-width: 230px;
	overflow: auto;
	border: 1px solid #ddd;
	z-index: 1;
	}

	.dropdown-content a {
	color: black;
	padding: 12px 16px;
	text-decoration: none;
	display: block;
	}

	.dropdown a:hover {background-color: #ddd;}

	.show {display: block;}
</style>	
	
	<!------------------------------------------------------------------->
  </head>
<?php
//Rellenamos algunas variables aca hast que se ordene arriba

?>
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
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
			
			<!-- ERRORES COMRPAS -->
			<div class="modal fade bs-example-modal-lg	"  tabindex="-1" role="dialog" aria-hidden="true" id="ventanamodalError" >
				<div class="modal-dialog modal-lg">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title" id="titulovError"></h4>
						</div>
						<div class="modal-body" id="cuerpovError" >
						
						</div>
						<div class="modal-footer"  id="pievError">
							<button type="button" id="cerrarpopError" style="display:none;" class="btn btn-default" data-dismiss="modal">Cerrar</button>&nbsp;
						</div>
					</div>
				</div>
			</div>
			<div class="alert alert-danger alert-dismissible fade in hide" role="alert" id="boxErroresCompras" >
			<button type="button" class="close"  aria-label="Close" onclick="cerrar_errores_compras()"><span aria-hidden="true" >×</span>
			</button>
			<strong>Errores:</strong><br /><p id="erroresCompras"></p>
			</div>
			<?php require_once("compras_cabecera_cuerpo.php"); ?>
            <!-- SECCION --> 
			<?php if ($idembarque > 0 && $preferencias_importacion == "S") {?>

				<div class="row">
				<div class="col-md-12 col-sm-12 col-xs-12">
					<div class="x_panel">
					<div class="x_title">
						<h2><span class="fa fa-ship"></span>&nbsp;Embarque Asociado</h2>
						<ul class="nav navbar-right panel_toolbox">
						<li>
						<a href="javascript:void(0);"  class="btn btn-sm btn-default hover_embarque" onmouseup="window.open('../embarque/embarque_add.php?ocn=<?php echo $ocnum;?>&path=compras', '_blank');"><span class="fa fa-ship"></span> <strong>Embarcacion</strong></a>
						</li>
						</ul>
						<div class="clearfix"></div>
					</div>
					
					</div>
				</div>
				</div>
				<!-- SECCION --> 

			<?php }
			if ($alerta_embarque != "" && $preferencias_importacion == "S") {
			    ?>
				<div class="row">
				<div class="col-md-12 col-sm-12 col-xs-12">
					<div class="x_panel">
						<div class="x_title">
							<h2><span class="fa fa-ship"></span>&nbsp;Embarque Asociado</h2>
							<ul class="nav navbar-right panel_toolbox">
							
							</ul>
							<div class="clearfix"></div>
						</div>
						<div style="display: flex;justify-content: space-between;align-items: center;">
							<?php echo $alerta_embarque; ?>
							<a href="javascript:void(0);"  class="btn btn-sm btn-default hover_embarque" onmouseup="window.open('../embarque/embarque_add.php?ocn=<?php echo $ocnum;?>&idcompra=<?php echo $idtransaccion; ?>&path=compras', '_blank');"><span class="fa fa-plus"></span> <strong>Agregar Embarcacion</strong></a>
						</div>

					
					</div>
				</div>
				</div>
				<!-- SECCION --> 

			<?php } ?>
            
			 <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2><span class="fa fa-search"></span></span>&nbsp;Buscar productos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
						<div class="col-md-12 col-xs-12">
							<div class="col-md-12" id="busqueda_productos">
								<?php require_once("compras_buscador_productos.php"); ?>
							</div>
							<div class="col-md-12" id="busqueda_productos_resultado">
								
							</div>
						</div>
						
                  </div>
                </div>
              </div>
            </div>
            
			 <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2><span class="fa fa-shopping-cart"></span>&nbsp;Detalle de compra</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
						<div class="col-md-12 col-xs-12" id="carritocompras">
							<?php require_once("compras_carrito.php"); ?>
							
						</div>
						
                  </div>
                </div>
              </div>
            </div>
			
			
			
			
			
			
			
			
            <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="ventanamodal">
				<div class="modal-dialog modal-lg">
				  <div class="modal-content">
					<div class="modal-header">
					  <h4 class="modal-title" id="titulov"></h4>
					</div>
					<div class="modal-body" id="cuerpov" >
						
					</div>
					<div class="modal-footer"  id="piev">
					  
					  <button type="button" id="cerrarpop" style="display:none;" class="btn btn-default" data-dismiss="modal">Cerrar</button>&nbsp;
					  
					</div>

				  </div>
				</div>
			</div>
			
			
			
			
			
            
          </div>
        </div>
        <!-- /page content -->
		<script>
			function mostrar_datos(){
				var idtransaccion=<?php echo $idt ?>;
				
				$("#titulov").html("Datos de Factura");
				//$("#piev").hide();
				
				
				var parametros = {
					"idtransaccion"	: idtransaccion
				};
				$.ajax({		  
					data:  parametros,
					url:   'compras_mostrar_cabecera.php',
					type:  'post',
					cache: false,
					timeout: 30000,  // I chose 3 secs for kicks: 3000
					crossDomain: true,
					beforeSend: function () {	
										
					},
					success:  function (response) {
						
						$("#cuerpov").html(response);	
						$("#ventanamodal").modal("show");
					}
				});

			}
			
			
			
			
			
			function cerrar_pop(){
				$("#ventanamodal").modal("hide");
			}
			
			
			function cerrar_pop2(){
				$("#modal_ventana").modal("hide");
			}
			
		</script>

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
