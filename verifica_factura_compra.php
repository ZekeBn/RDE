<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "31";
require_once("includes/rsusuario.php");

// recibe parametros
$suc = intval($_POST['suc']);
$pex = intval($_POST['pex']);
$fa = intval($_POST['fa']);
$prov = intval($_POST['prov']);

$digitosfac = 7;
if (strlen(trim($_POST['fa'])) < 7 or strlen(trim($_POST['fa'])) > 9) {
    $digitosfac = 7;
} else {
    $digitosfac = strlen(trim($_POST['fa']));
}

if (intval($suc) > 0 && intval($pex) > 0 && intval($fa) > 0 && intval($prov) > 0) {

    // convertir a factura completa
    $facompra = antisqlinyeccion(agregacero($suc, 3).agregacero($pex, 3).agregacero($fa, $digitosfac), "text");

    // buscar en la base
    $consulta = "Select * from compras where facturacompra=$facompra and idproveedor=$prov and idempresa = $idempresa and estado=1";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    if (trim($rs->fields['facturacompra']) != '') {
        echo "error";
        exit;
    } else {
        echo "";
        exit;
    }

}
