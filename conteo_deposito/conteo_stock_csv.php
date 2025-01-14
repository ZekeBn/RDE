<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");


function limpiacsv($txt)
{
    global $saltolinea;
    $txt = trim($txt);
    $txt = str_replace(";", ",", $txt);
    $txt = str_replace($saltolinea, "", $txt);
    return $txt;
}


$idconteo = intval($_GET['id']);
if (intval($idconteo) == 0) {
    header("location: conteo_stock.php");
    exit;
}

$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = conteo.iddeposito)  as deposito,
(select estadoconteo from estado_conteo where idestadoconteo = conteo.estado ) as estadoconteo,
(select usuario from usuarios where idusu = conteo.iniciado_por) as usuario,
(select usuario from usuarios where idusu = conteo.finalizado_por) as usuariofin
from conteo
where
estado <> 6
and estado = 3
and idconteo = $idconteo
and fecha_final is not  null
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rs->fields['iddeposito']);
$deposito = str_replace("'", "", trim($rs->fields['deposito']));
if (intval($rs->fields['idconteo']) == 0) {
    header("location: conteo_stock.php");
    exit;
}


$consulta = "
select 
$idconteo as idconteo,
$iddeposito as iddeposito,
'$deposito' as deposito,
(SELECT nombre FROM grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu) as grupo_stock,
insumos_lista.idinsumo as cod_articulo,
insumos_lista.descripcion as articulo,
(SELECT nombre FROM medidas where id_medida = insumos_lista.idmedida) as medida,
REPLACE((select cantidad_sistema from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo),'.',',') as stock_teorico,
REPLACE((select cantidad_contada from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo),'.',',') as cantidad_contada,
REPLACE((select diferencia from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo),'.',',') as diferencia,
REPLACE((select precio_costo from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo),'.',',') as precio_costo,
REPLACE((select diferencia_pc from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo),'.',',') as diferencia_pc,
REPLACE((select cantidad_contada*precio_costo from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo),'.',',') as total_pc,
REPLACE((select precio_venta from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo),'.',',') as precio_venta,
REPLACE((select diferencia_pv from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo),'.',',')as diferencia_pv,
REPLACE((select cantidad_contada*precio_venta from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo),'.',',') as total_pv
from insumos_lista 
where 
insumos_lista.idgrupoinsu in (SELECT idgrupoinsu FROM conteo_grupos where idconteo = $idconteo)
and insumos_lista.estado = 'A'
and insumos_lista.hab_invent = 1
order by (SELECT nombre FROM grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu) asc, descripcion asc
";
//echo $consulta;exit;
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





$impreso = date("d/m/Y H:i:s");

header('Content-Description: File Transfer');
header('Content-Type: application/force-download');
header('Content-Disposition: attachment; filename=conteo_det_'.$idconteo.'_'.$impreso.'.csv');


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
