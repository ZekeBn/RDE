 <?php
/*----------------------------------------
01/11/2023

---------------------------------------*/
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");

$idlistaprecio = intval($_POST['idlistaprecio']);
$idatc = intval($_POST['idatc']);
$idmesa = intval($_POST['idmesa']);

if ($idlistaprecio == 0 or $idmesa == 0 or $idatc == 0) {
    echo "error al obtener los datos de atc, idmesa o idlistaprecio".print_r($_POST);
    exit;

}
if ($idlistaprecio > 0) {
    $update = "Update mesas_atc set idlista_aplicada=$idlistaprecio where idatc=$idatc";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    //Por si cambio e precio en algun lugar
    $consulta = "
        update productos_listaprecios
        set
        precio = 
            COALESCE(
                (
                select 
                CASE redondeo_direccion
                WHEN 'A' THEN CEIL(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
                WHEN 'B' THEN FLOOR(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
                ELSE
                    ROUND(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
                END as redondeado
                from productos_sucursales, lista_precios_venta
                where
                productos_sucursales.idproducto = productos_listaprecios.idproducto
                and productos_sucursales.idsucursal = productos_listaprecios.idsucursal
                and lista_precios_venta.idlistaprecio = productos_listaprecios.idlistaprecio
                )
            ,0),
        reg_por = $idusu,
        reg_el = '$ahora'
        where
        idlistaprecio in (select idlistaprecio from lista_precios_venta where recargo_porc <> 0)
        and idlistaprecio > 1
        and idlistaprecio = $idlistaprecio
        ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    $buscar = "Select idtmpventares_cab  from tmp_ventares_cab where estado=1 and finalizado='S' and idventa IS NULL and idatc=$idatc and idmesa=$idmesa ";
    $rcab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    while (!$rcab->EOF) {
        $idcabecera = intval($rcab->fields['idtmpventares_cab']);

        //Traemos los articulos de tmpventares que existan en una lista de precio (solo los que existan), agrupamos por articulo ya que es solo para la lista
        $buscar = "Select tmp_ventares.idtmpventares_cab as idcabecera,tmp_ventares.precio,tmp_ventares.subtotal,tmp_ventares.cantidad,
            tmp_ventares.idproducto, tmp_ventares.idtipoproducto as idtipoproducto_carrito, productos.idtipoproducto as idtipoproducto
            from tmp_ventares 
            inner join tmp_ventares_cab on tmp_ventares_cab.idtmpventares_cab=tmp_ventares.idtmpventares_cab
            inner join productos on productos.idprod_serial = tmp_ventares.idproducto
            where 
            tmp_ventares.borrado='N' 
            and tmp_ventares.idproducto in
            (
                select idproducto 
                from productos_listaprecios 
                where 
                idlistaprecio=$idlistaprecio
            )
            and tmp_ventares_cab.idmesa=$idmesa 
            and tmp_ventares_cab.idatc=$idatc
            and tmp_ventares_cab.idtmpventares_cab=$idcabecera
            group by idproducto
            ";
        //echo $buscar;
        $rj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        while (!$rj->EOF) {

            $idproducto = intval($rj->fields['idproducto']);
            $precio_orig = floatval($rj->fields['precio']);
            $idtipoproducto = intval($rj->fields['idtipoproducto']);

            $buscar = "Select precio from productos_listaprecios where idproducto=$idproducto and idsucursal=$idsucursal and estado=1 and idlistaprecio=$idlistaprecio";
            $rsp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $precio_lista = floatval($rsp->fields['precio']);

            // si no es combinado ni combinado extendido
            if ($idtipoproducto <> 3 && $idtipoproducto <> 4) {
                if ($precio_orig != $precio_lista) {

                    $update = "
                        Update tmp_ventares 
                        set 
                        precio=$precio_lista,
                        subtotal=cantidad*$precio_lista,
                        idlistaprecio=$idlistaprecio
                        where  
                        idproducto=$idproducto 
                        and idmesa=$idmesa
                        and borrado='N' 
                        and idtmpventares_cab=$idcabecera
                        ";
                    $conexion->Execute($update) or die(errorpg($conexion, $update));

                }
            } else {
                // recalcular combinado en base a sabores

            }


            $rj->MoveNext();
        }
        //actualizar monto en la cabecera
        $buscar = "Select sum(subtotal) as total from 
            tmp_ventares where idtmpventares_cab=$idcabecera and borrado='N' ";
        $rs2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $total = floatval($rs2->fields['total']);
        $update = "update tmp_ventares_cab set monto=$total where idatc=$idatc and idmesa=$idmesa and idtmpventares_cab=$idcabecera ";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

        $rcab->MoveNext();
    }
    echo "OK";
    exit;
} else {
    echo "Errores: ";
    exit;

}


?>
