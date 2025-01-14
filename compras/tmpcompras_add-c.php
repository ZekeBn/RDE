<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");
require_once("../includes/funciones_proveedor.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");
require_once("./preferencias_compras.php");


//buscando moneda iddespachante
$buscar = "SELECT idtipo_servicio FROM `tipo_servicio` WHERE UPPER(tipo) = UPPER('DESPACHANTE') and estado=1";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$iddespachante_valor = intval($rsd->fields['idtipo_servicio']);

//buscando moneda nacional
$consulta = "SELECT tipo_moneda.idtipo, tipo_moneda.descripcion as nombre FROM tipo_moneda WHERE nacional='S'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];
$nombre_moneda_nacional = $rs_guarani->fields["nombre"];

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


$consultas = "SELECT tmpcompras.idtran FROM tmpcompras
inner join transacciones_compras as tr
on tr.numero = tmpcompras.idtran 
WHERE tr.idusu=$idusu 
and tr.estado=1
limit 1";
$rstransaccion = $conexion->Execute($consultas) or die(errorpg($conexion, $consultas));
$idtran = $rstransaccion->fields["idtran"];
if ($idtran > 0) {
    header("location: tmpcompras_edit.php?id=$idtran");
}
$ocnum = intval($_GET['ocnum']);
$buscar = "SELECT idproveedor, nombre, ruc
FROM proveedores
where
estado = 1
order by nombre asc";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idproveedor = trim(antixss($rsd->fields['idproveedor']));
    $nombre = trim(antixss($rsd->fields['nombre']));
    $ruc = trim(antixss($rsd->fields['ruc']));
    $resultados .= "
	<a class='a_link_proveedores' data-hidden-value='$ruc' href='javascript:void(0);' onclick=\"cambia_prov($idproveedor)\">[$idproveedor]-$nombre</a>
	";

    $rsd->MoveNext();
}
///////////////////////////////////
$idproveedor = intval($_GET['idproveedor']);
//$idcompra=intval($_GET['idcompra']);
$idcompra = intval($_GET['ocnum']);


$consulta = "SELECT tipocompra, idtipo_servicio
from proveedores where idproveedor=$idproveedor;
";
$rsproveedor = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tipocompra = intval($rsproveedor->fields['tipocompra']);
$idtipo_servicio_proveedor = intval($rsproveedor->fields['idtipo_servicio']);



$idtran = seleccionar_mayor_idtran();
// si se selecciono proveedor
if ($idproveedor > 0) {

    // busca si existe en la bd
    $consulta = "
	select * from proveedores where estado = 1 and idproveedor = $idproveedor
	";
    $rsprov = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $incrementa = $rsprov->fields['incrementa'];
    $diasvence = $rsprov->fields['diasvence'];
    $idtipocompra = $rsprov->fields['idtipocompra'];
    // si no existe redirecciona
    if (intval($rsprov->fields['idproveedor']) == 0) {
        header("location: tmpcompras_add.php");
        exit;
    }
    // si es incremental
    if ($incrementa == 'S') {
        $facturacompra = fact_num_facturas_proveedores($idproveedor);//001-001-".agregacero($rsfac->fields['fact_num']+1,7)
        $tipocomprobante_def = 1;
    }

    // actualiza numeracion proveedor
    $consulta = "
	select idtipo_origen from proveedores 
	where 
	 idproveedor=$idproveedor;
	";
    $rs_proveedor = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idtipo_orgen_proveedor = $rs_proveedor->fields['idtipo_origen'];

    if ($idtipo_orgen_proveedor != $id_tipo_origen_importacion) {

        $consulta = "
		update facturas_proveedores 
		set 
		fact_num = CAST(substring(factura_numero from 7 for 9) as UNSIGNED)
		where 
		fact_num is null
		and id_proveedor=$idproveedor;
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    } else {
        $consulta = "
		update facturas_proveedores 
		set 
		fact_num = CAST(LEFT(factura_numero, 11) AS UNSIGNED)
		where 
		fact_num is null
		and id_proveedor=$idproveedor;
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }

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
	from facturas_proveedores 
	where 
	id_proveedor=$idproveedor
	and estado<>6 
	order by fecha_compra desc 
	limit 1
	";
    //echo $consulta;exit;
    $rstimb = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $timbrado_bd = $rstimb->fields['timbrado'];
    $vto_timbrado_bd = $rstimb->fields['vtotimbrado'];
    $idtipocomprobante_bd = $rstimb->fields['idtipocomprobante'];
    if ($idtipocomprobante_bd > 0) {
        $tipocomprobante_def = $idtipocomprobante_bd;
    }
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
    $sucursal = antisqlinyeccion($_POST['sucursal'], "float");
    $fechahora = antisqlinyeccion($ahora, "date");
    $estado = antisqlinyeccion(1, "float");
    $idtipocompra = antisqlinyeccion($_POST['idtipocompra'], "int");
    $fecha_compra = antisqlinyeccion($_POST['fecha_compra'], "date");
    //$facturacompra_incrementa=antisqlinyeccion($_POST['facturacompra_incrementa'],"int");
    $totalcompra = antisqlinyeccion(0, "float");
    $monto_factura = antisqlinyeccion($_POST['monto_factura'], "float");
    $idproveedor = antisqlinyeccion($_GET['idproveedor'], "int");
    $moneda = antisqlinyeccion($_POST['idmoneda'], "int");
    $cambio = antisqlinyeccion('0', "float");
    $cambioreal = antisqlinyeccion('0', "float");
    $cambiohacienda = antisqlinyeccion('0', "float");
    $cambioproveedor = antisqlinyeccion('0', "float");
    $vencimiento = antisqlinyeccion($_POST['vencimiento'], "date");
    $timbrado = antisqlinyeccion($_POST['timbrado'], "int");
    $vto_timbrado = antisqlinyeccion($_POST['vto_timbrado'], "text");
    $ocnum = antisqlinyeccion($_POST['ocnum'], "int");
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "date");
    $facturacompra_guion = antisqlinyeccion(trim($_POST['facturacompra']), "text");
    $idtipocomprobante = antisqlinyeccion(trim($_POST['idtipocomprobante']), "int");
    $cdc = antisqlinyeccion(limpiacdc($_POST['cdc']), "text");
    $descripcion = antisqlinyeccion($_POST['descripcion'], "text");
    $idtipo_origen = antisqlinyeccion($_POST['idtipo_origen'], "int");
    $idcot = antisqlinyeccion($_POST['idcotizacion'], "int");
    $idmoneda = antisqlinyeccion($_POST['idmoneda'], "int");
    $radio_moneda = antisqlinyeccion($_POST['radio_moneda'], "int");
    $idcompra_ref = antisqlinyeccion($_POST['idcompra_ref'], "int");


    // echo json_encode($_POST);exit;
    // validar formato de factura
    $factura_part = explode("-", trim($_POST['facturacompra']));
    $factura_prov_suc = trim($factura_part[0]);
    $factura_prov_pex = trim($factura_part[1]);
    $factura_prov_nro = trim($factura_part[2]);
    $facturacompleta = trim($factura_prov_suc.$factura_prov_pex.$factura_prov_nro);
    $facturacompra_incrementa = intval($factura_prov_nro);
    $facturacompra = antisqlinyeccion($facturacompleta, "text");
    if ($preferencias_importacion == "N") {
        $radio_moneda = $id_moneda_nacional;
        $idtipo_origen = $id_tipo_origen_local;
    }
    if (intval($idcot) > 0 && intval($radio_moneda) != $id_moneda_nacional) {
        //

        $consulta = "
		Select cotizacion, fecha as fecha_cotizacion
		from cotizaciones 
		where 
		idcot=$idcot
		and estado<>6 
		limit 1
		";
        //echo $consulta;exit;
        $rscotizacion = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $cotizacion = $rscotizacion->fields['cotizacion'];
        $fecha_cotizacion = date("d/m/Y", strtotime($rstran->fields['fecha_cotizacion']));
        $monto_factura_aux = $monto_factura;
        $monto_factura = $monto_factura * $cotizacion;
        if ($multimoneda_local == "N" && $idtipo_origen == $id_tipo_origen_importacion) {
            $monto_factura = $monto_factura_aux;
        }

    }

    ///
    $parametros_array = [
        "sucursal" => $sucursal,
        "fechahora" => $fechahora,
        "estado" => $estado,
        "tipocompra" => $idtipocompra,
        "fecha_compra" => $fecha_compra,
        "totalcompra" => $totalcompra,
        "monto_factura" => $monto_factura,
        "idproveedor" => $idproveedor,
        "moneda" => $moneda,
        "cambio" => $cambio,
        "cambioreal" => $cambioreal,
        "cambiohacienda" => $cambiohacienda,
        "cambioproveedor" => $cambioproveedor,
        "vencimiento" => $vencimiento,
        "descripcion" => $descripcion,
        "timbrado" => $timbrado,
        "vto_timbrado" => $vto_timbrado,
        "ocnum" => $ocnum,
        "registrado_por" => $registrado_por,
        "registrado_el" => $registrado_el,
        "facturacompra_guion" => $facturacompra_guion,
        "idtipocomprobante" => $idtipocomprobante,
        "cdc" => $cdc,
        "factura_part" => $factura_part,
        "factura_prov_suc" => $factura_prov_suc,
        "factura_prov_pex" => $factura_prov_pex,
        "factura_prov_nro" => $factura_prov_nro,
        "facturacompleta" => $facturacompleta,
        "facturacompra_incrementa" => $facturacompra_incrementa,
        "facturacompra" => $facturacompra,
        "obliga_cdc" => $obliga_cdc,
        "tipos_comprobantes_vence_ar" => $tipos_comprobantes_vence_ar,
        "idtran" => $idtran,
        "idusu" => $idusu,
        "idempresa" => $idempresa,
        "idtipo_origen" => $idtipo_origen,
        "idcot" => $idcot,
        "idmoneda" => $idmoneda,
        "id_tipo_origen_importacion" => $id_tipo_origen_importacion,
        "idcompra_ref" => $idcompra_ref,
        "id_moneda_nacional" => $id_moneda_nacional,
        "new" => 1
    ];
    $respuesta = validar_cabecera_compra($parametros_array);
    if ($respuesta['valido'] == 'N') {
        $valido = $respuesta['valido'];
        $errores .= nl2br($respuesta['errores']);
    }
    // si todo es correcto inserta
    if ($respuesta["valido"] == "S" && $valido == "S") {
        $respuesta = registrar_cabecera_compra($parametros_array);
        if ($idtipocompra == 2) {
            $consulta = "
			select * from tmpcompravenc where idtran=$idtran
			";
            $rstieneven = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $totalvenc = intval($rstieneven->RecordCount());

            if (intval($rstieneven->fields['idtran']) == 0) {
                $consulta = "
				INSERT INTO tmpcompravenc
				( idtran, vencimiento, monto_cuota)
				VALUES
				($idtran,$vencimiento,$monto_factura);
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            }
        }
        if ($totalvenc == 1 && intval($idtran) > 0) {

            $consulta = "
			update tmpcompravenc
			set
			vencimiento = $vencimiento,
			monto_cuota = $monto_factura
			where
			idtran=$idtran
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }
        if ($idtipocompra == 1) {
            $consulta = "
				select * from tmpcompravenc where idtran=$idtran
				";
            $rstieneven = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $totalvenc = intval($rstieneven->RecordCount());
            if ($totalvenc > 0) {

                $consulta = "
				delete from tmpcompravenc
				where
				idtran=$idtran
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }

        }
        header("location: compras_detalles.php?id=".$respuesta["idtran"]);
        // exit;
    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());






?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>
	function cambiar_tipocompra(ocnum){
		var parametros = {
						"ocnum"     : ocnum,
						"obtener_tipocompra" : 1
				};
				// console.log(parametros);
				$.ajax({
					data:  parametros,
					url:   'consultar_tipocompra_ocn.php',
					type:  'post',
					beforeSend: function () {
						// $("#vencimiento").val('Cargando...');
					},
					success:  function (response) {
						// console.log(response);
						if(jQuery.parseJSON(response)['success']) {
							var obj = jQuery.parseJSON(response);
							if(obj.tipocompra != null) {
								$("#idtipocompra").val(obj.tipocompra);
								$("#idtipo_origen").val(obj.idtipo_origen);
								if(obj.idtipo_moneda !=0){
									$("#idmoneda").val(obj.idtipo_moneda);

								}else{
									var moneda_nacional= <?php echo $id_moneda_nacional ? $id_moneda_nacional : 0 ;?>;
									if (moneda_nacional != 0){
										$("#idmoneda").val(obj.moneda_nacional);
									}
								}
								moneda_div($("#idtipo_origen").val());
								
							}
						}else{
						}
					}
				});
	}
	document.addEventListener("DOMContentLoaded", function() {
      // Código que se ejecutará cuando el DOM esté listo
	  var id  = $("#idtipocompra").val();
      tipo_compra(id);

	  <?php if ($_GET['idproveedor'] && $preferencias_importacion == "S") { ?>
		verificar_proveedor_importacion();

	  <?php } ?>
	  <?php if ($_GET['ocnum']) { ?>
		cambiar_tipocompra( <?php echo $ocnum ? $ocnum : 0;?> );

	  <?php } ?>


    });
	window.onload = function() {

		moneda_div($('#idtipo_origen').val());
		$('#idcot').on('mousedown', function(event) {
			// Evitar que el select se abra
			event.preventDefault();
		});
		$("#idcot").css('background', '#EEE');
		$("#idcot").css('cursor', 'pointer');

	};
	window.ready = function() {

		moneda_div($('#idtipo_origen').val());
		$('#idcot').on('mousedown', function(event) {
                // Evitar que el select se abra
                event.preventDefault();
            });
			$("#idcot").css('background', '#EEE');
			$("#idcot").css('cursor', 'pointer');
		
	};
	function verificar_proveedor_importacion(){
		var selectElement = document.getElementById('idproveedor');
		var selectedOption = selectElement.options[selectElement.selectedIndex];
		var idmoneda = selectedOption.getAttribute('data-hidden-value');
		var idtipo_origen = selectedOption.getAttribute('data-hidden-value2');
		<?php if (intval($_POST['idtipo_origen']) == 0 || intval($_POST['idtipo_origen']) == $id_tipo_origen_importacion) {?>
			$("#idtipo_origen").val(idtipo_origen);
			$("#idmoneda").val(idmoneda);
		<?php } ?>

		
		var id_tipo_origen_importacion = <?php echo $id_tipo_origen_importacion ? $id_tipo_origen_importacion : 0;?>;
		if( id_tipo_origen_importacion != idtipo_origen){
			verificar_cotizacion_moneda();
		} 
	}
	function cargar_cotizacion(){
		var parametros = {
			"idmoneda"   : $("#idtipo_moneda").val()
		};
		$.ajax({
			data:  parametros,
			url:   './cotizacion_add_modal.php',
			type:  'post',
			beforeSend: function () {
				
			},
			success:  function (response) {
					alerta_modal("Agregar Cotizacion", response);
			}
		});
	}
	function verificar_cotizacion_moneda(){
		var idmoneda= $("#idmoneda").val();
		var fecha_factura = $("#fecha_compra").val();
		var despacho = <?php   echo ($iddespachante_valor == $idtipo_servicio_proveedor) ? "true" : "false"; ?>;


		var url = window.location.href;
		var parametrosURL = new URLSearchParams(new URL(url).search);
		var idcompra = parseInt( parametrosURL.get("idcompra") );





		// console.log("el despacho ",despacho);
		var parametros = {
		"idmoneda"   : idmoneda,
		"fecha_factura" : fecha_factura
		};
		// console.log(parametros);

		
			$.ajax({
				data:  parametros,
				url:   '../compras_ordenes/cotizaciones_hoy_modal.php',
				type:  'post',
				beforeSend: function () {
					
				},
				success:  function (response) {
					// console.log(response);
					if(JSON.parse(response)['success']==false){
						alerta_modal("Alerta!",JSON.parse(response)['error']);
						$("#idcot").css('border', '1px solid red');
						$('#idcot').prop('readonly', true);
					}else{
						
						var cotiza = JSON.parse(response)['cotiza'];
						if(cotiza == true){
							var idcot = JSON.parse(response)['idcot'];
							var cotizacion = JSON.parse(response)['cotizacion'];
							var fecha = JSON.parse(response)['fecha'];
							$('#idcot').html($('<option>', {
								value: idcot,
								text: cotizacion
							}));
							// console.log(fecha);
							$("#fecha_cotizacion").html(fecha);
							$("#fecha_cotizacion").css("display","table-cell");
							$("#fecha_cotizacion_text").removeClass("hide");
						
							// Seleccionar opción
							$('#idcot').val(idcot);
							$('#idcotizacion').val(idcot);
							$('#idcot').prop('readonly', true);
	
						}else{
							$('#idcot').html("");
							$('#idcot').prop('readonly', true);
							$("#idcot").css('border', '1px solid #ccc');
						}
						
					
					}
				}
			});

			
		// esto es si despacho solo usa el despacho de la compra asociada
		// if(!desapcho) {
		// }else{
		// 	if( idcompra > 0 ) {
		// 		$.ajax({
		// 			data:  parametros,
		// 			url:   './cot_despachante.php',
		// 			type:  'post',
		// 			beforeSend: function () {
						
		// 			},
		// 			success:  function (response) {
		// 				console.log(response);
		// 				if(JSON.parse(response)['success']==false){
		// 					alerta_modal("Alerta!",JSON.parse(response)['error']);
		// 					$("#idcot").css('border', '1px solid red');
		// 					$('#idcot').prop('readonly', true);
		// 				}else{
							
		// 					var cotiza = JSON.parse(response)['cotiza'];
		// 					if(cotiza == true){
		// 						var idcot = JSON.parse(response)['idcot'];
		// 						var cotizacion = JSON.parse(response)['cotizacion'];
		// 						var fecha = JSON.parse(response)['fecha'];
		// 						$('#idcot').html($('<option>', {
		// 							value: idcot,
		// 							text: cotizacion
		// 						}));
		// 						console.log(fecha);
		// 						$("#fecha_cotizacion").html(fecha);
		// 						$("#fecha_cotizacion").css("display","table-cell");
		// 						$("#fecha_cotizacion_text").removeClass("hide");
							
		// 						// Seleccionar opción
		// 						$('#idcot').val(idcot);
		// 						$('#idcotizacion').val(idcot);
		// 						$('#idcot').prop('readonly', true);
		
		// 					}else{
		// 						$('#idcot').html("");
		// 						$('#idcot').prop('readonly', true);
		// 						$("#idcot").css('border', '1px solid #ccc');
		// 					}
							
						
		// 				}
		// 			}
		// 		});
		// 	}else{
		// 		alerta_modal("Alerta!","No existe ninguna compra asociada que posea una Cotizacion de despacho activa ");
		// 		$("#idcot").css('border', '1px solid red');
		// 		$('#idcot').prop('readonly', true);
		// 	}
		// }

		var id_moneda_nacional=<?php echo $id_moneda_nacional; ?>;
		if (id_moneda_nacional != idmoneda &&  idmoneda != null){
			$("#box_monto_moneda").css("display", "none");
			///////////////////////////////////
			$("#radio_moneda_extranjera").click();
			///////////////////////////////////////////////////
			$("#radio_moneda_extranjera").val(idmoneda);
			$("#label_moneda_extranjera").html($("#idmoneda option:selected").text());
		}else{
			$("#box_monto_moneda").css("display", "none");
		}
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
					alerta_modal("Cotizaciones disponibles",response);
				}
			});
		}

	}
	function cerrar_pop(){
		$("#ventanamodal").modal("hide");
	}
	function alerta_modal(titulo,mensaje){
		$('#modal_ventana').modal('show');
		$("#modal_titulo").html(titulo);
		$("#modal_cuerpo").html(mensaje);
	}
	function IsJsonString(str) {
		try {
			JSON.parse(str);
		} catch (e) {
			return false;
		}
		return true;
	}
	function tipo_compra(tipo){
		
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
					url:   '../cargavto_new.php',
					type:  'post',
					beforeSend: function () {
						// $("#vencimiento").val('Cargando...');
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
			alerta_modal('Incorrecto',errores);
			$("#fecha_compra").val('');
		}else{
			//cargavto();
		}
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		var fecha_vencimiento=fecha_sumar_dias_js( $("#fecha_compra").val() );
		$("#vencimiento").val(fecha_vencimiento);
	}
	function validar_fecha_vencimiento(){

		var tipo_origen = $("#idtipo_origen").val(); // 1=local 2=importacion

		if(tipo_origen == 1) {
			fecha = $("#vencimiento").val();
			fecha_compracion = $("#fecha_compra").val();
			
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
	
	
	
			var fe1=fecha_compracion.split("-");
			var ano1=fe1[0];
			var mes1=fe1[1]-1;
			var meshtml1= fe1[1];
			var dia1=fe1[2];
			var fdesde = new Date(ano1, mes1, dia1);
			// fecha no puede estar en el futuro
		
			// la fecha no puede ser menor a la fecha desde
			if(f1 < fdesde){
				valido = 'N';
				errores = 'La Fecha de vencimiento ('+dia+'/'+meshtml+'/'+ano+') no puede ser menor a la fecha de la compra:'+dia1+'/'+meshtml1+'/'+ano1+'.';	
			}
			
			if(valido == 'N'){
				alerta_modal('Incorrecto',errores);
				$("#fecha_compra").val('');
			}else{
				//cargavto();
			}
		}
	}
	function verificarFormato() {
		// Dividir la cadena por el carácter "-"
		if ($("#idtipo_origen").val() == 1) {
			
			cadena = $("#facturacompra").val();
			const elementos = cadena.split("-");
			var valido = 'S';
			// Verificar que tenga exactamente 3 elementos separados por "-"
			if (elementos.length !== 3) {
				valido = 'N';
				errores = 'Debe mantener el Formato ejemplo: 001-001-0000123 favor verifique el valor: '+cadena;	
			
			}
	
			// Verificar que cada elemento tenga la longitud esperada
			if (
				elementos[0].length === 3 &&
				/^\d+$/.test(elementos[0]) &&
				elementos[1].length === 3 &&
				/^\d+$/.test(elementos[1]) &&
				elementos[2].length === 7 &&
				/^\d+$/.test(elementos[2])
			) {
				
			} else {
				valido = 'N';
				errores = 'Debe mantener el Formato ejemplo: 001-001-0000123 favor verifique el valor: '+cadena;	
			
			}
	
			if(valido == 'N'){
				alerta_modal('Incorrecto',errores);
				// $("#facturacompra").val('');
			}
		}
	
	}
	function fecha_sumar_dias_js(fechaString ) {
		var dias = <?php echo $diasvence != 0 ? $diasvence : 0; ?>;

		// Convertir la fecha a un objeto Date con la zona horaria local
		var fecha = new Date(Date.parse(fechaString + 'T00:00:00'));

		fecha.setDate(fecha.getDate() + dias); // Suma la cantidad de días a la fecha

		var year = fecha.getFullYear();
		var month = ('0' + (fecha.getMonth() + 1)).slice(-2); // Ajusta el mes para obtener el formato 'MM'
		var day = ('0' + fecha.getDate()).slice(-2); // Ajusta el día para obtener el formato 'DD'

		var fechaFinal = year + '-' + month + '-' + day; // Formato 'Y-m-d'

		return fechaFinal; // Muestra la fecha resultante en la consola

		// Si deseas devolver la fecha final en lugar de mostrarla en la consola, puedes usar 'return fechaFinal;'
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
			alerta_modal('ALERTA',errores);
		}else{
			//cargavto();
		}

		
	}
	function getParams(url) {
		const params = {};
		const query = url.split('?')[1]; // Obtiene la cadena de consulta sin el "?"
		if (query) {
		const pairs = query.split('&');
		for (const pair of pairs) {
			const [key, value] = pair.split('=');
			params[key] = decodeURIComponent(value); // Decodificar el valor (por si contiene caracteres especiales)
		}
		}
		return params;
  	}
	function cambia_prov(idproveedor,idemoneda,idtipo_origen){
		const url = window.location.search;
    	const params = getParams(url);
		var idcompra = null;
		var url_tmp;
		if(parseInt(params['idcompra']) > 0){
			idcompra = parseInt(params['idcompra']);
			url_tmp = 'tmpcompras_add.php?idcompra='+idcompra+'&idproveedor='+idproveedor;
		}else{
			url_tmp = 'tmpcompras_add.php?idproveedor='+idproveedor;
		}
		document.location.href=	url_tmp;
	}
	function tipo_comprobante(tipo){
		// CDC si es factura electronica
		if(tipo == 4){
			$("#cdc_box").show();
		}else{
			$("#cdc_box").hide();
			$("#vto_timbrado_box").show();
		}
		var tipos_comprobantes_vence = '<?php echo $tipos_comprobantes_vence; ?>'; 
		// vencimiento timbrado
		if(inArray(tipo, tipos_comprobantes_vence)){
			$("#vto_timbrado_box").show();
		}else{
			$("#vto_timbrado_box").hide();
		}
		
	}
	function inArray(needle, haystack) {
		var length = haystack.length;
		for(var i = 0; i < length; i++) {
			if(haystack[i] == needle) return true;
		}
		return false;
	}
	function get_numbers(txt) {
		var res = txt.replace(/\D/g, '');
		//alert(res);
		return res;
	}
	function filterFunction(event) {
		event.preventDefault();
		var input, filter, ul, li, a, i;
		input = document.getElementById("myInput");
		filter = input.value.toUpperCase();
		div = document.getElementById("myDropdown");
		a = div.getElementsByTagName("a");
		for (i = 0; i < a.length; i++) {
			txtValue = a[i].textContent || a[i].innerText;
			rucValue = a[i].getAttribute('data-hidden-value');
			if (txtValue.toUpperCase().indexOf(filter) > -1 || rucValue.indexOf(filter) > -1 ) {
			a[i].style.display = "block";
			} else {
			a[i].style.display = "none";
			}
		}
	}
	function myFunction(event) {
		//if($("myDropdown").is(":visible"){
			//document.getElementById("myDropdown").classList.toggle("hide");
		//} else {
			event.preventDefault();


			document.getElementById("myInput").classList.toggle("show");
			document.getElementById("myDropdown").classList.toggle("show");
			div = document.getElementById("myDropdown");
			// a = div.getElementsByTagName("a");
			// for (i = 0; i < a.length; i++) {
			// 	if ( i < 3) {
			// 		a[i].style.display = "block";
			// 	} else{
			// 		a[i].style.display = "none";
			// 	}
			// }
			$("#myInput").focus();
		//}
		$(document).mousedown(function(event) {
			var target = $(event.target);
			var myInput = $('#myInput');
			var myDropdown = $('#myDropdown');
			var div = $("#lista_proveedores");
			var button = $("#abrecierra");
			// Verificar si el clic ocurrió fuera del elemento #my_input
			if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown").length && myInput.hasClass('show')) {
			// Remover la clase "show" del elemento #my_input
			myInput.removeClass('show');
			myDropdown.removeClass('show');
			}
			
		});
	}
	function buscar_por_orden_compra(event){
		event.preventDefault();
		var parametros = {
				"idtransaccion"   : "idtransaccion",
				"idunico"		  : "idunico"
		};

		$("#titulov").html("Buscar Orden de Compra");
		$.ajax({		  
			data:  parametros,
			url:   'buscar_orden_compra_modal.php',
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {	
				
			},
			success:  function (response) {
				$("#ventanamodal").modal("show");
				$("#cuerpov").html(response);	
				
			}
		});
	}
	function buscar_embarque(event){
		event.preventDefault();
		
		var parametros = {
				"idtransaccion"   : "idtransaccion",
				"idunico"		  : "idunico"
		};

		$("#titulov").html("Buscar Embarque");
		$.ajax({		  
			data:  parametros,
			url:   'buscar_embarque_modal.php',
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {	
				
			},
			success:  function (response) {
				$("#ventanamodal").modal("show");
				$("#cuerpov").html(response);	
				
			}
		});

	}
	function agregar_proveedor(event){
		event.preventDefault();
		
		var parametros = {
				"idtransaccion"   : "idtransaccion",
				"idunico"		  : "idunico"
		};

		$("#titulov").html("Agregar Proveedores");
		$.ajax({		  
			data:  parametros,
			url:   'agregar_proveedor_modal.php',
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {	
				
			},
			success:  function (response) {
				$("#ventanamodal").modal("show");
				$("#cuerpov").html(response);	
				
			}
		});

	}

	function moneda_div(valor){
		var multimoneda_local = <?php echo $multimoneda_local == "S" ? "'$multimoneda_local'" : "'N'" ?>;
		if(valor == 1){
			if(multimoneda_local == "S"){
				$("#box_cotizacion").css("display", "block");
				$("#box_monto_moneda").css("display", "block");
				verificar_cotizacion_moneda();
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
</script>
<style type="text/css">
	.a_link_proveedores{
		display: block;
		padding: 0.8rem;
	}	
	.a_link_proveedores:hover{
		color:white;
		background: #73879C;
	}
	.dropdown_proveedores{
		position: absolute;
		top: 70px;
		left: 0;
		z-index: 99999;
		width: 35rem;
		overflow: auto;
		white-space: nowrap;
		background: #EEEEEE !important;
		border: #c2c2c2 solid 1px;
	}
	.dropdown_proveedores_input{ 
		position: absolute;
		top: 37px;
		left: 0;
		z-index: 99999;
		display:none;
		width: 35rem !important;
		padding: 5px !important;
	}
	.btn_proveedor_select:hover{
		border: #c2c2c2 solid 1px;
		color: #73879C;
	}
	
</style>
  </head>
  <body class="nav-md" >
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
                    <h2>Agregar Compra</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<!-- comienza a estar fuera de plantillas  -->
<?php  if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php  echo $errores; ?>
</div>
<?php } ?>


<a href="javascript:history.back();void(0);" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Regresar</a>

<?php if (intval($_GET['idproveedor']) == 0) { ?>
	<?php if ($idcompra == 0) { ?>
		<p>
			<?php if ($preferencias_importacion == "S") { ?>
				<a href="javascript:void(0);" onclick="buscar_embarque(event);" class="btn btn-sm btn-default">
					<span class="fa fa-ship"></span> Elegir por Embarque
				</a>
			<?php } ?>
			<a href="javascript:void(0);" onclick="buscar_por_orden_compra(event);" class="btn btn-sm btn-default">
				<span class="fa fa-list"></span> Elegir por Proforma
			</a>
	
		</p>
	<?php } ?>
<hr />
<?php } ?>
<form id="form1" name="form1" method="post" action="">
<?php if (intval($_GET['idproveedor']) > 0) { ?>
Obs: si respeta el orden de carga de datos algunos campos se completaran solos.
<?php }?>
<div class="clearfix"></div>
<br />



<div class="col-md-12 col-sm-12  " >
	<h2 style="font-size: 1.3rem;">Datos del proveedor</h2>
	<hr>
	<div class="col-md-6 col-xs-12 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">(1) Proveedor *</label>
		<?php if (isset($_GET['idproveedor'])) {?>
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
		        $value_selected = htmlentities($_GET['idproveedor']);
		    }

		    if ($_GET['idproveedor'] > 0) {
		        $add = "disabled";
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
		        'acciones' => ' required="required" onchange="cambia_prov(this.value);" '.$add,
		        'autosel_1registro' => 'N'

		    ];

		    // construye campo
		    echo campo_select($consulta, $parametros_array);

		    ?>
		</div>
		<?php }?>
		<?php if (!isset($_GET['idproveedor'])) {?>
		<div class="col-md-6 col-xs-12">
				<div class="" style="display:flex;">
					<div class="dropdown " id="lista_proveedores">
						<button onclick="myFunction(event)" class="btn  btn_proveedor_select" id="abrecierra">Seleccionar Proveedor</button>
						<input class="dropdown_proveedores_input"type="text" placeholder="Nombre Proveedor" id="myInput" onkeyup="filterFunction(event)" >
						<div id="myDropdown" class="dropdown-content hide dropdown_proveedores links-wrapper" style="max-width: 500px;max-height: 200px;overflow: auto;">
						<?php echo $resultados ?>
						</div>
					</div>
					<a  href="javascript:void(0);" onclick="agregar_proveedor(event);" class="btn btn-sm btn-default">
						<span  class="fa fa-plus"></span> Agregar Nuevo Proveedor
					</a>
				</div>
		</div>
		<?php }?>
	</div>

	<?php if (intval($_GET['idproveedor']) > 0 && $preferencias_importacion == "S") { ?>
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
	        'acciones' => ' required="required" onchange="moneda_div(this.value)" "'.$add,
	        'autosel_1registro' => 'N'

	    ];

	    // construye campo
	    echo campo_select($consulta, $parametros_array);

	    ?>
			</div>
		</div>
	<?php } ?>
</div>

<?php if (intval($_GET['idproveedor']) > 0) { ?>
<div class="col-md-12 col-sm-12  " >
	<h2 style="font-size: 1.3rem;">Cabecera</h2>
	<hr></div>


	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">(2) Fecha compra *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="date" name="fecha_compra" id="fecha_compra" value="<?php  if (isset($_POST['fecha_compra'])) {
		    echo htmlentities($_POST['fecha_compra']);
		} else {
		    echo date("Y-m-d");
		}?>" placeholder="Fecha compra" class="form-control" required onBlur="validar_fecha(this.value);" />                    
		</div>
	</div>

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">(3) Factura Nro *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" onchange="verificarFormato()" name="facturacompra" id="facturacompra" value="<?php  if (isset($_POST['facturacompra'])) {
			    echo htmlentities($_POST['facturacompra']);
			} else {
			    echo htmlentities($facturacompra);
			}?>" placeholder="Ej: 001-001-0000123" class="form-control" required <?php if ($incrementa == 'S') { ?> readonly<?php } ?> />                    
		</div>
	</div>
</div>	
<?php } ?>

<?php if (intval($_GET['idproveedor']) > 0 && $preferencias_importacion == "S") { ?>
	<div class="col-md-12 col-sm-12  " id="box_cotizacion" >
		<h2 style="font-size: 1.3rem;">Cotizacion</h2>
		<hr>
		<div class="col-md-6 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12"><small class="btn btn-sm btn-default fa fa-plus" onclick="cargar_cotizacion()"></small>Moneda *</label>
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
        'acciones' => '  onchange="verificar_cotizacion_moneda(this.value)" "'.$add,
        'autosel_1registro' => 'N'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
			</div>
		</div>

		<div class="col-md-6 col-sm-12 form-group" id="box_cotizaciones" >
			<?php require_once("select_cotizacion.php");?>
		</div>


	</div>
<?php } ?>




<?php if (intval($_GET['idproveedor']) > 0) { ?>
	<div class="col-md-12 col-sm-12  " >
		<h2 style="font-size: 1.3rem;">Datos Factura</h2>
		<hr></div>


		

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">(4) Monto factura *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<!-- <input type="text" name="monto_factura" id="monto_factura" value="<?php  if (isset($_POST['monto_factura'])) {
			    echo floatval($_POST['monto_factura']);
			} else {
			    echo floatval($rs->fields['monto_factura']);
			}?>" placeholder="Monto factura" class="form-control" required />                    
			-->
			<input type="text" name="monto_factura" id="monto_factura" value="<?php  if (isset($_POST['monto_factura'])) {
			    echo floatval($_POST['monto_factura']);
			} else {
			    echo floatval(monto_compra($idcompra));
			}?>" placeholder="Monto factura" class="form-control" required />                    
			</div>
		</div>

		
			<div class="col-md-6 col-sm-6 form-group" id="box_monto_moneda">
				<?php if ($preferencias_importacion == "S") { ?>
					<div class="row">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">(4) Moneda Monto*</label>
						<div style="display:flex;justify-content:space-around;">
							<div class="form-check" id="box_radio_moneda_extranjera" style="display:inline-block;">
								<input class="form-check-input" data-hidden-nacional="false" value="<?php echo $idmoneda_select; ?>" type="radio" name="radio_moneda" id="radio_moneda_extranjera" >
								<label class="form-check-label" id="label_moneda_extranjera" for="radio_moneda_extranjera">
									<?php echo $moneda_nombre; ?>
								</label>
							</div>
							<div class="form-check" id="box_radio_moneda_nacional" style="display:inline-block;">
								<input checked class="form-check-input" data-hidden-nacional="true" value="<?php echo $id_moneda_nacional; ?>" type="radio" name="radio_moneda" id="radio_moneda_nacional" >
								<label class="form-check-label" id="label_moneda_nacional" for="radio_moneda_nacional">
								<?php echo $nombre_moneda_nacional; ?>
								</label>
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
        $value_selected = htmlentities($rs->fields['idtipocomprobante']);
        if (intval($rs->fields['idtipocomprobante']) == 0) {
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
        'acciones' => ' onchange="tipo_comprobante(this.value);" '.$readonly_tipocomp,
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
        if (intval($tipocomprobante_def) == 4) {
            $muesta_cdc = 'style="display:display;"';
        } else {
            $muesta_cdc = 'style="display:none;"';
        }
    }
    ?>
		<div class="col-md-6 col-sm-6 form-group" id="cdc_box" <?php echo $muesta_cdc;  ?>>
			<label class="control-label col-md-3 col-sm-3 col-xs-12">CDC <?php $cdc_ast; ?></label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="cdc" id="cdc" value="<?php  if (isset($_POST['cdc'])) {
			    echo limpiacdc($_POST['cdc']);
			} else {
			    echo limpiacdc($rs->fields['cdc']);
			}?>" placeholder="CDC" class="form-control" <?php echo $cdc_req; ?> onchange="this.value = get_numbers(this.value)" />                    
			</div>
		</div>	

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Timbrado *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="timbrado" id="timbrado" value="<?php

            if ($incrementa != 'S') {
                if (isset($_POST['timbrado'])) {
                    echo intval($_POST['timbrado']);
                } else {
                    //echo intval($rs->fields['timbrado']);
                    echo $timbrado_bd;
                }
            } else {
                echo 1;
            }

    ?>" placeholder="Timbrado" class="form-control"  <?php if ($incrementa == 'S') { ?> readonly<?php } ?> />                    
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
        if (in_array($tipocomprobante_def, $tipos_comprobantes_vence_ar)) {
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
            if ($incrementa == 'S') {
                echo date("Y-m-d");
            } else {
                echo $vto_timbrado_bd;
            }
        }
    ?>" placeholder="Vto timbrado" class="form-control" <?php if ($incrementa == 'S') { ?> readonly<?php } ?>  />                    
			</div>
		</div>


		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Orden Compra</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<?php require_once("./tmpcompras_ocn.php"); ?>
			</div>
		</div>


		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal *</label>
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
        $value_selected = htmlentities($rs->fields['sucursal']);
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
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
			</div>
		</div>


		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo compra *</label>
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
        $value_selected = htmlentities($tipocompra);
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

		<div id="vencimiento_box" class="col-md-6 col-sm-6 form-group" <?php if ($_POST['idtipocompra'] != 2) { ?>style="display:none;"<?php } ?>>
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Vencimiento Factura *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="date" onblur ="validar_fecha_vencimiento()" name="vencimiento" aria-describedby="vencimientoHelp" id="vencimiento" value="<?php  if (isset($_POST['vencimiento'])) {
				    echo htmlentities($_POST['vencimiento']);
				} else {
				    echo fecha_sumar_dias($ahora, $diasvence);
				}?>" placeholder="Vencimiento" class="form-control"  />                    
				<small id="vencimientoHelp" class="form-text text-muted">Si el perfil del proveedor contiene días de crédito, se obtendrán automáticamente.</small>
			</div>
		</div>

		<div class="col-md-6 col-sm-6 form-group" >
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Comentario</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<textarea name="descripcion" id="descripcion" style="width:100%;resize: vertical;" rows="4" cols="50" ><?php  if (isset($_POST['descripcion'])) {
				    echo htmlentities($_POST['descripcion']);
				} else {
				    echo htmlentities($rs->fields['descripcion']);
				}?></textarea>
				</div>
			</div>
		</div>


		<?php if ($idcompra > 0) {?>
	
		<div class="col-md-6 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Gasto de la Compra</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
						<?php
                            // consulta
                            $consulta = "
							Select compras.idtran,compras.ocnum,
							proveedores.nombre as proveedor, compras.ocnum, compras.moneda,
							CONCAT('[', compras.idcompra, ']-',proveedores.nombre, '-',COALESCE(compras.ocnum,'SIN_ORDEN') ) as campo, compras.idcompra
							from gest_depositos_compras
							inner join proveedores on proveedores.idproveedor=gest_depositos_compras.idproveedor
							inner join usuarios on usuarios.idusu=gest_depositos_compras.registrado_por 
							inner join compras on compras.idcompra = gest_depositos_compras.idcompra
							where 
							revisado_por=0 
							and compras.estado <> 6
							and compras.idcompra = $idcompra
							order by fecha_compra desc 
							";

		    // valor seleccionado
		    if (isset($_POST['idtipocompra_ref'])) {
		        $value_selected = htmlentities($_POST['idtipocompra_ref']);
		    } else {
		        $value_selected = $idcompra;
		    }

		    // parametros
		    $parametros_array = [
		        'nombre_campo' => 'idcompra_ref',
		        'id_campo' => 'idcompra_ref',

		        'nombre_campo_bd' => 'campo',
		        'id_campo_bd' => 'idcompra',

		        'value_selected' => $value_selected,

		        'pricampo_name' => 'Seleccionar...',
		        'pricampo_value' => '',
		        'style_input' => 'readonly class="form-control"',
		        'acciones' => '  ',
		        'autosel_1registro' => 'S'

		    ];
		    // construye campo
		    echo campo_select($consulta, $parametros_array);

		    ?>
						<small>-- [idcompra]-proveedor-ocnum --<br> verificar en Registro de compras </small>
			</div>
		</div>
		<?php } ?>
		
		<div class="clearfix"></div>
		<br />

		<div class="form-group">
			<div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
				<button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
				<button type="button" class="btn btn-primary" onMouseUp="javascript:history.back();void(0);"><span class="fa fa-ban"></span> Cancelar</button>
			</div>
		</div>

		<input type="hidden" name="MM_insert" value="form1" />
		<input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
		<br />
<?php } ?>
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
  </body>
</html>
