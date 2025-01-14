 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "74";
require_once("includes/rsusuario.php");

$idsalon = intval($_GET['salon']);

$consulta = "
select * 
from salon
where
salon.idsalon = $idsalon
and estado_salon = 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idsucursal = intval($rs->fields['idsucursal']);

$idsalon = intval($rs->fields['idsalon']);
$id = $idsalon;

if ($idsalon == 0) {
    echo "Salon inexistente!";
    exit;
}


$consulta = "
select * from mesas
inner join salon on mesas.idsalon = salon.idsalon
where
mesas.idsalon = $idsalon
and salon.idsucursal = $idsucursal
and mesas.estadoex <> 6
order by numero_mesa desc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$numero_mesa_prox = intval($rs->fields['numero_mesa']) + 1;

$consulta = "
insert into mesas 
(idsalon,numero_mesa,idempresa,idsucursal)
values
($idsalon,$numero_mesa_prox,$idempresa,$idsucursal)
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



header("location: salones_mesa.php?salon=".$idsalon);
exit;

?>
