<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");


$iddeposito = intval($_GET['iddeposito']);
$idinsumo = intval($_GET['idinsumo']);
$idconteo = intval($_GET['id']);
$num_articulos_pendientes = 0;
$consulta = "SELECT descripcion from gest_depositos where iddeposito=$iddeposito";
$rs_depositos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$nombre_deposito = $rs_depositos->fields['descripcion'];
//////////////////////////////////////////////////////////////////////////////////////////
$consulta = "SELECT count(conteo_detalles.unicose) as idconteo_ref
from conteo_detalles
INNER JOIN conteo on conteo.idconteo = conteo_detalles.idconteo
LEFT JOIN gest_almcto_pasillo on gest_almcto_pasillo.idpasillo = conteo_detalles.idpasillo
INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = conteo_detalles.idalm
INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
INNER JOIN medidas on medidas.id_medida = conteo_detalles.idmedida_ref
where 
conteo.idconteo_ref = $idconteo
and conteo.estado = 2
ORDER BY conteo_detalles.idconteo, conteo_detalles.idalm
";
$rs_sub_conteo_detalles = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$num_detalles = intval($rs_sub_conteo_detalles->fields['idconteo_ref']);
if ($num_detalles == 0) {
    $location = "conteo_por_producto_detalle.php?id=".$iddeposito."&idinsumo=".$idinsumo;
    header("location: $location");
    exit;
}


$consulta = "select idconteo,estado from 
                conteo 
            where
                idconteo = $idconteo
                and idempresa = $idempresa
";
$rs_estado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$conteo_estado = $rs_estado->fields['estado'];
$idconteo = intval($rs_estado->fields['idconteo']);


if ($idconteo == 0 || $conteo_estado == 2) {
    $location = "conteo_por_producto_detalle.php?id=".$iddeposito."&idinsumo=".$idinsumo;
    header("location: $location");
    exit;
}

if ($idinsumo == 0) {
    $location = "conteo_stock_detalle.php?id=".$iddeposito;
    header("location: $location");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>
	function cerar_articulos(parametros) {
		console.log(parametros.idalms);
		event.preventDefault();
		var parametros_crear = {
			"idalms"		  : parametros.idalms,
			"iddeposito"		  : parametros.iddeposito,
			"idinsumo"		  : parametros.idinsumo,
			"idconteo"	: parametros.idconteo,
		};
		$.ajax({
				data:  parametros_crear,
				url:   'cerar_articulos_restantes.php',
				type:  'post',
				beforeSend: function () {
					// $("#conteo_productos").html('Cargando...');  
				},
				success:  function (response) {
					console.log(response);
					// if (JSON.parse(response)['success']==true) {
					// 	var url  = 'conteo_por_producto_detalle.php?id=<?php // echo $iddeposito?>&idinsumo=<?php // echo $idinsumo;?>';
					// 	document.location.href = url;
					// }
					location.reload();
				}
		});
	}
	function guardar_conteo(event, afecta_stock){
		event.preventDefault();
		
			var parametros = {
				"idconteo"		  	: <?php echo $idconteo; ?>,
				"mueve_stock"	  	: afecta_stock ? 1 : 0,
				"consolidar_conteo"	: 1
			};
			
			$.ajax({
					data:  parametros,
					url:   'guardar_conteo.php',
					type:  'post',
					beforeSend: function () {
						$("#conteo_productos").html('Cargando...');  
					},
					success:  function (response) {
						console.log(response);
						if (JSON.parse(response)['success']==true) {
							var url  = 'conteo_por_producto_detalle.php?id=<?php echo $iddeposito?>&idinsumo=<?php echo $idinsumo; ?>';
							document.location.href = url;
						}
					}
			});
	}
	function volver_atras(event){
		event.preventDefault();
		var url  = 'conteo_por_producto_detalle.php?id=<?php echo $iddeposito?>&idinsumo=<?php echo $idinsumo; ?>';
		document.location.href = url;
	}
	function convertirAEntero(cadena) {
		// Intentar convertir la cadena en un número entero
		const numero = parseInt(cadena);

		// Verificar si la conversión fue exitosa
		if (!isNaN(numero)) {
			return numero; // La conversión fue exitosa, retornar el número entero
		} else {
			return 0; // La conversión falló, retornar 0
		}
	}
	
	function mantiene_session(){
		var f=new Date();
		cad=f.getHours()+":"+f.getMinutes()+":"+f.getSeconds(); 
		var parametros = {
					"ses" : cad,
		};
		$.ajax({
					data:  parametros,
					url:   'mantiene_session.php',
					type:  'post',
					beforeSend: function () {
					},
					success:  function (response) {
						//alert(response);
					}
			});	
	}
	
	function solonumerosypuntoycoma(e)
			{
				var keynum = window.event ? window.event.keyCode : e.which;
				if ((keynum == 8) || (keynum == 46) || (keynum == 190) || (keynum == 110) || (keynum == 188))
				return true;
			
				return /\d/.test(String.fromCharCode(keynum));
			}

	
	function reabrir_conteo(unicose){
		var parametros = {
                "idsub_conteo"   : unicose
		};
		$.ajax({
                data:  parametros,
                url:   'conteo_consolidado_edit.php?idsub_conteo='+unicose,
                type:  'get',
                beforeSend: function () {
                    //   $("#conteo_productos").html('Cargando...');  
                },
                success:  function (response) {
					console.log(response);
					//   $("#conteo_productos").html(response);
					// location.reload();
					var url  = 'conteo_por_producto_detalle.php?id=<?php echo $iddeposito?>&idinsumo=<?php echo $idinsumo?>';
					document.location.href = url;

                }
        });

	}

	
</script>
<style>

.enlace-con-bloqueo {
          cursor: no-drop;
}
	.div_leyenda{
		width: 100%;
		display: flex;
		align-items: center;
		/* justify-content: center; */
	}
	.leyenda_alerta{
		width: 10px;
		height: 10px;
		background: #EA6153;
		display: inline-block;
		margin: 10px;
	}
	.mt-1{
		margin-top: 20px !important;
	}
	input:focus, select:focus {
		border: #add8e6 solid 3px !important; /* Este es un tono de azul pastel */
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
                    <h2>Conteo <?php echo "Deposito ".$nombre_deposito; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
	<a href="conteo_por_producto_detalle.php?id=<?php echo $iddeposito?>&idinsumo=<?php echo $idinsumo; ?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
</p>

<hr />
<div>
	<div class="alert alert-info" role="alert" >
		Observación: Para activar las opciones de guardado, debe resolver todos los conflictos relacionados con artículos faltantes.
	</div>
	<h4>Leyenda de Colores</h4>
	<div class="div_leyenda"> 
		<div class="leyenda_alerta "></div> <small>Articulo Faltante</small></div>
	</div>

			
			<div class="col-md-12 table-responsive " id="conteo_productos">
				<div class="">
					<table class="table table-bordered">
						<thead>
							<tr>
								<th>Producto</th>
								<th>Cantidad(Unidades)</th>
								<th>diferencia(Unidades)</th>
								<th>Vencimiento</th>
								<th>Almacenamiento</th>
								<th>Almacenado en</th>
								<th>Fila</th>
								<th>Columna</th>
								<th>Pasillo</th>
								<th>Medida_ref</th>
							</tr>
						</thead>
						<tbody>
							<?php
                            $i = 0;

$consulta = "SELECT conteo_detalles.*,
							(SELECT usuarios.nombres from usuarios where usuarios.idusu = conteo.iniciado_por ) as iniciado_por,
							medidas.nombre as medida_ref, gest_deposito_almcto_grl.nombre as almacenamiento, CONCAT(gest_deposito_almcto.nombre,' ',COALESCE(gest_deposito_almcto.cara, ''))  as tipo_almacenamiento, gest_almcto_pasillo.nombre as pasillo
							from conteo_detalles
							INNER JOIN conteo on conteo.idconteo = conteo_detalles.idconteo
							LEFT JOIN gest_almcto_pasillo on gest_almcto_pasillo.idpasillo = conteo_detalles.idpasillo
							INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = conteo_detalles.idalm
							INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
							INNER JOIN medidas on medidas.id_medida = conteo_detalles.idmedida_ref
							where 
							conteo.idconteo_ref = $idconteo
							and conteo.estado = 2
							ORDER BY conteo_detalles.idconteo, conteo_detalles.idalm
							";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if ($rs->RecordCount() > 0) {
    while (!$rs->EOF) {
        $unicose_det = $rs->fields['unicose'];
        ?>
							<?php if ($i != $rs->fields['idconteo']) {
							    $i = $rs->fields['idconteo'];

							    ?>
								<tr style="background:#337AB7;" >
									<td style="background:#337AB7;color:white;">Idconteo:<?php echo antixss($rs->fields['idconteo']); ?></td>
									<td align="center" colspan="3" style="background:#337AB7;color:white;">Registrado por: <?php echo antixss($rs->fields['iniciado_por']); ?></td>
									<td style="background:#337AB7;color:white;" colspan="6" align="right">
									<a href="javascript:void(0);" onclick="reabrir_conteo(<?php echo $i ?>);" class="btn btn-sm btn-info" title="" data-toggle="tooltip" data-placement="right" data-original-title="Reabrir"><span class="fa fa-reply"></span> Reabrir</a>
									</td>
								</tr>
							<?php

							    ///inicia la busqueda de almacenamientos
							    // idalm usados
							    //se compara con el deposito
							    //se muestra los que no se encuentran
							    $consulta = "SELECT DISTINCT(conteo_detalles.idalm)
							from conteo_detalles
							where 
							idconteo = $i";
							    $rs_almacenamientos_sc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
							    while (!$rs_almacenamientos_sc->EOF) {
							        $idalm_subconteo = $rs_almacenamientos_sc->fields['idalm'];
							        $consulta = "SELECT  gest_depositos_stock_almacto.disponible, gest_depositos_stock_almacto.fila, gest_depositos_stock_almacto.columna, gest_depositos_stock_almacto.idpasillo, insumos_lista.descripcion as insumo, gest_depositos_stock.lote, gest_depositos_stock.vencimiento,
								medidas.nombre as medida_ref, gest_deposito_almcto_grl.nombre as almacenamiento, CONCAT(gest_deposito_almcto.nombre,' ',COALESCE(gest_deposito_almcto.cara, ''))  as tipo_almacenamiento, gest_almcto_pasillo.nombre as pasillo
								from gest_depositos_stock_almacto 
								LEFT JOIN gest_almcto_pasillo on gest_almcto_pasillo.idpasillo = gest_depositos_stock_almacto.idpasillo
								INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = gest_depositos_stock_almacto.idalm
								INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
								INNER JOIN gest_depositos_stock ON gest_depositos_stock.idregseriedptostk = gest_depositos_stock_almacto.idregseriedptostk
								INNER JOIN  insumos_lista ON insumos_lista.idinsumo = gest_depositos_stock.idproducto
								INNER JOIN medidas on medidas.id_medida = gest_depositos_stock_almacto.idmedida
								where gest_depositos_stock_almacto.idalm = $idalm_subconteo
								and gest_depositos_stock.idproducto = $idinsumo
								and gest_depositos_stock_almacto.disponible > 0 
								and gest_depositos_stock_almacto.estado = 1
								";
							        $rs_sc_deposito = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

							        while (!$rs_sc_deposito->EOF) {
							            $fila_sc = intval($rs_sc_deposito->fields['fila']);
							            $columna_sc = intval($rs_sc_deposito->fields['columna']);
							            $idpasillo_sc = intval($rs_sc_deposito->fields['idpasillo']);
							            $lote_sc = antixss($rs_sc_deposito->fields['lote']);
							            $disponible_sc = intval($rs_sc_deposito->fields['disponible']);
							            $insumo_sc = $rs_sc_deposito->fields['insumo'];
							            $vencimiento_sc = $rs_sc_deposito->fields['vencimiento'];
							            $medida_ref_sc = $rs_sc_deposito->fields['medida_ref'];
							            $almacenamiento_sc = $rs_sc_deposito->fields['almacenamiento'];
							            $tipo_almacenamiento_sc = $rs_sc_deposito->fields['tipo_almacenamiento'];
							            $pasillo_sc = $rs_sc_deposito->fields['pasillo'];
							            $consulta = "SELECT unicose 
									from conteo_detalles
									where 
									idconteo = $i
									and idalm = $idalm_subconteo
									and fila = $fila_sc
									and columna = $columna_sc
									and idpasillo = $idpasillo_sc
									";
							            $rs_sc_consulta = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
							            $id_consulta_sc = intval($rs_sc_consulta->fields['unicose']);
							            if ($id_consulta_sc == 0) {
							                //  gregar html para el almacenamiento no verificado cambiar
							                // el html luego
							                $num_articulos_pendientes++;
							                ?>
									<tr class="alert alert-danger" role="alert">
										<td ><?php echo $insumo_sc; ?> </td>
										<td align="center" ><?php echo $disponible_sc; ?> </td>
										<td align="center" >0 </td>
										<td > Vencimiento: <?php echo $vencimiento_sc ? date("d/m/Y", strtotime($vencimiento_sc)) : "--" ?> <br> Lote: <?php echo ($lote_sc)   ?>  </td>
										<td ><?php echo $almacenamiento_sc; ?> </td>
										<td ><?php echo $tipo_almacenamiento_sc; ?> </td>
										<td ><?php echo $fila_sc; ?> </td>
										<td ><?php echo $columna_sc; ?> </td>
										<td ><?php echo $pasillo_sc; ?> </td>
										<td ><?php echo $medida_ref_sc; ?> </td>
									</tr>
									<?php
							            }
							            $rs_sc_deposito->MoveNext();
							        }

							        $rs_almacenamientos_sc->MoveNext();
							    }

							}

        ?>
							<tr >
								
								<td><?php echo antixss($rs->fields['descripcion']); ?></td>
								<td align="center"><?php echo formatomoneda($rs->fields['cantidad_contada'], 2, 'N'); ?></td>
								<td align="center"><?php echo formatomoneda($rs->fields['diferencia'], 2, 'N'); ?></td>
								<td> Vencimiento: <?php echo $rs->fields['vencimiento'] ? date("d/m/Y", strtotime($rs->fields['vencimiento'])) : "--" ?> <br> Lote: <?php echo ($rs->fields['lote'])   ?> </td>
								<td><?php echo antixss($rs->fields['almacenamiento']); ?></td>
								<td><?php echo antixss($rs->fields['tipo_almacenamiento']); ?></td>
								<td><?php echo antixss($rs->fields['fila']); ?></td>
								<td><?php echo antixss($rs->fields['columna']); ?></td>
								<td><?php echo antixss($rs->fields['pasillo']); ?></td>
								<td><?php echo antixss($rs->fields['medida_ref']); ?></td>
								
							</tr>

							<?php
        $rs->MoveNext();
    }
}
//lo mismo que al buscar por almacenamiento del while
// pero ahora se tiene que buscar en todos los almacenamientos
// del deposito seleccionado y compararlo con el conteo para
//verificar que no exista algun almacenamiento olvidado o mercaderia
// perdida
$consulta = "SELECT DISTINCT(conteo_detalles.idalm) 
									FROM conteo_detalles
									INNER JOIN conteo ON conteo.idconteo = conteo_detalles.idconteo
									where 
									conteo.idconteo_ref=$idconteo
									and estado=2
						";
$rs_conteo_deposito = $conexion->GetCol($consulta) or die(errorpg($conexion, $consulta));
$consulta = "SELECT DISTINCT(gest_depositos_stock_almacto.idalm) as idalm 
						FROM gest_depositos_stock_almacto
						INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = gest_depositos_stock_almacto.idalm
						INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
						INNER JOIN gest_depositos_stock ON gest_depositos_stock.idregseriedptostk = gest_depositos_stock_almacto.idregseriedptostk
						where 
						gest_deposito_almcto_grl.iddeposito = $iddeposito
						and gest_depositos_stock.idproducto = $idinsumo
						and gest_depositos_stock_almacto.disponible > 0
						and gest_depositos_stock_almacto.estado = 1";

$rs_stock_idalm = $conexion->GetCol($consulta) or die(errorpg($conexion, $consulta));
$result = array_diff($rs_stock_idalm, $rs_conteo_deposito);
$longitud = count($result);
if ($longitud > 0) {

    ?>
							
							<tr class="alert alert-danger" role="alert" >
									<td colspan="10" style="padding: 1.9vh;" >
										<div style="display: flex;align-items: center;justify-content: space-between;">
											<p style="display: contents;">Alamacenamientos no registrados con articulos disponibles en el sistema</p>
											<a href="javascript:void(0);" onclick="cerar_articulos({ idalms: <?php echo htmlspecialchars(json_encode($result), ENT_QUOTES, 'UTF-8'); ?>,idinsumo: <?php echo $idinsumo; ?>, iddeposito: <?php echo $iddeposito;?>, idconteo: <?php echo $idconteo; ?>});" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Cerar"><span class="fa fa-plus"></span> Cerar articulos Faltantes</a>
										</div>
									</td>
								</tr>
							<?php
}

foreach ($result as $idalm_faltante) {

    $consulta = "SELECT gest_depositos_stock_almacto.idregserie_almacto ,gest_depositos_stock_almacto.disponible, gest_depositos_stock_almacto.idalm,
							gest_depositos_stock_almacto.fila, gest_depositos_stock.lote, gest_depositos_stock.vencimiento, gest_depositos_stock_almacto.columna, gest_depositos_stock_almacto.idpasillo,
							gest_depositos_stock_almacto.disponible,medidas.nombre as medida_ref, gest_deposito_almcto_grl.nombre as almacenamiento,
							CONCAT(gest_deposito_almcto.nombre,' ',COALESCE(gest_deposito_almcto.cara, ''))  as tipo_almacenamiento, 
							gest_almcto_pasillo.nombre as pasillo,insumos_lista.descripcion as insumo
							FROM gest_depositos_stock_almacto
							LEFT JOIN gest_almcto_pasillo on gest_almcto_pasillo.idpasillo = gest_depositos_stock_almacto.idpasillo
							INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = gest_depositos_stock_almacto.idalm
							INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
							INNER JOIN gest_depositos_stock ON gest_depositos_stock.idregseriedptostk = gest_depositos_stock_almacto.idregseriedptostk
							INNER JOIN  insumos_lista ON insumos_lista.idinsumo = gest_depositos_stock.idproducto
							INNER JOIN medidas on medidas.id_medida = gest_depositos_stock_almacto.idmedida
							where 
							gest_depositos_stock_almacto.idalm = $idalm_faltante 
							and gest_depositos_stock_almacto.disponible > 0
							and gest_depositos_stock_almacto.estado = 1
							and gest_depositos_stock.idproducto = $idinsumo
							";

    $rs_stock_faltante = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    while (!$rs_stock_faltante->EOF) {

        ///////////////////////////////////////////////////
        $fila_stck_faltante = intval($rs_stock_faltante->fields['fila']);
        $columna_stck_faltante = intval($rs_stock_faltante->fields['columna']);
        $idpasillo_stck_faltante = intval($rs_stock_faltante->fields['idpasillo']);
        $lote_stck_faltante = antixss($rs_stock_faltante->fields['lote']);
        $disponible_stck_faltante = intval($rs_stock_faltante->fields['disponible']);
        $insumo_stck_faltante = $rs_stock_faltante->fields['insumo'];
        $vencimiento_stck_faltante = $rs_stock_faltante->fields['vencimiento'];
        $medida_ref_stck_faltante = $rs_stock_faltante->fields['medida_ref'];
        $almacenamiento_stck_faltante = $rs_stock_faltante->fields['almacenamiento'];
        $tipo_almacenamiento_stck_faltante = $rs_stock_faltante->fields['tipo_almacenamiento'];
        $pasillo_stck_faltante = $rs_stock_faltante->fields['pasillo'];
        $num_articulos_pendientes++;
        ?>
							<tr>
							<tr class="alert alert-danger" role="alert">
										<td ><?php echo $insumo_stck_faltante; ?> </td>
										<td align="center" ><?php echo $disponible_stck_faltante; ?> </td>
										<td align="center"><?php echo formatomoneda(0, 2, 'N'); ?></td>									
										<td > Vencimiento: <?php echo $vencimiento_stck_faltante ? date("d/m/Y", strtotime($vencimiento_stck_faltante)) : "--" ?> <br> Lote: <?php echo ($lote_stck_faltante)   ?>  </td>
										<td ><?php echo $almacenamiento_stck_faltante; ?> </td>
										<td ><?php echo $tipo_almacenamiento_stck_faltante; ?> </td>
										<td ><?php echo $fila_stck_faltante; ?> </td>
										<td ><?php echo $columna_stck_faltante; ?> </td>
										<td ><?php echo $pasillo_stck_faltante; ?> </td>
										<td ><?php echo $medida_ref_stck_faltante; ?> </td>
									</tr>
							</tr>
							<br>
							<?php
            $rs_stock_faltante->MoveNext();
    }
}
?>
						
						</tbody>
					</table>
				</div>
			</div>
			<div class="col-md-12 col-xs-12"  style="text-align:center;">
				<input type="hidden" name="ocinsumo" id="ocinsumo" value="" />

				<button  class="btn btn-secondary " style="width:15vw;"  onclick="volver_atras(event)"><span class="fa fa-reply"></span>&nbsp;Atras</button>
				<!-- agregar clase de css con <?php // echo $num_articulos_pendientes;?> -->

				<button  class="btn btn-info <?php  echo $num_articulos_pendientes > 0 ? "enlace-con-bloqueo" : "";?>" style="width:15vw;" id="btn_agregar" <?php  if ($num_articulos_pendientes == 0) { ?>onclick="guardar_conteo(event,false);" <?php } ?>><span class="fa fa-save"></span>&nbsp;Guardar Sin Afectar Stock</button>
				<button  class="btn btn-success <?php  echo $num_articulos_pendientes > 0 ? "enlace-con-bloqueo" : "";?>" style="width:15vw;" id="btn_agregar" <?php  if ($num_articulos_pendientes == 0) { ?> onclick="guardar_conteo(event,true);" <?php } ?> ><span class="fa fa-save"></span>&nbsp;Guardar y Afectar Stock</button>
			</div>
                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 


			<!-- POPUP OCULTO  -->
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
			<!-- FIN POPUP -->
            
            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
