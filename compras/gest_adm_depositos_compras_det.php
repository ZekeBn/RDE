<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "107";
//error_reporting(E_ALL);
require_once("../includes/rsusuario.php");
require_once("preferencias_compras.php");
require_once("../deposito/preferencias_deposito.php");

$idcompra = intval($_GET['id']);

// funciones para stock
require_once("../includes/funciones_stock.php");

$consulta = "SELECT idtipo_origen FROM tipo_origen WHERE  UPPER(tipo)='IMPORTACION'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_tipo_origen_importacion = intval($rs_guarani->fields["idtipo_origen"]);

if ($id_tipo_origen_importacion == 0) {
    $errores = "- Por favor cree el Origen IMPORTACON.<br />";
}

//buscando moneda nacional
$consulta = "SELECT  tipo_moneda.idtipo, tipo_moneda.descripcion as nombre FROM tipo_moneda WHERE nacional='S'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];
$nombre_moneda_nacional = $rs_guarani->fields["nombre"];


$idmoneda_select = "";

$idcompra = intval($_GET['idcompra']);
if ($idcompra == 0) {
    header("location: gest_adm_depositos_compras.php");
    exit;
}



if (isset($_POST['occompra']) && ($_POST['occompra'] > 0)) {

    $buscar = "Select * from preferencias_compras limit 1";
    $rsprefecompras = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $depodefecto = trim($rsprefecompras->fields['usar_depositos_asignados']);

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


    $deposito = intval($_POST['iddeposito']);
    $iddeposito = $deposito;
    $idcompra = $idcompra;
    $idproveedor = intval($_GET['proveedor']);
    //Depositos
    // se muestran los prodctos del deposito
    $buscar = "
	Select 
	iddeposito,descripcion 
	from gest_depositos 
	where 
	iddeposito=$deposito 
	and idempresa = $idempresa
	and estado = 1
	and tiposala <> 3
	";
    $rsptos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tiposala = intval($rsptos->fields['tiposala']);
    $iddeposito = intval($rsptos->fields['iddeposito']);
    //obtiene facturas de proveedores
    $consulta = "
	select 
	id_factura, fecha_compra, id_proveedor, factura_numero, fecha_compra, idcompra
	from facturas_proveedores 
	where 
	idcompra = $idcompra
	";
    $rsfac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_factura = $rsfac->fields['id_factura'];
    $fecha_compra = $rsfac->fields['fecha_compra'];
    $idprov = $rsfac->fields['id_proveedor'];
    $factura_numero = $rsfac->fields['factura_numero'];
    $fecha_compra = $rsfac->fields['fecha_compra'];
    $idcompra = $rsfac->fields['idcompra'];
    if (intval($id_factura) == 0) {
        echo "Factura inexistente!";
        exit;
    }

    if (intval($iddeposito) == 0 && $depodefecto != 'S') {
        echo "Deposito inexistente!";
        exit;
    }

    /* //Paso2 Actualizar ubicacion en costo_productos (SE HACE ABAJO EN EL WHLE)
    $update="Update costo_productos set ubicacion=$deposito where idcompra=$idcompra";
    $conexion->Execute($update) or die(errorpg($conexion,$update));
    */


    //  PREFERENCIA DESPACHO
    if ($preferencias_importacion == "S") {
        if (compra_importacion($idcompra)['success'] == true) {
            if (despacho_verificar($idcompra)['success'] == false) {
                $errores .= "- La Compra de Importacion no tiene un Despacho asociado.<br />";
                $valido = "N";
            }
        }
    }
    // fin preferencia


    $buscar = "Select * from compras_detalles where idcompra=$idcompra order by idregs asc";
    //  echo $buscar;
    //  exit;
    $rs2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    while (!$rs2->EOF) {
        if (intval($rs2->fields['iddeposito_compra']) == 0 && $iddeposito == 0) {
            $errores .= "- El deposito del id idregs ".$rs2->fields['idregs']." no tiene un deposito seleccionado.<br />";
            $valido = "N";
        }
        $rs2->MoveNext();
    }
    if ($valido == 'S') {
        $parametros_array = [
            'id_factura' => $id_factura,
            'fecha_compra' => $fecha_compra,
            'id_proveedor' => $idprov,
            'factura_numero' => $factura_numero,
            'fecha_compra' => $fecha_compra,
            'idcompra' => $idcompra,
            "usar_depositos_asignados" => $depodefecto,
            "deposito" => $deposito,
            "iddeposito" => $iddeposito,
            "tiposala" => $tiposala,
            "idempresa" => $idempresa,
            "idsucursal" => $idsucursal,
            "idusu" => $idusu
        ];

        verificar_compra($parametros_array);



        //header("location: gest_adm_depositos_compras.php".$uriadd);
        header("location: compra_verificada.php?id=".$idcompra);
        exit;


    } // if($valido == 'S'){

}


// consulta a la tabla
$consulta = "
select tipo_moneda.banderita, compras.idtipo_origen ,compras.moneda as idmoneda, cotizaciones.cotizacion, tipo_moneda.descripcion as nom_moneda
from compras
LEFT JOIN cotizaciones on cotizaciones.idcot = compras.idcot
LEFT JOIN tipo_moneda on tipo_moneda.idtipo = compras.moneda
where
compras.idcompra = $idcompra 
";
$rs_cot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$idmoneda_select = $rs_cot->fields['idmoneda'];
$cotizacion = $rs_cot->fields['cotizacion'];
$nombre_moneda = $rs_cot->fields['nom_moneda'];
$idtipo_origen = $rs_cot->fields['idtipo_origen'];
$banderita = $rs_cot->fields['banderita'];

if (is_null($cotizacion)) {
    $consulta = "select cotizacion from cotizaciones where cotizaciones.fecha = 
	(select compras.fechacompra from compras where idcompra = $idcompra)  order by cotizaciones.idcot desc limit 1";
    $gscotiz = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cotizacion = floatval($gscotiz->fields['cotizacion']);
}

$consulta = "
SELECT despacho.cotizacion as cot_despacho FROM despacho WHERE idcompra = $idcompra 
";
$rs_despa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$cot_despacho = $rs_despa->fields['cot_despacho'];
//validacion de funcion para validacion de costo promedio
?>
<!doctype html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
	<link href="../vendors/switchery/dist/switchery.min.css" rel="stylesheet">
	<script src="../vendors/switchery/dist/switchery.min.js" type="text/javascript"></script>
	<script>
		function generar_reporte_compra(){
			var idCompra = <?php echo $idcompra; ?>;
        var nuevaVentanaURL = 'compras_reporte.php?id=' + idCompra;
        window.open(nuevaVentanaURL, '_blank');
		}
		function generar_reporte_costo(){
			var idCompra = <?php echo $idcompra; ?>;
			var nuevaVentanaURL = 'compras_reporte_precio_costo_venta.php?id=' + idCompra;
			window.open(nuevaVentanaURL, '_blank');
		}
		function IsJsonString(str) {
			try {
				JSON.parse(str);
			} catch (e) {
				return false;
			}
			return true;
		}
		function registra_permiso(iddeposito){
			var direccionurl='gest_adm_depositos_autosel.php';	
			//alert(direccion);
			var parametros = {
			"iddeposito"  : iddeposito
			};
			$.ajax({		  
				data:  parametros,
				url:   direccionurl,
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function () {
						$("#box_td_"+iddeposito).html('Cargando...');	
				},
				success:  function (response, textStatus, xhr) {
					if(IsJsonString(response)){
						var obj = jQuery.parseJSON(response);
						if(obj.success == true){
							$("#box_td_"+iddeposito).html(obj.html_checkbox);
							if(obj.autosel_compras == "S"){
								var elems = document.querySelector('#box_'+iddeposito);
								$("#box_"+iddeposito).prop("checked",true)
								var switchery = new Switchery(elems);
								if(parseInt(obj.id_activo_anterior) >0){
									$("#box_td_"+obj.id_activo_anterior).html(obj.html_checkbox_anterior);
									var elemento_anterior = document.querySelector('#box_'+obj.id_activo_anterior);
									$("#box_"+obj.id_activo_anterior).prop("checked",false)
									var switchery = new Switchery(elemento_anterior);
								}
							}
							if(obj.autosel_compras == "N"){
								var elems = document.querySelector('#box_'+iddeposito);
								$("#box_"+iddeposito).prop("checked",false)
								var switchery = new Switchery(elems);
							}
						}else{
							alert(obj.errores);
						}
					}else{
						alert(response);	
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
		function switchery_reactivar(){
			var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));
			elems.forEach(function(html) {
				var switchery = new Switchery(html);
			});
		}
		function switchery_reactivar_uno(idsubmodulo){
				var elems = document.querySelector('#box_td_compra_cot_despacho');
				var switchery = new Switchery(elems);

		}
		function cerrar_pop(){
				$("#ventanamodal").modal("hide");
			}

			
			function borrar_gasto_asociado(event,idcompra,idregs,idempresa,nombre_producto){
			event.preventDefault();
			var parametros = {
					"idcompra"					: <?php echo $idcompra;?>,
					"idcompra_borrar"   	  	: idcompra,
					"idregs_borrar"		  		: idregs,
					"idempresa_borrar"		  	: idempresa,
					"nombre_producto_borrar"	: nombre_producto,
					"borrar"					: 1
			};
			// console.log(parametros);
			$.ajax({		  
				data:  parametros,
				url:   'gest_adm_gastos_compras_lista.php',
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function () {	
									
				},
				success:  function (response) {
					// console.log(response);
					$("#gest_admin_gastos_compra_listas").html(response);	
				}
			});

		}
		// function recargar_grilla_de_costeo(){
		// 	var idcompra=<?php echo $idcompra ?>;
		// 	var parametros = {
		// 			"idcompra"   	  : idcompra,
		// 	};
		// 	$.ajax({		  
		// 		data:  parametros,
		// 		url:   'gest_adm_costos.php',
		// 		type:  'post',
		// 		cache: false,
		// 		timeout: 3000,  // I chose 3 secs for kicks: 3000
		// 		crossDomain: true,
		// 		beforeSend: function () {	
									
		// 		},
		// 		success:  function (response) {
		// 			console.log(response);
		// 			$("#historial_costos_box").html(response);	
		// 		}
		// 	});
		// }
		function editar_deposito_compra(event,idcompra,idregs,idempresa,nombre_producto){
			event.preventDefault();
			var idcompra=<?php echo $idcompra ?>;
			var parametros = {
					"idcompra"   	  : idcompra,
					"idregs"		  : idregs,
					"idempresa"		  : idempresa,
					"nombre_producto"	: nombre_producto
			};

			$("#titulov").html("Deposito de Articulo");
			$.ajax({		  
				data:  parametros,
				url:   'editar_deposito_compra_modal.php',
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
		function registrar_cot_despacho_gasto(idcompra){
			event.preventDefault();
			var direccionurl='gastos_switch_update.php';	
			//alert(direccion);
			var parametros = {
			"idcompra"  : idcompra
			};
			$.ajax({		  
				data:  parametros,
				url:   direccionurl,
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function () {
					////////////////////////////////////////////
						$("#box_td_gasto_cot_despacho_"+idcompra).html('Cargando...');	
				},
				success:  function (response, textStatus, xhr) {
					if(IsJsonString(response)){
						var obj = jQuery.parseJSON(response);
						if(obj.success == true){
							
							recargar_grilla_datos(obj,1);
						}else{
							alert(obj.errores);
						}
					}else{
						alert(response);	
					}
					recargar_grilla_de_costeo();
					// console.log("hola");
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
		function registrar_cot_despacho(idcompra){
			event.preventDefault();
			var direccionurl='compras_switch_update.php';	
			//alert(direccion);
			var parametros = {
			"idcompra"  : idcompra
			};
			$.ajax({		  
				data:  parametros,
				url:   direccionurl,
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function () {
					////////////////////////////////////////////
						$("#box_td_compra_cot_despacho").html('Cargando...');	
				},
				success:  function (response, textStatus, xhr) {
					if(IsJsonString(response)){
						var obj = jQuery.parseJSON(response);
						if(obj.success == true){
							
							recargar_grilla_datos(obj,0);
						}else{
							alert(obj.errores);
						}
					}else{
						alert(response);	
					}
					recargar_grilla_de_costeo();
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
		function recargar_grilla_datos(obj,gastos){

			// console.log(obj.usa_cot_despacho );
			// event.preventDefault();
			if(gastos==0) {
				var direccionurl='gest_adm_depositos_compras_det_grillas.php';	
			}else{
				var direccionurl='gest_adm_gastos_compras_lista.php';	
			}
			//alert(direccion);
			var parametros = {
			"idcompra"  : <?php echo $idcompra; ?>,
			"idmoneda_select" : <?php echo $idmoneda_select;?>,
			"id_moneda_nacional" : <?php echo $id_moneda_nacional;?>,
			"cotizacion"	: <?php echo $cotizacion;?>,
			"gastos" : gastos

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
						// $("#box_td_compra_cot_despacho").html('Cargando...');	
				},
				success:  function (response, textStatus, xhr) {
					//console.log(direccionurl);
					// console.log(response);
							if(gastos==0) {
								$("#box_td_compra_cot_despacho").html(obj.html_checkbox);
								$("#box_compra_grl").html(response);
								if(obj.usa_cot_despacho == "S"){
									var elems = document.querySelector('#compra_cot_despacho');
									$("#compra_cot_despacho").prop("checked",true)
									var switchery = new Switchery(elems);
								}
								if(obj.usa_cot_despacho == "N"){
									var elems = document.querySelector('#compra_cot_despacho');
									
									$("#compra_cot_despacho").prop("checked",false)
									console.log(elems);
									var switchery = new Switchery(elems);
								}
							}else{
								$("#box_td_gasto_cot_despacho_"+obj.idcompra).html(obj.html_checkbox);
								$("#gest_admin_gastos_compra_listas").html(response);


								var tabla = document.getElementById('tabla_extranjera');

								// Buscar todos los elementos <input> dentro de la tabla
								var inputs = tabla.querySelectorAll('input[name^="gasto_cot_despacho_"]');

								// Array para almacenar los IDs de las filas
								var ids = [];

								// Recorrer los elementos <input> encontrados
								inputs.forEach(function(input) {
									// Obtener el ID de la fila a la que pertenece el input
									var switchery = new Switchery(input);
									// Agregar el ID al array
								});

								// Mostrar los IDs encontrados
								
								// if(obj.usa_cot_despacho == "S"){
								// 	var elems = document.querySelector('#gasto_cot_despacho_'+obj.idcompra);
								// 	$("#gasto_cot_despacho_"+obj.idcompra).prop("checked",true)
								// 	var switchery = new Switchery(elems);
								// }
								// if(obj.usa_cot_despacho == "N"){
								// 	var elems = document.querySelector('#gasto_cot_despacho_'+obj.idcompra);
								// 	$("#gasto_cot_despacho_"+obj.idcompra).prop("checked",false)
								// 	var switchery = new Switchery(elems);
								// }


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
	</script>
	<style>
		.sin_verificar{
		background: #ce2d4fa8;
			font-weight: bold;
		}
		.sin_verificar:hover{
		background: #ce2d4f;
			color: #000;
			font-weight: bold;
		}

		
		.verificado{
		background: #D7FFAB;
			font-weight: bold;
		}

		.verificado:hover{
		background: #C3EB97;
			color: #000;
			font-weight: bold;
		}


		.boton_reporte{
			width: 100%;
			padding: 1.1rem;
			background: #E88D67;
			color: #fff;
			box-sizing: border-box;
			margin-bottom: 1rem !important;
		}
		.boton_reporte:hover{
			background:#CAE5FF;
			color: black;
			border: 1px solid #CAE5FF;
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
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Verificar compra / Ingreso al stock</h2>
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

<p>
	<a href="javascript:history.back();void(0);" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Regresar</a>
	<!-- preferencias importacion  -->
	<?php if ($preferencias_importacion == "S" && $idtipo_origen == $id_tipo_origen_importacion) {?>
		<a href="../despacho/despacho_add.php?idcompra=<?php echo $idcompra; ?>" class="btn btn-sm btn-default <?php if (despacho_verificar($idcompra)['success']) {
		    echo "verificado";
		} else {
		    echo "sin_verificar";
		} ?>" title="Cotizacion Despacho" data-toggle="tooltip" data-placement="right"  data-original-title="Cotizacion Despacho"><span class="fa fa-suitcase"></span> Cotizacion Despacho</a>
	<?php } ?>
	<!-- fin de la preferencia  -->
	<?php if ($preferencias_importacion == "S") { ?>
		<a href="../compras/tmpcompras_add.php?idcompra=<?php echo $idcompra; ?>" class="btn btn-sm btn-default " title="Gastos" data-toggle="tooltip" data-placement="right"  data-original-title="Gastos"><span class="fa fa-plus"></span> Gastos Asociados</a>
	<?php } ?>
</p>

<?php if ((($multimoneda_local == "S" && $idtipo_origen != $id_tipo_origen_importacion) || $idmoneda_select != $id_moneda_nacional) && $preferencias_importacion == "S") { ?>
	<h2>Cotizacion <?php if ($banderita != '') {?><img src="../img/<?php echo $banderita?>"  width="20vw" /><?php }?></h2>
	
	
	<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
            <th align="center">Moneda</th>
			<th align="center">Cotizacion venta</th>
			<?php if ($idtipo_origen == $id_tipo_origen_importacion) { ?>
				<th align="center">Cotizacion Despacho</th>
			<?php } ?>
			
		</tr>
	  </thead>
	  <tbody>
		<tr>
			<td align="center"><?php echo antixss($nombre_moneda);  ?></td>
            <td align="right"><?php echo formatomoneda($cotizacion, "2", "S");  ?></td>
			<?php if ($idtipo_origen == $id_tipo_origen_importacion) { ?>
				<td align="center"><?php echo siono("N");  ?></td>
			<?php } ?>
		</tr>
		<?php if ($cot_despacho > 0) { ?>
			<tr>
				<td align="center"><?php echo antixss($nombre_moneda);  ?></td>
				<td align="right"><?php echo formatomoneda($cot_despacho, "2", "S");  ?></td>
				<?php if ($idtipo_origen == $id_tipo_origen_importacion) { ?>
					<td align="center"><?php echo siono("S");  ?></td>
				<?php } ?>
			</tr>
		<?php } ?>
	  </tbody>
    </table>
</div>


<?php } ?>
<hr />  
	<strong>Compras finalizadas (los productos aun  no ingresaron al stock)</strong><br />
<br />



<div id="box_compra_grl">
	<?php require_once('gest_adm_depositos_compras_det_grillas.php')?>
</div>
<div id="gest_admin_gastos_compra_listas">
	<?php require_once("./gest_adm_gastos_compras_lista.php"); ?>
</div>
<?php
$consulta = "
select gest_depositos.descripcion as deposito, facturas_proveedores.iddeposito, sucursales.nombre as sucursal,
facturas_proveedores.fecha_valida, facturas_proveedores.validado_por,
(select usuario from usuarios where idusu = facturas_proveedores.validado_por) as usu_validado_por
from facturas_proveedores 
inner join gest_depositos on gest_depositos.iddeposito = facturas_proveedores.iddeposito
inner join sucursales on sucursales.idsucu = gest_depositos.idsucursal
where 
idcompra = $idcompra
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<div class="warning">
<strong>Estado del Stock:</strong> 
<?php if ($rs->fields['iddeposito'] > 0) { ?>
Ingresado<br />
<strong>Deposito:</strong>  <?php echo $rs->fields['deposito']; ?> [<?php echo $rs->fields['iddeposito']; ?>]<br />
<strong>Local:</strong>  <?php echo $rs->fields['sucursal']; ?><br />
<strong>Fecha Validado:</strong>  <?php echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_valida'])); ?><br />
<strong>Usuario Validador:</strong>  <?php echo $rs->fields['usu_validado_por']; ?><br />
<?php } else { ?>
Pendiente de ingreso
<?php } ?>
</div>
<div class="clearfix"></div>
<br>
<br>
<br>
<div id="impresiones_box">
<h2 style="text-align: center;">Impresión de reporte</h2>
	<div id="historial_costos_box">
		<?php
//		if($preferencias_importacion=="S"){
?>
			<?php require_once("./gest_adm_costos.php"); ?>

		<?php // }?>
	</div>
</div>

<hr />
<form id="form1" name="form1" method="post" action="">
<?php if ($depodefecto == 'S') { ?>
<div class="col-md-12">
<div class="alert alert-info" role="alert">
 Atencion: El ingreso de los art&iacute;culos a dep&oacute;sitos asignados, se encuentra activo.Si desea dar ingreso a un dep&oacute;sito diferente,<br />
 seleccione de la lista desplegable , de esta forma,los art&iacute;culos ser&aacute;n ingresados al dep&oacute;sito seleccionado, y no a los establecios previamente.
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Deposito </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
        $deposito_por_defecto = "";
    if ($preferencia_autosel_compras == "S") {
        $select = "select iddeposito from gest_depositos where autosel_compras = 'S'";
        $rs_activo = $conexion->Execute($select) or die(errorpg($conexion, $select));
        $deposito_por_defecto = $rs_activo->fields['iddeposito'];
    }

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
        $value_selected = $deposito_por_defecto;
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
        'acciones' => '  ',
        'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
	</div>
</div>
<div class="col-md-6">


</div>







</div>
<?php } else { ?>
 
<strong>Dep&oacute;sito de Ingreso</strong> 





<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Deposito *</label>
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
        $value_selected = htmlentities($rs->fields['iddeposito']);
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

<?php } ?>
<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_reg_compras_resto_new.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>
    
	<input type="hidden" name="occompra" id="occompra" value="<?php echo $idcompra?>" />
  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />


                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>



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

  </body>
</html>
