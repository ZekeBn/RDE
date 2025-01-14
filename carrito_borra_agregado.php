 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

$idproducto = antisqlinyeccion($_POST['idprod'], "int");
$idingrediente = antisqlinyeccion($_POST['iding'], "int");
$idvtag = antisqlinyeccion($_POST['idvtag'], "int");
$idvt = antisqlinyeccion($_POST['idvt'], "int");
$usuario = $idusu;
$idsucursal = $idsucursal;
$idempresa = $idempresa;

//print_r($_POST);

$consulta = "
delete from tmp_ventares_agregado
where
idtmpventaresagregado = $idvtag
and idventatmp = $idvt
and idproducto = $idproducto
and idingrediente = $idingrediente
and idventatmp in (
                    select idventatmp 
                    from tmp_ventares 
                    where 
                    idventatmp = $idvt
                    and idempresa = $idempresa 
                    and idsucursal = $idsucursal
                 )
";
//echo $consulta;
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
delete from tmp_ventares
where
idventatmp_princ_delagregado = $idvt
and idtmpventaresagregado = $idvtag
";
//echo $consulta;
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

header("location: carrito_grilla_agregado.php?idvt=$idvt");
exit;

?>
