<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "613";

$dirsup = "S";
require_once("../includes/rsusuario.php");





$idventa = intval($_POST['idventa']);

function formatearNumero($numero)
{
    // Convertir el número a una cadena
    $numero = strval($numero);

    // Dividir la cadena en partes
    $parte1 = substr($numero, 0, 3);
    $parte2 = substr($numero, 3, 3);
    $parte3 = substr($numero, 6);

    // Unir las partes con guiones
    $numeroFormateado = $parte1 . '-' . $parte2 . '-' . $parte3;

    return $numeroFormateado;
}

$consulta = "
SELECT 
  ventas_detalles.pventa, SUM(ventas_detalles.cantidad) as cantidad, 
  productos.descripcion as producto
FROM
  ventas_detalles 
  INNER JOIN productos on productos.idprod = ventas_detalles.idprod 
WHERE 
  idventa = $idventa
  GROUP BY ventas_detalles.idprod, ventas_detalles.pventa
";
// echo $consulta;exit;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

////////////////////////////////////////////////////////

$consulta = "
SELECT devolucion_det.*,devolucion.registrado_el,
(select medidas.nombre from medidas where medidas.id_medida = devolucion_det.idmedida) as medida,
(select insumos_lista.descripcion from insumos_lista where insumos_lista.idproducto = devolucion_det.idproducto) as insumo,
(select gest_depositos.descripcion from gest_depositos WHERE gest_depositos.iddeposito = devolucion_det.iddeposito) as deposito
FROM devolucion_det
INNER JOIN devolucion on devolucion.iddevolucion = devolucion_det.iddevolucion
WHERE 
	devolucion.estado=3
  and devolucion.idventa=$idventa
  
";

$rs_devolucion = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$contar_devolucion = $rs_devolucion->RecordCount();


///////////////////////////////////////////////////////

$consulta = "
SELECT 
  cliente.razon_social as cliente, 
  CONCAT(
    COALESCE(vendedor.nombres, ''), 
    ' ', 
    COALESCE(vendedor.apellidos, '')
  ) as vendedor 
FROM 
  ventas 
  INNER JOIN cliente on cliente.idcliente = ventas.idcliente 
  INNER JOIN vendedor on vendedor.idvendedor = cliente.idvendedor 
WHERE 
  idventa = $idventa
";

$rs1 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));








?>
<div class="table-responsive" style="height: 50vh;">
  <div class="table-responsive">
  
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <tr>
        <th align="center">Cliente</th>
        <td align="center"><?php echo antixss($rs1->fields['cliente']); ?></td>
      </tr>
          <tr>
        <th align="center">Vendedor</th>
        <td align="center"><?php echo antixss($rs1->fields['vendedor']); ?></td>
      </tr>
      </table>
  </div>
  
  <br>
  <h2>Articulos Vendidos</h2>
  <hr>
  <div class="table-responsive">
      <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
      <tr>
        <th align="center">Precio Venta</th>
        <th align="center">cantidad</th>
        <th align="center">Producto</th>
      </tr>
      </thead>
      <tbody>
  <?php while (!$rs->EOF) { ?>
      <tr>
              <td align="center"><?php echo formatomoneda($rs->fields['pventa']); ?></td>
              <td align="center"><?php echo formatomoneda($rs->fields['cantidad']); ?></td>
              <td align="center"><?php echo antixss($rs->fields['producto']); ?></td>
          </tr>
  <?php

  $rs->MoveNext();
  } //$rs->MoveFirst();?>
  
      </tbody>
      </table>
  </div>
  <br />


<?php if (intval($contar_devolucion) > 0) { ?>
  <div class="table-responsive">
  <h2>Articulos Devueltos</h2>


<hr>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
    <tr>
      <th align="center">Deposito</th>
      <th align="center">Articulo</th>
      <th align="center">Cantidad</th>
      <th align="center">Medida</th>
      <th align="center">Lote</th>
      <th align="center">Vencimiento</th>
      <th align="center">Registrado el</th>
    </tr>
    </thead>
    <tbody>
<?php while (!$rs_devolucion->EOF) { ?>
    <tr>
      <td align="center"><?php echo antixss($rs_devolucion->fields['deposito']); ?></td>
      <td align="center"><?php echo antixss($rs_devolucion->fields['insumo']); ?></td>
      <td align="center"><?php echo formatomoneda($rs_devolucion->fields['cantidad']); ?></td>
      <td align="center"><?php echo antixss($rs_devolucion->fields['medida']); ?></td>
      <td align="center"><?php echo antixss($rs_devolucion->fields['lote']); ?></td>
      <td align="center"><?php echo antixss($rs_devolucion->fields['vencimiento']); ?></td>
      <td align="center"><?php echo antixss($rs_devolucion->fields['registrado_el']); ?></td>
    </tr>
<?php

$rs_devolucion->MoveNext();
} //$rs->MoveFirst();?>

    </tbody>
    </table>
</div>
<?php } ?>
  </div>


  
</div>


