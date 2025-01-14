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
require_once("../modelos/factura.php");
require_once("preferencias_compras.php");
global $$preferencias_importacion;
// consulta a la tabla

$gastos = intval($gastos);
if ($gastos == 0) {
    if (intval($_POST['gastos']) != 0) {
        $gastos = intval($_POST['gastos']);
    }
    if (intval($_GET['gastos']) != 0) {
        $gastos = intval($_GET['gastos']);
    }
}
$idcompra = intval($idcompra);
if ($idcompra == 0) {
    if (intval($_POST['idcompra']) != 0) {
        $idcompra = intval($_POST['idcompra']);
    }
    if (intval($_GET['idcompra']) != 0) {
        $idcompra = intval($_GET['idcompra']);
    }
}
$idmoneda_select = intval($idmoneda_select);
if ($idmoneda_select == 0) {
    if (intval($_POST['idmoneda_select']) != 0) {
        $idmoneda_select = intval($_POST['idmoneda_select']);
    }
    if (intval($_GET['idmoneda_select']) != 0) {
        $idmoneda_select = intval($_GET['idmoneda_select']);
    }
}
$id_moneda_nacional = intval($id_moneda_nacional);
if ($id_moneda_nacional == 0) {
    if (intval($_POST['id_moneda_nacional']) != 0) {
        $id_moneda_nacional = intval($_POST['id_moneda_nacional']);
    }
    if (intval($_GET['id_moneda_nacional']) != 0) {
        $id_moneda_nacional = intval($_GET['id_moneda_nacional']);
    }
}
$cotizacion = floatval($cotizacion);
if ($cotizacion == 0) {
    if (floatval($_POST['cotizacion']) != 0) {
        $cotizacion = floatval($_POST['cotizacion']);
    }
    if (floatval($_GET['cotizacion']) != 0) {
        $cotizacion = floatval($_GET['cotizacion']);
    }
}

if (!isset($nombre_moneda)) {
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

}


// echo json_encode($_POST);exit;

$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%DESPACHO\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_despacho = intval($rs_conceptos->fields['idconcepto']);

$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%FLETE\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_flete = intval($rs_conceptos->fields['idconcepto']);




$borrar = intval($_POST['borrar']);

if ($borrar == 1) {
    $idcompra_borrar = $_POST['idcompra_borrar'];
    $idregs_borrar = $_POST['idregs_borrar'];
    $idempresa_borrar = $_POST['idempresa_borrar'];
    $nombre_producto_borrar = $_POST['nombre_producto_borrar'];

    // $consulta="update  compras_detalles set estado=6 WHERE compras_detalles.idregs = $idregs_borrar";
    // $rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

    $consulta = "update  compras set estado = 6 WHERE compras.idcompra = $idcompra_borrar";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ///////////////////////////////////////////////////////////////////////
    $parametros_gastos = [
        "idcompra_ref" => $idcompra
    ];
    relacionar_gastos($parametros_gastos);
}

$consulta = "
	select compras.usa_cot_despacho,compras_detalles.codprod as codigoprod, compras_detalles.* , compras_detalles.costo as costo, insumos_lista.descripcion as descripcion, insumos_lista.idconcepto,
	(select cn_conceptos.descripcion from cn_conceptos where cn_conceptos.idconcepto = insumos_lista.idconcepto) as concepto,
	(select descripcion from gest_depositos where iddeposito=compras_detalles.iddeposito_compra) as deposito_por_defecto,
	compras.obsfactura, compras.moneda, tipo_moneda.descripcion as nombre_moneda,compras.facturacompra, compras.idcot
	from compras_detalles 
	inner join insumos_lista on insumos_lista.idinsumo = compras_detalles.codprod
	INNER JOIN compras on compras_detalles.idcompra = compras.idcompra 
	LEFT JOIN tipo_moneda on tipo_moneda.idtipo = compras.moneda
	where 
	compras.idcompra_ref = $idcompra
	and compras.estado !=6
	order by insumos_lista.descripcion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$buscar = "Select * from preferencias_compras limit 1";
$rsprefecompras = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$depodefecto = trim($rsprefecompras->fields['usar_depositos_asignados']);
$facturas = [];
function guardar_diccionario($clave, &$diccionario, $valor)
{
    if (array_key_exists($clave, $diccionario)) {
        $diccionario[$clave][] = $valor;
    } else {
        $diccionario[$clave] = [$valor];
    }
}
while (!$rs->EOF) {
    $idsub_compra = $rs->fields['idcompra'];
    $idreg = $rs->fields['idregs'];
    $usa_cot_despacho = $rs->fields['usa_cot_despacho'];
    $idinsumo = $rs->fields['codigoprod'];
    $producto = $rs->fields['descripcion'];
    $deposito = $rs->fields['deposito_por_defecto'];
    $concepto = $rs->fields['concepto'];
    $idconcepto = $rs->fields['idconcepto'];
    $cantidad = $rs->fields['cantidad'];
    $idmoneda = $rs->fields['moneda'];
    $costo = $rs->fields['costo'];
    $subtotal = $rs->fields['subtotal'];
    $iva = $rs->fields['iva'];
    $lote = $rs->fields['lote'];
    $vencimiento = $rs->fields['vencimiento'];
    $comentarios = $rs->fields['obsfactura'];
    $nombre_moneda = $rs->fields['nombre_moneda'];
    $facturacompra = $rs->fields['facturacompra'];
    $idcot = $rs->fields['idcot'];

    $factura = new factura($idsub_compra, $idreg, $idinsumo, $producto, $deposito, $concepto, $idconcepto, $cantidad, $idmoneda, $costo, $subtotal, $iva, $lote, $vencimiento, $comentarios, $nombre_moneda, $usa_cot_despacho, $facturacompra, "", "", "", "", "", $idcot);

    // guardar_diccionario($idmoneda, $facturas, $factura);

    if (array_key_exists($idmoneda, $facturas)) {
        if (array_key_exists($factura->idcompra, $facturas[$idmoneda])) {
            $facturas[$idmoneda][$factura->idcompra][] = $factura;

        } else {
            $facturas[$idmoneda][$factura->idcompra] = [$factura];

        }
    } else {
        $facturas[$idmoneda] = [];
        $facturas[$idmoneda][$factura->idcompra] = [$factura];
    }


    $rs->MoveNext();

}
$rs->MoveFirst();
// echo json_encode($rs->fields);
// echo json_encode($facturas);


// $consulta="
// SELECT despacho.cotizacion as cot_despacho
// FROM despacho
// WHERE idcompra = (select idcompra_ref from compras where idcompra = $idcompra)
// ";
$consulta = "
SELECT despacho.cotizacion as cot_despacho 
FROM despacho 
WHERE idcompra = ( $idcompra ) 
";
$rs_despa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$cot_despacho = floatval($rs_despa->fields['cot_despacho']);

?>

<?php
if ($preferencias_importacion == "S") { ?>

	<h2>Gastos Asociados</h2>
	<hr>
	<div class="table-responsive">
	<?php   if (count($facturas) > 0) { ?>
		<?php
             foreach ($facturas as $idmoneda => $monedas_array) {
                 $contador = 0;
                 foreach ($monedas_array as $id_factura) {
                     $bandera_factura = 0;
                     foreach ($id_factura as $factura) {
                         ?>
						<?php if ($contador == 0) { ?>
							<table <?php if ($factura->idmoneda != $id_moneda_nacional) { ?>id="tabla_extranjera"<?php }?> width="100%" class="table table-bordered jambo_table bulk_action">
								<thead>
								<?php if ($factura->idmoneda != $id_moneda_nacional) { ?>
									<h2>Gastos Moneda Extranjera</h2>
									
									<?php } else { ?>
										<h2>Gastos Moneda Nacional</h2>
									<?php } ?>
									<tr>
										<th align="center">idcompra</th>
										<th align="center">idreg</th>
										<th align="center">C&oacute;digo</th>
										<th align="center">Producto</th>
										<?php if ($depodefecto == 'S') { ?>
											<th align="center">Depo&sacute;ito asignado</th>
										<?php } ?>
										<th align="center">Concepto</th>
										<th align="center">Cantidad</th>
										<th align="center">Costo <?php if ($factura->idmoneda != $id_moneda_nacional) { ?>Moneda Nacional<?php } ?></th>
										<?php if ($factura->idmoneda != $id_moneda_nacional) { ?>
											<th align="center">Costo <?php echo $factura->nombre_moneda?></th>
										<?php } ?>
	
										<th align="center">Subtotal <?php if ($factura->idmoneda != $id_moneda_nacional) { ?>Moneda Nacional<?php } ?></th>
										<?php if ($factura->idmoneda != $id_moneda_nacional) { ?>
											<th align="center">Subtotal <?php echo $factura->nombre_moneda?></th>
										<?php } ?>
										<th align="center">Iva %</th>
										<th align="center">Lote</th>
										<th align="center">Vencimiento</th>
										<th align="center">Comentario</th>
										<?php if ($factura->idmoneda != $id_moneda_nacional && $cot_despacho != 0 && $preferencias_importacion == "S") { ?>
											<th>Usa Cot Despacho</th>
										<?php } ?>
										<th></th>
									</tr>
								</thead>
								<tbody>
	
						<?php $contador++;
						}
                         if ($contador != 0) {  ?>
	
						<?php if ($bandera_factura == 0) { ?>
							<tr>
								<td align="center" colspan="<?php if ($factura->idmoneda != $id_moneda_nacional) {
								    echo "15";
								} else {
								    echo "13";
								} ?>">
									 <?php
                                        if ($factura->idcot > 0) {

                                            $consulta = "SELECT cotizacion, fecha FROM cotizaciones WHERE idcot =$factura->idcot ";
                                            $rs_cotizaciones = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                                            $cotizacion_gasto_asociado = $rs_cotizaciones->fields['cotizacion'];
                                            $fecha_cotizacion_gasto_asociado = $rs_cotizaciones->fields['fecha'];
                                            echo "Factura: ".($factura->facturacompra)."  -- Cotizacion: ".formatomoneda($cotizacion_gasto_asociado, 2, "S")."  --  Fecha: ".date("d/m/Y ", strtotime($fecha_cotizacion_gasto_asociado)) ;
                                        } else {
                                            echo "Factura: ".($factura->facturacompra);

                                        }
						    ?>
									
								</td>
								<?php

                                if ($factura->idmoneda != $id_moneda_nacional && $cot_despacho != 0) { ?>
									<td align="center">
										<div align="center" id="box_td_gasto_cot_despacho_<?php echo $factura->idcompra; ?>">
											<input name="gasto_cot_despacho_<?php echo $factura->idcompra; ?>" id="gasto_cot_despacho_<?php echo $factura->idcompra; ?>" type="checkbox" value="S" class="js-switch" onChange="registrar_cot_despacho_gasto(<?php echo $factura->idcompra; ?>);" <?php if ($factura->usa_cot_despacho == "S") {
											    echo "checked";
											} ?>   >
										</div>
									</td>
								<?php } ?>
								<td align="center">
									<a href="javascript:void(0);" onclick="borrar_gasto_asociado(event,<?php echo $factura->idcompra ?>,<?php echo $factura->idreg?>,<?php echo $idempresa ?>,'<?php echo $factura->producto ?>');" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Borrar"><span class="fa fa-trash"></span></a>
								</td>
								
							</tr>
						<?php $bandera_factura = 1;
						} ?>
							<tr>
								<td align="center"><?php echo antixss($factura->idcompra); ?></td>
								<td align="center"><?php echo antixss($factura->idreg); ?></td>
								<td align="center"><?php echo antixss($factura->idinsumo); ?></td>
								<td align="center"><?php echo antixss($factura->producto); ?></td>
								<?php if ($depodefecto == 'S') { ?>
								<td align="center"><?php echo antixss($factura->deposito); ?></td>
								<?php } ?>
								<td align="center"><?php echo antixss($factura->concepto); ?></td>
								<td align="right"><?php echo formatomoneda($factura->cantidad, 4, 'N');  ?></td>
	
								<?php if ($factura->idmoneda != $id_moneda_nacional) { ?>
									<?php if ($factura->usa_cot_despacho == "S") { ?>
										<td align="right"><?php echo formatomoneda(($factura->costo / $cotizacion) * $cot_despacho) ;  ?></td>
									<?php } else { ?>
										<td align="right"><?php echo formatomoneda(($factura->costo)) ;  ?></td>
									<?php } ?>
								<?php } else { ?>
									<td align="right"><?php echo formatomoneda($factura->costo);  ?></td>
								<?php } ?>
	
	
								<?php if ($factura->idmoneda != $id_moneda_nacional) { ?>
										<td align="right"><?php echo formatomoneda($factura->costo / $cotizacion, "2", "S");  ?></td>
								<?php }?>
	
	
	
								<?php if ($factura->idmoneda != $id_moneda_nacional) { ?>
									<?php if ($factura->usa_cot_despacho == "S") { ?>
										<td align="right"><?php echo formatomoneda(($factura->subtotal / $cotizacion) * $cot_despacho) ;  ?></td>
									<?php } else { ?>
										<td align="right"><?php echo formatomoneda(($factura->subtotal)) ;  ?></td>
									<?php } ?>
								<?php } else { ?>
									<td align="right"><?php echo formatomoneda($factura->subtotal);  ?></td>
								<?php } ?>
	
	
								<?php if ($factura->idmoneda != $id_moneda_nacional) { ?>
										<td align="right"><?php echo formatomoneda($factura->subtotal / $cotizacion, "2", "S");  ?></td>
								<?php }?>
	
	
	
								<?php if ($factura->idconcepto != $idconcepto_despacho && $factura->idconcepto != $idconcepto_flete) { ?>
									
										<td align="center"><?php echo floatval($factura->iva);?>%</td>
									<?php } else {

									    $consulta = "select ivaml from facturas_proveedores_det_impuesto where id_factura = $factura->idcompra";
									    $rs_iva_variable = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
									    $ivaml = $rs_iva_variable->fields['ivaml'];
									    // $ivaml = formatomoneda($ivaml,2,"S")
									    ?>
											<?php if ($factura->usa_cot_despacho == "S") { ?>
												<td align="center"><?php echo formatomoneda(($ivaml / $cotizacion) * $cot_despacho, 0, "S"); ?> </td>
												<?php } else { ?>
													<td align="center"><?php echo formatomoneda($ivaml, 0, "S") ?> </td>
													<?php } ?>
												
								<?php } ?>
								<td align="center"><?php echo antixss($factura->lote); ?></td>
								<td align="center"><?php if ($factura->vencimiento != "") {
								    echo date("d/m/Y", strtotime($factura->vencimiento));
								} ?></td>
								<td align="center"><?php echo antixss($factura->obsfactura); ?></td>
								<td align="center">
										<!-- <a href="javascript:void(0);" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Detalle"><span class="fa fa-search"></span></a> -->
										<!-- <a href="javascript:void(0);" onclick="editar_deposito_compra(event,<?php echo $factura->idcompra ?>,<?php echo $factura->idregs?>,<?php echo $factura->idempresa ?>,'<?php echo $factura->descripcion ?>');" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Editar"><span class="fa fa-edit"></span></a> -->
										<!-- <a href="javascript:void(0);" onclick="borrar_gasto_asociado(event,<?php echo $factura->idcompra ?>,<?php echo $factura->idreg?>,<?php echo $idempresa ?>,'<?php echo $factura->producto ?>');" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Borrar"><span class="fa fa-trash"></span></a> -->
										<div style="width:4.2rem"></div>
									</td>
							</tr>
						<?php } ?>
						<?php if ($gastos == 1) { ?>
						<script>
							var elems = document.querySelector('#gasto_cot_despacho_'+<?php echo $factura->idcompra; ?>);
							var switchery = new Switchery(elems);
						</script>	
						<?php } ?>
					<?php } ?>
			<?php } ?>
				
				<?php
                     $contador++;
             } ?>
			  </tbody>
		</table>
	
	
	
	
		<?php } ?>
	</div>
<?php } ?>

    

	
<?php
// Código PHP


// Verificar si la cadena no es nula

if (($errores) != "") {

    // Generar el código JavaScript con jQuery
    $script = "
    <script>
            $('#titulovError').html('Error');
            $('#cuerpovError').html('$errores');	
			$('#ventanamodalError').modal('show');
    </script>
    ";
    // Imprimir el código JavaScript generado
    echo $script;
}
?>
    
