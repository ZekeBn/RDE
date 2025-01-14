<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "11";
$submodulo = "302";
require_once("includes/rsusuario.php");
//exit;

function limpiacsv($txt)
{
    global $saltolinea;
    $txt = trim($txt);
    $txt = str_replace(";", ",", $txt);
    $txt = str_replace($saltolinea, "", $txt);
    return $txt;
}

if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}



$consulta = "
select date(ventas.fecha) as fecha, ventas.factura, sucursales.nombre as sucursal, 
REPLACE(COALESCE((Select sum(subtotal_costo)-(sum(subtotal_costo)/11) as subtotal from ventas_detalles where idventa=ventas.idventa and iva=10),0),'.',',') as costo_grav_10,
REPLACE(COALESCE((Select sum(subtotal_costo)-(sum(subtotal_costo)/21) as subtotal from ventas_detalles where idventa=ventas.idventa and iva=5),0),'.',',') as costo_grav_05,
REPLACE(COALESCE((Select sum(subtotal_costo) as subtotal from ventas_detalles where idventa=ventas.idventa and iva=0),0),'.',',') as costo_exenta,
REPLACE(
COALESCE(
(Select sum(subtotal_costo)-(sum(subtotal_costo)/11) as subtotal from ventas_detalles where idventa=ventas.idventa and iva=10)
,0)
+COALESCE(
(Select sum(subtotal_costo)-(sum(subtotal_costo)/21) as subtotal from ventas_detalles where idventa=ventas.idventa and iva=5)
,0)
+COALESCE(
(Select sum(subtotal_costo) as subtotal from ventas_detalles where idventa=ventas.idventa and iva=0)
,0)
,'.',',') as costo_sin_iva
from ventas
inner join sucursales on sucursales.idsucu = ventas.sucursal
where
ventas.estado <> 6
and date(ventas.fecha) >= '$desde'
and date(ventas.fecha) <= '$hasta'
order by date(ventas.fecha) asc, ventas.factura asc
";
//$rs = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
//	CONCAT('\"',factura,'\"') as Factura,


$impreso = date("d/m/Y H:i:s");



$datos = "";


// asigna los datos de la consulta a una variable
$array = $rs->fields;

// CONSTRUYE CABECERA
foreach ($array as $key => $value) {
    $i++;
    $datos .= limpiacsv($key).';';
}
reset($array);
$datos .= $saltolinea;





$impreso = date("YmdHis");

header('Content-Description: File Transfer');
header('Content-Type: application/force-download');
header('Content-Disposition: attachment; filename=venta_detalle_'.$impreso.'.csv');


// imprime cabecera
echo $datos;


//CONSTRUYE CUERPO
$fila = 1;
while (!$rs->EOF) {
    $fila++;
    $array = $rs->fields;
    $i = 0;
    foreach ($array as $key => $value) {
        $i++;
        echo limpiacsv($value).';';
    }
    echo $saltolinea;
    $rs->MoveNext();
}


exit;
