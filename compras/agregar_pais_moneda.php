<?php
/*--------------------------------------------
Insertando proveedor a la db
30/5/2023
---------------------------------------------*/
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_proveedor.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

$valido = "S";
$error = "";

$idpais = $_POST["idpais"];
$idmoneda = $_POST["idmoneda"];
$consulta = "";

$consulta = "
update paises
		set
            idmoneda = $idmoneda
		where
            idpais = $idpais
";


//echo $consulta;
$rsc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



$respuesta = [
    "success" => true
];


echo json_encode($respuesta);
