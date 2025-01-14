<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");
$idinsumo_depo = intval($_POST['idinsumo_depo']);
$iddeposito = intval($_POST['iddeposito']);
if ($iddeposito == 0) {
    $iddeposito = $_GET['id'];
}
$whereadd2 = "";
if ($idinsumo_depo != 0) {
    $whereadd2 = "and gest_depositos_stock.idproducto = $idinsumo_depo";
}
$consulta = "
SELECT DISTINCT(gest_depositos_stock.idproducto),gest_depositos_stock.iddeposito, insumos_lista.descripcion 
FROM gest_depositos_stock
INNER JOIN insumos_lista on insumos_lista.idinsumo = gest_depositos_stock.idproducto
WHERE 
gest_depositos_stock.iddeposito = $iddeposito
and gest_depositos_stock.disponible > 0
and gest_depositos_stock.descripcion  not like \"%AJUSTE%\"
and gest_depositos_stock.descripcion  not like \"%DESCUENTO%\"
$whereadd2
";
$rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
    <tr>
    <th>Accion</th>
    <th>Idinsumo</th>
    <th>Articulo</th>
    </tr>
    </thead>
    <tbody>
        <?php
        $i = 1;
while (!$rs2->EOF) { ?>
            <tr>
                <td>
        <?php
$idinsumo = $rs2->fields['idproducto'];
    $mostrarbtn = "N";

    $mostrarbtn = "S";
    $link = "conteo_por_producto_detalle.php?id=".$iddeposito."&idinsumo=".$idinsumo;
    $txtbtn = "Abrir";
    $iconbtn = "plus";
    $tipoboton = "success";


    if ($mostrarbtn == 'S') {
        ?>
        <div class="btn-group">
        <a href="<?php echo $link; ?>" class="btn btn-sm btn-<?php echo $tipoboton; ?>" title="<?php echo $txtbtn; ?>" data-toggle="tooltip" data-placement="right"  data-original-title="<?php echo $txtbtn; ?>"><span class="fa fa-<?php echo $iconbtn; ?>"></span> <?php echo $txtbtn; ?></a>
        </div>
        <?php } ?>
        </td>
            <td align="center"><?php echo $rs2->fields['idproducto']; ?></td>
            <td align="center"><?php echo $rs2->fields['descripcion']; ?></td>
        </tr>
        <?php $i++;
    $rs2->MoveNext();
} ?>
    </tbody>
    </table>
</div>
<br /><br />