<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
$dirsup = 'S';
require_once("../includes/rsusuario.php");

if (isset($_POST['idorden_retiro'])) {
    $idorden_retiro = intval($_POST['idorden_retiro']);
}
if ($idorden_retiro > 0) {

    $consulta = "
	SELECT 
	  devolucion_det.*, devolucion.idventa,
	  devolucion.registrado_el, 
	  (
	    select 
	      medidas.nombre 
	    from 
	      medidas 
	    where 
	      medidas.id_medida = devolucion_det.idmedida
	  ) as medida, 
	  (
	    select 
	      insumos_lista.descripcion 
	    from 
	      insumos_lista 
	    where 
	      insumos_lista.idproducto = devolucion_det.idproducto
	  ) as insumo, 
	  (
	    select 
	      gest_depositos.descripcion 
	    from 
	      gest_depositos 
	    WHERE 
	      gest_depositos.iddeposito = devolucion_det.iddeposito
	  ) as deposito,
	  (
	        SELECT ventas_detalles.pventa 
	        from ventas_detalles 
	        WHERE ventas_detalles.idprod = devolucion_det.idproducto 
	        and ventas_detalles.idventa = devolucion.idventa limit 1
	    ) as pventa,
	    (
	        SELECT ventas.factura from ventas where ventas.idventa = devolucion.idventa
	    ) as factura
	FROM 
	  devolucion_det 
	  INNER JOIN devolucion on devolucion.iddevolucion = devolucion_det.iddevolucion
	  INNER JOIN retiros_ordenes on retiros_ordenes.iddevolucion = devolucion_det.iddevolucion
	WHERE 
	  retiros_ordenes.idorden_retiro = $idorden_retiro
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}


?>
<?php if ($idorden_retiro > 0) { ?>
	<strong>Articulos en la Orden de Devolucion:</strong>
	<div class="table-responsive">
		<table width="100%" class="table table-bordered jambo_table bulk_action">
		  <thead>
			<tr>
				<th align="center">Idventa</th>
				<th align="center">Factura</th>
				<th align="center">Cod Articulo</th>
				<th align="center">Concepto</th>
				<th align="center">Cantidad</th>
				<th align="center">Precio</th>
				<th align="center">Subtotal</th>
				<th align="center">Deposito</th>
			</tr>
		  </thead>
		  <tbody>
	<?php
        $tt = 0;
    while (!$rs->EOF) {
        $tt = $tt + floatval($rs->fields['subtotal']);

        ?>
			<tr>
				<td align="center"><?php echo intval($rs->fields['idventa']); ?></td>
				<td align="center"><?php echo antixss($rs->fields['factura']); ?></td>
				<td align="center"><?php if ($rs->fields['idproducto'] > 0) {
				    echo antixss($rs->fields['idproducto']);
				}  ?></td>
				<td align="left"><?php echo antixss($rs->fields['insumo']); ?></td>
				<td align="right"><?php echo formatomoneda($rs->fields['cantidad'], 4, 'N');  ?></td>
				<td align="right"><?php echo formatomoneda($rs->fields['pventa']);  ?></td>
				<td align="right"><?php echo formatomoneda($rs->fields['pventa'] * $rs->fields['cantidad']);  ?></td>
				<td align="left"><?php echo antixss($rs->fields['deposito']);  ?></td>
			</tr>
	<?php $rs->MoveNext();
    } //$rs->MoveFirst();?>
			
		  </tbody>
		</table>
	</div>
	<br />
<?php } ?>
