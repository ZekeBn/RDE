<?php

function buscar_deposito_devolucion()
{
    global $conexion;
    $consulta = "SELECT iddeposito FROM gest_depositos WHERE autosel_devolucion = 'S' ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $deposito = intval($rs->fields['iddeposito']);
    return $deposito;
}
function devolver_producto($parametros_array)
{
    global $conexion;
    global $ahora;
    global $idusu;

    // stock_tipomov idtipomov 4 traslado entrante ? preguntar para stock_mov

    require_once("funciones_stock.php");
    require_once('../insumos/preferencias_insumos_listas.php');
    global $preferencias_costo_promedio;

    $idempresa = intval($parametros_array['idempresa']);
    $iddeposito = intval($parametros_array['iddeposito']);
    $idorden_retiro = intval($parametros_array['idorden_retiro']);


    // tabla para designar compras y ventas
    $consulta_tipo_mov = "select idtipomov from stock_tipomov where UPPER(tipomov) like UPPER(\"Devolucion%\") ";
    $rs__tipo_mov = $conexion->Execute($consulta_tipo_mov) or die(errorpg($conexion, $consulta_tipo_mov));
    $tipo_mov_devolucion = intval($rs__tipo_mov->fields['idtipomov']);


    // echo "idorden_retiro: $idorden_retiro<br>";
    // echo "iddeposito: $iddeposito<br>";
    // echo "idempresa: $idempresa<br>";
    // exit;


    $buscar = "SELECT devolucion_det.*, insumos_lista.idinsumo,insumos_lista.maneja_lote, insumos_lista.costo, insumos_lista.descripcion,ventas.factura, ventas.fecha as fecha_ventas
    FROM retiros_ordenes 
    INNER JOIN devolucion on devolucion.iddevolucion = retiros_ordenes.iddevolucion
    INNER JOIN devolucion_det on devolucion_det.iddevolucion = devolucion.iddevolucion
    INNER JOIN insumos_lista on insumos_lista.idproducto = devolucion_det.idproducto
    INNER JOIN ventas on ventas.idventa = devolucion.idventa
    WHERE 
    retiros_ordenes.idorden_retiro = $idorden_retiro
    and devolucion.estado = 3
    ";
    $rs2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


    while (!$rs2->EOF) {

        $idp = antisqlinyeccion($rs2->fields['idproducto'], 'int');
        $idinsumo = antisqlinyeccion($rs2->fields['idinsumo'], 'int');
        $cantidad = floatval($rs2->fields['cantidad']);
        $lote = antisqlinyeccion($rs2->fields['lote'], 'text');
        $vencimiento = antisqlinyeccion($rs2->fields['vencimiento'], 'date');
        $id_deposito_articulo = intval($rs2->fields['iddeposito']);
        $costo = floatval($rs2->fields['costo']);
        $iddevolucion = antisqlinyeccion($rs2->fields['iddevolucion'], 'int');
        $factura_numero = antisqlinyeccion($rs2->fields['factura'], 'text');
        $fecha_venta = antisqlinyeccion($rs2->fields['fecha_ventas'], 'date');
        $iddevolucion_det = intval($rs2->fields['iddevolucion_det']);

        // buscando proveedor;
        $fecha_compra = $fecha_venta;


        $pchar = antisqlinyeccion($rs2->fields['descripcion'], 'text');


        //////////////////idproveedor de donde vino el producto
        $consulta = "";
        if ($lote != "NULL" and $lote != "") {
            $consulta = "SELECT 
                        costo_productos.idproveedor
                    from 
                        gest_depositos_stock
                        INNER JOIN costo_productos on costo_productos.idseriepkcos = gest_depositos_stock.idseriecostos
                    WHERE 
                        gest_depositos_stock.lote = $lote 
                        and DATE_FORMAT(gest_depositos_stock.vencimiento, \"%Y-%m-%d\") = DATE_FORMAT($vencimiento, \"%Y-%m-%d\")
                        and iddeposito = $id_deposito_articulo ";

        } else {
            $consulta = "SELECT 
                costo_productos.idproveedor
            from 
                gest_depositos_stock
                INNER JOIN costo_productos on costo_productos.idseriepkcos = gest_depositos_stock.idseriecostos
            WHERE   
                gest_depositos_stock.lote is NULL
                and gest_depositos_stock.vencimiento is NULL
                and iddeposito = $id_deposito_articulo";
        }
        $rs_proveedor = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idproveedor = floatval($rs_proveedor->fields['idproveedor']);
        /////////////////////////////////////////////////////////

        //////////////////////costo promoedio

        $precio_costo = "";
        $costo_promedio = $costo;
        $costo_cif = "";

        $buscar = "SELECT gest_depositos_stock_gral.idproducto,
            SUM(gest_depositos_stock_gral.disponible) as cantidad 
            from gest_depositos_stock_gral 
            where gest_depositos_stock_gral.estado = 1  
            and gest_depositos_stock_gral.idproducto = $idinsumo GROUP BY
            idproducto";
        $rs_deposito = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $cantidad_deposito = floatval($rs_deposito->fields['cantidad']);// para costos_productos cantidad_stock



        if (isset($id_deposito_articulo) && $id_deposito_articulo > 0 && $iddeposito == 0) {
            $iddeposito = intval($id_deposito_articulo);
            $deposito = intval($id_deposito_articulo);
            // echo $iddeposito." ".($id_deposito_articulo);
        } else {
            $deposito = $iddeposito;
        }
        $buscar = "select * from gest_depositos_stock_gral where idproducto=$idinsumo and iddeposito=$deposito";

        // echo $buscar; exit;
        $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idprod_depo = intval($rsb->fields['idproducto']);
        $cantidad_deposito_seleccionado = floatval($rsb->fields['disponible']);

        if ($idprod_depo > 0) {
            //ya existe en el stock gral, damos update nomas
            $update = "Update gest_depositos_stock_gral set disponible=$cantidad_deposito_seleccionado+$cantidad, costo_promedio=$costo where idproducto=$idinsumo and iddeposito=$deposito";
            // echo $update; exit;
            $conexion->Execute($update) or die(errorpg($conexion, $update));
        } else {
            //$tiposala=1;//para forzar ue sea deposito siempre
            //no existe, insert
            $insertar = "Insert into gest_depositos_stock_gral
                (iddeposito,idproducto,disponible,tipodeposito,last_transfer,estado,descripcion,idempresa, costo_promedio)
                values
                ($iddeposito,$idinsumo,$cantidad,0,'$ahora',1,$pchar,$idempresa, $costo_promedio)";
            // echo $insertar; exit;
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        }



        movimientos_stock($idinsumo, $cantidad, $iddeposito, $tipo_mov_devolucion, '+', $iddevolucion, $ahora);

        $idseriepkcos = select_max_id_suma_uno("costo_productos", "idseriepkcos")["idseriepkcos"];
        $inserta = "insert into costo_productos 
        (idseriepkcos,idempresa,id_producto,registrado_el,precio_costo,costo_promedio,costo_cif,idproveedor,cantidad,numfactura,
        costo2,disponible,idcompra,fechacompra,modificado_el,lote,vencimiento,retornado, cantidad_stock)
        values
        ($idseriepkcos, $idempresa,$idinsumo,'$ahora' ,$costo,$costo,$costo,$idproveedor,'$cantidad',$factura_numero,
        0,'$cantidad',$iddevolucion,$fecha_compra,'$ahora',$lote,$vencimiento,'S', $cantidad_deposito)";
        $conexion->Execute($inserta) or die(errorpg($conexion, $inserta));
        $inserta = "Select * from costo_productos where idseriepkcos=$idseriepkcos";

        $rstm = $conexion->Execute($inserta) or die(errorpg($conexion, $inserta));

        ////////////////////////////////////////////////verificar
        $serie = $idseriepkcos;

        $idproductocostos = antisqlinyeccion($rstm->fields['id_producto'], 'int');

        $subpro = intval($rstm->fields['subprod']);
        $produccion = intval($rstm->fields['produccion']);
        $factura = antisqlinyeccion($rstm->fields['numfactura'], 'text');
        $pcosto = floatval($rstm->fields['precio_costo']);
        $vto = antisqlinyeccion($rstm->fields['vencimiento'], 'text');
        //No existe y damos de alta

        $insertar = "Insert into gest_depositos_stock
        (idproducto,idseriecostos,disponible,cantidad,iddeposito,
        subproducto,produccion,lote,vencimiento,recibido_el,
        autorizado_por,verificado_por,verificado_el,facturanum,descripcion,costogs,idempresa)
        values
        ($idproductocostos,$serie,$cantidad,$cantidad,$iddeposito,$subpro,$produccion,$lote,$vencimiento,
        '$ahora',$idusu,$idusu,'$ahora',$factura,$pchar,$pcosto,$idempresa)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        //echo $insertar."<br />";

        ///////////////////////////////////////verificar esto de arriba
        /*-----------------------------------------------------------------------------------------------------------*/
        //Por ultimo, hacemos efectivo el ingreso en costo_productos


        $update = "Update costo_productos set disponible=$cantidad,ubicacion=$iddeposito,modificado_el='$ahora' where idseriepkcos=$idseriepkcos ";
        $conexion->Execute($update) or die(errorpg($conexion, $update));


        $buscar = "UPDATE devolucion_det set iddeposito=$deposito where iddevolucion_det = $iddevolucion_det ";


        $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $buscar = "UPDATE retiros_ordenes set iddeposito=$deposito where idorden_retiro = $idorden_retiro ";


        $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $rs2->MoveNext();
    }
}
