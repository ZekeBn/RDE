<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "180";
require_once("includes/rsusuario.php");
//require_once '../clases/PHPExcel.php';
$idsucursal = intval($_REQUEST['ids']);
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
// rellenar telefonos
$consulta = "
update  cliente
set
telefono=(select SUBSTR(tmp_ventares_cab.telefono, 1, 45) from tmp_ventares_cab where idclienteped = cliente.idcliente order by tmp_ventares_cab.idtmpventares_cab desc limit 1)
where 
telefono is null
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

if ($idsucursal > 0) {
    $addsucu = " and sucursal=$idsucursal ";

}


// NO EXCLUIR ANULADOS
$buscar = "
select 
idcliente, ruc, razon_social, fantasia as nombre_fantasia,  nombre, apellido, documento, telefono, celular, email, direccion,
(select canal_venta from canal_venta where idcanalventa = cliente.idcanalventacli) as canal_venta,
(select concat(nombres,' ',apellidos) from vendedor where idvendedor = cliente.idvendedor) as vendedor,
(select  sucursales.nombre from sucursales where idsucu= cliente.sucursal ) as sucursal_asignada,
registrado_el

from cliente 
where
estado <> 6 
$addsucu
order by idcliente asc
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
header('Content-Disposition: attachment; filename=clientes_'.$impreso.'.csv');

echo $datos;
exit;
