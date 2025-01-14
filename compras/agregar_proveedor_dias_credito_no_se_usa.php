<?php

//  sin uso
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

$valido = "S";
$error = "";
$agregar_dias_creditos = $_POST["agregar_dias_creditos"];
if ($agregar_dias_creditos == 1) {

    $nombre_dias = $_POST["nombre_dias"];
    $patron = '/^(\d+,)*\d+$/';
    $valido = "S";
    $errores = "";


    if ($valido == 'N') {
        $respuesta = [
            "success" => false,
            "errores" => $errores
        ];

    } else {
        $iddiascred = select_max_id_suma_uno("proveedores_dias_credito", "iddiascred")["iddiascred"];
        $consulta = "Insert into proveedores_dias_credito 
        (iddiascred,nombre)
        values
        ($iddiascred,'$nombre_dias')";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $array = explode(",", $nombre_dias);
        $contador = 1;
        foreach ($array as $dias) {
            $iddiascred_det = select_max_id_suma_uno("proveedores_dias_credito_det", "iddiascred_det")["iddiascred_det"];
            $consulta = "Insert into proveedores_dias_credito_det
            (iddiascred_det,iddiascred,nro_cuotas,dias)
            values
            ($iddiascred_det,$iddiascred,$contador,$dias)";

            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $contador += 1;
        }


        $respuesta = [
            "success" => true,
            "iddiascred" => $iddiascred
        ];
    }
    echo json_encode($respuesta);

}
