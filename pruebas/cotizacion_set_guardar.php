<?php

include('./simple_html_dom.php');
require_once("../includes/funciones.php");
require_once("../includes/conexion.php");

$cotizacion = null;
$errores = "";
$meses = [
    1 => "Enero",
    2 => "Febrero",
    3 => "Marzo",
    4 => "Abril",
    5 => "Mayo",
    6 => "Junio",
    7 => "Julio",
    8 => "Agosto",
    9 => "Septiembre",
    10 => "Octubre",
    11 => "Noviembre",
    12 => "Diciembre"
];

// Obtener el número del mes actual
$numeroMes = date('n');
$anho = date('Y');

// Obtener el nombre del mes en español
$nombreMes = $meses[$numeroMes];
// Establecer las opciones de cURL
$url = "https://www.set.gov.py/web/portal-institucional/cotizaciones";

$dom = file_get_html($url);
$dia = null;
$fecha = null;
$cotizacion = null;
foreach ($dom->find('h4') as $col) {
    if (strpos(($col->plaintext), "del mes de $nombreMes $anho")) {
        $dia = end($col->parentNode()->parentNode()->parentNode()->children()[1]->find("table tr"))->find('td', 0)->plaintext;
        $fecha = $anho."-".$numeroMes."-".$dia;
        $fecha = date("Y-m-d", strtotime($fecha));
        $cotizacion = end($col->parentNode()->parentNode()->parentNode()->children()[1]->find("table tr"))->find('td', 1)->plaintext;
        break;
    }
}
$cadenaConComa = "$cotizacion";
$cadenaConPunto = str_replace('.', '', $cadenaConComa);
$cadenaConPunto = str_replace(',', '.', $cadenaConPunto);
$valorFlotante = floatval($cadenaConPunto);

$cotizacion = $valorFlotante;
$estado = antisqlinyeccion(1, "int");
$consulta = "SELECT tipo_moneda.idtipo FROM tipo_moneda WHERE UPPER(descripcion) like \"%DOLAR%\" ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tipo_moneda = $rs->fields['idtipo'];
$registrado_el = antisqlinyeccion($ahora, "text");
$registrado_por = $idusu;



// validaciones basicas
$valido = "S";
$alerta = "N";
$errores = "";

if (!is_numeric($cotizacion)) {
    $valido = "N";
    $alerta = "S";
    $errores .= "Comuníquese con el soporte técnico, ya que set.gov.py puede estar fuera de servicio o alterado estructuralmente";
}
if (intval($cotizacion) == 0) {
    $valido = "N";
    $alerta = "S";
    // $errores.=" - El campo cotizacion no puede ser cero o nulo.<br />";
}
if (intval($tipo_moneda) == 0) {
    $valido = "N";
    $alerta = "S";
    // $errores.=" - El campo tipo_moneda no puede ser cero o nulo.<br />";
}

if (intval($fecha) == "") {
    $valido = "N";
    $alerta = "S";
    // $errores.=" - El campo fecha no puede ser nulo.<br />";
} else {
    if (intval($tipo_moneda) != 0) {
        $consulta = "SELECT 
            count(*) as cotizaciones_datos
        FROM 
            cotizaciones
        WHERE 
            cotizaciones.estado = 1 
            AND DATE(cotizaciones.fecha) = '$fecha'
            AND cotizaciones.tipo_moneda = $tipo_moneda
            ORDER BY cotizaciones.fecha DESC
            LIMIT 1";

        $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $num_cot = intval($rsmax->fields['cotizaciones_datos']);
        if ($num_cot > 0) {
            $valido = "N";
            $fecha_format = date("d/m/Y", strtotime($fecha.""));
            // $errores.=" - Ya existe una cotizacion con la fecha $fecha_format.<br />";
        }
    }

}


// si todo es correcto inserta
if ($valido == "S") {


    $consulta = "
    insert into cotizaciones
    (cotizacion, estado, fecha, tipo_moneda, registrado_por, registrado_el)
    values
    ($cotizacion, $estado, '$fecha', $tipo_moneda, $registrado_por, $registrado_el)
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    exit;

} else {
    if ($alerta == "S") {

        $errores = "Comuníquese con el soporte técnico, ya que set.gov.py puede estar fuera de servicio o alterado estructuralmente";

        echo $errores;
    }
}
