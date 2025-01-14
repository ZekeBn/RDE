<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");

if (intval($idmoneda_orden) == 0) {
    $idmoneda = intval($_POST['idmoneda']);
} else {
    $idmoneda = $idmoneda_orden;
}
$ahorad = date("Y-m-d", strtotime(date("Y-m-d") . " -1 day"));
$consulta = "SELECT 
                cotizaciones.cotizacion,cotizaciones.idcot,cotizaciones.fecha
            FROM 
                cotizaciones
            WHERE 
                cotizaciones.estado = 1 
                AND DATE(cotizaciones.fecha) = '$ahorad'
                AND cotizaciones.tipo_moneda = $idmoneda
                ORDER BY cotizaciones.fecha DESC
                LIMIT 1
		";
$rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$fecha = $rsmax->fields['fecha'];
$idcot = intval($rsmax->fields['idcot']);
$cotizacion = $rsmax->fields['cotizacion'];
if ($idcot > 0) {

    $res = [
        "success" => true,
        "fecha" => $fecha,
        "idcot" => $idcot,
        "cotizacion" => $cotizacion
    ];
} else {
    $formateada = date("d/m/Y", strtotime($ahorad));
    $res = [
        "success" => false,
        "error" => "No hay cotizaciones para el d&iacute;a $formateada,favor cargue la cotizacion del d&iacute;a",
    ];
}
if (intval($no_mostrar_json) == 0) {

    echo json_encode($res);
}
?>


