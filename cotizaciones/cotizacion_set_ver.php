<?php
include('./simple_html_dom.php');
require_once("../includes/funciones.php");
require_once("../includes/conexion.php");
require_once("./preferencias_cotizacion.php");

function reemplazar_coma($valor)
{
    $cadenaConComa = "$valor";
    $cadenaConPunto = str_replace('.', '', $cadenaConComa);
    $cadenaConPunto = str_replace(',', '.', $cadenaConPunto);
    $valorFlotante = floatval($cadenaConPunto);
    return $valorFlotante;
}

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
// $fecha_string = $_POST['fecha'];
// $hoy =  new DateTime($fecha_string);
$hoy = new DateTime();
$ayer = new DateTime();
$ayer = $ayer->modify('-1 day');
// echo $fecha->format('d')." fecha fecha".$hoy->format('d');exit;
$numeroMes = "";
$dia_ayer = "";
$anho = "";

if ($cotiza_dia_anterior == "S") {
    $numeroMes = $ayer->format('n');
    $dia_ayer = $ayer->format('d');
    $anho = $ayer->format('Y');
} else {
    $numeroMes = $hoy->format('n');
    $dia_ayer = $hoy->format('d');
    $anho = $hoy->format('Y');
}

// Obtener el nombre del mes en español
$nombreMes = $meses[$numeroMes];
// Establecer las opciones de cURL
$url = "https://www.set.gov.py/web/portal-institucional/cotizaciones";

$dom = file_get_html($url);
$dia = null;
$fecha = null;
$cotizacion = null;
$cotizacion_compra = null;
$fecha_ultimo_dia = null;
$cotizacion_ultimo_dia = null;

foreach ($dom->find('h4') as $col) {

    if (strpos(($col->plaintext), "del mes de $nombreMes $anho")) {

        $ultimo_dia = end($col->parentNode()->parentNode()->parentNode()->children()[1]->find("table tr"))->find('td', 0)->plaintext;
        $cotizacion_ultimo_dia = end($col->parentNode()->parentNode()->parentNode()->children()[1]->find("table tr"))->find('td', 1)->plaintext;
        $cotizacion_ultimo_dia_venta = end($col->parentNode()->parentNode()->parentNode()->children()[1]->find("table tr"))->find('td', 2)->plaintext;
        $fecha_ultimo_dia = $anho."-".$numeroMes."-".$ultimo_dia;
        $fecha_ultimo_dia = date("Y-m-d", strtotime($fecha_ultimo_dia));

        foreach (($col->parentNode()->parentNode()->parentNode()->children()[1]->find("table tr")) as $td) {

            if ($td->find('td', 0)->plaintext == $dia_ayer) {

                $dia = $td->find('td', 0)->plaintext;
                $fecha = $anho."-".$numeroMes."-".$dia_ayer;
                $fecha = date("Y-m-d", strtotime($fecha));
                $cotizacion = $td->find('td', 2)->plaintext;//2 es la venta
                $cotizacion_compra = $td->find('td', 1)->plaintext;
                break;
            }
        }
    }
}

$cotizacion = reemplazar_coma($cotizacion);
$cotizacion_compra = reemplazar_coma($cotizacion_compra);
$estado = antisqlinyeccion(1, "int");

$consulta = "SELECT tipo_moneda.idtipo FROM tipo_moneda WHERE UPPER(descripcion) like \"%DOLAR%\" ";
$respuesta = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$tipo_moneda = $respuesta->fields['idtipo'];
$errores = "";

if (!is_numeric($cotizacion) || $cotizacion == 0) {

    $valido = "N";
    $alerta = "S";
    $errores .= "Verifique que www.set.gov.py/web/portal-institucional/cotizaciones posea cargada la cotizacion referente a  $dia_ayer/$numeroMes/$anho o Comuníquese con el soporte técnico la SET puede estar fuera de servicio o alterado estructuralmente<br>";
    $texto = "<br>Ultima cotizacion cargada en la SET<br> $fecha_ultimo_dia Compra: $cotizacion_ultimo_dia Venta: $cotizacion_ultimo_dia_venta";

}
if ($errores == "") {

    $respuesta = [
        "cotizacion" => $cotizacion,
        "compra" => $cotizacion_compra,
        "idmoneda" => $idmoneda,
        "moneda" => "DOLAR",
        "fecha" => $fecha,
        "success" => true
    ];
} else {
    $respuesta = [

        "success" => false,
        "error" => $errores,
        "cotizacion" => reemplazar_coma($cotizacion_ultimo_dia_venta),
        "compra" => reemplazar_coma($cotizacion_ultimo_dia),
        "fecha" => $fecha_ultimo_dia,
        "idmoneda" => $idmoneda,
        "moneda" => "DOLAR",
        "texto" => $texto
    ];
}

echo json_encode($respuesta);
