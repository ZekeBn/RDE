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

// NO EXCLUIR ANULADOS
$buscar = "
select cliente_delivery.idclientedel, cliente_delivery.nombres, cliente_delivery.apellidos, cliente_delivery.telefono,
cliente.idcliente as idcliente_facturacion, cliente.ruc, cliente.razon_social
from cliente_delivery 
inner join cliente on cliente.idcliente = cliente_delivery.idcliente
where 
 cliente_delivery.estado = 1 
order by cliente_delivery.idclientedel desc
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
header('Content-Disposition: attachment; filename=clientesdeliv_'.$impreso.'.csv');

echo $datos;
exit;
