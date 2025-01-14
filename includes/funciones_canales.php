 <?php

function validar_cambio_canal($parametros_array)
{
    global $conexion;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";

    $idcanal_nuevo = intval($parametros_array['idcanal_nuevo']);

    if (intval($parametros_array['idcanal_nuevo']) == 0) {
        $valido = "N";
        $errores .= " - El campo idcanal no puede estar vacio.<br />";
    }
    if (intval($parametros_array['idpedido']) == 0) {
        $valido = "N";
        $errores .= " - El campo idpedido no puede estar vacio.<br />";
    }
    if (intval($parametros_array['registrado_por']) == 0) {
        $valido = "N";
        $errores .= " - El campo registrado_por no puede estar vacio.<br />";
    }

    $idpedido = intval($parametros_array['idpedido']);




    $consulta = "
    select idcanal, idventa, estado, idsucursal from tmp_ventares_cab where idtmpventares_cab = $idpedido
    ";
    $rscan = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcanal_viejo = intval($rscan->fields['idcanal']);
    $idventa = intval($rscan->fields['idventa']);
    $estado = intval($rscan->fields['estado']);
    $idsucursal = intval($rscan->fields['idsucursal']);

    if ($idcanal_nuevo == $idcanal_viejo) {
        $valido = "N";
        $errores .= " - El canal nuevo no puede ser el mismo que el canal antiguo.<br />";
    }

    $cambio_a_realizar = $idcanal_viejo.'->'.$idcanal_nuevo;
    $cambios_permitidos = ['1->3','3->1']; // 1->3 carry a delivery  3->1 delivery a carry

    if (!in_array($cambio_a_realizar, $cambios_permitidos)) {
        $valido = "N";
        $errores .= " - El cambio de canal ($cambio_a_realizar) que intentas realizar no esta permitido.<br />";
    }
    if ($idventa > 0) {
        $valido = "N";
        $errores .= " - No se puede cambiar el canal por que el pedido ya fue facturado.<br />";
    }
    if ($estado <> 1) {
        $valido = "N";
        $errores .= " - No se puede cambiar el canal por que el pedido no esta activo.<br />";
    }


    // validaciones por cada caso
    // delivery a carry
    if ($cambio_a_realizar == '3->1') {


    }
    // carry a delivery
    if ($cambio_a_realizar == '1->3') {

        //$valido="N";
        //$errores.=" - El cambio de canal que intentas realizar no esta implementado.<br />";

        $idclientedel = intval($parametros_array['idclientedel']);
        $iddomicilio = intval($parametros_array['iddomicilio']);
        if ($idclientedel == 0) {
            $valido = "N";
            $errores .= " - El campo cliente delivery no puede estar vacio.<br />";
        }
        if ($iddomicilio == 0) {
            $valido = "N";
            $errores .= " - El campo direccion envio no puede estar vacio.<br />";
        }
        if ($parametros_array['llevapos'] != 'S' && $parametros_array['llevapos'] != 'N') {
            $valido = "N";
            $errores .= " - Debe indicar si lleva o no pos.<br />";
        }
        if ($parametros_array['llevapos'] == 'N') {
            if (floatval($parametros_array['cambio']) <= 0) {
                $valido = "N";
                $errores .= " - Debe indicar cambio de cuanto llevara si el pago sera en efectivo.<br />";
            }
        }
        // si pasa las validaciones de campos valida por bd
        if ($valido == 'S') {

            // valida que exista el cliente delivery (ya tuvo que venir de un formulario insertado anteriormente)
            $consulta = "SELECT idclientedel FROM cliente_delivery where estado <> 6 and idclientedel = $idclientedel";
            $rsclidel = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idclientedel = intval($rsclidel->fields['idclientedel']);
            if ($idclientedel == 0) {
                $valido = "N";
                $errores .= " - Cliente delivery enviado no existe.<br />";
            }
            // valida que exista el domicilio delivery (ya tuvo que venir de un formulario insertado anteriormente)
            $consulta = "SELECT iddomicilio, idclientedel FROM cliente_delivery_dom where estado <> 6 and iddomicilio = $iddomicilio";
            $rsclidom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idclientedel_dom = intval($rsclidom->fields['idclientedel']);
            $iddomicilio = intval($rsclidom->fields['iddomicilio']);
            if ($iddomicilio == 0) {
                $valido = "N";
                $errores .= " - Domicilio de delivery enviado no existe.<br />";
            }
            if ($idclientedel != $idclientedel_dom) {
                $valido = "N";
                $errores .= " - Cliente delivery enviado [$idclientedel] no pertenece al del domicilio [$idclientedel_dom].<br />";
            }

            // buscar el articulo delivery
            $consulta = "
            select zonas_delivery.idproducto_zona 
            from zonas_delivery 
            inner join productos on productos.idprod_serial = zonas_delivery.idproducto_zona
            inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial 
            where 
            idzonadel = (select idzonadel from cliente_delivery_dom where iddomicilio = $iddomicilio)
            and productos_sucursales.idsucursal = $idsucursal
            limit 1;
            ";
            //echo $consulta;exit;
            $rsprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idproducto = $rsprod->fields['idproducto_zona'];
            if (intval($idproducto) == 0) {
                $valido = "N";
                $errores .= "- La zona seleccionada no tiene un producto asignado.".$saltolinea;
            }

        }


    }


    //echo $errores;exit;

    return ["errores" => $errores,"valido" => $valido];
}

function registrar_cambio_canal($parametros_array)
{
    global $conexion;
    global $saltolinea;
    global $ahora;
    $idempresa = 1;

    // parametros estandar
    $idcanal_nuevo = antisqlinyeccion($parametros_array['idcanal_nuevo'], "int");
    $idpedido = antisqlinyeccion($parametros_array['idpedido'], "int");
    $registrado_por = antisqlinyeccion($parametros_array['registrado_por'], "int");



    // parametros delivery
    $idclientedel = antisqlinyeccion($parametros_array['idclientedel'], "int");
    $iddomicilio = antisqlinyeccion($parametros_array['iddomicilio'], "int");
    $llevapos = antisqlinyeccion($parametros_array['llevapos'], "text");
    $observacion_delivery = antisqlinyeccion($parametros_array['observacion_delivery'], "text");
    $cambio = antisqlinyeccion($parametros_array['cambio'], "text");



    $consulta = "
    select idcanal, idsucursal from tmp_ventares_cab where idtmpventares_cab = $idpedido
    ";
    $rscan = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcanal_viejo = $rscan->fields['idcanal'];
    $idsucursal = $rscan->fields['idsucursal'];

    $cambio_a_realizar = $idcanal_viejo.'->'.$idcanal_nuevo;


    $consulta = "
    insert into cambio_canal_log
    (idtmpventares_cab, idcanal_viejo, idcanal_nuevo, registrado_por, registrado_el)
    values
    ($idpedido, $idcanal_viejo, $idcanal_nuevo, $registrado_por, '$ahora')
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    // carry a delivery
    if ($cambio_a_realizar == '1->3') {
        //echo $cambio_a_realizar;exit;
        // insertar en el carrito el articulo delivery
        $consulta = "
        select zonas_delivery.idproducto_zona, precio, tipoiva
        from zonas_delivery 
        inner join productos on productos.idprod_serial = zonas_delivery.idproducto_zona
        inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial 
        where 
        idzonadel = (select idzonadel from cliente_delivery_dom where iddomicilio = $iddomicilio)
        and productos_sucursales.idsucursal = $idsucursal
        limit 1;
        ";
        //echo $consulta;exit;
        $rsprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idproducto = $rsprod->fields['idproducto_zona'];
        $precio = $rsprod->fields['precio'];
        $tipoiva = $rsprod->fields['tipoiva'];
        if (intval($idproducto) == 0) {
            $valido = "N";
            $errores = "- La zona seleccionada no tiene un producto asignado.".$saltolinea;
        }
        // agrega el nuevo articulo delivery al carrito
        $consulta = "
        INSERT INTO tmp_ventares
        (idtmpventares_cab, idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, finalizado, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2, subtotal, iva) 
        VALUES 
        ($idpedido, $idproducto, 6, 1, $precio, '$ahora',$registrado_por, 'S', 'N', $idsucursal, $idempresa, 'N', 'N', 'N', NULL, NULL, $precio, $tipoiva)
        ";
        //echo $consulta;exit;
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



        // actualiza monto cabecera
        $consulta = "
        update tmp_ventares_cab
        set 
        monto = (
            COALESCE
            (
                (
                    select sum(subtotal) as total_monto
                    from tmp_ventares
                    where
                    tmp_ventares.idsucursal = tmp_ventares_cab.idsucursal
                    and tmp_ventares.borrado = 'N'
                    and tmp_ventares.borrado_mozo = 'N'
                    and tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
                )
            ,0)
            
        )
        where
        idtmpventares_cab = $idpedido
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // actualiza cabecera
        $consulta = "
        update tmp_ventares_cab
        set
        delivery = 'S',
        idclientedel = $idclientedel,
        iddomicilio = $iddomicilio,
        llevapos = $llevapos, 
        observacion_delivery = $observacion_delivery,
        cambio = $cambio,
        delivery_costo = 0,
        delivery_zona = (select idzona from gest_zonas where estado <> 6 limit 1),
        ruc = (select cliente.ruc from cliente_delivery inner join cliente on cliente.idcliente = cliente_delivery.idcliente where idclientedel = $idclientedel),
        razon_social = (select cliente.razon_social from cliente_delivery inner join cliente on cliente.idcliente = cliente_delivery.idcliente where idclientedel = $idclientedel),
        nombre_deliv = (select nombres from cliente_delivery where idclientedel = $idclientedel),
        apellido_deliv = (select apellidos from cliente_delivery where idclientedel = $idclientedel),
        telefono = (select telefono from cliente_delivery where idclientedel = $idclientedel),
        direccion = (select direccion from cliente_delivery_dom where iddomicilio = $iddomicilio),
        url_maps = (select url_maps from cliente_delivery_dom where iddomicilio = $iddomicilio),
        latitud = (select latitud from cliente_delivery_dom where iddomicilio = $iddomicilio),
        longitud = (select longitud from cliente_delivery_dom where iddomicilio = $iddomicilio)
        where
        idtmpventares_cab = $idpedido
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
    // delivery a carry
    if ($cambio_a_realizar == '3->1') {
        // borra el articulo delivery idtipoproducto=6
        $consulta = "
        update tmp_ventares
        set
        borrado = 'S'
        where
        idtipoproducto=6
        and idtmpventares_cab = $idpedido
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // actualiza cabecera
        $consulta = "
        update tmp_ventares_cab
        set
        delivery = 'N',
        delivery_zona = NULL
        where
        idtmpventares_cab = $idpedido
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // actualiza cabecera chapa solo si esta vacio
        $consulta = "
        update tmp_ventares_cab
        set
        chapa = CONCAT(trim(nombre_deliv),' ',trim(apellido_deliv))
        where
        idtmpventares_cab = $idpedido
        and chapa is null
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // actualiza monto cabecera
        $consulta = "
        update tmp_ventares_cab
        set 
        monto = (
            COALESCE
            (
                (
                    select sum(subtotal) as total_monto
                    from tmp_ventares
                    where
                    tmp_ventares.idsucursal = tmp_ventares_cab.idsucursal
                    and tmp_ventares.borrado = 'N'
                    and tmp_ventares.borrado_mozo = 'N'
                    and tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
                )
            ,0)
            
        )
        where
        idtmpventares_cab = $idpedido
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }

    // por ultimo actualiza el canal
    $consulta = "
    update tmp_ventares_cab
    set
        idcanal=$idcanal_nuevo
    where
        idtmpventares_cab = $idpedido
        and estado = 1
        and idventa is null
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


}





?>
