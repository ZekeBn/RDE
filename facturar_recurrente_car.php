 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "26";
$submodulo = "310";
require_once("includes/rsusuario.php");

$texto_defecto_inicio = "Servicio de sistema informatico: ".$saltolinea; // traer de preferencias

if (intval($_POST['idcliente']) > 0) {
    $idcliente = intval($_POST['idcliente']);
}

if ($idcliente == 0) {
    echo "No se indico el cliente.";
    exit;
}

$consulta = "
select *,
(select usuario from usuarios where tmp_carrito_factu.registrado_por = usuarios.idusu) as registrado_por,
(select sucursal from sucursal_cliente where idsucursal_clie = operacion.idsucursal_clie) as sucursal_clie
from tmp_carrito_factu
inner join detalle on detalle.iddetalle = tmp_carrito_factu.iddetalle
inner join operacion on operacion.idoperacion = detalle.idoperacion 
inner join operacion_clase on operacion_clase.idoperacion_clase = operacion.idoperacion_clase
where 
 tmp_carrito_factu.estado = 1 
 and tmp_carrito_factu.idcliente = $idcliente
 and tmp_carrito_factu.registrado_por = $idusu
order by detalle.vencimiento asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="center"></th>
            <th align="center">Vencimiento</th>
            <th align="center">Concepto</th>
            <th align="center">Monto Cuota</th>
            <th align="center">Monto Cobrado</th>
            <th align="center">Monto Quita</th>
            <th align="center">Saldo Cuota</th>
            <th align="center">Monto Facturado</th>
            <th align="center">Pendiente de Facturacion</th>
            <th align="center">Monto Abonar</th>

        </tr>
      </thead>
      <tbody>
<?php
$monto_cuota_acum = 0;
$cobra_cuota_acum = 0;
$quita_cuota_acum = 0;
$saldo_cuota_acum = 0;
$facturado_cuota_acum = 0;
$facturado_cuota_saldo_acum = 0;
$monto_abonar_acum = 0;


while (!$rs->EOF) {

    $monto_cuota = $rs->fields['monto_cuota'];
    $cobra_cuota = $rs->fields['cobra_cuota'];
    $quita_cuota = $rs->fields['quita_cuota'];
    $saldo_cuota = $rs->fields['saldo_cuota'];
    $facturado_cuota = $rs->fields['facturado_cuota'];
    $facturado_cuota_saldo = $rs->fields['facturado_cuota_saldo'];
    $monto_abonar = $rs->fields['monto_abonar'];

    $monto_cuota_acum += $monto_cuota;
    $cobra_cuota_acum += $cobra_cuota;
    $quita_cuota_acum += $quita_cuota;
    $saldo_cuota_acum += $saldo_cuota;
    $facturado_cuota_acum += $facturado_cuota;
    $facturado_cuota_saldo_acum += $facturado_cuota_saldo;
    $monto_abonar_acum += $monto_abonar;

    $iddetalle = $rs->fields['iddetalle'];



    $fechas_txt .= '- '.antixss($rs->fields['operacion_clase']).' ('.antixss($rs->fields['sucursal_clie']).') '.mesespanol(date("m", strtotime($rs->fields['vencimiento']))).'/'.date("Y", strtotime($rs->fields['vencimiento'])).", ".$saltolinea;

    ?>
        <tr>

            <td align="center">        
                <div class="btn-group">
                    <a href="facturar_recurrente_car_del.php?id=<?php echo intval($rs->fields['idcarritofactutmp']); ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash"></span></a>
                </div>
            </td>
            <td align="right" <?php if (strtotime($rs->fields['vencimiento']) > strtotime(date("Y-m-d"))) { ?>style="color:#090;font-weight:bold;"<?php } ?>><?php echo date("d/m/Y", strtotime($rs->fields['vencimiento']));  ?></td>
            <td align="right"><?php echo antixss($rs->fields['operacion_clase']);  ?><?php if (trim($rs->fields['sucursal_clie']) != '') {
                echo "<br />".antixss($rs->fields['sucursal_clie']);
            } ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['monto_cuota']);  ?></td>

            <td align="right"><?php echo formatomoneda($rs->fields['cobra_cuota']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['quita_cuota']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['saldo_cuota']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['facturado_cuota']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['facturado_cuota_saldo']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['monto_abonar']);  ?></td>

        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();

$fechas_txt = $texto_defecto_inicio.substr(trim($fechas_txt), 0, -1)."";


?>
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
          <td align="right"><?php echo formatomoneda($monto_abonar_acum); ?></td>
          </tr>
      </tbody>
    </table>
</div><input name="fechas_txt" id="fechas_txt" type="hidden" value="<?php echo $fechas_txt; ?>" />
