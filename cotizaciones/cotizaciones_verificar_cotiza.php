<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "183";
$dirsup = 'S';
require_once("../includes/rsusuario.php");

$idmoneda = intval($_POST['idmoneda']);


$res = null;


$consulta = "SELECT cotiza from tipo_moneda where idtipo = $idmoneda";

$rscotiza = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cotiza_moneda = intval($rscotiza -> fields['cotiza']);

$res = $res = [
    "success" => false,
    "error" => $rscotiza,
];
;
if ($cotiza_moneda == 1) {
    $res = [
        "success" => true,
        "cotiza" => true,
        "error" => null
    ];
} else {
    $res = [
        "success" => true,
        "cotiza" => false,
        "error" => null
    ];
}
echo json_encode($res);

?>


