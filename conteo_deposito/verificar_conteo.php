<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");
require_once("../insumos/preferencias_insumos_listas.php");



//Recibir valores por POST
$editar = antisqlinyeccion(intval($_POST['editar']), "int");


if ($editar > 0) {
    $respuesta = "";
    $idalm = intval($_POST['idalm']);
    $unicose = intval($_POST['unicose']);
    $fila = intval($_POST['fila']);
    $columna = intval($_POST['columna']);
    $idpasillo = intval($_POST['idpasillo']);
    $tipo_almacenamiento = intval($_POST['tipo_almacenamiento']);
    $idconteo = intval($_POST['idconteo']);


    $parametros_array = [
        'idalm' => $idalm,
        'fila' => $fila,
        'columna' => $columna,
        'idpasillo' => $idpasillo,
        'tipo_almacenamiento' => $tipo_almacenamiento,
        'idconteo' => $idconteo,
    ];
    // var_dump($parametros_array);exit;

    if ($tipo_almacenamiento == 1) {
        $consulta = "SELECT 
                        unicose 
                    FROM 
                        conteo_detalles 
                        INNER JOIN conteo ON conteo.idconteo = conteo_detalles.idconteo 
                    WHERE 
                        conteo_detalles.idconteo = $idconteo
                        and conteo_detalles.fila = $fila 
                        and conteo_detalles.columna = $columna 
                        and conteo.estado = 1
                        and idalm = $idalm
        ";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $unicose_encontrado = intval($rs->fields['unicose_encontrado']);
        if ($unicose_encontrado > 0 && $unicose_encontrado != $unicose) {
            $respuesta = [
                "success" => true,
                "error" => "- Un artículo con la misma ubicación ya se encuentra registrado en este conteo en el almacenamiento seleccionado.<br>"
            ];
        } else {
            $respuesta = [
                "success" => false
            ];
        }
    }
    echo json_encode($respuesta);
}
