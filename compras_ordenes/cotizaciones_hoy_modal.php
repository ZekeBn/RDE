<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "183";
$dirsup = 'S';
require_once("../includes/rsusuario.php");
require_once("../compras/preferencias_compras.php");

if (intval($idmoneda_orden) == 0) {
    $idmoneda = intval($_POST['idmoneda']);
} else {
    $idmoneda = $idmoneda_orden;
}
$fecha_factura = "";
if ($_POST['fecha_factura']) {
    $fecha_factura = $_POST['fecha_factura'];
}

//preferencias de cotizacion
$preferencias_cotizacion = "SELECT * FROM preferencias_cotizacion";
$rs_preferencias_cotizacion = $conexion->Execute($preferencias_cotizacion) or die(errorpg($conexion, $preferencias_cotizacion));

$cotiza_dia_anterior = $rs_preferencias_cotizacion->fields["cotiza_dia_anterior"];
$editar_fecha = $rs_preferencias_cotizacion->fields["editar_fecha"];
/// fin de preferencias

$res = null;

$consulta = "SELECT cotiza from tipo_moneda where idtipo = $idmoneda";

$rscotiza = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cotiza_moneda = intval($rscotiza -> fields['cotiza']);


if ($cotiza_moneda == 1) {

    $ahorad = date("Y-m-d", strtotime(date("Y-m-d")));

    if ($cotiza_dia_anterior == 'S') {

        $ahorad = date("Y-m-d", strtotime(date("Y-m-d") . " -1 day"));
    }
    if ($preferencias_cot_fecha_fact == "S" && $fecha_factura != "") {
        $ahorad = date("Y-m-d", strtotime($fecha_factura));
    }
    $whereadd1 = "";
    if ($_POST['idcot'] != "") {
        $idcot = $_POST['idcot'];
        $whereadd1 = "and idcot = $idcot";
    } else {
        $whereadd1 = "AND DATE(cotizaciones.fecha) = '$ahorad' ";
    }
    $consulta = "SELECT 
                    cotizaciones.cotizacion,cotizaciones.idcot,cotizaciones.fecha
                FROM 
                    cotizaciones
                WHERE 
                    cotizaciones.estado = 1 
                    AND cotizaciones.tipo_moneda = $idmoneda
                    $whereadd1
                    ORDER BY cotizaciones.fecha DESC
                    LIMIT 1";
    $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $fecha = $rsmax->fields['fecha'];
    $idcot = intval($rsmax->fields['idcot']);
    $cotizacion = $rsmax->fields['cotizacion'];

    if ($idcot > 0) {

        $res = [
            "success" => true,
            "fecha" => date("d/m/Y", strtotime($fecha)),
            "idcot" => $idcot,
            "cotiza" => true,
            "cotizacion" => $cotizacion
        ];
    } else {
        $formateada = date("d/m/Y", strtotime($ahorad));
        $res = [
            "success" => false,
            "cotiza" => false,
            "error" => "<div id='modal_cotizacion' >No hay cotizaciones para el d&iacute;a $formateada,favor cargue la cotizacion del d&iacute;a,. Favor cambielo <a  href='javascript:void(0)' onclick='cargar_cotizacion($idmoneda)'>[ Aqui ]</a>, o se utilizara la cotizacion del dia anterior</div>",
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
