 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "55";

require_once("includes/rsusuario.php");

$idsucursal = intval($_GET['idsucu']);
$filtrosucu = "";
$nombresucu = "TODOS";
if ($idsucursal > 0) {
    $filtrosucu = " and prosu.idsucursal = $idsucursal  ";

    $consulta = "
    select nombre as sucursal from sucursales
    where 
    idsucu = $idsucursal";
    $rssucu = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $nombresucu = trim($rssucu->fields['sucursal']);

}


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






function limpiacsv($txt)
{
    global $saltolinea;
    $txt = trim($txt);
    $txt = str_replace(";", ",", $txt);
    $txt = str_replace($saltolinea, "", $txt);
    $txt = utf8_decode($txt);
    return $txt;
}


//(select descripcion from formas_pago where formas_pago.idforma = ventas.formapago) as 'Forma de Pago',
// NO EXCLUIR ANULADOS
$buscar = "select 
insu.idinsumo as codigo_articulo,
pro.idprod as codigo_producto,
CONCAT('\'',pro.barcode) as codigo_barras,
pro.codplu as codigo_pesable,
pro.idprodexterno as codigo_externo,
pro.descripcion as articulo_nombre,
pro.descripcion_larga as descripcion,
pro.idmedida,
m.nombre as medida,
pro.idcategoria,
ca.nombre as categoria,
subca.idsubcate,
subca.descripcion as subcategoria,
insu.idgrupoinsu as id_grupo_stock,
ginsu.nombre as grupo_stock,
insu.idproveedor,
prov.nombre as proveedores,
insu.idconcepto,
cn_con.descripcion as concepto,
CASE WHEN insu.hab_compra = 1 THEN 'SI' ELSE 'NO' END as habilita_compra,
CASE WHEN insu.hab_invent = 1 THEN 'SI' ELSE 'NO' END as habilita_inventario,
CASE WHEN insu.acepta_devolucion = 'S' THEN 'SI' ELSE CASE WHEN insu.acepta_devolucion = 'N' THEN 'NO' ELSE 'NO' END END as acepta_devolucion,
CASE WHEN insu.solo_conversion = 1 THEN 'SI' ELSE 'NO' END as solo_conversion,
insu.tipoiva as iva,
pcen.idcentroprod,
pcen.descripcion as centro_produccion,
prod_agru.idagrupacionprod,
prod_agru.agrupacion_prod as agrupacion_prod,
cn_plan.idserieun as cuenta_contable_compra_cod_interno,
cn_plan.cuenta_txt as cuenta_contable_compra_nro,
cn_plan.descripcion as cuenta_contable_compra_descripcion,
REPLACE(insu.rendimiento_porc,'.',',') as rendimiento,
CASE WHEN pro.hab_venta = 'S' THEN 'SI' ELSE CASE WHEN pro.hab_venta = 'N' THEN 'NO' ELSE 'NO' END END as arti_para_venta,
CASE WHEN precio_abierto = 'S' THEN 'SI' ELSE 'NO' END as precio_abierto,
REPLACE(pro.precio_min,'.',',') as precio_min,
REPLACE(pro.precio_max,'.',',') as precio_max,
sucu.idsucu as idsucursal,
sucu.nombre as sucursal,
REPLACE(prosu.precio,'.',',') as precio_sucursal,
CASE WHEN prosu.activo_suc = 1 THEN 'SI' ELSE CASE WHEN prosu.activo_suc = 0 THEN 'NO' ELSE 'NO' END END as activo_sucursal,
(
    select proimpre.idimpresora 
    from  producto_impresora proimpre 
    inner join impresoratk impre on impre.idimpresoratk = proimpre.idimpresora 
    where 
    proimpre.idproducto = pro.idprod
    and impre.borrado='N' 
    and impre.idsucursal = prosu.idsucursal 
)  as cod_impresora,
(
    select impre.descripcion
    from  producto_impresora proimpre 
    inner join impresoratk impre on impre.idimpresoratk = proimpre.idimpresora 
    where 
    proimpre.idproducto = pro.idprod
    and impre.borrado='N' 
    and impre.idsucursal = prosu.idsucursal 
)  as nombre_impresora,
CASE WHEN pro.web_muestra = 'S' THEN 'SI' ELSE 'NO' END as web_muestra,
pro.idmarca,
marca.marca
from productos pro 
inner join productos_sucursales prosu on prosu.idproducto = pro.idprod 
inner join sucursales sucu on sucu.idsucu = prosu.idsucursal
left join medidas m on m.id_medida = pro.idmedida
left join categorias ca on ca.id_categoria = pro.idcategoria
left join sub_categorias subca on subca.idsubcate = pro.idsubcate
left join insumos_lista insu on insu.idproducto = pro.idprod
left join grupo_insumos ginsu on ginsu.idgrupoinsu = insu.idgrupoinsu
left join proveedores prov on prov.idproveedor = insu.idproveedor
left join cn_conceptos cn_con on cn_con.idconcepto = insu.idconcepto
left join produccion_centros pcen on pcen.idcentroprod = insu.idcentroprod
left join produccion_agrupacion prod_agru on prod_agru.idagrupacionprod = insu.idagrupacionprod
left join cn_plancuentas_detalles cn_plan on cn_plan.idserieun =insu.idplancuentadet
left join marca on marca.idmarca = pro.idmarca
where
pro.borrado='N' 
$filtrosucu 
order by pro.descripcion ASC
";
$rsvdet = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//    CONCAT('\"',factura,'\"') as Factura,
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



$impreso = date("YmdHis");

header('Content-Description: File Transfer');
header('Content-Type: application/force-download');
header('Content-Disposition: attachment; filename=formato_csv_modificacion_masiva_'.$nombresucu.'_'.$impreso.'.csv');

echo $datos;
exit;

?>
