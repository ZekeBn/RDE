<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
$rs = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $consulta = "
        select *
        from categorias 
        where 
         estado = 1 
         order by nombre asc
        ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $data = [];
    echo "[";
    foreach ($rs as $value) {
        array_push($data, json_encode($rs->fields));
    }

    echo implode(',', $data);
    echo "]";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["idusu"])) {
    $myfile = fopen("log.txt", "a") or die("Unable to open file!");
    fwrite($myfile, $_POST["idusu"]);
    fclose($myfile);
}


// nombre del modulo al que pertenece este archivo
