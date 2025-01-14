<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
$dirsup = 'S';
require_once("../includes/rsusuario.php");
$idventa = $_POST['idventa'];
$idorden_retiro = $_POST['idorden_retiro'];
$devolucion = $_POST['devolucion'];
$consulta = "SELECT 
  ventas_detalles.pventa, SUM(ventas_detalles.cantidad) as cantidad, 
  productos.descripcion as producto
FROM
  ventas_detalles 
  INNER JOIN productos on productos.idprod = ventas_detalles.idprod 
WHERE 
  idventa = $idventa
  GROUP BY ventas_detalles.idprod, ventas_detalles.pventa
";
$rs_ventas_detalle = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



if ($devolucion == 1) {
    $consulta = "
    SELECT 
  devolucion_det.*, 
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
  ) as deposito 
FROM 
  devolucion_det 
  INNER JOIN devolucion on devolucion.iddevolucion = devolucion_det.iddevolucion
  INNER JOIN retiros_ordenes on retiros_ordenes.iddevolucion = devolucion_det.iddevolucion
WHERE 
	retiros_ordenes.idorden_retiro = $idorden_retiro
      
    ";

    $rs_devolucion = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}


?>
<div class="table-responsive" >
    <h2 style="font-weight: bold;color: #EE964B;">Articulos de la Factura</h2>
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead style = "background: #EE964B;color: white;font-weight: bold;">
      <tr>
            <th align="center">Precio Venta</th>
            <th align="center">cantidad</th>
            <th align="center">Producto</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs_ventas_detalle->EOF) { ?>
		<tr>
            <td align="center"><?php echo formatomoneda($rs_ventas_detalle->fields['pventa']); ?></td>
            <td align="center"><?php echo formatomoneda($rs_ventas_detalle->fields['cantidad']); ?></td>
            <td align="center"><?php echo antixss($rs_ventas_detalle->fields['producto']); ?></td>
        </tr>
<?php

$rs_ventas_detalle->MoveNext();
}  ?>
	  </tbody>
    </table>

    <?php if ($devolucion == 1) { ?>
    <h2 style="font-weight: bold;color: #EE964B;">Devolucion</h2>
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead style = "background: #EE964B;color: white;font-weight: bold;">
      <tr>
            <th align="center">cantidad</th>
            <th align="center">Producto</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs_devolucion->EOF) { ?>
		<tr>
            <td align="center"><?php echo formatomoneda($rs_devolucion->fields['cantidad']); ?></td>
            <td align="center"><?php echo antixss($rs_devolucion->fields['insumo']); ?></td>
        </tr>
    
<?php

$rs_devolucion->MoveNext();
}  ?>
	  </tbody>
    </table>

    <?php } ?>
</div>