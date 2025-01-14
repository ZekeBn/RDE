<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "180";
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
// rellenar telefonos
$consulta = "
update  cliente
set
telefono=(select SUBSTR(tmp_ventares_cab.telefono, 1, 45) from tmp_ventares_cab where idclienteped = cliente.idcliente order by tmp_ventares_cab.idtmpventares_cab desc limit 1)
where 
telefono is null
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$buscar = "
select 
cliente.idcliente, cliente.ruc, cliente.razon_social, cliente.fantasia as nombre_fantasia,  cliente.nombre, cliente.apellido, cliente.documento, cliente.telefono, cliente.celular, cliente.email, cliente.direccion,
(select canal_venta from canal_venta where idcanalventa = cliente.idcanalventacli) as canal_venta,
(select concat(nombres,' ',apellidos) from vendedor where idvendedor = cliente.idvendedor) as vendedor,
(select  sucursales.nombre from sucursales where idsucu= cliente.sucursal ) as sucursal_asignada,
sucursal_cliente.sucursal as sucursal_cliente,
sucursal_cliente.direccion as sucursal_direccion,
sucursal_cliente.telefono as sucursal_telefono,
sucursal_cliente.mail as sucursal_mail,
cliente.registrado_el as cliente_registrado_el,
sucursal_cliente.registrado_el as sucursal_registrado_el
from cliente
inner join sucursal_cliente on sucursal_cliente.idcliente = cliente.idcliente
where
cliente.estado <> 6
and sucursal_cliente.estado <> 6
order by cliente.idcliente asc, sucursal_cliente.idsucursal_clie
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
header('Content-Disposition: attachment; filename=clientes_suc_'.$impreso.'.csv');

echo $datos;
exit;
