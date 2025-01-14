 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");

//echo $idcaja;
$consulta = "
select *, (select chapa from tmp_ventares_cab where idventa = ventas.idventa and chapa is not null order by idtmpventares_cab desc limit 1) as chapa
from ventas 
inner join canal on canal.idcanal = ventas.idcanal
where
idcaja = $idcaja
and idempresa = $idempresa
and estado <> 6
";
//echo $consulta;
$rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>      <br />
    <h1 align="center">Ventas de la Caja:</h1>
    <br />
<table width="900" border="1">
  <thead>
    <tr>
      <th align="center" bgcolor="#F8FFCC"><strong>Fecha</strong></th>
      <th align="center" bgcolor="#F8FFCC">Total Venta</th>
      <th align="center" bgcolor="#F8FFCC"><strong>+ Delivery</strong></th>
      <th align="center" bgcolor="#F8FFCC"><strong>- Descuentos</strong></th>
      <th align="center" bgcolor="#F8FFCC"><strong>= Total Cobrado</strong></th>
      <th align="center" bgcolor="#F8FFCC">Canal</th>
      <th align="center" bgcolor="#F8FFCC">Obs</th>
      <th align="center" bgcolor="#F8FFCC">[detalle]</th>
      </tr>
  </thead>
  <tbody>
<?php while (!$rsv->EOF) {
    $tventaacum += $rsv->fields['total_venta'];
    $tdeliveryacum += $rsv->fields['otrosgs'];
    $tdescuentoacum += $rsv->fields['descneto'];
    $tcobradoacum += $rsv->fields['total_cobrado'];

    ?>
    <tr>
      <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rsv->fields['fecha'])); ?></td>
      <td align="right"><?php echo formatomoneda($rsv->fields['total_venta']); ?></td>
      <td align="right"><?php echo formatomoneda($rsv->fields['otrosgs']); ?></td>
      <td align="right"><?php echo formatomoneda($rsv->fields['descneto']); ?></td>
      <td align="right"><?php echo formatomoneda($rsv->fields['total_cobrado']); ?></td>
      <td align="center"><?php echo $rsv->fields['canal']; ?></td>
      <td align="center"><?php echo $rsv->fields['chapa']; ?></td>
      <td align="center"><a href="rs_caja_ventas_det.php?id=<?php echo $rsv->fields['idventa']; ?>">[detalle]</a></td>
      </tr>
<?php $rsv->MoveNext();
} ?>
    <tr>
      <td align="center" bgcolor="#F8FFCC"><strong>Totales:</strong></td>
      <td align="right" bgcolor="#F8FFCC"><strong><?php echo formatomoneda($tventaacum); ?></strong></td>
      <td align="right" bgcolor="#F8FFCC"><strong><?php echo formatomoneda($tdeliveryacum); ?></strong></td>
      <td align="right" bgcolor="#F8FFCC"><strong><?php echo formatomoneda($tdescuentoacum); ?></strong></td>
      <td align="right" bgcolor="#F8FFCC"><strong style="color:#1A8400;"><?php echo formatomoneda($tcobradoacum); ?></strong></td>
      <td align="right" bgcolor="#F8FFCC">&nbsp;</td>
      <td align="right" bgcolor="#F8FFCC">&nbsp;</td>
      <td align="right" bgcolor="#F8FFCC">&nbsp;</td>
      </tr>
  </tbody>
</table>
