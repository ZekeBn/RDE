<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "300";
require_once("includes/rsusuario.php");
//require_once '../clases/PHPExcel.php';

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

$buscar = "
SELECT 
cliente_delivery.idclientedel, cliente_delivery.nombres, cliente_delivery.apellidos, cliente_delivery.telefono,
cliente.idcliente as idcliente_facturacion, cliente.ruc, cliente.razon_social,
cliente_delivery_dom.iddomicilio, cliente_delivery_dom.direccion, cliente_delivery_dom.referencia, cliente_delivery_dom.nombre_domicilio, cliente_delivery_dom.latitud, cliente_delivery_dom.longitud, 
(select describezona from zonas_delivery where idzonadel =  cliente_delivery_dom.idzonadel) as zona,
(select sucursales.nombre from zonas_delivery inner join sucursales on sucursales.idsucu = zonas_delivery.idsucursal_forzar where idzonadel =  cliente_delivery_dom.idzonadel) as forzar_sucursal
FROM cliente_delivery_dom
inner join cliente_delivery on cliente_delivery.idclientedel = cliente_delivery_dom.idclientedel
inner join cliente on cliente.idcliente = cliente_delivery.idcliente
WHERE 
cliente_delivery_dom.estado = 1
and cliente_delivery.estado = 1
order by idclientedel asc
";
$rsvdet = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//	CONCAT('\"',factura,'\"') as Factura,
$rs = $rsvdet;

$impreso = date("d/m/Y H:i:s");


$rs = $rsvdet;

$datos = "";


// asigna los datos de la consulta a una variable
$array = $rs->fields;

// CONSTRUYE CABECERA
$array = $rs->fields;
foreach ($array as $key => $value) {
    $i++;
    $datos .= limpiacsv($key).';';
}
reset($array);
$datos .= $saltolinea;

//CONSTRUYE CUERPO
$ante = 0;
$fila = 1;
while (!$rs->EOF) {
    $fila++;
    $array = $rs->fields;
    $i = 0;
    foreach ($array as $key => $value) {
        $i++;
        $datos .= limpiacsv($value).';';
    }
    $datos .= $saltolinea;
    $rs->MoveNext();
}



$impreso = date("d/m/Y H:i:s");

header('Content-Description: File Transfer');
header('Content-Type: application/force-download');
header('Content-Disposition: attachment; filename=clientesdelivdom_'.$impreso.'.csv');

echo $datos;
exit;
