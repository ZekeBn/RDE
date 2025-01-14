<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "55";
$dirsup = 'S';
require_once("../includes/rsusuario.php");


set_time_limit(0);

function limpiadepo($texto)
{
    //eliminando etiquetas html
    $texto = strip_tags($texto);
    //compruebo que los caracteres sean los permitidos
    $permitidos = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 ";
    for ($i = 0; $i < strlen($texto); $i++) {
        if (strpos($permitidos, substr($texto, $i, 1)) === false) {
            //echo substr($texto,$i,1);
        } else {
            $result = $result.substr($texto, $i, 1);
        }
    }
    return $result;
}


$consulta = "
SELECT iddeposito, descripcion, orden_nro
FROM gest_depositos 
where 
estado <> 6 
order by gest_depositos.orden_nro asc
";
$rsdep = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$seladd = "";
while (!$rsdep->EOF) {
    $deposito = limpiadepo($rsdep->fields['descripcion']);
    $iddeposito = limpiadepo($rsdep->fields['iddeposito']);
    $seladd .= "
	COALESCE(REPLACE((
	select sum(disponible) as total
	from gest_depositos_stock_gral
	where
	idproducto = insumos_lista.idinsumo
	and idproducto = insumos_lista.idinsumo
	and iddeposito = $iddeposito
	),'.',','),0) as '$deposito',
	";

    $rsdep->MoveNext();
}





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
$idsucu = intval($_GET['idsucu']);
if ($idsucu > 0) {
    $whereadd = " and ventas.sucursal = $idsucu ";
}

//(select descripcion from formas_pago where formas_pago.idforma = ventas.formapago) as 'Forma de Pago',
// NO EXCLUIR ANULADOS
$buscar = "
select 
insumos_lista.idinsumo as cod_articulo, 
insumos_lista.idinsumo as cod_producto, 
insumos_lista.descripcion, 
medidas.nombre as unidadmedida,
$seladd
COALESCE(REPLACE((
select sum(disponible) as total
from gest_depositos_stock_gral
where
idproducto = insumos_lista.idinsumo
),'.',','),0) as stock_teorico,
COALESCE(REPLACE(insumos_lista.costo,'.',','),0) as ultimocosto,
COALESCE(REPLACE((
SELECT precio 
FROM productos_sucursales 
inner join gest_depositos on gest_depositos.idsucursal = productos_sucursales.idsucursal
where  
productos_sucursales.idproducto = insumos_lista.idproducto
limit 1
),'.',','),0) as precio_venta,
COALESCE(REPLACE((
select sum(disponible) as total
from gest_depositos_stock_gral
where
idproducto = insumos_lista.idinsumo
)*insumos_lista.costo,'.',','),0) as valorizado_pcosto,
COALESCE(REPLACE((
select sum(disponible) as total
from gest_depositos_stock_gral
where
idproducto = insumos_lista.idinsumo
)*(
SELECT precio 
FROM productos_sucursales 
inner join gest_depositos on gest_depositos.idsucursal = productos_sucursales.idsucursal
where  
productos_sucursales.idproducto = insumos_lista.idproducto
limit 1
),'.',','),0) as valorizado_pventa


from insumos_lista
inner join medidas on medidas.id_medida = insumos_lista.idmedida
where
mueve_stock = 'S'
and insumos_lista.estado = 'A'
and insumos_lista.hab_invent = 1
order by  insumos_lista.descripcion asc
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
header('Content-Disposition: attachment; filename=deposito_stock_glob_det_'.$impreso.'.csv');

echo $datos;
exit;
