<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "80";

require_once("../includes/rsusuario.php");

$idconteo = intval($_GET['id']);

if ($idconteo == 0) {
    header("Location: conteo_importar.php");
    exit;
}
$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = conteo.iddeposito)  as deposito,
(select estadoconteo from estado_conteo where idestadoconteo = conteo.estado ) as estadoconteo,
(select usuario from usuarios where idusu = conteo.iniciado_por) as usuario
from conteo
where
estado <> 6
and (estado = 1 or estado = 2)
and idconteo = $idconteo
and afecta_stock = 'N'
and fecha_final is null
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rs->fields['iddeposito']);
if (intval($rs->fields['idconteo']) == 0) {
    header("location: conteo_stock.php");
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
(select descripcion from gest_depositos where iddeposito=$iddeposito) as deposito,
(select nombre from categorias where id_categoria = insumos_lista.idcategoria ) as categoria,
(select descripcion from sub_categorias where idsubcate = insumos_lista.idsubcate ) as subcategoria,
(select nombre from grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu ) as grupo_stock
from insumos_lista
inner join medidas on medidas.id_medida = insumos_lista.idmedida
where
insumos_lista.estado = 'A'	
and insumos_lista.hab_invent = 1
and insumos_lista.idgrupoinsu in (SELECT idgrupoinsu FROM conteo_grupos where idconteo = $idconteo)
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
header('Content-Disposition: attachment; filename=conteo_'.$iddeposito.'_'.$impreso.'.csv');

echo $datos;
exit;
