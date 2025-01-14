<?php
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");

if (intval($ocnum) == 0) {
    $ocn = intval($_POST['ocn']);
} else {
    $ocn = $ocnum;
}
//Traemos los productos seleccionados para la compra
$buscar = "
select compras_ordenes.ocnum, embarque.estado_embarque, compras_ordenes.fecha, puertos.descripcion as puerto , embarque.idembarque, embarque.idcompra , compras_ordenes.estado_orden
from compras_ordenes 
LEFT JOIN embarque on embarque.ocnum = compras_ordenes.ocnum
LEFT JOIN puertos on puertos.idpuerto = embarque.idpuerto
where 
compras_ordenes.ocnum_ref =$ocnum
and compras_ordenes.estado = 2
";
$rscu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$estado_orden = intval($rs->fields['estado_orden']);





?>
.
<br />
<style>

	.sin_embarque{
      background: #ce2d4fa8;
		  font-weight: bold;
    }
    .sin_embarque:hover{
      background: #ce2d4f;
    	color: #000;
		  font-weight: bold;
    }

    
    .con_embarque{
      background: #D7FFAB;
		  font-weight: bold;
    }

    .con_embarque:hover{
      background: #C3EB97;
    	color: #000;
		  font-weight: bold;
    }

	.transito{
		background: #ce2d4fa8;
    	color: white;
		font-weight: bold;

	}
	.completo{
		background: #D7FFAB;
    	color: #405467;
		font-weight: bold;
	}
</style>
<script>
	function alerta_modal(titulo,mensaje){
		$('#modal_ventana').modal('show');
		$("#modal_titulo").html(titulo);
		$("#modal_cuerpo").html(mensaje);
	}
	function crear_suborden(){
		var ocn=<?php echo $ocn ?>;
	
		var parametros = {
						"ocn"			: ocn
		};
		$.ajax({
				data:  parametros,
				url:   'crear_sub_ordenes.php',
				type:  'post',
				beforeSend: function () {
						// $("#generar_compra").text('Cargando...');
						
				},
				success:  function (response) {
					console.log(response);
					if(JSON.parse(response)["success"] == false) {
						alerta_modal("Error",JSON.parse(response)["error"] )

					}
					if(JSON.parse(response)["success"] == true) {
						document.location.href='compras_ordenes_det.php?id='+JSON.parse(response)["ocnum"];

					}
					
				}
		});
	}

	
</script>
<div class="table-responsive">
	<h2>Ordenes asociadas</h2>
	<?php if ($estado_orden != 2) {?>
		<a id="crear_suborden" href="javascript:void(0);" onclick="crear_suborden()" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar Sub Orden</a>
	<?php } ?>
	<hr>
	<br>
	<div class="clearfix"></div>
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
		<tr>
        	<th  align="center" ><strong>Orden ID:</strong></th>
        	<th  align="center" ><strong>Fecha</strong></th>
        	<th  align="center" ><strong>Puerto</strong></th>
        	<th  align="center" ><strong>Estado</strong></th>
            <th  align="center" ><strong>Embarque</strong></th>
            
		</tr>
    </thead>
    <tbody>
        <?php $tot = 0;
while (!$rscu->EOF) {
    $puerto = $rscu->fields['puerto'];
    $fechacompra = $rscu->fields['fecha'];
    $idembarque = intval($rscu->fields['idembarque']);
    $subocnum = $rscu->fields['ocnum'];
    $estado_embarque = $rscu->fields['estado_embarque'];
    $idcompra = $rscu->fields['idcompra'];


    $class_css = "";
    if ($idembarque > 0) {

        $class_css = "con_embarque";
    } else {
        $class_css = "sin_embarque";

    }
    ?>
        <tr >
		<td align="center">
					<h6><?php echo trim($subocnum) ?></h6>
				</td>	
			<td align="center">
				<h6><?php echo trim($fechacompra) ?></h6>
			</td>	
			<td align="center">
				<h6><?php echo trim($puerto) ?></h6>
			</td>
			<td align="center">
				<h6><?php if ($estado_embarque != 0) {
				    echo trim($estado_embarque) == 1 ? "Activo" : "Finalizado";
				} ?></h6>
			</td>
            <td align="center">
                <a href="javascript:void(0);" class="btn btn-sm btn-default <?php echo $class_css;?> " title="Embarque" data-toggle="tooltip"  onmouseup="document.location.href='../embarque/embarque_add.php?ocn=<?php echo trim($subocnum) ?>'"><span class="fa fa-ship"></span></a>		
            </td>
        </tr>
		<tr >
			<td colspan="2">
				<?php
                    $ocn = $subocnum;
    $buscar = "
						Select *, cotizaciones.cotizacion,compras_ordenes.finalizado_el as fin,
						( Select nombre from medidas where medidas.id_medida = compras_ordenes_detalles.idmedida ) as medida,
						(select insumos_lista.descripcion from insumos_lista where idinsumo = compras_ordenes_detalles.idprod )  as articulo,
						(
							select productos.barcode
							from insumos_lista
							inner join productos on productos.idprod_serial = insumos_lista.idproducto
							where
							idinsumo = compras_ordenes_detalles.idprod
						)  as barcode
						from  compras_ordenes_detalles
						inner join compras_ordenes on compras_ordenes.ocnum = compras_ordenes_detalles.ocnum
						LEFT JOIN cotizaciones on cotizaciones.idcot = compras_ordenes.idcot
						where
						compras_ordenes_detalles.ocnum=$ocn
						and compras_ordenes.estado = 2
						order by compras_ordenes_detalles.descripcion asc
						";
    $rscu2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tprod = $rscu2->RecordCount();

    $cot_ref = floatval($rscu2->fields['cotizacion']);
    $costo_ref = floatval($rscu2->fields['costo_ref']);

    $buscar = "SELECT *,
						( select insumos_lista.descripcion from insumos_lista where idinsumo = compras_ordenes_detalles.idprod )  as articulo,
						(
							select productos.barcode
							from insumos_lista
							inner join productos on productos.idprod_serial = insumos_lista.idproducto
							where
							idinsumo = compras_ordenes_detalles.idprod
						)  as barcode
						from  compras_ordenes_detalles
						inner join compras_ordenes on compras_ordenes.ocnum = compras_ordenes_detalles.ocnum
						where
						compras_ordenes_detalles.ocnum=$ocn
						and compras_ordenes.estado = 1
						order by compras_ordenes_detalles.ocseria desc
						limit 1
						";
    $rsultag = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $cotizacion = "SELECT cotizaciones.cotizacion, costo_ref, tipo_moneda.descripcion as nombre_moneda, tipo_moneda.banderita
						from compras_ordenes
						LEFT JOIN cotizaciones on cotizaciones.idcot = compras_ordenes.idcot
						INNER JOIN tipo_moneda on tipo_moneda.idtipo = cotizaciones.tipo_moneda
						where ocnum = $ocn";
    $rscotizacion = $conexion->Execute($cotizacion) or die(errorpg($conexion, $cotizacion));
    $cot_ref = floatval($rscotizacion->fields['cotizacion']);
    $costo_ref = floatval($rscotizacion->fields['costo_ref']);
    $banderita = antixss($rscotizacion->fields['banderita']);
    $nombre_moneda = antixss($rscotizacion->fields['nombre_moneda']);


    ?>
				<div id="grilla_box1" class="col-md-12" >
					<h2 >Orden Solicitada</h2>
					<div class="table-responsive">
						<table width="100%" class="table table-bordered jambo_table bulk_action">
						<thead>
				<tr>
					<th  align="center" ><strong>Codigo</strong></th>
					<th  align="center" ><strong>Producto</strong></th>
					<th  align="center" ><strong>Medida Compra</strong></th>
					<th  align="center" ><strong>Cantidad</strong></th>
					<!-- <th  align="center" ><strong>Transito</strong></th> -->
					<th  align="center" ><strong>Precio Compra</strong></th>
					<th  align="center" ><strong>Sub Total GS</strong></th>
				
				</tr>
						</thead>
						<tbody>
				<?php $tot = 0;
    $fin = $rscu2->fields['fin'];
    while (!$rscu2->EOF) {
        $subt = $rscu2->fields['precio_compra_total'];
        $tot = $tot + $subt;?>
				<tr <?php if (intval($rscu2->fields['cant_transito']) != 0) { ?> class="transito" <?php } else { ?> class="completo" <?php }?>>
				
				
					<td align="left"><?php echo trim($rscu2->fields['idprod']) ?></td>
					<td align="left"><?php echo trim($rscu2->fields['articulo']) ?></td>
					<!-- <td align="left"><?php //echo intval($rscu2->fields['cant_transito']) == 0 ? "Si":"No"?></td> -->
					<td align="right"><?php echo($rscu2->fields['medida']); ?></td>
				<td align="right"><?php echo formatomoneda($rscu2->fields['cantidad'], 4, 'N'); ?></td>
				<td align="right"><?php echo formatomoneda($rscu2->fields['precio_compra'], 4, 'N'); ?></td>
				<td align="right"><?php echo formatomoneda($rscu2->fields['precio_compra_total'], 4, 'N'); ?></td>
				
				</tr>
				<?php $rscu2->MoveNext();
    } ?>
				<tr>
					<td height="26" colspan="8" align="right" >
						<div class="footer_grilla_prod">
							<div>
								[<?php echo $nombre_moneda; ?>]
								<?php if ($banderita != '') {?><img src="../img/<?php echo $banderita?>"  width="20vw" /><?php }?>
							</div>
							<div><strong>Total Pedido:</strong> <?php echo formatomoneda($tot, 2, 'N');?></div>
						</div>
					</td>
				</tr>
				<?php if ($costo_ref > 0) { ?>
					<tr>
						<td height="26" colspan="8" align="center">
							<div style="font-size: 1.4rem;">
								<strong>Cotizacion Ref. Gs: <?php echo formatomoneda($cot_ref, 2, 'N');?></strong>
								<strong>Total Pedido Gs:<?php echo formatomoneda($costo_ref, 2, 'N');?></strong>
							</div>
							<div style="font-size: 1.4rem;">
								<div><strong>Finalizado el: </strong> <?php echo $fin;?></div>
							</div>
				
						</td>
					</tr>
				<?php }
				?>
				</tbody>
						</table>
					</div>
				</div>
			</td>
			<td></td>
<td colspan="2">
	
	<div class="clearfix"></div>
	<div id="grilla_box2" class="col-md-12" >
		<h2 >Recibidos</h2>
		<?php

        $idcompra = $idcompra;
    //Traemos los productos seleccionados para la compra
    $buscar = "
				Select *, cotizaciones.cotizacion,compras.registrado as fin,
				( Select nombre from medidas where medidas.id_medida = compras_detalles.idmedida ) as medida,
				( select insumos_lista.descripcion from insumos_lista where idinsumo = compras_detalles.codprod )  as articulo,
				(
					select productos.barcode
					from insumos_lista
					inner join productos on productos.idprod_serial = insumos_lista.idproducto
					where
					idinsumo = compras_detalles.codprod
				)  as barcode
				from  compras_detalles
				inner join compras on compras.idcompra = compras_detalles.idcompra
				LEFT JOIN cotizaciones on cotizaciones.idcot = compras.idcot
				where
				compras.idcompra=$idcompra
				order by articulo asc
				";
    $rscu_detalles_compra = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $tprod = $rscu_detalles_compra->RecordCount();





    $cotizacion = "SELECT cotizaciones.cotizacion, tipo_moneda.descripcion as nombre_moneda, tipo_moneda.banderita
				from compras
				LEFT JOIN cotizaciones on cotizaciones.idcot = compras.idcot
				INNER JOIN tipo_moneda on tipo_moneda.idtipo = cotizaciones.tipo_moneda
				where idcompra = $idcompra";
    $rscotizacion = $conexion->Execute($cotizacion) or die(errorpg($conexion, $cotizacion));
    $cot_ref = floatval($rscotizacion->fields['cotizacion']);
    $banderita = antixss($rscotizacion->fields['banderita']);
    $nombre_moneda = antixss($rscotizacion->fields['nombre_moneda']);


    ?>
	
	<div class="table-responsive">
		<table width="100%" class="table table-bordered jambo_table bulk_action">
		<thead>
			<tr>
				<th  align="center" ><strong>Codigo</strong></th>
				<th  align="center" ><strong>Producto</strong></th>
				<th  align="center" ><strong>Medida Compra</strong></th>
				<th  align="center" ><strong>Cantidad</strong></th>
				<!-- <th  align="center" ><strong>Transito</strong></th> -->
				<th  align="center" ><strong>Precio Compra</strong></th>
				<th  align="center" ><strong>Sub Total GS</strong></th>
	
			</tr>
		</thead>
		<tbody>
			<?php $tot = 0;
    $fin = $rscu_detalles_compra->fields['fin'];
    while (!$rscu_detalles_compra->EOF) {
        $subt = $rscu_detalles_compra->fields['subtotal'];
        $tot = $tot + $subt;?>
			<tr >
	
	
				<td align="left"><?php echo trim($rscu_detalles_compra->fields['codprod']) ?></td>
				<td align="left"><?php echo trim($rscu_detalles_compra->fields['articulo']) ?></td>
				<!-- <td align="left"><?php //echo intval($rscu_detalles_compra->fields['cant_transito']) == 0 ? "Si":"No"?></td> -->
				<td align="right"><?php echo($rscu_detalles_compra->fields['medida']); ?></td>
			  <td align="right"><?php echo formatomoneda($rscu_detalles_compra->fields['cantidad'], 4, 'N'); ?></td>
			  <td align="right"><?php echo formatomoneda($rscu_detalles_compra->fields['costo'] / $cot_ref, 4, 'N'); ?></td>
			  <td align="right"><?php echo formatomoneda($rscu_detalles_compra->fields['subtotal'] / $cot_ref, 4, 'N'); ?></td>
	
	
			</tr>
			<?php $rscu_detalles_compra->MoveNext();
    } ?>
			<tr>
			<td height="26" colspan="9" align="right" >
					<div class="footer_grilla_prod">
						<div>
							[<?php echo $nombre_moneda; ?>]
							<?php if ($banderita != '') {?><img src="../img/<?php echo $banderita?>"  width="20vw" /><?php }?>
						</div>
						<div><strong>Total Pedido:</strong> <?php echo formatomoneda($tot / $cot_ref, 2, 'N');?></div>
						
					</div>
				</td>
			</tr>
			<?php if ($cot_ref > 0) { ?>
	
	
	
	
			<tr>
					<td height="26" colspan="9" align="center">
						<div style="font-size: 1.4rem;">
							<strong>Cotizacion Ref. Gs: <?php echo formatomoneda($cot_ref, 2, 'N');?></strong>
							<strong>Total Pedido Gs:<?php echo formatomoneda($tot, 2, 'N');?></strong>
						</div>
						<div style="font-size: 1.4rem;">
						<div><strong>Finalizado el: </strong> <?php echo $fin;?></div>
						</div>
	
					</td>
				</tr>
			<?php }
			?>
			</tbody>
		</table>
	</div>
</td>



</div>



		</tr>
        <?php $rscu->MoveNext();
} ?>
        </tbody>
	</table>
</div>
