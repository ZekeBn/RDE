 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";

require_once("includes/rsusuario.php");

$id = intval($_POST['id']);

$consulta = "
select *
from tmp_ventares_cab
where
idsucursal = $idsucursal
and idempresa = $idempresa
and idtmpventares_cab = $id
and finalizado = 'S'
and impreso = 'N'
order by idtmpventares_cab asc
limit 1
";
//echo $consulta;
$rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id = intval($rscab->fields['idtmpventares_cab']);

// si no hay nada para imprimir recarga la pagina
if ($id == 0) {
    echo "registro inexistente";
    exit;
}

$consulta = "
update tmp_ventares_cab 
set impreso = 'S',
ultima_impresion = '$ahora'
where
idtmpventares_cab = $id
and impreso = 'N'
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
