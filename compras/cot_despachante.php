<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "183";
$dirsup = 'S';
require_once("../includes/rsusuario.php");
require_once("../compras/preferencias_compras.php");


if (intval($idcompra) == 0) {
    $idcompra = intval($_POST['idcompra']);
}



$consulta = "SELECT cotizacion from despacho where idcompra = $idcompra";

$rscotiza = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cotizacion = floatval($rscotiza -> fields['cotizacion']);



if ($cotizacion > 0) {

    $res = [
        "success" => true,
        "cotizacion" => $cotizacion
    ];
} else {
    $res = [
        "success" => false,
        "error" => "<div id='modal_cotizacion' >Error no existe ninguna compra relacionada </div>",
    ];
}


if (intval($no_mostrar_json) == 0) {
    echo json_encode($res);
}

?>


