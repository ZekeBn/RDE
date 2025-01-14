 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

/*
si el grupo tiene 1 solo producto cargado
debe hacer un insert directo y no figurar en la lista
si la cantidad es 6 y solo hay 1 producto cargado hace 6 insert
*/



$idlistacombo = intval($_POST['idlista']);

// busca si existe la lista
$consulta = "
select * from combos_listas where idlistacombo = $idlistacombo and idempresa = $idempresa and estado = 1
";
$rscombo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idlistacombo = $rscombo->fields['idlistacombo'];
$idprodprinc = $rscombo->fields['idproducto'];



// si existe
if ($idlistacombo > 0) {

    $consulta = "
    delete from tmp_combos_listas
    where
    idventatmp is null
    and idempresa = $idempresa
    and idsucursal = $idsucursal
    and idusuario = $idusu
    and idlistacombo = $idlistacombo
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    echo "OK";
}
?>
