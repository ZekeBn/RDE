<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "11";
$submodulo = "290";
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
if (intval($_GET['idsucu']) > 0) {
    $idsucu = intval($_GET['idsucu']);
    $whereadd .= " and ventas.sucursal = $idsucu ";
}


$consulta = "
select ventas.idcliente, cliente.razon_social, cliente.ruc, cliente.documento,
REPLACE(COALESCE(sum(totalcobrar),0),'.',',') as total_ventas,
REPLACE(COALESCE(count(*),0),'.',',') as cantidad_ventas
from ventas 
inner join cliente on cliente.idcliente = ventas.idcliente
where 
 ventas.estado <> 6 
 $whereadd
 and date(ventas.fecha) >= '$desde'
 and date(ventas.fecha) <= '$hasta'
 group by cliente.razon_social, ventas.idcliente
order by sum(totalcobrar)  desc
limit 100000
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
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
header('Content-Disposition: attachment; filename=ranking_cliente_'.$impreso.'.csv');


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
