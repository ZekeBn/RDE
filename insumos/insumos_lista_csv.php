<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");


function limpiacsv($txt)
{
    global $saltolinea;
    $txt = trim($txt);
    $txt = str_replace(";", ",", $txt);
    $txt = str_replace($saltolinea, "", $txt);
    return $txt;
}

/*
agregar:
centro produccion
cuenta contable compra

*/


$consulta = "
select idinsumo as codigo_articulo, idproducto as codigo_producto, descripcion as articulo,
insumos_lista.idmedida,
(select nombre from medidas where id_medida = insumos_lista.idmedida ) as medida,
insumos_lista.idcategoria,
(select nombre from categorias where id_categoria = insumos_lista.idcategoria ) as categoria,
insumos_lista.idsubcate,
(select descripcion from sub_categorias where idsubcate = insumos_lista.idsubcate ) as subcategoria,
insumos_lista.idgrupoinsu,
(select nombre from grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu ) as grupo_stock,
insumos_lista.idproveedor,
(select nombre from proveedores where idproveedor = insumos_lista.idproveedor ) as proveedor,
insumos_lista.idagrupacionprod as codigo_agrupacion,
(select produccion_agrupacion.agrupacion_prod from produccion_agrupacion where idagrupacionprod = insumos_lista.idagrupacionprod) as agrupacion_produccion,
insumos_lista.idconcepto,
(select descripcion from cn_conceptos where idconcepto = insumos_lista.idconcepto) as concepto,
CASE WHEN hab_compra = 1 THEN 'SI' ELSE 'NO' END AS habilita_compra,
CASE WHEN hab_invent = 1 THEN 'SI' ELSE 'NO' END AS habilita_inventario,
CASE WHEN 
	insumos_lista.acepta_devolucion = 'S' 
THEN 
	'SI' 
ELSE
	CASE WHEN 
		insumos_lista.acepta_devolucion = 'N'
	THEN
		'NO'
	ELSE
		''
	END
 
 END as acepta_devolucion,
 
CASE WHEN 
	solo_conversion = 1 
THEN
	'SI'
ELSE
	'NO'
END as solo_conversion,

REPLACE(COALESCE(insumos_lista.costo,0),'.',',') as ultimo_costo,
tipoiva as iva,
insumos_lista.idcentroprod,
(select produccion_centros.descripcion from produccion_centros where idcentroprod = insumos_lista.idcentroprod) as centro_produccion,
idplancuentadet as cuenta_contable_compra_codinterno,
(select cuenta from cn_plancuentas_detalles where idserieun = idplancuentadet) as cuenta_contable_compra_nro,
(select descripcion from cn_plancuentas_detalles where idserieun = idplancuentadet) as cuenta_contable_compra_descripcion,
REPLACE(COALESCE(rendimiento_porc,0),'.',',') as rendimiento
from insumos_lista 
where 
 estado = 'A' 
$whereadd
order by descripcion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//	CONCAT('\"',factura,'\"') as Factura,


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
header('Content-Disposition: attachment; filename=art_ins_'.$impreso.'.csv');

echo $datos;
exit;
