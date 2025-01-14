 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
require_once("includes/rsusuario.php");

if (intval($_POST['idproducto']) > 0) {
    $idproducto = intval($_POST['idproducto']);
}
if (intval($_POST['idsucursal_imp']) > 0) {
    $idsucursal_imp = intval($_POST['idsucursal_imp']);
}
if (intval($idproducto) == 0) {
    echo "No se recibio el ID producto.";
    exit;
}
if (intval($idsucursal_imp) == 0) {
    echo "No se recibio el ID sucursal.";
    exit;
}
$consulta = "
select iddeposito, descripcion,
(SELECT iddeposito FROM producto_deposito where idproducto = $idproducto and idsucursal = $idsucursal_imp and iddeposito = gest_depositos.iddeposito  ) as asignado
from gest_depositos 
where 
estado = 1
and tiposala <> 3
order by descripcion asc
";
$rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$asig_hay = 0;
while (!$rsimp->EOF) {
    $iddeposito = $rsimp->fields['iddeposito'];
    $asignado = $rsimp->fields['asignado'];
    $checked = "";
    if ($asignado > 0) {
        $checked = "checked";
    }
    if ($asignado > 0) {
        $asig_hay++;
        $btn = "success";
        $icon = "check-circle-o";
    } else {
        $btn = "default";
        $icon = "times-circle-o";
    }


    ?>&nbsp;&nbsp;
<a href="javascript:void(0);" class="btn btn-sm btn-<?php echo $btn; ?>" onMouseUp="asigna_deposito('<?php echo $iddeposito; ?>','<?php echo $idsucursal_imp; ?>');"><span class="fa fa-<?php echo $icon; ?>"></span> <?php echo antixss($rsimp->fields['descripcion']); ?></a>
<br />
<?php $rsimp->MoveNext();
}
if ($asig_hay > 0) {
    $btn = "default";
    $icon = "times-circle-o";
} else {
    $btn = "primary";
    $icon = "check-circle-o";

}


?>&nbsp;&nbsp;
<a href="javascript:void(0);" class="btn btn-sm btn-<?php echo $btn; ?>" onMouseUp="borra_deposito('<?php echo $idsucursal_imp; ?>','SACAR');"><span class="fa fa-<?php echo $icon; ?>"></span> NINGUNO</a>






