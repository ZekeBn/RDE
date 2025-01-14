 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "26";
$submodulo = "310";
require_once("includes/rsusuario.php");

/* RELLENAR SUCURSAL
INSERT INTO sucursal_cliente
( idcliente, sucursal, estado, registrado_por, registrado_el, borrado_por, borrado_el)
select idcliente, 'CASA MATRIZ', 1, 1, NOW(), NULL, NULL
from operacion
WHERE
operacion.idcliente not in (select idcliente from sucursal_cliente)
GROUP by idcliente;

update `sucursal_cliente` set sucursal = 'CASA MATRIZ';

UPDATE operacion set idsucursal_clie =
    (
    select idsucursal_clie
    from sucursal_cliente
    where
    idcliente = operacion.idcliente
    limit 1
    )
where
idsucursal_clie is null;

*/

if (intval($_POST['idcliente']) > 0) {
    $idcliente = intval($_POST['idcliente']);
}

if ($idcliente == 0) {
    echo "No se indico el cliente.";
    exit;
}


$findemes = date("Y-m-").ultimoDiaMes(date("m"), date("Y"));
$proximomes = date("Y-m-d", strtotime($findemes."+ 1 days"));
$findemes_proximomes = date("Y", strtotime($proximomes)).'-'.date("m", strtotime($proximomes)).'-'.ultimoDiaMes(date("m"), date("Y"));

$consulta = "
SELECT detalle.*, operacion_clase.operacion_clase, operacion.observacion as observacion,
(select sucursal from sucursal_cliente where idsucursal_clie = operacion.idsucursal_clie) as sucursal_clie
FROM operacion 
inner join detalle on operacion.idoperacion = detalle.idoperacion 
inner join operacion_clase on operacion_clase.idoperacion_clase = operacion.idoperacion_clase
where 
operacion.idcliente = $idcliente 
and operacion.estado = 1 
and detalle.vencimiento <= '$findemes_proximomes'
and detalle.facturado_cuota_saldo > 0
and detalle.iddetalle not in (
    select tmp_carrito_factu.iddetalle
    from tmp_carrito_factu
    inner join detalle on detalle.iddetalle = tmp_carrito_factu.iddetalle
    inner join operacion on operacion.idoperacion = detalle.idoperacion 
    inner join operacion_clase on operacion_clase.idoperacion_clase = operacion.idoperacion_clase
    where 
     tmp_carrito_factu.estado = 1 
     and tmp_carrito_factu.idcliente = $idcliente
     and tmp_carrito_factu.registrado_por = $idusu
)
order by detalle.vencimiento asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="center"><a href="javascript:void(0);" onMouseUp="agregar_cta_todas();" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar Todas</a></th>
            <th align="center">Vencimiento</th>
            <th align="center">Concepto</th>
            <th align="center">Monto Cuota</th>
            <th align="center">Monto Cobrado</th>
            <th align="center">Monto Quita</th>
            <th align="center">Saldo Cuota</th>
            <th align="center">Monto Facturado</th>
            <th align="center">Pendiente de Facturacion</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) {

    $monto_cuota = $rs->fields['monto_cuota'];
    $cobra_cuota = $rs->fields['cobra_cuota'];
    $quita_cuota = $rs->fields['quita_cuota'];
    $saldo_cuota = $rs->fields['saldo_cuota'];
    $facturado_cuota = $rs->fields['facturado_cuota'];
    $facturado_cuota_saldo = $rs->fields['facturado_cuota_saldo'];

    $monto_cuota_acum += $monto_cuota;
    $cobra_cuota_acum += $cobra_cuota;
    $quita_cuota_acum += $quita_cuota;
    $saldo_cuota_acum += $saldo_cuota;
    $facturado_cuota_acum += $facturado_cuota;
    $facturado_cuota_saldo_acum += $facturado_cuota_saldo;

    $iddetalle = $rs->fields['iddetalle'];

    ?>
        <tr>
            <td align="center">        
                <input name="iddetalle_<?php echo $iddetalle; ?>" id="iddetalle_<?php echo $iddetalle; ?>" type="text" value="<?php echo floatval($rs->fields['facturado_cuota_saldo']); ?>">    
                <div class="btn-group">
                    <a href="javascript:void(0);" onMouseUp="agregar_cta(<?php echo intval($iddetalle); ?>);" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a>
                </div>
            </td>
            <td align="right" <?php if (strtotime($rs->fields['vencimiento']) > strtotime(date("Y-m-d"))) { ?>style="color:#090;font-weight:bold;"<?php } ?>><?php echo date("d/m/Y", strtotime($rs->fields['vencimiento']));  ?></td>
            <td align="right"><?php echo antixss($rs->fields['operacion_clase']);  ?><?php if (trim($rs->fields['sucursal_clie']) != '') {
                echo "<br />".antixss($rs->fields['sucursal_clie']);
            } ?><?php if (trim($rs->fields['observacion']) != '') {
                echo "<br />".antixss($rs->fields['observacion']);
            } ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['monto_cuota']);  ?></td>

            <td align="right"><?php echo formatomoneda($rs->fields['cobra_cuota']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['quita_cuota']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['saldo_cuota']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['facturado_cuota']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['facturado_cuota_saldo']);  ?></td>

        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
        <tr style="background-color:#CCC; font-weight:bold;">
          <td>Totales:</td>
          <td align="center">&nbsp;</td>
          <td align="center">&nbsp;</td>
          <td align="right"><?php echo formatomoneda($monto_cuota_acum); ?></td>
          <td align="right"><?php echo formatomoneda($cobra_cuota_acum); ?></td>
          <td align="right"><?php echo formatomoneda($quita_cuota_acum); ?></td>
          <td align="right"><?php echo formatomoneda($saldo_cuota_acum); ?></td>
          <td align="right"><?php echo formatomoneda($facturado_cuota_acum); ?></td>
          <td align="right"><?php echo formatomoneda($facturado_cuota_saldo_acum); ?></td>

          </tr>
      </tbody>
    </table>
</div>

<br />
