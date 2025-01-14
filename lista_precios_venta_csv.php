 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "322";
require_once("includes/rsusuario.php");


$idlistaprecio = intval($_GET['id']);
$idsucu = intval($_GET['idsucu']);
//exit;


function limpiacsv($txt)
{
    global $saltolinea;
    $txt = trim($txt);
    $txt = str_replace(";", ",", $txt);
    $txt = str_replace($saltolinea, "", $txt);
    return $txt;
}
$separador = "\n".str_repeat("=", 100)."\n";
$datalog = "idlistaprecio=$idlistaprecio idsucu=$idsucu $separador";

$whereadd = '';
if ($idlistaprecio > 0) {
    $whereadd .= "
    and lista_precios_venta.idlistaprecio = $idlistaprecio
    ";
}
if ($idsucu > 0) {
    $whereadd .= "
    and sucursales.idsucu = $idsucu
    ";
}

$consulta = "
select 
idproductolista,
idprod_serial as codigo_producto, 
barcode as codigo_barras, 
descripcion as producto, 
(select nombre from categorias where productos.idcategoria = categorias.id_categoria) as categoria,
(select descripcion from sub_categorias where productos.idsubcate =  sub_categorias.idsubcate) as subcategoria,
productos_listaprecios.idsucursal as idsucursal,
sucursales.nombre as sucursal,
lista_precios_venta.idlistaprecio,
lista_precios_venta.lista_precio,
(select CASE WHEN activo_suc = 0 THEN 'NO' ELSE 'SI' END as activo from productos_sucursales where idproducto = productos_listaprecios.idproducto and idsucursal = productos_listaprecios.idsucursal) as activo_sucursal,
REPLACE(COALESCE((precio),0),'.',',') as precio_actual,
'' as precio_nuevo
from productos
inner join productos_listaprecios on productos_listaprecios.idproducto = productos.idprod_serial
inner join lista_precios_venta on lista_precios_venta.idlistaprecio  = productos_listaprecios.idlistaprecio
inner join sucursales on sucursales.idsucu= productos_listaprecios.idsucursal
inner join medidas on medidas.id_medida = productos.idmedida
where
productos.borrado = 'N'
and productos_listaprecios.estado = 1
and lista_precios_venta.estado <> 6
and sucursales.estado <> 6
and lista_precios_venta.recargo_porc = 0
and lista_precios_venta.idlistaprecio > 1
$whereadd
order by productos.descripcion asc, sucursales.idsucu asc, lista_precios_venta.idlistaprecio asc
";
$datalog .= $consulta . $separador;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

grabar_log($datalog, 'i');

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



$impreso = date("YmdHis");

header('Content-Description: File Transfer');
header('Content-Type: application/force-download');
header('Content-Disposition: attachment; filename=lista_precios_prod_'.$impreso.'.csv');

echo $datos;
exit;


?>

?>
