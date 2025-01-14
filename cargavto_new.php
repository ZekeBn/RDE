 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "31";
require_once("includes/rsusuario.php");


$proveedorid = intval($_POST['idproveedor']);
$tipocompra = intval($_POST['tipocompra']);
$fechacompra = date("Y-m-d", strtotime($_POST['fechacompra']));
if (trim($_POST['fechacompra']) == '') {
    $fechacompra = date("Y-m-d");
}
if ($tipocompra == 2) {
    $buscar = "Select diasvence from proveedores where idproveedor=$proveedorid and estado <> 6";
    $rsfv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $dias = intval($rsfv->fields['diasvence']);
    if ($dias > 0) {
        $fecha = date("Y-m-d");
        if ($fechacompra != '') {
            $fecha = date("Y-m-d", strtotime($fechacompra));
        }
        $fec_vencimi = date("Y-m-d", strtotime("$fecha + $dias days"));

    } else {

        $fec_vencimi = $fechacompra;

    }
} else {
    //contado
    $fec_vencimi = $fechacompra;

}

$arr = [
    'vencimiento' => $fec_vencimi
];

// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;


?>
