 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

/*
migrar_agregado_producto.php
*/
$consulta = "
select agregado_usaprecioprod from preferencias_caja limit 1
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$agregado_usaprecioprod = $rsprefcaj->fields['agregado_usaprecioprod'];

$idventatmp = antisqlinyeccion($_POST['idvt'], "int");
$idproducto = antisqlinyeccion($_POST['idprod'], "int");
$idingrediente = antisqlinyeccion($_POST['iding'], "int");
$idlistaprecio = intval($_SESSION['idlistaprecio']);
$idcanalventa = intval($_SESSION['idcanalventa']);


//$prod_1=antisqlinyeccion($_POST['prod_1'],"int");
//$prod_2=antisqlinyeccion($_POST['prod_2'],"int");
$fechahora = antisqlinyeccion(date("Y-m-d H:i:s"), "text");
$usuario = $idusu;
$idsucursal = $idsucursal;
$idempresa = $idempresa;


// hacer consulta y obtener
$consulta = "
SELECT agregado.idproducto, agregado.idingrediente, agregado.alias, agregado.precio_adicional, 
insumos_lista.descripcion, agregado.cantidad, medidas.nombre,
insumos_lista.idproducto as idproductoag
FROM agregado 
inner join ingredientes on ingredientes.idingrediente = agregado.idingrediente
inner join insumos_lista on insumos_lista.idinsumo=ingredientes.idinsumo
inner join medidas on insumos_lista.idmedida=medidas.id_medida
WHERE
agregado.idproducto = $idproducto
and agregado.idingrediente = $idingrediente
and agregado.idempresa = $idempresa
and insumos_lista.idempresa = $idempresa
";
//echo $consulta;exit;
$rsagregado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$precio_adicional = antisqlinyeccion($rsagregado->fields['precio_adicional'], "float");
$alias = antisqlinyeccion($rsagregado->fields['alias'], "text");
$cantidad = antisqlinyeccion($rsagregado->fields['cantidad'], "float");
$idproductoag = $rsagregado->fields['idproductoag'];
$subtotal_ag = $rsagregado->fields['precio_adicional'];
//echo $idproductoag;exit;


if ($idcanalventa > 0) {
    $consulta = "
    select *, canal_venta.canal_venta,
    (select lista_precios_venta.lista_precio from  lista_precios_venta where idlistaprecio = canal_venta.idlistaprecio) as lista_precio
    from canal_venta 
    inner join canal_venta_perm on canal_venta_perm.idcanalventa = canal_venta.idcanalventa
    where 
    canal_venta_perm.idusuario = $idusu
    and canal_venta_perm.estado = 1 
    and canal_venta.estado = 1 
    and canal_venta.idcanalventa = $idcanalventa
    order by canal_venta.canal_venta asc
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $lista_precio = $rs->fields['lista_precio'];
    $idlistaprecio = $rs->fields['idlistaprecio'];
}
// si esta activa la lista de precios
if ($idlistaprecio > 0) {
    $consulta = "
    select 
    COALESCE(
        CASE redondeo_direccion
        WHEN 'A' THEN CEIL((($subtotal_ag*(lista_precios_venta.recargo_porc/100))+$subtotal_ag)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
        WHEN 'B' THEN FLOOR((($subtotal_ag*(lista_precios_venta.recargo_porc/100))+$subtotal_ag)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
        ELSE
            ROUND((($subtotal_ag*(lista_precios_venta.recargo_porc/100))+$subtotal_ag)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
        END
    ,0) as precio_adicional_lista
     
    from lista_precios_venta
    where 
    idlistaprecio = $idlistaprecio
    ";
    $rslistp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $subtotal_ag = $rslistp->fields['precio_adicional_lista'];




}
// usa precio del producto agregado
//$agregado_usaprecioprod="S";
if ($agregado_usaprecioprod == 'S') {
    if ($idlistaprecio > 0) {
        // para lista de precios
        $joinadd_lp = " inner join productos_listaprecios on productos_listaprecios.idproducto = productos.idprod_serial ";
        // and productos_listaprecios.estado = 1
        $whereadd_lp = "
        and productos_listaprecios.idsucursal = $idsucursal 
        and productos_listaprecios.idlistaprecio = $idlistaprecio 
        
        ";
        $seladd_lp = " productos_listaprecios.precio ";
    } else {
        $seladd_lp = " productos_sucursales.precio ";
    }
    //and productos_sucursales.activo_suc = 1
    $consulta = "
    select productos.idmedida, productos.idtipoproducto, $seladd_lp as precio
    from productos 
    inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
    $joinadd_lp
    where
    productos.idprod_serial is not null
    and productos.idprod_serial = $idproductoag
    and productos.borrado = 'N'

    and productos_sucursales.idsucursal = $idsucursal 


    $whereadd_lp

    order by productos.descripcion asc
    ";
    //echo $consulta;exit;
    $rsagp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $subtotal_ag = $rsagp->fields['precio'];
    //echo $subtotal_ag;exit;

}




$cantidad_ag = $rsagregado->fields['cantidad'];
$precio_ag = $subtotal_ag / $cantidad_ag;


if (intval($rsagregado->fields['idproducto']) == 0) {
    echo "Error! producto no existe o no pertenece a tu empresa.";
    exit;
}
// buscar producto si es combinado
/*
if($_POST['prod_1'] > 0 && $_POST['prod_2'] > 0){
    $consulta="
    select (sum(p1)/2) as precio
    from productos
    where
    idprod_serial is not null
    and productos.idempresa = $idempresa
    and productos.sucursal = $idsucursal
    and (idprod_serial = $prod_1 or idprod_serial = $prod_2)
    order by productos.descripcion asc
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $precio=antisqlinyeccion($rs->fields['precio'],"int");
}
*/
$consulta = "
INSERT INTO tmp_ventares_agregado
(idventatmp, idproducto, idingrediente, precio_adicional, alias, cantidad, fechahora)
VALUES 
($idventatmp,$idproducto, $idingrediente, $precio_ag, $alias, $cantidad, $fechahora)
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
select max(idtmpventaresagregado) as idtmpventaresagregado 
from tmp_ventares_agregado 
where 
fechahora = $fechahora
";
$rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$idproductoag = antisqlinyeccion($rsagregado->fields['idproductoag'], "int");
$idventatmp_princ_delagregado = $idventatmp;

$idtmpventaresagregado = antisqlinyeccion($rsmax->fields['idtmpventaresagregado'], "int");

$consulta = "
INSERT INTO tmp_ventares
(idventatmp_princ_delagregado, idtmpventaresagregado, idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal) 
VALUES 
($idventatmp_princ_delagregado, $idtmpventaresagregado, $idproductoag, 5,$cantidad_ag,$precio_ag, $fechahora,$idusu, 'N', $idsucursal, $idempresa, 'N', 'N', 'N', NULL, NULL,$subtotal_ag)
;
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// buscar cantidad total de ese producto y responder
$consulta = "
select 
count(idventatmp) as total
from tmp_ventares_agregado
where 
idventatmp = $idventatmp
and tmp_ventares_agregado.idingrediente = $idingrediente
and tmp_ventares_agregado.idproducto = $idproducto
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

echo intval($rs->fields['total']);


?>
