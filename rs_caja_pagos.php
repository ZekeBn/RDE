 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");

//Comprobar apertura de caja en fecha establecida
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);
if ($idcaja == 0) {
    echo "<meta http-equiv='refresh' content='0; url=teso_caja_chica.php'/>"     ;
    exit;
}
if ($estadocaja == 3) {
    echo "<meta http-equiv='refresh' content='0; url=teso_caja_chica.php'/>"     ;
    exit;
}



//echo $idcaja;
$consulta = "
select cuentas_empresa_pagos.*, 
(select descripcion from gest_bancos where banco = cuentas_empresa_pagos.banco limit 1) as banco,
(select nombre from proveedores where cuentas_empresa.idproveedor = proveedores.idproveedor and idempresa = $idempresa limit 1) as proveedor,
cuentas_empresa.tipo as tipofactura
from cuentas_empresa_pagos
inner join cuentas_empresa on cuentas_empresa_pagos.idcta = cuentas_empresa.idcta
where
cuentas_empresa_pagos.idcaja = $idcaja
and cuentas_empresa_pagos.idempresa = $idempresa
and cuentas_empresa_pagos.estado <> 6
ORDER BY cuentas_empresa_pagos.fecha_pago asc
";
$rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
select *,(select nombre from proveedores where idproveedor=pagos_extra.idprov) as proveedor
from pagos_extra where idcaja=$idcaja and estado<> 6 order by fecha desc
";
$rsvef = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tpagosef = floatval($rsvef->fields['totalp']);


?>      <br />
    <h1 align="center">Pagos de la Caja:</h1>
    <br />
<table width="900" border="1">
  <thead>
    <tr>
      <th align="center" bgcolor="#F8FFCC"><strong>Fecha</strong></th>
      <th align="center" bgcolor="#F8FFCC">Concepto</th>
      <th align="center" bgcolor="#F8FFCC">Monto</th>
      <th align="center" bgcolor="#F8FFCC">Factura</th>
      <th align="center" bgcolor="#F8FFCC">Proveedor</th>
  
      </tr>
  </thead>
  <tbody>
<?php while (!$rsvef->EOF) {
    $tmontoacum += $rsvef->fields['monto_abonado'];
    $tipofactura = $rsvef->fields['tipofactura'];
    $facturanro = $rsvef->fields['factura'];
    if ($tipofactura == 1) {
        $tipofacturatxt = "CREDITO";
    } elseif ($tipofactura == 2) {
        $tipofacturatxt = "CONTADO";
    } else {
        $tipofacturatxt = "NO DEFINIDO";
    }
    ?>
    <tr>
      <td height="31" align="center"><?php echo date("d/m/Y H:i:s", strtotime($rsvef->fields['fecha'])); ?></td>
      <td align="right"><?php echo $rsvef->fields['concepto'] ?></td>
      <td align="right"><?php echo formatomoneda($rsvef->fields['monto_abonado']); ?></td>
      <td align="right"><?php echo $facturanro; ?></td>
      <td align="right"><?php echo capitalizar($rsvef->fields['proveedor']); ?></td>
    
      </tr>
<?php $rsvef->MoveNext();
} ?>
    <tr>
      <td align="center" bgcolor="#F8FFCC"><strong>Totales:</strong></td>
      <td align="right" bgcolor="#F8FFCC">&nbsp;</td>
      <td align="right" bgcolor="#F8FFCC"><strong><?php echo formatomoneda($tmontoacum); ?></strong></td>
      <td align="right" bgcolor="#F8FFCC">&nbsp;</td>
      <td align="right" bgcolor="#F8FFCC">&nbsp;</td>
      
  
      </tr>
  </tbody>
</table>
