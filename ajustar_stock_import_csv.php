 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "80";

require_once("includes/rsusuario.php");

$idajuste = intval($_GET['idajuste']);

$idajuste = intval($_GET['id']);
if ($idajuste == 0) {
    header("location: ajustar_stock.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from gest_depositos_ajustes_stock 
where 
idajuste = $idajuste
and estado = 'A'
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idajuste = intval($rs->fields['idajuste']);
$iddeposito = intval($rs->fields['iddeposito']);
if ($idajuste == 0) {
    header("location: ajustar_stock.php");
    exit;
}


set_time_limit(0);



function limpiacsv($txt)
{
    global $saltolinea;
    $txt = trim($txt);
    $txt = str_replace(";", ",", $txt);
    $txt = str_replace($saltolinea, "", $txt);
    return $txt;
}


//Lista de depositos
$buscar = "Select * from gest_depositos where iddeposito=$iddeposito ";
$rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$sucursal_deposito = intval($rsf->fields['idsucursal']);
$tiposala = intval($rsf->fields['tiposala']);



$consulta = "
select 
'' as cantidad_contada,
COALESCE(REPLACE((select disponible from gest_depositos_stock_gral where idproducto = insumos_lista.idinsumo and iddeposito = $iddeposito),'.',','),0) as stock_teorico,
medidas.nombre as medida,
insumos_lista.descripcion,
insumos_lista.idinsumo as codigo_articulo,
(select idprod_serial from productos where idprod_serial = insumos_lista.idproducto) as codigo_producto,
(select idprodexterno from productos where idprod_serial = insumos_lista.idproducto) as codigo_prod_externo,
(select barcode from productos where idprod_serial = insumos_lista.idproducto) as codigo_barras,
(select descripcion from gest_depositos where iddeposito=$iddeposito) as deposito



from insumos_lista
inner join medidas on medidas.id_medida = insumos_lista.idmedida
where
insumos_lista.estado = 'A'    
and insumos_lista.hab_invent = 1
order by insumos_lista.descripcion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$impreso = date("d/m/Y H:i:s");


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
header('Content-Disposition: attachment; filename=ajust_'.$idajuste.'_'.$impreso.'.csv');

echo $datos;
exit;



?>
