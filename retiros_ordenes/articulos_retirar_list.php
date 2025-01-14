<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "613";

$dirsup = "S";
require_once("../includes/rsusuario.php");



if ($idorden_retiro == 0) {
    $idorden_retiro = intval($_GET['idorden_retiro']);
}
if ($idorden_retiro == 0) {
    $idorden_retiro = intval($_POST['idorden_retiro']);
}
$editar_deposito = $_POST['editar_deposito'];
$mostrar_fecha = intval($_POST['mostrar_fecha']);
if ($editar_deposito == 1) {

    $iddeposito = $_POST['iddeposito'];
    $iddevolucion_det = $_POST['iddevolucion_det'];
    $idempresa = $_POST['idempresa'];

    $consulta = "UPDATE 
                devolucion_det
               set
                iddeposito = $iddeposito
               WHERE 
                iddevolucion_det = $iddevolucion_det
    ";

    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


}
/////////////////////////////////////////////////////////////////////////////////////////////////
$consulta = "
SELECT devolucion_det.*,devolucion.registrado_el,retiros_ordenes.modificado_el,retiros_ordenes.iddeposito as deposito_orden,
(select medidas.nombre from medidas where medidas.id_medida = devolucion_det.idmedida) as medida,
(select insumos_lista.descripcion from insumos_lista where insumos_lista.idproducto = devolucion_det.idproducto) as insumo,
(select gest_depositos.descripcion from gest_depositos WHERE gest_depositos.iddeposito = devolucion_det.iddeposito) as deposito
FROM devolucion_det
INNER JOIN devolucion on devolucion.iddevolucion = devolucion_det.iddevolucion
INNER JOIN retiros_ordenes on retiros_ordenes.iddevolucion = devolucion.iddevolucion
WHERE 
  devolucion.estado=3
  and retiros_ordenes.idorden_retiro = $idorden_retiro
";

$rs_devolucion = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$deposito_orden = intval($rs_devolucion->fields['deposito_orden']);
$nota_credito = intval($rs_devolucion->fields['idnotacred']);
////////////////////////////////////////////////////////////////////////////////////////////////////



?>
<?php if ($mostrar_fecha == 1) { ?>
    <div class="table-responsive">
        <table width="100%" class="table table-bordered jambo_table bulk_action">
            <tr>
                <th align="center">Fecha de ultima Modificacion</th>
                <td align="center"><?php echo antixss($rs_devolucion->fields['modificado_el']); ?></td>
            </tr>
            <?php // if($deposito_orden > 0 ){?>
                <tr>
                    <th align="center">ID Nota de credito Asociada</th>
                    <td align="center"><?php echo $nota_credito; ?></td>
                </tr>
            <?php  // }?>
        </table>
    </div>
<?php } ?>
<div class="table-responsive">
    <h2>Articulos a Retirar</h2>
    <div class="alert alert-info" role="alert">
        El dep&oacute;sito asignado a cada art&iacute;culo corresponde al dep&oacute;sito al que ingresar&aacute; en el sistema.
    </div>
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">idproducto</th>
			<th align="center">Articulo</th>
			<th align="center">Cantidad</th>
			<th align="center">Medida</th>
			<th align="center">Lote</th>
			<th align="center">Vencimiento</th>
			<th align="center">Registrado el</th>
			<th align="center">Deposito</th>
            <?php if ($boton_editar_deposito == 1) { ?>
                <th></th>
            <?php } ?>
		</tr>
	  </thead>
	  <tbody>
      <?php while (!$rs_devolucion->EOF) { ?>
          <tr>
            
            <td align="center"><?php echo intval($rs_devolucion->fields['idproducto']); ?></td>
            <td align="center"><?php echo antixss($rs_devolucion->fields['insumo']); ?></td>
            <td align="center"><?php echo formatomoneda($rs_devolucion->fields['cantidad']); ?></td>
            <td align="center"><?php echo antixss($rs_devolucion->fields['medida']); ?></td>
            <td align="center"><?php echo antixss($rs_devolucion->fields['lote']); ?></td>
            <td align="center"><?php echo formatofecha($rs_devolucion->fields['vencimiento'], "Y-m-d"); ?></td>
            <td align="center"><?php echo antixss($rs_devolucion->fields['registrado_el']); ?></td>
            <td align="center"><?php echo antixss($rs_devolucion->fields['deposito']); ?></td>
            <?php if ($boton_editar_deposito == 1) { ?>
                <td align="center">
                    <a href="javascript:void(0);" onclick="editar_deposito_compra(event,<?php echo $rs_devolucion->fields['iddevolucion_det'] ?>,<?php echo $idorden_retiro ?>);" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Editar"><span class="fa fa-edit"></span></a>
                </td>
            <?php } ?>
          </tr>
      <?php $rs_devolucion->MoveNext();
      } //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>