<?php

include('./simple_html_dom.php');
require_once("../includes/funciones.php");
require_once("../includes/conexion.php");
require_once("./preferencias_cotizacion.php");
global $cotiza_dia_anterior;
global $editar_fecha;
global $usa_cot_compra;


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
$cotizacion_compra = null;
foreach ($dom->find('h4') as $col) {
    if (strpos(($col->plaintext), "del mes de $nombreMes $anho")) {
        foreach (($col->parentNode()->parentNode()->parentNode()->children()[1]->find("table tr")) as $td) {
            if ($td->find('td', 0)->plaintext == $dia_ayer) {

                $dia = $td->find('td', 0)->plaintext;
                $fecha = $anho."-".$numeroMes."-".$dia_ayer;
                $fecha = date("Y-m-d", strtotime($fecha));
                $cotizacion = $td->find('td', 2)->plaintext; //2 es la venta
                $cotizacion_compra = $td->find('td', 1)->plaintext; //2 es la venta
                break;
            }
        }
    }
}


$cotizacion = reemplazar_coma($cotizacion);
$cotizacion_compra = reemplazar_coma($cotizacion_compra);
$estado = antisqlinyeccion(1, "int");
$consulta = "SELECT tipo_moneda.idtipo FROM tipo_moneda WHERE UPPER(descripcion) like \"%DOLAR%\" ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tipo_moneda = $rs->fields['idtipo'];



$r = [
    "cotizacion" => $cotizacion,
    "compra" => $cotizacion_compra,
    "idmoneda" => $idmoneda,
    "moneda" => "DOLAR",
    "fecha" => $fecha
];
var_dump($r);
