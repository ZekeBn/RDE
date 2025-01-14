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
select *,
(SELECT idimpresora FROM producto_impresora where idproducto = $idproducto and idimpresora = impresoratk.idimpresoratk ) as asignado
from impresoratk 
where 
borrado = 'N'
and idsucursal = $idsucursal_imp
and tipo_impresora = 'COC'
order by descripcion asc
";
$rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$asig_hay = 0;
while (!$rsimp->EOF) {
    $idimpresoratk = $rsimp->fields['idimpresoratk'];
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
<a href="javascript:void(0);" class="btn btn-sm btn-<?php echo $btn; ?>" onMouseUp="asigna_impresora('<?php echo $idimpresoratk; ?>','<?php echo $idsucursal_imp; ?>');"><span class="fa fa-<?php echo $icon; ?>"></span> <?php echo antixss($rsimp->fields['descripcion']); ?></a>
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
<a href="javascript:void(0);" class="btn btn-sm btn-<?php echo $btn; ?>" onMouseUp="borra_impresoras('<?php echo $idsucursal_imp; ?>','SACAR');"><span class="fa fa-<?php echo $icon; ?>"></span> Ninguna</a>






