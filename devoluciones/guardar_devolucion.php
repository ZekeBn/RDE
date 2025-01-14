<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "613";

$dirsup = "S";
require_once("../includes/rsusuario.php");

$iddevolucion = intval($_POST['iddevolucion']);

$consulta = "
UPDATE 
    devolucion
set
    estado = 3
where
    iddevolucion = $iddevolucion
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$idorden_retiro = select_max_id_suma_uno("retiros_ordenes", "idorden_retiro")["idorden_retiro"];



$consulta = "
insert into retiros_ordenes 
(idorden_retiro, iddevolucion, estado, iddeposito)
values 
($idorden_retiro, $iddevolucion, 1, 0)
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


echo json_encode([
    "success" => true
]);
