<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "31";


require_once("includes/rsusuario.php");
$proveedorid = intval($_POST['pp']);
$tipocompra = intval($_POST['tpc']);
$fechacompra = $_POST['fcomp'];
if ($tipocompra == 2) {
    $buscar = "Select diasvence from proveedores where idproveedor=$proveedorid";
    $rsfv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $dias = intval($rsfv->fields['diasvence']);
    if ($dias > 0) {
        $fecha = date("Y-m-d");
        if ($fechacompra != '') {
            $fecha = date("Y-m-d", strtotime($fechacompra));
        }
        $fec_vencimi = date("Y-m-d", strtotime("$fecha + $dias days"));
        $fec_vencimi;

    } else {

        $fec_vencimi = '';

    }
} else {
    //contado
    $fec_vencimi = '';

}
?>
<input type="date" name="factura_venc" id="factura_venc" value="<?php echo $fec_vencimi?>" />