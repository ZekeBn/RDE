 <?php

function validar_produccion($parametros_array)
{

    //print_r($parametros_array);exit;

    global $ahora;
    global $saltolinea;
    global $conexion;
    global $idempresa;
    global $idusu;


    $errores = "";
    $valido = "S";

    // parametros externos
    $idreceta = intval($parametros_array['idreceta']);
    $tipo_entrega_insumo = antisqlinyeccion($parametros_array['tipo_entrega_insumo'], 'int'); // 1 limitado 2 sin limite
    $vueltas = antisqlinyeccion(1, 'int');
    $resultante_real = antisqlinyeccion($parametros_array['resultante_real'], 'float');
    //$iddeposito=antisqlinyeccion($_POST['iddeposito'],'int');
    $iddeposito = antisqlinyeccion($parametros_array['iddeposito'], 'int');
    $opr = intval($parametros_array['opr']);
    $prod_auto = trim($parametros_array['prod_auto']); // si es automatica de un traslado o no
    $idtandatraslado = intval($parametros_array['idtandatraslado']);
    $vto = antisqlinyeccion($parametros_array['vto'], 'text');
    $lote = antisqlinyeccion($parametros_array['lote'], 'int');


    // busca en preferencias si quiere validar o no el disponible de stock
    $consulta = "
    SELECT     produccion_nostock, lote_auto FROM preferencias where idempresa = $idempresa
    ";
    $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $lote_auto = $rspref->fields['lote_auto'];
    if ($rspref->fields['produccion_nostock'] == 2) {
        $valida_stock = "S";
    } else {
        $valida_stock = "N";
    }
    if ($prod_auto == 'S') {
        $valida_stock = "N";
    }
    $consulta = "
    select obliga_loteyvenc from preferencias_produccion limit 1
    ";
    $rsprefprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $obliga_loteyvenc = $rsprefprod->fields['obliga_loteyvenc'];





    // valida que los ingredientes estan cargados
    $consulta = "
    SELECT *, (select nombre as medida_obj from medidas where medidas.id_medida = recetas_produccion.medida ) as medida_obj,
    (select id_medida as medida_obj from medidas where medidas.id_medida = recetas_produccion.medida ) as idmedida_obj,
         COALESCE((
        select sum(disponible) as total_stock 
        from gest_depositos_stock_gral 
        where 
        idproducto = insumos_lista.idinsumo 
        and iddeposito = $iddeposito
        and idempresa = $idempresa
        ),0) as total_stock,
        (select idinsumo from prod_lista_objetivos where unicopkss = recetas_produccion.idobjetivo) as idinsumo_final,
    recetas_produccion.nombre as receta
    FROM recetas_produccion
    inner join recetas_detalles_produccion on recetas_detalles_produccion.idreceta = recetas_produccion.idreceta
    inner join insumos_lista on insumos_lista.idinsumo = recetas_detalles_produccion.idinsumo
    inner join medidas on medidas.id_medida = insumos_lista.idmedida
    where 
    recetas_produccion.idreceta = $idreceta
    and recetas_produccion.estado <> 6
    and recetas_produccion.idempresa = $idempresa
    and recetas_detalles_produccion.idempresa = $idempresa
    and insumos_lista.idempresa = $idempresa
    and recetas_produccion.cantidad_resultante > 0
    order by insumos_lista.descripcion asc
    ";
    //echo $consulta;
    $rsrec = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idinsumo_final = $rsrec->fields['idinsumo_final'];
    $idobjetivo = $rsrec->fields['idobjetivo'];
    $cantidad_teorica = $rsrec->fields['cantidad_resultante'];
    if ($idobjetivo == 0) {
        $valido = "N";
        $errores .= "- Los ingredientes de la receta no estan cargados.".$saltolinea;
    }
    // validaciones
    if (intval($parametros_array['tipo_entrega_insumo']) != 1) {
        $valido = "N";
        $errores .= "- Tu empresa no tiene habilitado este tipo de entrega de insumos.".$saltolinea;
    }
    if (floatval($parametros_array['resultante_real']) <= 0) {
        $valido = "N";
        $errores .= "- Debes indicar el resultante real.".$saltolinea;
    }
    if (intval($parametros_array['iddeposito']) <= 0) {
        $valido = "N";
        $errores .= "- Debes indicar el deposito de produccion.".$saltolinea;
    }
    if ($prod_auto != 'S') {
        if ($obliga_orden_produccion > 0) {
            if ($opr <= 0) {
                $valido = "N";
                $errores .= "- Debes indicar el numero de orden de produccion.".$saltolinea;
            }
        }
    }
    if ($prod_auto == 'S') {
        if ($idtandatraslado == 0) {
            $valido = "N";
            $errores .= "- Debes indicar la tanda de traslado cuando es una produccion automatica.".$saltolinea;
        }
    }
    if (!function_exists(movimientos_stock)) {
        $valido = "N";
        $errores .= "- No se incluyo las funciones de stock.".$saltolinea;
    }
    // si envio vencimiento
    if (trim($parametros_array['vto']) != '') {
        if (trim($parametros_array['fecha_producido']) != '') {
            $comparar = date("Y-m-d", strtotime($parametros_array['fecha_producido']));
        } else {
            $comparar = date("Y-m-d");
        }
        if (strtotime($parametros_array['vto']) < strtotime(date("Y-m-d", strtotime($comparar)))) {
            $valido = "N";
            $errores .= "- La fecha de vencimiento no puede estar en el pasado.".$saltolinea;
        }
    }
    // si envio fecha producido
    if (trim($parametros_array['fecha_producido']) != '') {
        if (strtotime(date("Y-m-d", strtotime($parametros_array['fecha_producido']))) > strtotime(date("Y-m-d"))) {
            $valido = "N";
            $errores .= "- La fecha de produccion no puede estar en el futuro.".$saltolinea;
        }
    }
    if ($lote_auto != 'S') {

        if ($parametros_array['lote'] != '') {
            $consulta = "
            select idinsumo from prod_lista_objetivos where unicopkss = $idobjetivo limit 1
            ";
            $rsobj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idinsumo_final = intval($rsobj->fields['idinsumo']);
            $consulta = "
            select idproducido 
            from produccion_producido 
            where 
            estado <> 6 
            and idinsumo_final = $idinsumo_final
            and lote = $lote
            limit 1
            ";
            $rslot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            if ($rslot->fields['idproducido'] > 0) {
                $valido = "N";
                $errores .= "- Ya existe otra produccion con el mismo lote para este articulo.".$saltolinea;
            }

        } // if($parametros_array['lote'] != ''){

    } // if($lote_auto != 'S'){

    // si obliga lote y vencimiento
    if ($obliga_loteyvenc == 'S') {
        // si no es una produccion automatica
        if ($prod_auto != 'S') {
            // si no genera automaticamente el lote
            if ($lote_auto != 'S') {
                if (trim($parametros_array['lote']) == '') {
                    $valido = "N";
                    $errores .= "- Debe indicar el lote.".$saltolinea;
                }
            }
            if (trim($parametros_array['vto']) == '') {
                $valido = "N";
                $errores .= "- Debe indicar el vencimiento del lote.".$saltolinea;
            }

        } // if($prod_auto != 'S'){
    }



    // valida que exista deposito
    $consulta = "select * 
        from gest_depositos 
        where 
        iddeposito = $iddeposito
        and idempresa = $idempresa
        limit 1
    ";
    $rsdepex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsdepex->fields['iddeposito']) <= 0) {
        $valido = "N";
        $errores .= "- El deposito seleccionado no existe.".$saltolinea;
    }
    // si envio orden de produccion
    if ($prod_auto != 'S') {
        if ($opr > 0) {

            // busca si la orden de produccion pertenece al mismo producto objetivo
            $consulta = "
            select * from produccion_ordenes where opr = $opr
            ";
            $rsopr = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idinsumo_orden = $rsopr->fields['idinsumo_final'];
            if ($idinsumo_orden != $idinsumo_final) {
                $valido = "N";
                $errores .= "- El producto objetivo [$idinsumo_orden] de la orden de produccion no es el mismo que la produccion actual [$idinsumo_final].".$saltolinea;
            }

            // busca si la orden de produccion esta activa
            if ($rsopr->fields['estado_orden'] != 2 && $rsopr->fields['estado_orden'] != 3) {
                $valido = "N";
                $errores .= "- La orden de produccion indicada ya no esta vigente.".$saltolinea;
            }

        }
    }



    // si corresponde por parametro valida que haya stock disponible
    if ($prod_auto != 'S') {
        if ($valida_stock == 'S') {
            // recorre receta
            while (!$rsrec->EOF) {

                // datos de insumos para stock
                $ninsumo_stock = $rsrec->fields['descripcion'];
                $ninsumo_stock = str_replace("'", "", $ninsumo_stock);
                $totalenstock = $rsrec->fields['total_stock'];
                $cantidad_porvuelta = $rsrec->fields['cantidad'];
                $cantidad_total = regladetres($rsrec->fields['cantidad_resultante'], $cantidad_porvuelta, floatval($parametros_array['resultante_real']));
                $diferencia = $rsrec->fields['total_stock'] - $cantidad_total;


                if ($totalenstock < $cantidad_total) {
                    $valido = "N";
                    $errores .= "- No queda suficiente $ninsumo_stock en stock para esta produccion, quedan $totalenstock y necesitas $cantidad_total.".$saltolinea;
                }
                if ($totalenstock <= 0) {
                    $valido = "N";
                    $errores .= "- El insumo $ninsumo_stock no tiene disonibilidad en stock.".$saltolinea;
                }




                $rsrec->MoveNext();
            }
            $rsrec->MoveFirst(); // reinicia el recordset

        }
    }



    $res = ["valido" => $valido, 'errores' => $errores];
    return $res;
}

function producir($parametros_array)
{

    //print_r($parametros_array);exit;

    global $ahora;
    global $saltolinea;
    global $conexion;
    global $idempresa;
    global $idusu;


    $errores = "";
    $valido = "S";

    // busca en preferencias
    $consulta = "
    SELECT     produccion_nostock, lote_auto FROM preferencias where idempresa = $idempresa
    ";
    $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $lote_auto = $rspref->fields['lote_auto'];

    // parametros externos
    $idreceta = intval($parametros_array['idreceta']);
    $tipo_entrega_insumo = antisqlinyeccion($parametros_array['tipo_entrega_insumo'], 'int'); // 1 limitado 2 sin limite
    $vueltas = antisqlinyeccion(1, 'int');
    $resultante_real = antisqlinyeccion(floatval($parametros_array['resultante_real']), 'float');
    //$iddeposito=antisqlinyeccion($_POST['iddeposito'],'int');
    $iddeposito = antisqlinyeccion($parametros_array['iddeposito'], 'int');
    $opr = intval($parametros_array['opr']);
    $prod_auto = trim($parametros_array['prod_auto']); // si es automatica de un traslado o no
    $idtandatraslado = intval($parametros_array['idtandatraslado']);
    $vto = antisqlinyeccion($parametros_array['vto'], 'text');
    $fecha_producido = antisqlinyeccion($parametros_array['fecha_producido'], 'text');
    $lote = antisqlinyeccion($parametros_array['lote'], 'int');
    if ($prod_auto == 'S') {
        $automatica_traslado = 1;
    } else {
        $automatica_traslado = 0;
    }



    // conversion
    if (trim($parametros_array['fecha_producido']) == '') {
        $fecha_producido = antisqlinyeccion(date("Y-m-d"), 'text');
    }
    $fecha_producido_movst = str_replace("'", "", $fecha_producido);

    $consulta = "
    SELECT *, (select nombre as medida_obj from medidas where medidas.id_medida = recetas_produccion.medida ) as medida_obj,
    (select id_medida as medida_obj from medidas where medidas.id_medida = recetas_produccion.medida ) as idmedida_obj,
         COALESCE((
        select sum(disponible) as total_stock 
        from gest_depositos_stock_gral 
        where 
        idproducto = insumos_lista.idinsumo 
        and iddeposito = $iddeposito
        and idempresa = $idempresa
        ),0) as total_stock,
    recetas_produccion.nombre as receta
    FROM recetas_produccion
    inner join recetas_detalles_produccion on recetas_detalles_produccion.idreceta = recetas_produccion.idreceta
    inner join insumos_lista on insumos_lista.idinsumo = recetas_detalles_produccion.idinsumo
    inner join medidas on medidas.id_medida = insumos_lista.idmedida
    where 
    recetas_produccion.idreceta = $idreceta
    and recetas_produccion.estado <> 6
    and recetas_produccion.idempresa = $idempresa
    and recetas_detalles_produccion.idempresa = $idempresa
    and insumos_lista.idempresa = $idempresa
    and recetas_produccion.cantidad_resultante > 0
    order by insumos_lista.descripcion asc
    ";
    //echo $consulta;
    $rsrec = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cantidad_teorica = $rsrec->fields['cantidad_resultante'];
    $idobjetivo = $rsrec->fields['idobjetivo'];
    $consulta = "
    SELECT *, insumos_lista.descripcion as descripcion, prod_lista_objetivos.idinsumo as idinsumo_final
    FROM prod_lista_objetivos
    inner join insumos_lista on insumos_lista.idinsumo = prod_lista_objetivos.idinsumo
    where
    prod_lista_objetivos.estado <> 6
    and prod_lista_objetivos.idempresa = $idempresa
    and insumos_lista.idempresa = $idempresa
    and prod_lista_objetivos.unicopkss = $idobjetivo
    order by insumos_lista.descripcion asc
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //echo $consulta;
    $idinsumo_final = $rs->fields['idinsumo_final'];
    $nombreinsu_final = $rs->fields['descripcion'];
    $nombreinsu_final = str_replace("'", "", $nombreinsu_final);




    // insertar resultado de la produccion
    $consulta = "
    insert into produccion_producido
    (idinsumo_final, cantidad_real, cantidad_teorica, costo, vueltas, idreceta, idusuario, fechahora, fecha_producido, estado, idempresa, iddeposito, opr, automatica_traslado, idtandatraslado, lote, vto)
    values
    ($idinsumo_final, $resultante_real, $cantidad_teorica, 0, $vueltas, $idreceta, $idusu, '$ahora', $fecha_producido, 1, $idempresa, $iddeposito, $opr, $automatica_traslado, $idtandatraslado, NULL, $vto)
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // ultimo id insertado de la cabecera de esta empresa
    $consulta = "select max(idproducido) as lastid from produccion_producido where idempresa = $idempresa";
    $rslastid = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $lastid = intval($rslastid->fields['lastid']);
    $idproducido = $lastid;
    if ($lote_auto == 'S') {
        $lote = $idproducido;
    }

    // buscar la receta
    $consulta = "
    SELECT * ,(Select  descripcion from insumos_lista where idinsumo=recetas_detalles_produccion.idinsumo and idempresa=$idempresa) as nombreinsu
    FROM recetas_detalles_produccion 
    where 
    idreceta = $idreceta 
    and idobjetivo = $idobjetivo 
    and idempresa = $idempresa
    ";
    $rsrecetadet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //echo $consulta;exit;

    // busca si existe en stock general el insumo final
    $buscar = "
    Select * 
    from gest_depositos_stock_gral 
    where 
    idproducto=$idinsumo_final 
    and idempresa=$idempresa 
    and estado=1 
    and iddeposito = $iddeposito
    ";
    $rsst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    // si no existe inserta
    if (intval($rsst->fields['idproducto']) == 0) {
        $insertar = "
        INSERT INTO gest_depositos_stock_gral
        (iddeposito, idproducto, disponible, tipodeposito, last_transfer, estado, descripcion, idempresa) 
        VALUES 
        ($iddeposito,$idinsumo_final,0,1,'$ahora',1,'$nombreinsu_final',$idempresa
        )";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        movimientos_stock($idinsumo_final, 0, $iddeposito, 7, '+', $idproducido, $fecha_producido_movst);
    }

    // aumentar insumo final en stock
    $consulta = "
    UPDATE gest_depositos_stock_gral 
    SET 
    disponible=(disponible+$resultante_real)
    WHERE 
    idempresa=$idempresa 
    and iddeposito=$iddeposito
    and idproducto=$idinsumo_final
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    movimientos_stock($idinsumo_final, $resultante_real, $iddeposito, 7, '+', $idproducido, $fecha_producido_movst);



    $costo_acumulado = 0;
    while (!$rsrecetadet->EOF) {
        $idinsumo = $rsrecetadet->fields['idinsumo'];
        $costo_insumo = 0;
        $nombreinsu = $rsrecetadet->fields['nombreinsu'];
        $nombreinsu = str_replace("'", "", $nombreinsu);




        $cantidad_porvuelta = $rsrecetadet->fields['cantidad'];
        $cantidad_total = regladetres($rsrec->fields['cantidad_resultante'], $cantidad_porvuelta, floatval($parametros_array['resultante_real']));
        $cantidad = floatval($cantidad_total);

        // busca si existe en stock general cada insumo de la receta
        $buscar = "Select * from gest_depositos_stock_gral where idproducto=$idinsumo and idempresa=$idempresa and estado=1 and iddeposito = $iddeposito";
        $rsst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        // si no existe inserta
        if (intval($rsst->fields['idproducto']) == 0) {
            $insertar = "INSERT INTO gest_depositos_stock_gral
            (iddeposito, idproducto, disponible, tipodeposito, last_transfer, estado, descripcion, idempresa) 
            VALUES 
            ($iddeposito,$idinsumo,0,1,'$ahora',1,'$nombreinsu',$idempresa
            )";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

            movimientos_stock($idinsumo, 0, $iddeposito, 8, '-', $idproducido, $fecha_producido_movst);

        }






        // descontar insumo de receta en stock
        $consulta = "
        UPDATE gest_depositos_stock_gral 
        SET 
        disponible=(disponible-$cantidad)
        WHERE 
        idempresa=$idempresa 
        and iddeposito=$iddeposito
        and idproducto=$idinsumo
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        movimientos_stock($idinsumo, $cantidad, $iddeposito, 8, '-', $idproducido, $fecha_producido_movst);



        // descontar stock fisico de costoproductos y depositos stock, devuelve el costo total del insumos utilizado
        $costo_insumo_utilizado = descuenta_stock_prod($idinsumo, $cantidad, $iddeposito, $idproducido);
        // costo unitario promedio de los insumos utilizados
        if ($cantidad > 0) {
            $costo_insumo_unitario = round(floatval($costo_insumo_utilizado) / $cantidad, 4);
        } else {
            $costo_insumo_unitario = 0;
        }
        // costo del insumo actual utilizado
        $costo_acum_insu[$idinsumo] = $costo_insumo_utilizado;
        // costo acumulado de todos los insumos
        $costo_acumulado += $costo_insumo_utilizado;

        $costo_insumo_unitario = floatval($costo_insumo_unitario);
        if (!$costo_insumo_unitario > 0) {
            $costo_insumo_unitario = 0;
        }

        //  insertar en el detalle de produccion
        $consulta = "
        insert into produccion_producido_det
        (idproducido, idinsumo, idinsumo_final, cantidad, costo_insumo, costo_insumo_unitario, idempresa, fechahora_det)
        values
        ($lastid, $idinsumo, $idinsumo_final, $cantidad, $costo_insumo_utilizado, $costo_insumo_unitario, $idempresa, '$ahora')
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        $rsrecetadet->MoveNext();
    }

    // calcular el costo por producto
    $costo_insumo_final = round(($costo_acumulado / $resultante_real), 4);

    // aumentar stock fisico de costo productos y depositos stock
    $consulta = "
    insert into costo_productos 
    (cantidad,precio_costo,id_producto,idempresa,registrado_el,ubicacion,disponible,idcompra,idproducido,fechacompra,lote,vencimiento)
    values
    ($resultante_real, $costo_insumo_final, $idinsumo_final, $idempresa, '$ahora', $iddeposito, $resultante_real, 0, $idproducido, '$ahora',$lote,$vto)
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // id insertado
    $consulta = "select max(idseriepkcos) as ultid from costo_productos where idempresa = $idempresa";
    $rsulid = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $ultid = $rsulid->fields['ultid'];


    $consulta = "
    INSERT INTO gest_depositos_stock
    (idproducto,idseriecostos,fechacompra,disponible,cantidad,iddeposito,recibido_el,verificado_el,descripcion,costogs,idempresa)
    values
    ($idinsumo_final, $ultid, '$ahora', $resultante_real, $resultante_real, $iddeposito,'$ahora', '$ahora', '$nombreinsu_final', $costo_insumo_final, $idempresa)
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
    update produccion_producido 
    set
    costo = (select sum(costo_insumo) from produccion_producido_det where produccion_producido_det.idproducido = produccion_producido.idproducido),
    lote = $lote
    where
    produccion_producido.idproducido = $idproducido;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
    update insumos_lista 
    set 
    costo = COALESCE((
            select (costo/cantidad_real) as costo
            from produccion_producido 
            where 
            produccion_producido.idinsumo_final = insumos_lista.idinsumo 
            and idempresa = $idempresa
            order by fecha_producido desc, fechahora desc 
            limit 1
            ),0)
    where
    idempresa = $idempresa
    and idinsumo = $idinsumo_final
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // si trabaja con orden
    if ($opr > 0) {
        $consulta = "
        update produccion_ordenes 
        set 
        cantidad_producida  = (select sum(cantidad_real) from produccion_producido where opr = $opr and estado = 1)
        where
        opr = $opr
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
        update produccion_ordenes 
        set 
        saldo_producir  = cantidad_producir-cantidad_producida
        where
        opr = $opr
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // estado produciendose
        $consulta = "
        update produccion_ordenes 
        set 
        estado_orden  = 3
        where
        opr = $opr
        and cantidad_producir > cantidad_producida
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // estado finalizado
        $consulta = "
        update produccion_ordenes 
        set 
        estado_orden  = 4
        where
        opr = $opr
        and cantidad_producir <= cantidad_producida
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }

    // update masivo: update produccion_producido set costo = (select sum(costo_insumo) from produccion_producido_det where idproducido = produccion_producido.idproducido);


    $res = ['idproducido' => $idproducido];


    return $res;
}
function validar_anulacion_produccion($parametros_array)
{
    global $conexion;
    global $ahora;
    global $saltolinea;

    $valido = "S";
    $errores = "";

    $idproducido = intval($parametros_array['idproducido']);
    $anulado_por = intval($parametros_array['anulado_por']);
    $anulado_el = $ahora;

    if ($anulado_por <= 0) {
        $valido = "N";
        $errores .= "- No indico el usuario.".$saltolinea;
    }

    // validar que exista y no este anulada
    $consulta = "
    SELECT * FROM produccion_producido where estado = 1 and idproducido = $idproducido limit 1
    ";
    $rsprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsprod->fields['idproducido']) == 0) {
        $valido = "N";
        $errores .= "- La produccion [$idproducido], no existe o ya fue anualada.".$saltolinea;
    }


    $res = ['valido' => $valido,'errores' => $errores];
    return $res;
}
function registrar_anulacion_produccion($parametros_array)
{
    global $conexion;
    global $ahora;

    $valido = "S";
    $errores = "";

    $idproducido = $parametros_array['idproducido'];
    $anulado_por = $parametros_array['anulado_por'];
    $anulado_el = $ahora;

    // buscar producto final
    $consulta = "
    select idinsumo_final, cantidad_real, iddeposito, opr, fecha_producido
    from produccion_producido
    where
    idproducido = $idproducido 
    ";
    $rsprodcabanul = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $iddeposito = $rsprodcabanul->fields['iddeposito'];
    $fecha_producido = $rsprodcabanul->fields['fecha_producido'];
    $idinsumo_descontar = $rsprodcabanul->fields['idinsumo_final'];
    $cantidad_descontar = $rsprodcabanul->fields['cantidad_real'];
    $opr = $rsprodcabanul->fields['cantidad_real'];

    // descontar stock de producto final
    descontar_stock($idinsumo_descontar, $cantidad_descontar, $iddeposito);
    descontar_stock_general($idinsumo_descontar, $cantidad_descontar, $iddeposito);
    movimientos_stock($idinsumo_descontar, $cantidad_descontar, $iddeposito, 19, '-', $idproducido, $fecha_producido);


    // buscar ingreddientes
    $consulta = "
    select idproducido, idinsumo, idinsumo_final, cantidad, costo_insumo, costo_insumo_unitario, idempresa, fechahora_det
    from produccion_producido_det
    where 
    idproducido = $idproducido 
    ";
    $rsprodanul = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // reccorrer y restaurar (aumentar) stock de ingredientes
    while (!$rsprodanul->EOF) {
        $idinsumo_aumentar = $rsprodanul->fields['idinsumo'];
        $cantidad_aumentar = $rsprodanul->fields['cantidad'];
        $costo_unitario = $rsprodanul->fields['costo_insumo_unitario'];

        // aumentar stock de ingredientes
        aumentar_stock($idinsumo_aumentar, $cantidad_aumentar, $costo_unitario, $iddeposito);
        aumentar_stock_general($idinsumo_aumentar, $cantidad_aumentar, $iddeposito);
        movimientos_stock($idinsumo_aumentar, $cantidad_aumentar, $iddeposito, 19, '+', $idproducido, $fecha_producido);

        $rsprodanul->MoveNext();
    }



    // actualizar cabecera como anulada
    $consulta = "
    update produccion_producido 
    set 
    estado = 6 ,
    anulado_por = $anulado_por,
    anulado_el = '$ahora'
    where 
    idproducido = $idproducido 
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // si trabaja con orden
    if ($opr > 0) {
        $consulta = "
        update produccion_ordenes 
        set 
        cantidad_producida  = 
            (
            select sum(cantidad_real) 
            from produccion_producido 
            where 
            opr = $opr
            and estado = 1
            and idproducido <> $idproducido 
            )
        where
        opr = $opr
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
        update produccion_ordenes 
        set 
        saldo_producir  = cantidad_producir-cantidad_producida
        where
        opr = $opr
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // estado produciendose
        $consulta = "
        update produccion_ordenes 
        set 
        estado_orden  = 3
        where
        opr = $opr
        and cantidad_producir > cantidad_producida
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // estado finalizado
        $consulta = "
        update produccion_ordenes 
        set 
        estado_orden  = 4
        where
        opr = $opr
        and cantidad_producir <= cantidad_producida
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }

    $res = ['valido' => $valido,'errores' => $errores, 'idproducido' => $idproducido];
    return $res;
}
?>
