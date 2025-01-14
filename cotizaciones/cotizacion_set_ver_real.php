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
$hoy = new DateTime();
$ayer = $hoy->modify('-1 day');

$numeroMes = $ayer->format('n');
$dia_ayer = $ayer->format('d');

$anho = $ayer->format('Y');

// Obtener el nombre del mes en español
$nombreMes = $meses[$numeroMes];
// Establecer las opciones de cURL
$url = "https://www.set.gov.py/web/portal-institucional/cotizaciones";

$dom = file_get_html($url);

$dia = null;
$fecha = null;
$cotizacion = null;
$fecha_ultimo_dia = null;
$cotizacion_ultimo_dia = null;

foreach ($dom->find('h4') as $col) {

    if (strpos(($col->plaintext), "del mes de $nombreMes $anho")) {

        $ultimo_dia = end($col->parentNode()->parentNode()->parentNode()->children()[1]->find("table tr"))->find('td', 0)->plaintext;
        $cotizacion_ultimo_dia = end($col->parentNode()->parentNode()->parentNode()->children()[1]->find("table tr"))->find('td', 3)->plaintext;
        $fecha_ultimo_dia = $anho . "-" . $numeroMes . "-" . $ultimo_dia;
        $fecha_ultimo_dia = date("Y-m-d", strtotime($fecha_ultimo_dia));

        foreach (($col->parentNode()->parentNode()->parentNode()->children()[1]->find("table tr")) as $td) {

            if ($td->find('td', 0)->plaintext == $dia_ayer) {

                $dia = $td->find('td', 0)->plaintext;
                $fecha = $anho . "-" . $numeroMes . "-" . $dia_ayer;
                $fecha = date("Y-m-d", strtotime($fecha));
                $cotizacion = $td->find('td', 3)->plaintext;
                break;
            }
        }
    }
}

$cadenaConComa = "$cotizacion";
$cadenaConPunto = str_replace('.', '', $cadenaConComa);
$cadenaConPunto = str_replace(',', '.', $cadenaConPunto);
$valorFlotante = floatval($cadenaConPunto);
$cotizacion = $valorFlotante;
$estado = antisqlinyeccion(1, "int");
$consulta = "SELECT tipo_moneda.idtipo FROM tipo_moneda WHERE UPPER(descripcion) like \"%DOLAR%\" ";
$respuesta = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tipo_moneda = $respuesta->fields['idtipo'];
$errores = "";
$texto = "";

if (!is_numeric($cotizacion) || $cotizacion == 0) {

    $valido = "N";
    $alerta = "S";
    $errores .= "Verifique que www.set.gov.py/web/portal-institucional/cotizaciones posea cargada la cotizacion referente a  $dia_ayer/$numeroMes/$anho o Comuníquese con el soporte técnico la SET puede estar fuera de servicio o alterado estructuralmente<br>";
    $texto = "La ultima cotizacion cargada es $fecha_ultimo_dia : $cotizacion_ultimo_dia ";
}
if ($errores == "") {

    $respuesta = [
        "cotizacion" => $cotizacion,
        "idmoneda" => $idmoneda,
        "moneda" => "DOLAR",
        "fecha" => $fecha,
        "success" => true
    ];
} else {
    $respuesta = [

        "success" => false,
        "error" => $errores,
        "texto" => $texto
    ];
}
echo json_encode($respuesta);
