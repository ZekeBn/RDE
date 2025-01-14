 <?php
/*---------------------------------------------------------
01/08/2022:
Se trabaja y mejora la edicion de pedidos
Se agregan telefono, email, lupa para visualizar los pedidos
25/01/23 Se corrige timestam en BD (scar los valores por defecto en los campos fecha para catering)

03/08/2023
if(($_GET['idsucu']) > 0){
    $idsuc=intval($_GET['idsucu']);
    $whereadd.=" and ventas.sucursal = $idsuc";

}
se agrega el filtro por sucursal, pero por permisos en nueva tabla de sucursales_permisos_paneles
sucursales_permisos_panel



-----------------------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");

// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "354";
require_once("includes/rsusuario.php");
require_once("includes/funciones_cobros.php");
require_once("includes/funciones_ventas.php");
require_once("includes/funciones_stock.php");
// comprobar existencia de deposito del tipo salon de ventas
$consulta = "
SELECT * 
FROM gest_depositos
where
tiposala = 2
and idempresa = $idempresa
and idsucursal = $idsucursal
and estado = 1
";
//echo $consulta;
$rsdep = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rsdep->fields['iddeposito']);
if ($iddeposito == 0) {
    $errores .= "No existe deposito de ventas asignado a la sucursal actual.";
    $valido = "N";
}

//Comprobar apertura de caja en fecha establecida
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal and tipocaja = 1 order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);
$idcaja_compartida = intval($rscaja->fields['idcaja_compartida']);
if ($idcaja_compartida > 0) {
    $idcaja = $idcaja_compartida;
}
if ($idcaja == 0) {
    $errores .= "- La caja debe estar abierta para poder realizar una venta.";
    $valido = "N";
}
if ($estadocaja == 3) {
    $errores .= "- La caja debe estar abierta para poder realizar una venta.";
    $valido = "N";
}
$descuento_glob = 0;
if ($descuento_neto > 0) {
    $descuento_glob = $descuento_neto;
}
$idcanal = 2;
//////////////////////////////////////////////
//listado de pedidos

$buscar = "
select 
desstock.*,pe.id_cliente_solicita as idclie,
cli.razon_social,cli.ruc, pe.id_cliente_sucu_pedido
 from tmp_eventos_descuento_stock desstock
inner join pedidos_eventos pe on pe.regid =desstock.idevento 
inner join cliente cli on cli.idcliente = pe.id_cliente_solicita
where 
desstock.idusu = $idusu
order by pe.regid asc
";
$rga = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rga->EOF) {
    $idevento = intval($rga->fields['idevento']);
    $nombreevento = $rga->fields['nombreevento'];
    $razon_social = $rga->fields['razon_social'];
    $ruc = $rga->fields['ruc'];
    $idclienteped = intval($rga->fields['idclie']);
    $idsucursal_clie_ped = intval($rga->fields['id_cliente_sucu_pedido']);
    if ($idevento == 0) {
        echo "No deberia entrar nunca en este condicional.";
        exit;
    }

    //cargamos en la carpeta tmp
    if ($idevento > 0) {
        $consulta = "
        INSERT INTO tmp_ventares_cab
        (razon_social, ruc, chapa, observacion, monto, idusu, fechahora, idsucursal, 
        idempresa,idcanal,delivery,idmesa,telefono,delivery_zona,direccion,llevapos,
        cambio,observacion_delivery,delivery_costo,iddomicilio,idclientedel,
        nombre_deliv,apellido_deliv,idatc,notificado, idclienteped, idsucursal_clie_ped, ideventoped) 
        VALUES 
        ('$razon_social', '$ruc', NULL, NULL, 0, $idusu, '$ahora', $idsucursal, 1,$idcanal,'N',0,
        NULL,0,NULL,NULL,0,NULL,0,NULL,NULL,NULL,NULL,0,'S',$idclienteped,$idsucursal_clie_ped,$idevento)
        ";
        $rsahora = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //echo $consulta;
        //exit;
        // buscar ultimo id insertado
        $consulta = "
        select idtmpventares_cab
        from tmp_ventares_cab
        where
        idusu = $idusu
        and idsucursal = $idsucursal
        order by idtmpventares_cab desc
        limit 1
        ";
        $rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idtmpventares_cab = $rscab->fields['idtmpventares_cab'];


        // vaciar carrito si hay algo precargado
        $consulta = "
        select * 
        from tmp_ventares 
        where
        usuario = $idusu
        and finalizado = 'N'
        and registrado = 'N'
        and idsucursal = $idsucursal
        and borrado = 'N'
        ;
        ";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // recorre borra
        while (!$rs->EOF) {

            $idventatmp = $rs->fields['idventatmp'];
            $idtmpventaresagregado = intval($rs->fields['idtmpventaresagregado']);

            // borra los detalles que contienen ese producto
            $consulta = "
            update tmp_ventares
            set borrado = 'S'
            where
            idventatmp = $idventatmp
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // borra los agregados relacionados al idventatmp principal
            $consulta = "
            update tmp_ventares
            set 
            borrado = 'S'
            where
            idventatmp_princ_delagregado = $idventatmp
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // borra los agregados de la tabla de agregados
            $consulta = "
            delete from tmp_ventares_agregado
            where
            idventatmp = $idventatmp
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            if ($idtmpventaresagregado > 0) {
                $consulta = "
                delete from tmp_ventares_agregado
                where
                idtmpventaresagregado = $idtmpventaresagregado
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }


            $rs->MoveNext();
        }


        // busca detalle
        $consulta = "
        select pedidos_eventos_detalles.idprodserial, pedidos_eventos_detalles.cantidad, pedidos_eventos_detalles.subtotal, pedidos_eventos_detalles.observacion, productos.idtipoproducto
        from pedidos_eventos_detalles
        inner join productos on productos.idprod_serial =  pedidos_eventos_detalles.idprodserial
        where
        idpedidocatering = $idevento
        ";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        while (!$rs->EOF) {

            $idproducto = $rs->fields['idprodserial'];
            $cantidad = floatval($rs->fields['cantidad']);
            //$subtotal=floatval($rs->fields['subtotal']);
            $subtotal = 0;
            //$precio_unitario=$subtotal/$cantidad;
            $precio_unitario = 0;
            $observacion = antisqlinyeccion($rs->fields['observacion'], "text");
            $idtipoproducto = $rs->fields['idtipoproducto'];
            $combinado = "'N'";

            // inserta en carrito
            $consulta = "
            INSERT INTO tmp_ventares
            (idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal,idlistaprecio,idpedidocat,idtmpventares_cab, observacion) 
            VALUES 
            ($idproducto, $idtipoproducto,$cantidad,$precio_unitario,'$ahora',$idusu,'N', $idsucursal, 1, 'N', 'N', $combinado, NULL, NULL,$subtotal,1,NULL,$idtmpventares_cab,
            $observacion)
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $rs->MoveNext();
        }



        // marcar como finalizado
        $consulta = "
        update tmp_ventares
        set 
        idtmpventares_cab = $idtmpventares_cab,
        finalizado = 'S',
        cocinado='S',
        retirado='S',
        impreso_coc='S'
        where
        usuario = $idusu
        and registrado = 'N'
        and borrado = 'N'
        and finalizado = 'N'
        and idsucursal = $idsucursal
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // validar venta
        $parametros_array_vta = [
            'idcaja' => $idcaja,
            'afecta_caja' => 'N',
            'iddeposito' => $iddeposito,
            'idtmpventares_cab' => $idtmpventares_cab,
            'idsucursal' => $idsucursal,
            'idempresa' => $idempresa,
            'factura_suc' => '',
            'factura_pexp' => '',
            'factura_nro' => '',
            'registrado_por' => $idusu,
            'idcliente' => $idclienteped,
            'idmoneda' => 1,
            'idvendedor' => '',
            'tipo_venta' => 1,
            'fecha' => $ahora,
            'descuento_factura' => 0,
            'vencimiento_factura' => '', // nuevo agregar a la funcion
            'detalle_agrupado' => 'N',

        ];
        //print_r($parametros_array_vta);exit;
        $res = validar_venta($parametros_array_vta);
        if ($res['valido'] == 'N') {
            $errores .= nl2br($res['errores']);
            $valido = "N";

            // anula el pedido
            $consulta = "
            update tmp_ventares_cab
            set 
            estado = 6
            where
            idtmpventares_cab = $idtmpventares_cab
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            echo $errores;
            exit;
        }

        // registra venta
        $idventa = registrar_venta($parametros_array_vta);
        if (intval($idventa) > 0) {

            // ventas relacionadas con pedidos previendo pago multiple
            $consulta = "
            INSERT INTO 
            ventas_pedidos_eventos
            (idevento, idventa, monto_aplicado) 
            select $idevento, idventa, totalcobrar
            from ventas
            where
            idventa = $idventa
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // recalcular saldo evento
            $parametros_array_evento['idevento'] = $idevento;
            actualiza_saldo_evento($parametros_array_evento);


            $consulta2 = "update pedidos_eventos set detallado = 2,descontado = 1 where regid=$idevento";
            $rdescuento = $conexion->Execute($consulta2) or die(errorpg($conexion, $consulta2));
            //Asignamos ya a los eventos que ya fueron procesados en el detalle del stock y marcamos el detallado en 2
            //y asignamos que ya fue descontado.

            //Eliminamos los archivos temporales de los pedidos
            $consulta = "
            delete from tmp_eventos_descuento_stock where idusu = $idusu and idevento = $idevento ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            // si todo finalizo correctamente
            $consulta = "
            update ventas set finalizo_correcto = 'S' where idventa = $idventa
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }

    }


    $rga->MoveNext();
}

$tpedidos = $rga->RecordCount();
$errores = $idventa;
$valido = "S";

$arr = [
'valido' => $valido,
'errores' => $errores
];

//print_r($arr);

// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada

echo $respuesta;
exit;
