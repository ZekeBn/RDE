 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

$id = intval($_POST['id']);
$consulta = "
update tmp_ventares_cab
set 
impreso = 'N'
where
impreso = 'S'
and idtmpventares_cab = $id
and idsucursal = $idsucursal
";
//echo $consulta;
//$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

$id = intval($_POST['id']);
$consulta = "
update tmp_ventares
set 
impreso_coc = 'N'
where
impreso_coc = 'S'
and idtmpventares_cab = $id
and idsucursal = $idsucursal
";
//echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

echo "Impresion Enviada!!";



?>
