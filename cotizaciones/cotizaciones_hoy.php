<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "183";
$dirsup = 'S';
require_once("../includes/rsusuario.php");
require_once("preferencias_cotizacion.php");


if (intval($idmoneda_orden) == 0) {
    $idmoneda = intval($_POST['idmoneda']);
} else {
    $idmoneda = $idmoneda_orden;
}
//preferencias de cotizacion

$preferencias_cotizacion = "SELECT * FROM preferencias_cotizacion where idpref = 1";
$rs_preferencias_cotizacion = $conexion->Execute($preferencias_cotizacion) or die(errorpg($conexion, $preferencias_cotizacion));

$cotiza_dia_anterior = $rs_preferencias_cotizacion->fields["cotiza_dia_anterior"];
$editar_fecha = $rs_preferencias_cotizacion->fields["editar_fecha"];
/// fin de preferencias

$res = null;


$consulta = "SELECT cotiza from tipo_moneda where idtipo = $idmoneda";

$rscotiza = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cotiza_moneda = intval($rscotiza -> fields['cotiza']);
$fecha = "";

if ($cotiza_moneda == 1) {

    if (intval($idcot) == 0) {
        $idcot = $_POST['idcot'];
    }

    $fecha_compra = $_POST['fecha_compra'];

    if ($cotiza_dia_anterior == "S") {
        $fecha_compra = $fecha_compra. " - 1 day";
    }
    $whereadd = "";
    if ($idcot != 0) {
        $whereadd = " AND idcot = $idcot";
    } else {
        if ($fecha_compra != "") {
            if ($cotiza_dia_anterior == 'S') {

                $ahorad = date("Y-m-d", strtotime($fecha_compra));
            }
            $fecha = $ahorad;
        } else {

            $ahorad = date("Y-m-d", strtotime(date("Y-m-d")));

            if ($cotiza_dia_anterior == 'S') {

                $ahorad = date("Y-m-d", strtotime(date("Y-m-d") . " -1 day"));
            }
            $fecha = $ahora;
        }
        $whereadd = " AND DATE(cotizaciones.fecha) = DATE('$fecha') ";
    }


    $consulta = "SELECT 
                    cotizaciones.cotizacion,
                    cotizaciones.idcot,
                    cotizaciones.fecha
                FROM 
                    cotizaciones
                WHERE 
                    cotizaciones.estado = 1 
                    $whereadd
                    AND cotizaciones.tipo_moneda = $idmoneda
                ORDER BY 
                    cotizaciones.fecha DESC
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
            "fecha_format" => date("d/m/Y", strtotime($fecha)),
            "idcot" => $idcot,
            "cotiza" => true,
            "cotizacion" => $cotizacion
        ];
    } else {
        $formateada = date("d/m/Y", strtotime($ahorad));
        $res = [
            "success" => false,
            "cotiza" => false,
            "error" => "<span style='font-size: larger;'>No hay cotizaciones para el día $formateada, favor cargue la cotización del día. Favor cambielo <a target='_blank' href='..\cotizaciones\cotizaciones.php'>[ Aquí ]</a></span>",
        ];
    }

} else {
    $res = [
        "success" => true,
        "cotiza" => false,
    ];

}
if (intval($no_mostrar_json) == 0) {
    echo json_encode($res);
}

?>


