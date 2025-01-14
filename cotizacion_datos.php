 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");

$idmoneda = intval($_POST['idmoneda']);

if ($idmoneda > 0) {
    $consulta = "
    SELECT *, tipo_moneda.borrable, tipo_moneda.descripcion, cotizaciones.cotizacion as cotizacion
    FROM cotizaciones
    inner join tipo_moneda on tipo_moneda.idtipo = cotizaciones.tipo_moneda
    where
    cotizaciones.estado = 1
    and tipo_moneda.estado = 1
    and cotizaciones.tipo_moneda = $idmoneda
    order by cotizaciones.fecha desc
    limit 1
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    echo floatval($rs->fields['cotizacion']);

} else {
    echo "0";
}

?>
