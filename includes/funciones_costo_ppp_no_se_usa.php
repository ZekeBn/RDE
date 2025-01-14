<?php

function stock_costo_aumentar_ppp($parametros_array)
{

    // variables globales
    global $conexion;

    $idproducto = $parametros_array['idproducto'];
    $cantidad_aumentar = $parametros_array['cantidad_aumentar'];
    $costo_unitario = $parametros_array['costo_unitario'];
    $iddeposito = $parametros_array['iddeposito'];
    $fecha_tanda_completa = $parametros_array['fecha_tanda_completa'];
    $idcompra = intval($parametros_array['idcompra']);
    $idproducido = intval($parametros_array['idproducido']);
    $idventa = $parametros_array['idventa'];


    // se usa solo para devoluciones de venta
    $idventa = intval($idventa);
    if ($idventa > 0) {
        $consulta = "
    select *
    from ventas_detalles
    where
    idventa = $idventa
    and idproducto = $idproducto
    limit 1
    ";
        $rscosto = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $costo_unitario = floatval($rscosto->fields['precio_costo']);
    }

    // obtiene el costo total
    $consulta = "
    select  sum(costo) as costo,  sum(total_costo)  as total_costo, sum(disponible) as disponible
    from depositos_stock_costo_ppp
    where
    idproducto = $idproducto
    and estado_disponible = 1
    ";
    //echo $consulta;
    $rsant = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $total_costo_ant = floatval($rsant->fields['total_costo']);
    $costo_ant = floatval($rsant->fields['costo']);
    $total_costo_mov = floatval($cantidad_aumentar * $costo_unitario);
    $disponible_ant = floatval($rsant->fields['disponible']);
    $disponible_new = floatval($disponible_ant + $cantidad_aumentar);
    $costo_new = floatval(($total_costo_ant + $total_costo_mov) / $disponible_new);
    //echo $total_costo_ant;
    $total_costo_new = $costo_new * $disponible_new;
    if ($disponible_new > 0) {
        $estado_disponible = 1;
    } else {
        $estado_disponible = 2;
    }

    // cera todos los registros anteriores
    $consulta = "
    update depositos_stock_costo_ppp
    set
    disponible = 0,
    estado_disponible = 2
    where
    idproducto = $idproducto
    and estado_disponible = 1
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // inserta nuevo registro
    $consulta = "
    INSERT INTO depositos_stock_costo_ppp
    (
        iddeposito, idproducto, cantidad, disponible, fecha_tanda,
        fecha_tanda_completa, costo, total_costo, estado_disponible, ficticio, idcompra, idproducido
    )
        VALUES
    (0, $idproducto, $disponible_new, $disponible_new, '$fecha_tanda_completa', '$fecha_tanda_completa', $costo_new, $total_costo_new, $estado_disponible, 0,$idcompra,$idproducido);
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


}

function stock_ppp_descuenta($parametros_array_sc)
{

    // variables globales
    global $conexion;

    // recibe parametros
    $idproducto = intval($parametros_array_sc['idproducto']);
    $cantidad_descontar = floatval($parametros_array_sc['cantidad_descontar']);
    $iddeposito = intval($parametros_array_sc['iddeposito']);
    $fecha_tanda_completa = date("Y-m-d H:i:s", strtotime($parametros_array_sc['fecha_tanda_completa']));
    $idcompra = intval($parametros_array_sc['idcompra']);
    $idventa = intval($parametros_array_sc['idventa']);


    // se usan solo para devoluciones de compra
    $idcompra = intval($idcompra);
    if ($idcompra > 0) {
        $consulta = "
    select *
    from facturas_proveedores_compras
    where
    id_factura = $idcompra
    and idproducto = $idproducto
    limit 1
    ";
        $rscosto = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $costo_unitario = floatval($rscosto->fields['precio']);
    }


    // obtiene el costo total
    $consulta = "
    select sum(costo) as costo, sum(total_costo)  as total_costo, sum(disponible) as disponible
    from depositos_stock_costo_ppp
    where
    idproducto = $idproducto
    and estado_disponible = 1
    ";
    $rsant = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $total_costo_ant = floatval($rsant->fields['total_costo']);
    $costo_ant = floatval($rsant->fields['costo']);
    if ($idcompra > 0) {
        $total_costo_mov = floatval($cantidad_descontar * $costo_unitario);
        $costo_mov = $costo_unitario;
    } else {
        $total_costo_mov = floatval($cantidad_descontar * $costo_ant);
        $costo_mov = $costo_ant;
    }
    $disponible_ant = floatval($rsant->fields['disponible']);
    $disponible_new = floatval($disponible_ant - $cantidad_descontar);
    // para evitar error de division by cero y que arroja NaN
    if (($total_costo_ant - $total_costo_mov) <= 0) {
        $costo_new = 0;
    } else {
        $costo_new = floatval(($total_costo_ant - $total_costo_mov) / $disponible_new);
    }
    // evitar error de infinito
    if (is_infinite($costo_new)) {
        $costo_new = 0;
    }
    // para evitar error Nan
    if (is_nan($costo_new)) {
        $costo_new = 0;
    }
    if (floatval($costo_new) <= 0) {
        $costo_new = 0;
    }
    $total_costo_new = floatval($costo_new * $disponible_new);
    if (floatval($total_costo_new) <= 0) {
        $total_costo_new = 0;
    }
    if ($disponible_new > 0) {
        $estado_disponible = 1;
    } else {
        $estado_disponible = 2;
    }


    // cera todos los registros anteriores
    $consulta = "
    update depositos_stock_costo_ppp
    set
    disponible = 0,
    estado_disponible = 2
    where
    idproducto = $idproducto
    and estado_disponible = 1
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // inserta nuevo registro
    $consulta = "
    INSERT INTO depositos_stock_costo_ppp
    (
        iddeposito, idproducto, cantidad, disponible, fecha_tanda,
        fecha_tanda_completa, costo, total_costo, estado_disponible, ficticio, idcompra, idproducido, idventa
    )
        VALUES
    (0, $idproducto, $disponible_new, $disponible_new, '$fecha_tanda_completa', '$fecha_tanda_completa', $costo_new, $total_costo_new, $estado_disponible, 0,0,0, $idventa);
    ";
    //echo $consulta;
    //exit;
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    return $costo_mov;

}
