 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "74";
require_once("includes/rsusuario.php");

$idmesa = intval($_GET['idmesa']);

$consulta = "
select * from mesas
inner join salon on mesas.idsalon = salon.idsalon
where
mesas.idmesa = $idmesa
and estadoex = 1
order by numero_mesa desc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$idmesa = intval($rs->fields['idmesa']);
$idsalon = intval($rs->fields['idsalon']);
$id = $idmesa;

if ($idmesa == 0) {
    echo "Mesa inexistente!";
    exit;
}

$consulta = "
select * 
from mesas
where
estado_mesa > 1 
and estadoex = 1 
and idmesa = $idmesa
limit 1
";
$rsmesval = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idmesaval = intval($rsmesval->fields['idmesa']);
if ($idmesaval > 0) {
    $errores .= "- No se puede borrar la mesa por que esta abierta.<br />";
    echo $errores;
    exit;
    //$valido="N";
}

$numero_mesa_prox = intval($rs->fields['numero_mesa']) + 1;
/*
$consulta="
delete from mesas
where
idmesa = $idmesa
";
$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
*/
$consulta = "
update mesas
set
estadoex = 6,
estado_mesa = 1,
anulado_por=$idusu,
anulado_el='$ahora'
where 
idmesa = $idmesa
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


header("location: salones_mesa.php?salon=".$idsalon);
exit;

?>
