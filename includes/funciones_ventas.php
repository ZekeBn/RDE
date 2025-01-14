 <?php
function validar_pedido($parametros_array)
{
    global $conexion;
    global $saltolinea;
    global $ahora;

    // inicializar
    $valido = "S";
    $errores = "";

    // recibe parametros
    $idtmpventares_cab = intval($parametros_array['idtmpventares_cab']);
    $idtmpventares_cab_old = intval($parametros_array['idtmpventares_cab_old']);
    $idmesa = intval($parametros_array['idmesa']); // opcional
    $idusu = intval($parametros_array['idusu']);
    $idsucursal = intval($parametros_array['idsucursal']);
    $idempresa = 1;

    $razon_social = trim(strtoupper($parametros_array['razon_social']));
    $ruc = trim(strtoupper($parametros_array['ruc']));
    $chapa = trim(strtoupper($parametros_array['chapa']));
    $observacion = trim(strtoupper($parametros_array['observacion']));
    $iddomicilio = intval($parametros_array['iddomicilio']);
    $idmozo = intval($parametros_array['idmozo']);
    $idcanal = intval($parametros_array['idcanal']);
    $idzona = intval($parametros_array['idzona']); // zona delivery esto va a desaparecer
    $diplomatico = $parametros_array['diplomatico'];

    //RUC PRED
    $consulta = "
    select ruc, razon_social from cliente where  estado = 1 and borrable = 'N' order by idcliente asc limit 1
    ";
    $rsclipred = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $ruc_pred = antixss($rsclipred->fields['ruc']);
    $razon_social_pred = antixss($rsclipred->fields['razon_social']);



    // si viene de mesa tablet y no es el primer registro
    if ($idtmpventares_cab_old > 0) {
        $consulta = "
        select 
        sum(cantidad) as total
        from tmp_ventares 
        where 
        tmp_ventares.idtmpventares_cab = $idtmpventares_cab_old
        and tmp_ventares.borrado='N'
        and tmp_ventares.finalizado = 'N'
        ";
        $rscartot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $totalprod = intval($rscartot->fields['total']);
    } else {
        $consulta = "
        select 
        sum(cantidad) as total
        from tmp_ventares 
        where 
        tmp_ventares.idtmpventares_cab = $idtmpventares_cab
        and tmp_ventares.borrado='N'
        and tmp_ventares.finalizado = 'N'
        ";
        $rscartot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $totalprod = intval($rscartot->fields['total']);
    }

    // validaciones
    if ($totalprod == 0) {
        $errores .= "-Debe agregar al menos 1 producto al carrito.".$saltolinea;
        $valido = "N";
    }
    if ($idtmpventares_cab == 0) {
        $valido = 'N';
        $errores .= '- Debes indicar la cabecera del pedido.'.$saltolinea;
    }
    if ($idcanal == 0) {
        $valido = 'N';
        $errores .= '- Debes indicar el canal.'.$saltolinea;
    }
    //echo $idcanal;
    //exit;

    if (trim($parametros_array['razon_social']) == '') {
        $errores .= "- Debe indicar la razon social.".$saltolinea;
        $valido = "N";
    }
    if (trim($parametros_array['ruc']) == '') {
        $errores .= "- Debe indicar el ruc.".$saltolinea;
        $valido = "N";
    }
    if ($diplomatico != 'S') {
        if ($ruc != $ruc_pred) {
            $ruc_ar = explode("-", $ruc);
            $ruc_pri = intval($ruc_ar[0]);
            $ruc_dv = intval($ruc_ar[1]);
            if ($ruc_pri <= 0) {
                $errores .= "- El ruc no puede ser cero o menor.".$saltolinea;
                $valido = "N";
            }
            if (strlen($ruc_dv) <> 1) {
                $errores .= "- El digito verificador del ruc no puede tener 2 numeros.".$saltolinea;
                $valido = "N";
            }
            if (calcular_ruc($ruc_pri) <> $ruc_dv) {
                $digitocor = calcular_ruc($ruc_pri);
                $errores .= "- El digito verificador del ruc no corresponde a la cedula el digito debia ser $digitocor para la cedula $ruc_pri.".$saltolinea;
                $valido = "N";
            }
        }
        if ($ruc == $ruc_pred && $razon_social <> $razon_social_pred) {
            $errores .= "- La Razon Social debe ser $razon_social_pred si el RUC es $ruc_pred.".$saltolinea;
            $valido = "N";
        }
        if ($ruc <> $ruc_pred && $razon_social == $razon_social_pred) {
            $errores .= "- El RUC debe ser $ruc_pred si la Razon Social es $razon_social_pred.".$saltolinea;
            $valido = "N";
        }
    }
    // validaciones condicionadas
    // si el CANAL es DELIVERY
    if ($idcanal == 3) {
        if ($idzona == 0) {
            $valido = 'N';
            $errores .= '- Debes indicar la zona de delivery.'.$saltolinea;
        }
        if (!$iddomicilio > 0) {
            $valido = 'N';
            $errores .= '- Debes indicar el domicilio.'.$saltolinea;
        }
    }
    // si el CANAL es MESA
    if ($idcanal == 4) {
        if ($idmesa == 0) {
            $valido = 'N';
            $errores .= '- Debes indicar la mesa.'.$saltolinea;
        }
        /*if(intval($idatc) == 0){
            $valido='N';
            $errores.='- No se genero el ATC.'.$saltolinea;
        }*/
        // busca si ya existe un tmp cab sin registrar para esta mesa
        $consulta = "
        select * 
        from tmp_ventares_cab 
        where 
        idmesa = $idmesa 
        and estado <> 6 
        and registrado = 'N'
        ";
        //echo $consulta;
        $rs_cab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($rs_cab->fields['idtmpventares_cab'] > 0) {
            if ($rs_cab->fields['idtmpventares_cab'] != $idtmpventares_cab) {
                $valido = 'N';
                $errores .= '- Ya existe otra cabecera activa para esta mesa.'.$saltolinea;
            }
        }
    }
    // si hay descuento obliga a poner motivo
    if ($descuento > 0) {
        if (trim($parametros_array['motivo_descuento']) == '') {
            $valido = 'N';
            $errores .= '- Debes indicar el motivo de descuento.'.$saltolinea;
        }
    }
    // si es una mesa no puede ser delivery
    if ($idmesa > 0) {
        if ($idzona > 0) {
            $valido = 'N';
            $errores .= '- No puedes enviar delivery a una mesa.'.$saltolinea;
        }
    }

    if ($iddomicilio > 0) {
        $buscar = "
        Select *, referencia 
        from cliente_delivery 
        inner join cliente_delivery_dom    on cliente_delivery.idclientedel=cliente_delivery_dom.idclientedel
        where 
        iddomicilio=$iddomicilio 
        and cliente_delivery.idempresa=$idempresa 
        limit 1
        ";
        $rscasa = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idclientedel = $rscasa->fields['idclientedel'];
        $iddomicilio = $rscasa->fields['iddomicilio'];
        $direccion = trim($rscasa->fields['direccion']);
        $direccion = str_replace("'", "", $direccion);
        if (trim($rscasa->fields['referencia']) != '') {
            $direccion .= ' - '.trim($rscasa->fields['referencia']);
        }
        $telefono = trim($rscasa->fields['telefono']);
        $nombre_deliv = antisqlinyeccion(trim($rscasa->fields['nombres']), 'text');
        $apellido_deliv = antisqlinyeccion(trim($rscasa->fields['apellidos']), 'text');
        if ($idzona == 0) {
            $valido = 'N';
            $errores .= '- Debes seleccionar la zona del delivery.<br />';
        }
    }
    // si es mesa y es la primera vez que registra solo en tablet
    if ($idtmpventares_cab_old > 0) {
        if ($idcanal != 4) {
            $valido = 'N';
            $errores .= '- No puedes cambiar la cabecera a un pedido que no es mesa.<br />';
        }

    }

    $res_array = [
        'valido' => $valido,
        'errores' => $errores
    ];

    return $res_array;
}
function registrar_pedido($parametros_array)
{
    global $conexion;
    global $saltolinea;
    global $ahora;

    // recibe parametros
    $idtmpventares_cab = intval($parametros_array['idtmpventares_cab']);
    $idtmpventares_cab_old = intval($parametros_array['idtmpventares_cab_old']);
    // recibe parametros
    $razon_social = antisqlinyeccion($parametros_array['razon_social'], "text");
    $ruc = antisqlinyeccion($parametros_array['ruc'], "text");
    $idclientedel = antisqlinyeccion($parametros_array['idclientedel'], "int");
    $iddomicilio = antisqlinyeccion($parametros_array['iddomicilio'], "int");
    $nombre_deliv = antisqlinyeccion($parametros_array['nombres'], "text");
    $apellido_deliv = antisqlinyeccion($parametros_array['apellidos'], "text");
    $direccion = antisqlinyeccion($parametros_array['direccion'], "text");
    $telefono = antisqlinyeccion($parametros_array['telefono'], "int");
    $llevapos = antisqlinyeccion($parametros_array['llevapos'], "text");
    $cambio = antisqlinyeccion($parametros_array['cambio'], "int");
    $observacion_delivery = antisqlinyeccion($parametros_array['observacion_delivery'], "text");
    $delivery_zona = antisqlinyeccion($parametros_array['delivery_zona'], "int");
    $delivery_costo = antisqlinyeccion($parametros_array['delivery_costo'], "int");
    $chapa = antisqlinyeccion($parametros_array['chapa'], "text");
    $observacion = antisqlinyeccion($parametros_array['observacion'], "text");
    //$monto=antisqlinyeccion($parametros_array['monto'],"int");
    $idusu = antisqlinyeccion($parametros_array['idusu'], "int");
    $fechahora = antisqlinyeccion($parametros_array['fechahora'], "text");
    //$finalizado=antisqlinyeccion('N',"text");
    //$cocinado=antisqlinyeccion('N',"text");
    ///$retirado=antisqlinyeccion('N',"text");
    //$registrado=antisqlinyeccion('N',"text");
    $fechahora_coc = antisqlinyeccion($parametros_array['fechahora_coc'], "text");
    $fechahora_reg = antisqlinyeccion($parametros_array['fechahora_reg'], "text");
    //$idventa=antisqlinyeccion($parametros_array['idventa'],"int");
    $idcanal = antisqlinyeccion($parametros_array['idcanal'], "int");
    //$anulado_por=antisqlinyeccion($parametros_array['anulado_por'],"int");
    //$anulado_el=antisqlinyeccion($parametros_array['anulado_el'],"text");
    //$anulado_idcaja=antisqlinyeccion($parametros_array['anulado_idcaja'],"int");
    $idmesa = antisqlinyeccion(intval($parametros_array['idmesa']), "int");
    //$idmesa_tmp=antisqlinyeccion($parametros_array['idmesa_tmp'],"int");
    //$impreso=antisqlinyeccion($parametros_array['impreso'],"text");
    //$ultima_impresion=antisqlinyeccion($parametros_array['ultima_impresion'],"text");
    //$tipoventa=antisqlinyeccion($parametros_array['tipoventa'],"int");
    $clase = antisqlinyeccion($parametros_array['clase'], "int");
    $idmozo = antisqlinyeccion(intval($parametros_array['idmozo']), "int");
    $iddelivery = antisqlinyeccion($parametros_array['iddelivery'], "int");
    $url_maps = antisqlinyeccion($parametros_array['url_maps'], "text");
    $latitud = antisqlinyeccion($parametros_array['latitud'], "text");
    $longitud = antisqlinyeccion($parametros_array['longitud'], "text");
    $idatc = antisqlinyeccion(intval($parametros_array['idatc']), "int");


    $idsucursal = antisqlinyeccion($parametros_array['idsucursal'], "int");
    $idempresa = antisqlinyeccion($parametros_array['idempresa'], "int");
    $factura_suc = antisqlinyeccion($parametros_array['factura_suc'], "int");
    $factura_pexp = antisqlinyeccion($parametros_array['factura_pexp'], "int");





    // conversiones
    // si el canal es delivery
    if ($idcanal == 3) {
        $delivery = antisqlinyeccion('S', "text");
    } else {
        $delivery = antisqlinyeccion('N', "text");
    }

    // canal mesa
    if ($idcanal == 4) {
        $parametros_array_atc = [
            'idmesa' => $idmesa,
            'idsucursal' => $idsucursal
        ];
        $idatc = intval(generar_atc_mesa($parametros_array_atc));
    }

    // actualiza detalle si proviene de una mesa tablet
    if ($idtmpventares_cab_old > 0) {
        $consulta = "
        update tmp_ventares 
        set 
        idtmpventares_cab = $idtmpventares_cab,
        idmesa = $idmesa 
        where 
        idtmpventares_cab = $idtmpventares_cab_old
        and registrado = 'N'
        and finalizado = 'N'
        and borrado = 'N'
        and usuario = $idusu
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }


    // ya viene insertado con el nuevo metodo, siempre se actualiza
    $consulta = "
    update tmp_ventares_cab
    set
        razon_social=$razon_social,
        ruc=$ruc,
        delivery=$delivery,
        idclientedel=$idclientedel,
        iddomicilio=$iddomicilio,
        nombre_deliv=$nombre_deliv,
        apellido_deliv=$apellido_deliv,
        direccion=$direccion,
        telefono=$telefono,
        llevapos=$llevapos,
        cambio=$cambio,
        observacion_delivery=$observacion_delivery,
        delivery_zona=$delivery_zona,
        delivery_costo=$delivery_costo,
        chapa=$chapa,
        observacion=$observacion,
        monto=0,
        idusu=$idusu,
        fechahora=$fechahora,
        idsucursal=$idsucursal,
        idempresa=$idempresa,
        idcanal=$idcanal,
        idmesa=$idmesa,
        idmozo=$idmozo,
        iddelivery=$iddelivery,
        idatc=$idatc
    where
    idtmpventares_cab = $idtmpventares_cab
    and registrado = 'N'
    and idventa is null
    and estado <> 6
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // actualizar montos en cabecera
    actualiza_cabecera_pedido($idtmpventares_cab);

    // generar tanda de pedido para cocina
    $idtmpventares_tan = generar_tanda_cocina($parametros_array);

    // asigna tanda al detalle
    $consulta = "
    update tmp_ventares
    set 
    finalizado = 'S',
    idtmpventares_tan = $idtmpventares_tan
    where
    idtmpventares_cab = $idtmpventares_cab
    and idtmpventares_tan is null
    and finalizado = 'N'
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // marcar como finalizado en tanda
    $consulta = "
    update tmp_ventares_tanda
    set
    detalle_completo = 'S'
    where
    idtmpventares_tan = $idtmpventares_tan
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    // arma respuesta
    $res_array = [
        'valido' => $valido,
        'errores' => $errores
    ];

    return $res_array;
}
function validar_venta($parametros_array)
{
    global $conexion;
    global $saltolinea;
    global $ahora;


    // inicializar
    $valido = "S";
    $valido_pri = "S";
    $errores = "";

    // parametros entrada
    $idcaja = intval($parametros_array['idcaja']);
    $iddeposito = intval($parametros_array['iddeposito']);
    $idtmpventares_cab = intval($parametros_array['idtmpventares_cab']);
    $idsucursal = intval($parametros_array['idsucursal']);
    $idempresa = 1;
    $factura_suc = intval($parametros_array['factura_suc']);
    $factura_pexp = intval($parametros_array['factura_pexp']);
    $factura_nro = intval($parametros_array['factura_nro']);
    $registrado_por = intval($parametros_array['registrado_por']);
    $idcliente = intval($parametros_array['idcliente']);
    $idmoneda = intval($parametros_array['idmoneda']);
    $idvendedor = intval($parametros_array['idvendedor']);
    $tipo_venta = intval($parametros_array['tipo_venta']); // 1 contado 2 credito
    $fecha = trim($parametros_array['fecha']);
    $descuento_sobre_factura = floatval($parametros_array['descuento_factura']);
    $detalle_agrupado = trim($parametros_array['detalle_agrupado']);
    $afecta_caja = substr(strtoupper(trim($parametros_array['afecta_caja'])), 0, 1);
    $detalle_pagos = $parametros_array['detalle_pagos']; // debe ser un array y solo es obligatorio cuando afecta_caja es S

    $consulta = "
    select factura_obliga from preferencias where idempresa = $idempresa
    ";
    $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $factura_obliga = $rspref->fields['factura_obliga'];

    // preferencias caja
    $consulta = "SELECT usa_motorista, obliga_motorista, valida_duplic_tipo FROM preferencias_caja WHERE  idempresa = $idempresa ";
    $rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $usa_motorista = trim($rsprefcaj->fields['usa_motorista']);
    $obliga_motorista = trim($rsprefcaj->fields['obliga_motorista']);
    $valida_duplic_tipo = trim($rsprefcaj->fields['valida_duplic_tipo']);
    /*
    se obtiene por bd
    $ruchacienda=intval($parametros_array['ruchacienda']);
    $dv=intval($parametros_array['dv']);
    $razon_social=antisqlinyeccion($parametros_array['razon_social'],'text');*/
    // conversiones
    if (intval($factura_nro) > 0) {
        $ticketofactura = "FAC";
    } else {
        $ticketofactura = "TK";
    }
    if ($afecta_caja == '') {
        $afecta_caja = "S";
    }
    // si es a credito no afecta caja
    if ($tipo_venta == 2) {
        $afecta_caja = "N";
    }

    // validaciones basicas
    if ($idsucursal == 0) {
        $valido = "N";
        $valido_pri = "N";
        $errores .= "- No envio la Sucursal.".$saltolinea;
    }
    if ($idempresa == 0) {
        $valido = "N";
        $valido_pri = "N";
        $errores .= "- No envio la Empresa.".$saltolinea;
    }
    if ($factura_obliga == 'S') {
        if ($factura_suc == 0) {
            $valido = "N";
            $valido_pri = "N";
            $errores .= "- No envio la Sucursal de la Factura.".$saltolinea;
        }
        if ($factura_pexp == 0) {
            $valido = "N";
            $valido_pri = "N";
            $errores .= "- No envio el Punto de Expedicion  de la Factura.".$saltolinea;
        }
        if ($factura_nro == 0) {
            $valido = "N";
            $valido_pri = "N";
            $errores .= "- No envio el Numero de Factura.".$saltolinea;
        }
    }
    if ($iddeposito == 0) {
        $valido = "N";
        $valido_pri = "N";
        $errores .= "- No se indico el deposito de ventas.".$saltolinea;
    }
    if ($idcaja == 0) {
        $valido = "N";
        $valido_pri = "N";
        $errores .= "- No se indico la caja.".$saltolinea;
    }
    if ($idtmpventares_cab == 0) {
        $valido = "N";
        $valido_pri = "N";
        $errores .= "- No se indico el codigo de pedido.".$saltolinea;
    }
    if ($registrado_por == 0) {
        $valido = "N";
        $valido_pri = "N";
        $errores .= "- No se indico el usuario que registro la venta.".$saltolinea;
    }
    if ($idcliente == 0) {
        $valido = "N";
        $valido_pri = "N";
        $errores .= "- No se indico el cliente.".$saltolinea;
    }
    if ($idmoneda == 0) {
        $valido = "N";
        $valido_pri = "N";
        $errores .= "- No se indico la moneda.".$saltolinea;
    }
    if ($tipo_venta != 1 && $tipo_venta != 2) {
        $valido = "N";
        $valido_pri = "N";
        $errores .= "- No se indico la condicion de venta (credito o contado).".$saltolinea;
    }
    if ($detalle_agrupado != 'S' && $detalle_agrupado != 'N') {
        $valido = "N";
        $valido_pri = "N";
        $errores .= "- No se indico si se agrupa el detalle de la venta.".$saltolinea;
    }
    if ($fecha == '') {
        $valido = "N";
        $valido_pri = "N";
        $errores .= "- No se indico la fecha de venta.".$saltolinea;
    }
    // si afecta caja
    if ($afecta_caja == 'S') {
        // calcula el total de pagos
        $total_sum_pagos = 0;
        foreach ($parametros_array['detalle_pagos'] as $pago) {
            //print_r($pago);exit;
            $total_sum_pagos += $pago['monto'];
        }
    }


    /*
    se obtiene por bd
    if($ruc_hacienda == 0){
        $valido="N";
        $valido_pri="N";
        $errores.="- No se indico el usuario que registro la venta.".$saltolinea;
    }
    if($dv == 0){
        $valido="N";
        $valido_pri="N";
        $errores.="- No se indico el usuario que registro la venta.".$saltolinea;
    }
    if(trim($parametros_array['razon_social']) == ''){
        $valido="N";
        $valido_pri="N";
        $errores.="- No se indico la razon social.".$saltolinea;
    }*/

    // si las validaciones primarias (que no consultan bd) estan correctas continua con validaciones BD
    if ($valido_pri == "S") {

        // preferencias
        $consulta = "SELECT * FROM preferencias WHERE  idempresa = $idempresa ";
        $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $ventas_nostock = trim($rspref->fields['ventas_nostock']);
        $mueve_stock = trim($rspref->fields['mueve_stock']);
        $autoimpresor = trim($rspref->fields['autoimpresor']);
        $ventacaj_impcocina = trim($rspref->fields['ventacaj_impcocina']);
        $usa_vendedor = $rspref->fields['usa_vendedor'];
        $obliga_vendedor = trim($rspref->fields['obliga_vendedor']);

        //Comprobar apertura de caja en fecha establecida
        $consulta = "
        Select * 
        from caja_super 
        where 
        estado_caja=1 
        and $idcaja = $idcaja 
        limit 1
        ";
        $rscaja = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcaja = intval($rscaja->fields['idcaja']);
        if ($idcaja == 0) {
            $errores .= "- La caja debe estar abierta para poder realizar una venta.".$saltolinea;
            $valido = "N";
        }

        // comprobar existencia de deposito del tipo salon de ventas
        $consulta = "
        SELECT * 
        FROM gest_depositos
        where
        tiposala = 2
        and idempresa = $idempresa
        and idsucursal = $idsucursal
        and estado = 1
        limit 1
        ";
        //echo $consulta;
        $rsdep = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $iddeposito = intval($rsdep->fields['iddeposito']);
        if ($iddeposito == 0) {
            $errores .= "No existe deposito de ventas asignado a la sucursal actual.".$saltolinea;
            $valido = "N";
        }

        // comprobar existencia de deposito del tipo salon de ventas
        $consulta = "
        SELECT * 
        FROM gest_depositos
        where
        tiposala = 2
        and idempresa = $idempresa
        and idsucursal = $idsucursal
        and iddeposito = $iddeposito
        and estado = 1
        limit 1
        ";
        //echo $consulta;
        $rsdep = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $iddeposito = intval($rsdep->fields['iddeposito']);
        if ($iddeposito == 0) {
            $errores .= "El deposito indicado no pertenece a la sucursal indicada.".$saltolinea;
            $valido = "N";
        }


        // valida vendedor dependiendo de la parametrizacion
        if ($usa_vendedor == 'S') {
            if ($obliga_vendedor == 'S') {
                if (intval($idvendedor) == 0) {
                    $valido = 'N';
                    $errores .= '- No indicaste el vendedor.<br />';
                }
            } else {
                $idvendedor = 0;
            }
        } else {
            $idvendedor = 0;
        }


        // si envio domicilio de delivery
        if ($iddomicilio > 0) {
            $buscar = "
            Select *, referencia 
            from cliente_delivery 
            inner join cliente_delivery_dom    on cliente_delivery.idclientedel=cliente_delivery_dom.idclientedel
            where 
            iddomicilio=$iddomicilio 
            and cliente_delivery.idempresa=$idempresa 
            limit 1
            ";
            $rscasa = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idclientedel = $rscasa->fields['idclientedel'];
            $iddomicilio = $rscasa->fields['iddomicilio'];
            $direccion = trim($rscasa->fields['direccion']);
            $direccion = str_replace("'", "", $direccion);
            if (trim($rscasa->fields['referencia']) != '') {
                $direccion .= ' - '.trim($rscasa->fields['referencia']);
            }
            $telefono = trim($rscasa->fields['telefono']);
            $nombre_deliv = antisqlinyeccion(trim($rscasa->fields['nombres']), 'text');
            $apellido_deliv = antisqlinyeccion(trim($rscasa->fields['apellidos']), 'text');
            if ($idzona == 0) {
                $valido = 'N';
                $errores .= '- Debes seleccionar la zona del delivery.'.$saltolinea;
            }
        }

        $timbradodatos = timbrado_tanda($factura_suc, $factura_pexp, $idempresa);
        $tipoimpreso = $timbradodatos['tipoimpreso'];
        if ($tipoimpreso != 'PRE') {
            $autoimpresor = 'S';
        } else {
            $autoimpresor = 'N';
        }

        //print_r($timbradodatos);exit;

        // si envio factura
        if ($ticketofactura == 'FAC') {



            // si es autoimpresor reemplaza el numero de factura enviada
            if ($autoimpresor == 'S') {
                // omite la factura escrita y fuerza la que debe ser segun bd
                $proxfactura = prox_factura_auto($factura_suc, $factura_pexp, $idempresa);

            } else {
                $proxfactura = $factura_nro;
            }
            $factura = antisqlinyeccion(trim(agregacero($factura_suc, 3).''.agregacero($factura_pexp, 3).''.agregacero($proxfactura, 7)), 'text');
            $timbradodatos = timbrado_tanda($factura_suc, $factura_pexp, $idempresa, $proxfactura);
            //echo $proxfactura;
            //print_r($timbradodatos);exit;
            $fac_nro = $proxfactura;
            $fac_suc = $factura_suc;
            $fac_pexp = $factura_pexp;
            $idtandatimbrado = $timbradodatos['idtanda'];
            $timbrado = intval($timbradodatos['timbrado']);
            $valido_hasta = $timbradodatos['valido_hasta'];
            $valido_desde = $timbradodatos['valido_desde'];
            $inicio_timbrado = $timbradodatos['inicio'];
            $fin_timbrado = $timbradodatos['fin'];
            if (intval($idtandatimbrado) == 0) {
                $valido = 'N';
                $errores .= '- No existe tanda de timbrado para este punto de expedicion.'.$saltolinea;
            }
            if ($valida_duplic_tipo == 'FT') {
                $whereaddfac = " and timbrado = $timbrado ";
            }
            $consulta = "
            select idventa, factura, fecha 
            from ventas
             where 
             factura = $factura 
             and estado <> 6 
             $whereaddfac
             limit 1;
            ";
            //echo $consulta;exit;
            $rsfacex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $fechafacex = date("d/m/Y", strtotime($rsfacex->fields['fecha']));
            if ($rsfacex->fields['idventa'] > 0) {
                $valido = 'N';
                $errores .= '- Ya existe otra factura con la misma numeracion: '.$factura.', registrada en fecha: '.$fechafacex.'.<br />';
            }
        }

        $consulta = "
        select * from cliente where idcliente = $idcliente limit 1
        ";
        $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $diplomatico = trim($rscli->fields['diplomatico']);
        $carnet_diplomatico = trim($rscli->fields['carnet_diplomatico']);
        // si es diplomatico
        if ($diplomatico == 'S') {
            // valida que envie el carnet
            if ($carnet_diplomatico == '') {
                $valido = "N";
                $errores .= "- No se indico el carnet de diplomatico, edite el cliente.".$saltolinea;
            }
        }
        // carrito
        $consulta = "
        select count(*) as total, sum(subtotal) as monto_total 
        from tmp_ventares 
        where 
        borrado = 'N' 
        and idtmpventares_cab = $idtmpventares_cab
        ";
        //echo $consulta;exit;
        $rscarr = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rscarr->fields['total']) == 0) {
            $valido = "N";
            $errores .= "- No se cargo ningun producto en el pedido.".$saltolinea;
        }

        if (floatval($descuento_sobre_factura) > 0) {
            // busca si existe el producto descuento debe estar borrado
            $consulta = "
            select idprod_serial from productos where idtipoproducto = 8 limit 1
            ";
            $rsdesc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idproducto_descuento = intval($rsdesc->fields['idprod_serial']);
            // si no existe crea
            if ($idproducto_descuento == 0) {
                $valido = 'N';
                $errores .= '- No existe el articulo descuento, ingrese a productos y se creara automaticamente.'.$saltolinea;
            }
        }
        $monto_global = floatval($rscarr->fields['monto_total']) - floatval($descuento_sobre_factura);

        // si afecta caja
        if ($afecta_caja == 'S') {
            // valida si la sumatoria es igual al total global
            if ($total_sum_pagos != $monto_global) {
                $total_sum_txt = formatomoneda($total_sum_pagos);
                $monto_global_txt = formatomoneda($monto_global);
                $valido = 'N';
                $errores .= "- La sumatoria de formas de pago ($total_sum_txt) no coincide con el total de la venta ($monto_global_txt).".$saltolinea;
            }
        }

        // iva
        /*$consulta="
        select count(*) as total
        from tmp_ventares
        where
        borrado = 'N'
        and idtmpventares_cab = $idtmpventares_cab
        and iva is null
        ";
        $rscarr=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
        if(intval($rscarr->fields['total']) > 0){
            $valido="N";
            $errores.="- Existen articulos que no tienen porcentaje de iva en el carrito.".$saltolinea;
        }*/
        if ($afecta_caja == 'S') {
            //echo "funcion de caja no terminada";
            //exit;
            //$carrito_pagos[0] = array('idformapago' => 1, 'monto' => 15000);
            //$carrito_pagos[1] = array('idformapago' => 4, 'monto' => 12000);
            $carrito_pagos = $parametros_array['detalle_pagos'];
            $parametros_array_caja = [
                'carrito_pagos' => $carrito_pagos,
                'idcaja' => $idcaja,
                'idsucursal' => $idsucursal,
                'fecha_pago' => $fecha,
            ];
            //print_r($parametros_array_caja);
            // validar caja
            $res = movimiento_caja_valida($parametros_array_caja);
            if ($res['valido'] == 'N') {
                $valido = "N";
                $errores .= $res['errores'];
            }
        }

    } // if($valido_pri == "S"){

    $res_array = [
        'valido' => $valido,
        'errores' => $errores
    ];

    return $res_array;
}
function registrar_venta($parametros_array)
{
    global $conexion;
    global $saltolinea;
    global $ahora;

    //print_r($parametros_array);exit;


    // preferencias
    $idempresa = 1;
    $consulta = "SELECT * FROM preferencias WHERE  idempresa = $idempresa ";
    $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $ventas_nostock = trim($rspref->fields['ventas_nostock']);
    $mueve_stock = trim($rspref->fields['mueve_stock']);
    $autoimpresor = trim($rspref->fields['autoimpresor']);
    $ventacaj_impcocina = trim($rspref->fields['ventacaj_impcocina']);
    $usa_vendedor = $rspref->fields['usa_vendedor'];
    $obliga_vendedor = trim($rspref->fields['obliga_vendedor']);


    // recibe parametros
    $idtmpventares_cab = antisqlinyeccion($parametros_array['idtmpventares_cab'], "int");
    $fecha = antisqlinyeccion($parametros_array['fecha'], "text");
    $idatc = antisqlinyeccion($parametros_array['idatc'], "int");
    $fechasola = antisqlinyeccion(date("Y-m-d", strtotime($parametros_array['fecha'])), "text");
    $idcaja = antisqlinyeccion($parametros_array['idcaja'], "int");
    $afecta_caja = substr(strtoupper(trim($parametros_array['afecta_caja'])), 0, 1);
    $idcliente = antisqlinyeccion($parametros_array['idcliente'], "int");
    $idclientedel = antisqlinyeccion($parametros_array['idclientedel'], "int");
    $iddomicilio = antisqlinyeccion($parametros_array['iddomicilio'], "int");
    $tipo_venta = antisqlinyeccion($parametros_array['tipo_venta'], "int");
    $idempresa = antisqlinyeccion(1, "int");
    $idsucursal = antisqlinyeccion($parametros_array['idsucursal'], "int");
    $total_venta = antisqlinyeccion($parametros_array['total_venta'], "int");
    //$idtransaccion=antisqlinyeccion($parametros_array['idtransaccion'],"int");
    $idventa = antisqlinyeccion($parametros_array['idventa'], "text");
    $trackid = antisqlinyeccion($parametros_array['trackid'], "text");
    $idsalida = antisqlinyeccion($parametros_array['idsalida'], "int");
    $registrado_por = antisqlinyeccion($parametros_array['registrado_por'], "int");
    $segurogs = antisqlinyeccion($parametros_array['segurogs'], "int");
    $otrosgs = antisqlinyeccion($parametros_array['otrosgs'], "int");
    $hojalevante = antisqlinyeccion($parametros_array['hojalevante'], "text");
    $numhoja = antisqlinyeccion($parametros_array['numhoja'], "int");
    $totaliva10 = antisqlinyeccion($parametros_array['totaliva10'], "int");
    $totaliva5 = antisqlinyeccion($parametros_array['totaliva5'], "int");
    $texe = antisqlinyeccion($parametros_array['texe'], "int");
    $idpedido = antisqlinyeccion($parametros_array['idpedido'], "int");
    $idmesa = antisqlinyeccion($parametros_array['idmesa'], "int");
    $factura = antisqlinyeccion($parametros_array['factura'], "text");
    $recibo = antisqlinyeccion($parametros_array['recibo'], "text");
    $idsucursal_clie = antisqlinyeccion($parametros_array['idsucursal_clie'], "int");

    //$descporc=antisqlinyeccion($parametros_array['descporc'],"int");
    $descneto = antisqlinyeccion(intval($parametros_array['descneto']), "int");
    //$total_cobrado=antisqlinyeccion($parametros_array['total_cobrado'],"int");
    $monto_iva = antisqlinyeccion($parametros_array['monto_iva'], "float");
    $formapago = antisqlinyeccion($parametros_array['formapago'], "int");
    $estado = 2;
    $deliv = antisqlinyeccion($parametros_array['deliv'], "text");
    //$totalcobrar=antisqlinyeccion($parametros_array['totalcobrar'],"int");
    $idmoneda = antisqlinyeccion($parametros_array['idmoneda'], "int");
    $tipoimpresion = antisqlinyeccion($parametros_array['tipoimpresion'], "int");
    $idvendedor = antisqlinyeccion(intval($parametros_array['idvendedor']), "int");
    $motivo_descuento = antisqlinyeccion($parametros_array['motivo_descuento'], "text");
    $idcanal = antisqlinyeccion($parametros_array['idcanal'], "int");
    $idzona = antisqlinyeccion($parametros_array['idzona'], "int");
    $operador_pedido = antisqlinyeccion($parametros_array['operador_pedido'], "int");
    $recibido = antisqlinyeccion($parametros_array['recibido'], "float");
    $vuelto = antisqlinyeccion($parametros_array['vuelto'], "float");
    $idadherente = intval($parametros_array['idadherente']);
    $idserviciocom = intval($parametros_array['idserviciocom']);
    $idmozo = antisqlinyeccion($parametros_array['idmozo'], "int");
    $iddelivery = antisqlinyeccion($parametros_array['iddelivery'], "int");
    $detalle_agrupado = antisqlinyeccion($parametros_array['detalle_agrupado'], "text");
    $idtandatimbrado = antisqlinyeccion($parametros_array['idtandatimbrado'], "int");
    $timbrado = antisqlinyeccion($parametros_array['timbrado'], "int");
    $factura_sucursal = antisqlinyeccion($parametros_array['factura_sucursal'], "int");
    $factura_puntoexpedicion = antisqlinyeccion($parametros_array['factura_puntoexpedicion'], "int");
    $impreso = antisqlinyeccion($parametros_array['impreso'], "text");
    $monto_recibido = floatval($parametros_array['monto_recibido']);
    $iddeposito = floatval($parametros_array['iddeposito']);

    $factura_suc = intval($parametros_array['factura_suc']);
    $factura_pexp = intval($parametros_array['factura_pexp']);
    $factura_nro = intval($parametros_array['factura_nro']);
    $vencimiento_factura = trim($parametros_array['vencimiento_factura']);
    $detalle_pagos = $parametros_array['detalle_pagos'];


    $descuento_sobre_factura = $descneto;
    //$descuento_sobre_factura=floatval($parametros_array['descuento_factura']);
    //$descneto=$descuento_sobre_factura;
    //$parametros_array['descneto']=$descuento_sobre_factura;

    // conversiones
    if ($vencimiento_factura == '') {
        $vencimiento_factura = $parametros_array['fecha'];
    }
    if (intval($parametros_array['factura_nro']) > 0) {
        $ticketofactura = "FAC";
    } else {
        $ticketofactura = "TK";
    }
    if ($ticketofactura == "TK") {
        $factura = 'NULL';
        $factura_suc = '';
        $factura_pexp = '';
    } else {
        $factura = antisqlinyeccion(trim(agregacero($factura_suc, 3).'-'.agregacero($factura_pexp, 3).'-'.agregacero($factura_nro, 7)), 'text');
    }
    //echo $ticketofactura;exit;
    //echo $factura;exit;



    // si envio factura
    if ($ticketofactura == 'FAC') {

        $timbradodatos = timbrado_tanda($factura_suc, $factura_pexp, $idempresa);
        $tipoimpreso = $timbradodatos['tipoimpreso'];
        if ($tipoimpreso != 'PRE') {
            $autoimpresor = 'S';
        } else {
            $autoimpresor = 'N';
        }

        // si es autoimpresor reemplaza el numero de factura enviada
        if ($autoimpresor == 'S') {
            // omite la factura escrita y fuerza la que debe ser segun bd
            $proxfactura = prox_factura_auto($factura_suc, $factura_pexp, $idempresa);
            //$factura=antisqlinyeccion(trim(agregacero($factura_suc,3).agregacero($factura_pexp,3).agregacero($proxfactura,7)),'text');
        } else {
            $fac_nro = $parametros_array['factura_nro'];
            $proxfactura = $fac_nro;
        }
        $factura = antisqlinyeccion(trim(agregacero($factura_suc, 3).agregacero($factura_pexp, 3).agregacero($proxfactura, 7)), 'text');
        $timbradodatos = timbrado_tanda($factura_suc, $factura_pexp, $idempresa, $proxfactura);

        // omite la factura escrita y fuerza la que debe ser segun bd
        //$proxfactura=prox_factura_auto($factura_suc,$factura_pexp,$idempresa);
        //$timbradodatos=timbrado_tanda($factura_suc,$factura_pexp,$idempresa,$proxfactura);
        $factura = antisqlinyeccion(trim(agregacero($factura_suc, 3).''.agregacero($factura_pexp, 3).''.agregacero($proxfactura, 7)), 'text');
        $fac_nro = $proxfactura;
        $fac_suc = $factura_suc;
        $fac_pexp = $factura_pexp;
        $idtandatimbrado = $timbradodatos['idtanda'];
        $timbrado = $timbradodatos['timbrado'];
        $valido_hasta = $timbradodatos['valido_hasta'];
        $valido_desde = $timbradodatos['valido_desde'];
        $inicio_timbrado = $timbradodatos['inicio'];
        $fin_timbrado = $timbradodatos['fin'];
        //print_r($timbradodatos);
        //exit;
        if (intval($idtandatimbrado) == 0) {
            $valido = 'N';
            $errores .= '- No existe tanda de timbrado para este punto de expedicion.<br />';
            echo $errores;
            exit;
        }

    }



    // datos de la bd
    $consulta = "
    select * 
    from cliente 
    where 
    idcliente = $idcliente
    limit 1
    ";
    $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $ruc = trim($rscli->fields['ruc']);
    $razon_social = trim($rscli->fields['razon_social']);
    $diplomatico = antisqlinyeccion(trim($rscli->fields['diplomatico']), "text");
    $carnet_diplomatico = antisqlinyeccion(trim($rscli->fields['carnet_diplomatico']), "text");
    // si es diplimatico
    if (trim($rscli->fields['diplomatico']) == 'S') {
        // construye para enviar a la funcion
        $parametros_array = [
            'idatc' => 0,
            'idtmpventares_cab' => $idtmpventares_cab,
            'diplo' => 'S',
            'idempresa' => 1,
            'idsucursal' => $idsucursal

        ];
        // exonera el iva en el pedido
        $res = diplomatico_preticket($parametros_array);
    }


    //si no envio sucursal del cliente
    if (intval($idsucursal_clie) == 0) {
        $consulta = "
        select idsucursal_clie 
        from sucursal_cliente 
        where 
        idcliente = $idcliente 
        and estado = 1 
        order by idsucursal_clie asc 
        limit 1
        ";
        $rssuccli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idsucursal_clie = antisqlinyeccion($rssuccli->fields['idsucursal_clie'], "int");
    }

    // temporal
    $ruc_ar = explode("-", $ruc);
    $ruchacienda = intval($ruc_ar[0]);
    $dv = intval($ruc_ar[1]);
    // temporal

    // conversiones
    // para evitar division by 0 error
    if ($parametros_array['total_venta'] > 0) {
        $descporc = floatval($parametros_array['descneto'] / $parametros_array['total_venta']);
    } else {
        $descporc = 0;
    }
    $total_cobrado = floatval($parametros_array['total_venta'] - $parametros_array['descneto']);
    $totalcobrar = floatval($parametros_array['total_venta'] - $parametros_array['descneto']);
    $vuelto = floatval($monto_recibido - $totalcobrar);

    // codigo unico, por si se inserta 2 veces al mismo tiempo con mismo usuario para eviar duplicidad
    $codtran = md5(date("YmdHis").$idusu.rand(5, 15));
    $dv = intval(substr($dv, 0, 1));

    //echo $factura;exit;
    // insertar cabecera de ventas
    $consulta = "
    insert into ventas
    (
    idatc, fecha, fechasola, idcaja, idcliente, idclientedel, iddomicilio, tipo_venta, idempresa, sucursal, total_venta, 
    idtransaccion, trackid, idsalida, registrado_por, segurogs, otrosgs, hojalevante, numhoja, totaliva10, 
    totaliva5, texe, idpedido, idmesa, factura, recibo, ruc, ruchacienda, dv, razon_social, descporc, descneto, total_cobrado, 
    monto_iva, formapago, estado, deliv, totalcobrar, anulado_por, anulado_el, moneda, tipoimpresion, vendedor, obs, 
    idcanal, idzona, operador_pedido, recibido, vuelto, idadherente, idserviciocom, idmozo, iddelivery, detalle_agrupado, 
    idtandatimbrado, timbrado, factura_sucursal, factura_puntoexpedicion, impreso, diplomatico, carnet_diplomatico,
    finalizo_correcto, codtran, idsucursal_clie
    )
    select 
    idatc, $fecha, $fechasola, $idcaja , $idcliente, tmp_ventares_cab.idclientedel, 
    tmp_ventares_cab.iddomicilio, $tipo_venta, $idempresa, $idsucursal, tmp_ventares_cab.monto, 
    0, 0, NULL, $registrado_por, 0, 0, NULL, NULL, 0, 0, 0, idtmpventares_cab, 
    idmesa, $factura, NULL, '$ruc', $ruchacienda, $dv, '$razon_social', $descporc, 
    $descneto, $total_cobrado, 0 as monto_iva, 0 as formapago, 1, NULL as deliv, $totalcobrar, 0, 
    NULL, $idmoneda,  1, $idvendedor, $motivo_descuento, tmp_ventares_cab.idcanal, $idzona, tmp_ventares_cab.idusu, 
    $monto_recibido, $vuelto, $idadherente, $idserviciocom, $idmozo, $iddelivery, $detalle_agrupado, 
    $idtandatimbrado, $timbrado, $factura_sucursal, $factura_puntoexpedicion, 'N', 
    $diplomatico, $carnet_diplomatico, 'N' as finalizo_correcto, '$codtran', $idsucursal_clie
    from tmp_ventares_cab
    where
    idtmpventares_cab = $idtmpventares_cab
    limit 1
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    // obtiene el idventa insertado
    $consulta = "
    select idventa 
    from ventas 
    where 
    registrado_por = $registrado_por 
    and codtran = '$codtran'
    order by idventa desc 
    limit 1
    ";
    $rsidv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idventa = intval($rsidv->fields['idventa']);

    if ($diplomatico == 'S') {
        $idtipoiva_sql = " 0, ";
        $tipoiva_sql = " 0, ";
    } else {
        $idtipoiva_sql = " COALESCE((select idtipoiva from productos where idprod_serial = tmp_ventares.idproducto),0), ";
        $tipoiva_sql = "COALESCE((select tipoiva from productos where idprod_serial = tmp_ventares.idproducto),0), ";
    }


    // insertar detalle de ventas
    $consulta = "
    insert into ventas_detalles
    (
    idventa, idventatmp, cantidad, pventa, subtotal,  idemp, sucursal, idprod, 
    pchar, 
    retirado_por, costo, 
    subtotal_costo, utilidad, 
    idtipoiva, 
    iva, 
    registrado_el, descuento, subtotal_sindesc, descuento_porc, subtotal_monto_iva, 
    idprod_mitad1, idprod_mitad2, existencia, serialcostos, subtotal_monto_iva_excluido_diplomatico, 
    iva_excluido_diplomatico, subtotal_anterior_diplomatico, pventa_anterior_diplomatico, pventa_monto_iva_anterior, 
    cortesia, rechazo, idmotivorecha,
    id_forma_afectacion_tributaria_iva
    )
    select 
    $idventa, idventatmp, cantidad, precio, subtotal, $idempresa, $idsucursal, idproducto, 
    CASE WHEN 
        idtipoproducto = 7
    THEN
        observacion
    ELSE
        NULL
    END as pchar,  
    NULL, 0 as costo,
    0 as subtotal_costo, 0 as utilidad, 
    $idtipoiva_sql
    $tipoiva_sql
    '$ahora', descuento, 0 as subtotal_sindesc, 0 as descuento_porc, 
    0 as subtotal_monto_iva, idprod_mitad1, idprod_mitad2, 0, 0, COALESCE(monto_iva_sindiplo,0), 
    COALESCE(iva_sindiplo,0), COALESCE(subtotal_sindiplo,0), COALESCE(precio_sindiplo,0),
     COALESCE(monto_iva_unit_sindiplo,0), cortesia, rechazo, idmotivorecha,
    COALESCE((                    
        select id_forma_afectacion_tributaria_iva
        from tipo_iva 
        inner join productos on productos.idtipoiva = tipo_iva.idtipoiva
        where 
        idprod_serial = tmp_ventares.idproducto
    ),0) as id_forma_afectacion_tributaria_iva
    from tmp_ventares 
    where
    idtmpventares_cab = $idtmpventares_cab
    and borrado = 'N'
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
    update ventas 
    set
    total_venta = COALESCE((select sum(subtotal) from ventas_detalles where idventa = ventas.idventa),0),
    totalcobrar = COALESCE((select sum(subtotal) from ventas_detalles where idventa = ventas.idventa),0)-descneto,
    total_cobrado = COALESCE((select sum(subtotal) from ventas_detalles where idventa = ventas.idventa),0)-descneto
    where
    idventa = $idventa
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // recorremos ventas detalles para generar el impuesto
    $consulta = "
    Select  idventadet, idtipoiva, idprod, subtotal
    from ventas_detalles
    where
    idventa=$idventa 
    order by idventadet asc
    ";
    $rsvdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    while (!$rsvdet->EOF) {
        $idventadet = intval($rsvdet->fields['idventadet']);
        $idtipoiva = intval($rsvdet->fields['idtipoiva']);
        $idprod = intval($rsvdet->fields['idprod']);
        $subtotal = floatval($rsvdet->fields['subtotal']);
        // calcular base imponible
        if ($rscli->fields['diplomatico'] != 'S') {
            if (intval($idtipoiva) == 0) {
                $idtipoiva = 1;
            }
            $consulta = "
            select idtipoiva, iva_porc, iva_describe, iguala_compra_venta, estado, hab_compra, hab_venta
            from tipo_iva 
            where
            idtipoiva = $idtipoiva
            ";
            $rsbimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $iva_porc = floatval($rsbimp->fields['iva_porc']); // alicuota
            $subtotal_monto_iva = calcular_iva($iva_porc, $subtotal);
            $base_imponible = $subtotal - $subtotal_monto_iva;
        } else {
            $iva_porc = 0; // alicuota
            $subtotal_monto_iva = calcular_iva($iva_porc, $subtotal);
            $base_imponible = $subtotal - $subtotal_monto_iva;
            $tipoiva = 0;
            $idtipoiva = 3;
        }




        $consulta = "
        SELECT 
        idtipoiva, iva_porc, monto_porc, exento 
        FROM tipo_iva_detalle 
        WHERE 
        idtipoiva = $idtipoiva
        ";
        $rsivadet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        while (!$rsivadet->EOF) {

            $gravadoml = $base_imponible * ($rsivadet->fields['monto_porc'] / 100);
            $ivaml = $gravadoml * ($rsivadet->fields['iva_porc'] / 100);
            $exento = $rsivadet->fields['exento'];
            $iva_porc_col = $rsivadet->fields['iva_porc'];
            $monto_col = $gravadoml + $ivaml;

            //ventas_detalles_impuesto
            $consulta = "
            INSERT INTO ventas_detalles_impuesto
            (idventadet, idventa, idproducto, idtipoiva, iva_porc_col, monto_col,
            gravadoml,  ivaml,  exento) 
            VALUES 
            ($idventadet, $idventa, $idprod, $idtipoiva, $iva_porc_col, $monto_col,
            $gravadoml,  $ivaml,  '$exento'
            )
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $rsivadet->MoveNext();
        }
        $rsvdet->MoveNext();
    }

    // busca si existe el producto descuento debe estar borrado
    $consulta = "
    select idprod_serial from productos where idtipoproducto = 8 limit 1
    ";
    $rsdesc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idproducto_descuento = intval($rsdesc->fields['idprod_serial']);

    // prorratear descuento
    if ($descuento_sobre_factura > 0 && $idproducto_descuento > 0) {
        $descuento_negativo = $descuento_sobre_factura * -1;
        //echo $descuento_negativo.'a';exit;
        //$year_det_f=date("Y");
        $texto_descuento = "NULL";
        if (trim($pchar_descuento) != '') {
            $texto_descuento = trim($pchar_descuento);
        }

        // insertar 1 registro en ventas detalles
        $consulta = "
        INSERT INTO ventas_detalles
        (
        pventa, cantidad, subtotal, idventa, idemp, sucursal, idprod, pchar, 
        retirado_por, costo, subtotal_costo, utilidad, idtipoiva, iva, base_imponible,
        registrado_el, descuento, subtotal_sindesc, descuento_porc, subtotal_monto_iva,
        idprod_mitad1, idprod_mitad2, existencia, serialcostos,
        subtotal_monto_iva_excluido_diplomatico, iva_excluido_diplomatico,
        subtotal_anterior_diplomatico, pventa_anterior_diplomatico,
        pventa_monto_iva_anterior, cortesia, rechazo, idmotivorecha,
        idventatmp
        )        
        VALUES 
        (
        $descuento_negativo, 1, $descuento_negativo, $idventa, 1, $idsucursal, $idproducto_descuento, $texto_descuento,
        NULL, 0, 0, $descuento_negativo, 0, 0, 0,
        '$ahora', 0, $descuento_negativo, 0, 0,
        NULL, NULL, 0, 0,
        0, NULL,
        0, 0,
        0, NULL, NULL, NULL,
        NULL
        )
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
        select idventadet from ventas_detalles where idventa = $idventa order by idventadet desc limit 1 
        ";
        $rsvdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idventadet = $rsvdet->fields['idventadet'];

        $parametros_array = [
            'idventa' => $idventa,
            'descuento' => $descuento_sobre_factura,

        ];
        $res = prorratear_descuento_impuesto($parametros_array);
        //print_r($res);exit;
        // insertar en ventas detalles impuesto

        foreach ($res as $key => $value) {
            $iva_porc = $key;

            $monto_iva = $value['monto_iva'];
            $monto_col = $value['monto_columna'];
            $gravadoml = $monto_col - $monto_iva;
            if ($iva_porc == 0) {
                $exento = 'S';
            } else {
                $exento = 'N';
            }
            if (floatval($monto_col) <> 0) {
                $consulta = "
                INSERT INTO ventas_detalles_impuesto
                (idventadet, idventa, idproducto, idtipoiva, iva_porc_col, monto_col, gravadoml,
                 ivaml, exento) 
                VALUES 
                ($idventadet, $idventa, $idproducto_descuento, 4, $iva_porc, $monto_col, $gravadoml,
                 $monto_iva,  '$exento')
                ";
                //echo $consulta;exit;
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }

        }

    } /// if($descuento > 0 && $idproducto_descuento > 0){


    // si es contado genera el movimiento de caja
    if ($tipo_venta == 1) {
        if ($afecta_caja == 'S') {
            $parametros_array_caja = [
                'carrito_pagos' => $detalle_pagos,
                'idventa' => $idventa,
                'idcaja' => $idcaja,
                'idsucursal' => $idsucursal,
                'fecha_pago' => $parametros_array['fecha'],
                'idusu' => $registrado_por
            ];
            //print_r($parametros_array_caja);
            $res_caja = movimiento_caja_registra($parametros_array_caja);
            $idpago = $res_caja['idpago'];
        }
    }
    // si es a credito genera la operacion de credito
    if ($tipo_venta == 2) {
        $parametros_array_credito = [
            'vencimiento_factura' => $vencimiento_factura,
            'registrado_por' => $registrado_por,
            'idventa' => $idventa,
            'idadherente' => 0,
            'idserviciocom' => 0,
        ];
        generar_cuenta_credito($parametros_array_credito);
    }




    // busca los detalles insertados
    $consulta = "
    select * 
    from ventas_detalles 
    where 
    idventa = $idventa
    ";
    $rsvdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // recorre el detalle
    while (!$rsvdet->EOF) {
        $idproducto = $rsvdet->fields['idprod'];
        $idventatmp = intval($rsvdet->fields['idventatmp']);
        $idventadet = intval($rsvdet->fields['idventadet']);
        $cantidad = floatval($rsvdet->fields['cantidad']);
        //Paso 1): Traer la receta del producto
        $consulta = "Select * from recetas where idproducto='$idproducto' and recetas.idempresa = $idempresa";
        $rsre = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idreceta = intval($rsre->fields['idreceta']);
        //echo 'cant:'.$cantidad;exit;
        // si tiene receta
        if ($idreceta > 0) {
            //Lista de componenentes de la receta
            $consulta = "
            Select idreceta,idprod,ingrediente,cantidad,insumos_lista.idinsumo,costo,idmedida,tipoiva,
            insumos_lista.mueve_stock as mueve_stock_ins
             from recetas_detalles 
             inner join ingredientes on ingredientes.idingrediente=recetas_detalles.ingrediente
             inner join insumos_lista on insumos_lista.idinsumo=ingredientes.idinsumo
             where 
             recetas_detalles.idprod=$idproducto
            
             and recetas_detalles.idreceta=$idreceta
             and recetas_detalles.idempresa = $idempresa 
             and insumos_lista.idempresa = $idempresa
             and ingredientes.idingrediente not in (
                                                    select tmp_ventares_sacado.idingrediente 
                                                    from tmp_ventares_sacado
                                                    where
                                                    tmp_ventares_sacado.idventatmp = $idventatmp
                                                    and tmp_ventares_sacado.idproducto = $idproducto
                                                    )
             ";
            $rsrecu = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            //recorre ingredientes de la receta
            while (!$rsrecu->EOF) {


                $ir = intval($rsrecu->fields['idreceta']);
                $in = intval($rsrecu->fields['ingrediente']);
                $pp = intval($rsrecu->fields['idprod']);
                $insu = intval($rsrecu->fields['idinsumo']);
                $idmedida_med = $rsrecu->fields['idmedida'];
                $mueve_stock_ins = trim($rsrecu->fields['mueve_stock_ins']);
                if (floatval($rsrecu->fields['cantidad']) > 0) {
                    // cantidad receta por cantidad vendida
                    $cant = floatval($rsrecu->fields['cantidad']) * floatval($cantidad);
                } else {
                    // cantidad receta es cero entonces cantidad vendida
                    $cant = floatval($cantidad);
                }


                // descuenta stock general
                if ($mueve_stock != 'N' && $mueve_stock_ins != 'N') {
                    descontar_stock_general($insu, $cant, $iddeposito);
                    $costo_insumo_utilizado = descuenta_stock_vent($insu, $cant, $iddeposito);
                    movimientos_stock($insu, $cant, $iddeposito, 2, '-', $idventa, $ahora);
                    // costo unitario promedio de los insumos utilizados
                    $costo_insumo_unitario = round($costo_insumo_utilizado / $cant, 4);
                    // costo del insumo actual utilizado
                    $costo_acum_insu[$insu] = $costo_insumo_utilizado;
                    // costo acumulado de todos los insumos
                    $costo_acumulado += $costo_insumo_utilizado;
                } else {
                    $costo_insumo_utilizado = 0;
                    //$costo_acum_insu[$insu]=$costo_insumo_utilizado;
                    $costo_insumo_unitario = 0;
                    $costo_acumulado = 0;
                }


                // insertar receta de ventas
                $insertar = "Insert into venta_receta
                (idventa,idventadet,idprod,idingrediente,idinsumo,cantidad,idmedida,costo,
                costo_unitario,fechahora)
                values
                ($idventa,$idventadet,'$pp',$in,$insu,$cant,$idmedida_med,$costo_insumo_utilizado,
                $costo_insumo_unitario,'$ahora')";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));



                $rsrecu->MoveNext();
            }




        }


        // tablas de combos y costeo

        // tablas combinados  y costeo

        // tablas combinado extendido y costeo

        // tablas agregados




        //Sumar todos los costos de venta receta y updatear el costo global en detalles de la venta y utilidad
        $consulta = "
        update  venta_receta 
        set 
        costo_unitario = costo/cantidad
        where
        idventadet = $idventadet
        and idventa = $idventa
        and cantidad > 0
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
        update ventas_detalles 
        set 
        subtotal_costo = COALESCE((
                        select sum(costo) 
                        from venta_receta 
                        where 
                        idventadet = ventas_detalles.idventadet
                        ),0)
        WHERE
        idventadet = $idventadet
        and idventa = $idventa
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
        update ventas_detalles 
        set 
        costo = COALESCE(subtotal_costo/cantidad,0)
        WHERE
        idventadet = $idventadet
        and idventa = $idventa
        and cantidad > 0
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
        update ventas_detalles 
        set 
        utilidad = subtotal-subtotal_costo
        WHERE
        idventadet = $idventadet
        and idventa = $idventa
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




        $rsvdet->MoveNext();
    }

    // updates para factura
    $consulta = "
    UPDATE `ventas_detalles` set subtotal_monto_iva=(subtotal-((subtotal)/(1+iva/100))) where idventa = $idventa;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
    update ventas_detalles set subtotal_sindesc = subtotal+descuento where idventa = $idventa;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
    update ventas_detalles set descuento_porc = ((descuento/subtotal)*100) where descuento > 0 and idventa = $idventa;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
    update ventas set 
    razon_social = (select cliente.razon_social from cliente where cliente.idcliente = ventas.idcliente limit 1) 
    where 
    razon_social is null 
    and idventa = $idventa;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    // calcular totales de venta de detalle
    $consulta = "
    update ventas 
    set
    total_venta = COALESCE((select sum(subtotal) from ventas_detalles where idventa = ventas.idventa and subtotal > 0),0),
    totalcobrar = COALESCE((select sum(subtotal) from ventas_detalles where idventa = ventas.idventa and subtotal > 0),0)-descneto,
    total_cobrado = COALESCE((select sum(subtotal) from ventas_detalles where idventa = ventas.idventa and subtotal > 0),0)-descneto
    where
    idventa = $idventa
    ";
    //$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    // calcular el iva
    $consulta = "
    update ventas set 
    monto_iva = 
    COALESCE(( select sum(subtotal_monto_iva) from ventas_detalles where ventas_detalles.idventa = ventas.idventa ),0)
    
    -COALESCE((ventas.descneto/11),0)
    where
    idventa = $idventa;
    ;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // actualiza last comprobantes
    if ($factura_nro > 0) {
        $consulta = "
        update lastcomprobantes 
        set 
        numfac = $factura_nro,
        factura = $factura
        where 
        pe = $factura_pexp 
        and idsuc = $factura_suc
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }


    // pone idventa en tablas temporales
    $consulta = "
    update tmp_ventares_cab set idventa = $idventa, registrado='S', estado = 3, finalizado = 'S' where idtmpventares_cab = $idtmpventares_cab
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
    update tmp_ventares set idventa = $idventa, registrado = 'S' where idtmpventares_cab = $idtmpventares_cab and borrado = 'N'
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    // por seguridad marca como finalizado correctamente, en impresiones no debe imprimir si aca no finalizo bien
    $consulta = "
    update ventas set finalizo_correcto = 'S' where idventa = $idventa
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    return $idventa;
}
function actualiza_cabecera_pedido($idtmpventares_cab)
{

    global $conexion;
    global $saltolinea;
    global $ahora;

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
                            tmp_ventares.idempresa = tmp_ventares_cab.idempresa
                            and tmp_ventares.idsucursal = tmp_ventares_cab.idsucursal
                            and tmp_ventares.borrado = 'N'
                            and tmp_ventares.borrado_mozo = 'N'
                            and tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
                        )
                    ,0)
                    
                )
    WHERE
        idtmpventares_cab = $idtmpventares_cab
        and idventa is null
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}
function generar_tanda_cocina($parametros_array)
{
    global $saltolinea;
    global $ahora;
    global $conexion;

    // recibe parametros
    $idusu = $parametros_array['idusu'];
    $idcanal = $parametros_array['idcanal'];

    // codigo unico, por si se inserta 2 veces al mismo tiempo con mismo usuario para eviar duplicidad
    $codtran = date("YmdHis").$idusu.rand(5, 15);

    // generar
    $consulta = "
    INSERT INTO tmp_ventares_tanda 
    (fechahora, idusu, codtran, detalle_completo, idcanal)
    VALUES 
    ('$ahora', $idusu, $codtran, 'N', $idcanal);
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // obtiene el generado
    $consulta = "
    select max(idtmpventares_tan) as idtmpventares_tan
    from 
    tmp_ventares_tanda
    where
    idusu = $idusu
    and codtran = $codtran
    ";
    $rstan = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idtmpventares_tan = intval($rstan->fields['idtmpventares_tan']);


    return     $idtmpventares_tan;
}
function genera_cabecera_pedido($parametros_array)
{

    global $saltolinea;
    global $ahora;
    global $conexion;

    // recibe parametros
    $idusu = intval($parametros_array['idusu']);
    $idatc = intval($parametros_array['idatc']); // opcional
    $idcanal = intval($parametros_array['idcanal']);





    // busca si ya existe
    $consulta = "
    select * 
    from tmp_ventares_cab 
    where 
    idusu = $idusu 
    and finalizado = 'N' 
    and registrado = 'N'
    order by idtmpventares_cab desc 
    limit 1
    ";
    //echo $consulta;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idtmpventares_cab = intval($rs->fields['idtmpventares_cab']);
    //echo $idtmpventares_cab;
    //exit;
    // si no existe
    if ($idtmpventares_cab == 0) {
        // si envio usuario
        if ($idusu > 0) {
            $consulta = "
            INSERT INTO tmp_ventares_cab 
            (razon_social, ruc, delivery, idclientedel, iddomicilio, nombre_deliv, apellido_deliv, direccion, telefono, llevapos, cambio, observacion_delivery, delivery_zona, delivery_costo, chapa, observacion, monto, idusu, fechahora, finalizado, cocinado, retirado, registrado, fechahora_coc, fechahora_reg, idsucursal, idempresa, idventa, idcanal, estado, anulado_por, anulado_el, anulado_idcaja, idmesa, idmesa_tmp, impreso, ultima_impresion, tipoventa, clase, idmozo, iddelivery, url_maps, latitud, longitud, idatc) 
            VALUES 
            ('', '', 'N', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', $idusu, '$ahora', 'N', 'N', 'N', 'N', NULL, NULL, '1', '1', NULL, '1', '1', '0', NULL, NULL, '0', NULL, 'N', NULL, '0', '0', '0', NULL, NULL, NULL, NULL, $idatc);    
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // obtiene el insertado
            $consulta = "
            select * from tmp_ventares_cab where idusu = $idusu and finalizado = 'N' order by idtmpventares_cab desc limit 1
            ";
            //echo $consulta;
            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idtmpventares_cab = intval($rs->fields['idtmpventares_cab']);
        }

    }



    return $idtmpventares_cab;

}
function generar_atc_mesa($parametros_array)
{

    global $saltolinea;
    global $ahora;
    global $conexion;

    // inicializar
    $valido = "S";
    $errores = "";

    $idmesa = intval($parametros_array['idmesa']);
    $idsucursal = intval($parametros_array['idsucursal']);


    $consulta = "
    select idatc 
    from mesas_atc 
    where 
    estado = 1 
    and idmesa = $idmesa 
    and idsucursal = $idsucursal 
    order by idatc desc 
    limit 1
    ";
    $rsatc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idatc = intval($rsatc->fields['idatc']);
    // si no hay atc de mesas activas
    if ($idatc == 0) {
        // genero un atc de mesa activa
        $consulta = "
        INSERT INTO mesas_atc 
        (idmesa, idsucursal, fecha_inicio, cant_adultos, cant_ninos, cant_nopaga, idmozo, idgruporeserva, idgrupomesa, estado, fecha_pre_tickete, anulado_el, anulado_por, registrado_el, registrado_por) 
        VALUES 
        ($idmesa, $idsucursal, '$ahora', 0, 0, 0, $idusu, 0, 0, 1, NULL, NULL, NULL, '$ahora', $idusu);
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // busco el atc generado
        $consulta = "
        select max(idatc) as idatc 
        from mesas_atc 
        where 
        registrado_por = $idusu
        and estado = 1 
        order by idatc desc 
        limit 1
        ";
        $rsatc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idatc = intval($rsatc->fields['idatc']);

        // actualizo el estado de mesa a abierto
        $consulta = "
        update mesas set estado_mesa = 2 where idmesa = $mesa
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }

    return $idatc;
}
function agregar_carrito($parametros_array)
{

    global $saltolinea;
    global $ahora;
    global $conexion;

    // inicializar
    $valido = "S";
    $errores = "";

    // recibe parametros
    $idtmpventares_cab = intval($parametros_array['idtmpventares_cab']);
    $idproducto = intval($parametros_array['idproducto']);
    $cantidad = floatval($parametros_array['cantidad']);
    $precio = floatval($parametros_array['precio']); // opcional para idtipoproducto = 1
    $idmesa = intval($parametros_array['idmesa']); // opcional
    $idusu = intval($parametros_array['idusu']);
    $idsucursal = intval($parametros_array['idsucursal']);
    $idlistaprecio = intval($parametros_array['idlistaprecio']);
    $idempresa = 1;
    $subtotal = $precio * $cantidad;
    $combinado = "N";

    // si no envio la lista de precios
    if ($idlistaprecio == 0) {
        $idlistaprecio = 1;
    }


    // si no envio el precio
    if ($precio == 0) {

        // si la lista de precios no es la normal
        $seladd_lp = " productos_sucursales.precio ";
        if (intval($idlistaprecio) > 0) {
            $idlistaprecio = intval($_SESSION['idlistaprecio']);
            $joinadd_lp = " inner join productos_listaprecios on productos_listaprecios.idproducto = productos.idprod_serial ";
            $whereadd_lp = "
            and productos_listaprecios.idsucursal = $idsucursal 
            and productos_listaprecios.idlistaprecio = $idlistaprecio 
            and productos_listaprecios.estado = 1
            ";
            $seladd_lp = " productos_listaprecios.precio ";
        }

        // PRECIO
        $consulta = "
        select productos.idprod_serial,productos.idmedida, productos.idtipoproducto, $seladd_lp as precio
        from productos 
        inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
        $joinadd_lp
        where
        productos.idprod_serial = $idproducto
        and productos.borrado = 'N'
        
        and productos_sucursales.idsucursal = $idsucursal 
        and productos_sucursales.activo_suc = 1
        
        $whereadd_lp
        
        order by productos.descripcion asc
        ";
        $rsprecio = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $precio = floatval($rsprecio->fields['precio']);
        $subtotal = $precio * $cantidad;

    } // if($precio == 0){

    // temporal esto luego va a desaparecer por que se va a insertar 2 registros en 0.5 en carrito
    $idprod_mitad1 = intval($parametros_array['idprod_mitad1']);
    $idprod_mitad2 = intval($parametros_array['idprod_mitad2']);
    if ($idprod_mitad1 > 0 && $idprod_mitad2 > 0) {
        $combinado = "S";
    }
    // temporal esto luego va a desaparecer por que se va a insertar 2 registros en 0.5 en carrito


    // tipoproducto, borrado e iva
    $consulta = "
    select idtipoproducto, borrado, tipoiva from productos where idprod_serial = $idproducto limit 1
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idtipoproducto = intval($rs->fields['idtipoproducto']);
    $borrado = trim($rs->fields['borrado']);
    $tipoiva = intval($rs->fields['tipoiva']);

    // conversion combinado
    $idempresa = 1;


    // validaciones
    if ($idproducto <= 0) {
        $valido = "N";
        $errores .= "-Debe indicar el producto.".$saltolinea;
    }
    if ($cantidad <= 0) {
        $valido = "N";
        $errores .= "-Debe indicar la cantidad.".$saltolinea;
    }
    // el precio si puede ser cero, pero no menor o vacio
    if ($precio < 0 or trim($parametros_array['precio']) == '') {
        $valido = "N";
        $errores .= "-Debe indicar un precio valido.".$saltolinea;
    }
    // la mesa es opcional pero si envia no puede ser negativo
    if ($idmesa < 0) {
        $valido = "N";
        $errores .= "-Debe indicar la mesa.".$saltolinea;
    }
    if ($borrado != 'N') {
        $valido = "N";
        $errores .= "-Esta intentando agregar un producto borrado.".$saltolinea;
    }

    if ($valido == 'S') {


        $consulta = "
        INSERT INTO tmp_ventares
        (idtmpventares_cab, idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2, subtotal, iva) 
        VALUES 
        ($idtmpventares_cab, $idproducto, $idtipoproducto, $cantidad, $precio, '$ahora',$idusu, 'N', $idsucursal, $idempresa, 'N', 'N', '$combinado', $idprod_mitad1, $idprod_mitad2, $subtotal, $tipoiva)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


    $res_array = [
        'valido' => $valido,
        'errores' => $errores
    ];


    return $res_array;
}
function agregar_carrito_codbar($parametros_array)
{

    global $saltolinea;
    global $ahora;
    global $conexion;

    // inicializar
    $valido = "S";
    $errores = "";

    // recibe parametros
    $barcode = trim($parametros_array['barcode']);
    $cantidad = floatval($parametros_array['cantidad']);
    $precio = floatval($parametros_array['precio']);
    $idmesa = intval($parametros_array['idmesa']); // opcional
    $idusu = intval($parametros_array['idusu']);
    $idsucursal = intval($parametros_array['idsucursal']);
    $idempresa = 1;

    // obtiene el idproducto
    $consulta = "
    select * from productos where barcode = '$barcode'
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $parametros_array['idproducto'] = intval($rs->fields['idproducto']);

    if (trim($parametros_array['barcode']) == '') {
        $valido = "N";
        $errores .= "-No envio el codigo de barras.".$saltolinea;
    }


    // agrega al carrito
    $res = agregar_carrito($parametros_array);
    if ($res['valido'] == 'N') {
        $valido = "N";
        $errores .= $res['errores'];
    }

    // arma respuesta
    $res_array = [
        'valido' => $valido,
        'errores' => $errores
    ];

    // respuesta
    return $res_array;

}
function agregar_carrito_codplu($parametros_array)
{

    global $saltolinea;
    global $ahora;
    global $conexion;

    // inicializar
    $valido = "S";
    $errores = "";

    // recibe parametros
    $codplu = trim($parametros_array['codplu']);
    $cantidad = floatval($parametros_array['cantidad']);
    $precio = floatval($parametros_array['precio']);
    $idmesa = intval($parametros_array['idmesa']); // opcional
    $idusu = intval($parametros_array['idusu']);
    $idsucursal = intval($parametros_array['idsucursal']);
    $idempresa = 1;

    // obtiene el idproducto
    $consulta = "
    select * from productos where codplu = $codplu
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $parametros_array['idproducto'] = intval($rs->fields['idproducto']);

    if (trim($parametros_array['codplu']) == '') {
        $valido = "N";
        $errores .= "-No envio el codigo plu.".$saltolinea;
    }


    // agrega al carrito
    $res = agregar_carrito($parametros_array);
    if ($res['valido'] == 'N') {
        $valido = "N";
        $errores .= $res['errores'];
    }

    // arma respuesta
    $res_array = [
        'valido' => $valido,
        'errores' => $errores
    ];

    // respuesta
    return $res_array;

}
function agregar_carrito_combinado_ext($parametros_array)
{

    global $saltolinea;
    global $ahora;
    global $conexion;

    // inicializar
    $valido = "S";
    $errores = "";

    // recibe parametros
    $idproducto_principal = $parametros_array['idproducto_principal'];
    $idusu = intval($parametros_array['idusu']);
    $idsucursal = intval($parametros_array['idsucursal']);
    $idempresa = 1;

    // busca producto principal para ver si pertenece a la empresa
    $consulta = "
    select * 
    from productos 
    where 
    idempresa = $idempresa 
    and idtipoproducto = 4
    and idprod_serial = $idproducto_principal
    and productos.borrado = 'N'
    order by 
    descripcion asc
    limit 1
    ";
    $rsprod2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cat_princ = $rsprod2->fields['idcategoria'];
    $idtipoproducto = $rsprod2->fields['idtipoproducto'];
    $idprod_serial = $rsprod2->fields['idprod_serial'];
    $cantidadmax = $rsprod2->fields['combinado_maxitem'];
    $combinado_tipoprecio = $rsprod2->fields['combinado_tipoprecio'];
    if (intval($rsprod2->fields['idprod_serial']) == 0) {
        $valido = "N";
        $errores .= "-Producto principal inexistente.".$saltolinea;
    }



    // busca si inserto y devuelve la cantidad de ese producto para esa lista para esa empresa para ese usuario y sucursal
    $consulta = "
    select count(*) as total
    from tmp_combinado_listas
    where
    tmp_combinado_listas.idproducto_principal = $prodprinc
    and tmp_combinado_listas.idsucursal = $idsucursal
    and tmp_combinado_listas.idusuario = $idusu
    and tmp_combinado_listas.idempresa = $idempresa
    and tmp_combinado_listas.idventatmp is null
    ";
    $rsinsertado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $totalbd_part = intval($rsinsertado->fields['total']);

    // cantidad total agregada
    $totagregado = intval($rsinsertado->fields['total']);

    // verifica si se agrego el maximo
    if ($cantidadmax != $totagregado) {
        $valido = "N";
        $errores .= "-Ya se llego al maximo en este grupo.".$saltolinea;
    }
    if ($combinado_tipoprecio != 1 && $combinado_tipoprecio != 2 && $combinado_tipoprecio != 3) {
        $valido = "N";
        $errores .= "-No se indico el tipo de precio a usar.".$saltolinea;
    }

    if ($valido == "S") {
        // precio promedio
        if ($combinado_tipoprecio == 1) {
            $consulta = "            
            select *, 
            (sum(
                (
                select productos_sucursales.precio as p1 
                from productos
                inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial 
                where 
                borrado = 'N' 
                and idprod_serial = tmp_combinado_listas.idproducto_partes 
                and idtipoproducto = 1
                and productos_sucursales.idsucursal = $idsucursal
                limit 1
                )
            )/$cantidadmax) as promedio
            from tmp_combinado_listas
            where
            tmp_combinado_listas.idproducto_principal = $prodprinc
            and tmp_combinado_listas.idsucursal = $idsucursal
            and tmp_combinado_listas.idusuario = $idusu
            and tmp_combinado_listas.idempresa = $idempresa
            and tmp_combinado_listas.idventatmp is null
            ";
            $rsprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $precio = round($rsprod->fields['promedio'], 0);

            // precio mayor
        } elseif ($combinado_tipoprecio == 2) {
            $consulta = "
            select *, productos_sucursales.precio as p1
            from productos 
            inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
            where 
            borrado = 'N' 
            and productos_sucursales.idsucursal = $idsucursal
            and idtipoproducto = 1
            and productos.idempresa = $idempresa
            and idprod_serial in (
                                    select idproducto_partes
                                    from tmp_combinado_listas
                                    where
                                    tmp_combinado_listas.idproducto_principal = $prodprinc
                                    and tmp_combinado_listas.idsucursal = $idsucursal
                                    and tmp_combinado_listas.idusuario = $idusu
                                    and tmp_combinado_listas.idempresa = $idempresa
                                    and tmp_combinado_listas.idventatmp is null
                                )
            order by productos_sucursales.precio desc
            limit 1
            ";
            $rsprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $precio = $rsprod->fields['p1'];

            // definido
        } elseif ($combinado_tipoprecio == 3) {
            $consulta = "
            select *, productos_sucursales.precio as p1 
            from productos
            inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
            where 
            borrado = 'N' 
            and productos_sucursales.idsucursal = $idsucursal
            and idtipoproducto = 4
            and productos.idempresa = $idempresa
            and idprod_serial = $idprod_princ
            ";
            $rsprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $precio = $rsprod->fields['p1'];
        }

        // agrega al carrito
        $parametros_array = [
            'cantidad' => 1,
            'precio' => $precio,
            'idmesa' => '',
            'idusu' => $idusu,
            'idsucursal' => $idsucursal,
            'idempresa' => $idempresa,
            'idproducto' => $idproducto_principal
        ];
        $res = agregar_carrito($parametros_array);
        $valido_car = "S";
        if ($res['valido'] == 'N') {
            $valido = "N";
            $valido_car = "N";
            $errores .= $res['errores'];
        }
        // si se agrego al carrito
        if ($valido_car == 'S') {
            // busca el id temporal insertado
            $consulta = "
            select idventatmp
            from tmp_ventares  
            where 
            usuario = $idusu 
            and idsucursal = $idsucursal
            and idempresa = $idempresa
            and idtipoproducto = $idtipoproducto
            order by idventatmp desc
            limit 1
            ";
            $rsmaxid = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $maxid = $rsmaxid->fields['idventatmp'];

            //actualiza en la tabla temporal de combinados listas por cada lista
            $consulta = "
            update tmp_combinado_listas
            set 
            tmp_combinado_listas.idventatmp = $maxid
            where
            tmp_combinado_listas.idsucursal = $idsucursal
            and tmp_combinado_listas.idusuario = $idusu
            and tmp_combinado_listas.idempresa = $idempresa
            and tmp_combinado_listas.idproducto_principal = $prodprinc
            and tmp_combinado_listas.idventatmp is null
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        } // if($valido_car == 'S'){



    } // if($valido == 'S'){




    // arma respuesta
    $res_array = [
        'valido' => $valido,
        'errores' => $errores
    ];

    // respuesta
    return $res_array;

}

function agregar_carrito_combo($parametros_array)
{

    //
    agregar_carrito($parametros_array);

}
function agregar_carrito_combinado($parametros_array)
{


}
function movimiento_caja_valida($parametros_array)
{
    $valido = "S";
    $errores = "";
    //parametros
    $carrito_pagos = $parametros_array['carrito_pagos'];
    $idcaja = intval($parametros_array['idcaja']);
    $idsucursal = intval($parametros_array['idsucursal']);
    $fecha_pago = $parametros_array['fecha_pago'];



    // validaciones
    if ($idcaja == 0) {
        $valido = "N";
        $errores .= "- No envio el idcaja.".$saltolinea;
    }
    if ($idsucursal == 0) {
        $valido = "N";
        $errores .= "- No envio el idsucursal.".$saltolinea;
    }
    if ($fecha_pago == '') {
        $valido = "N";
        $errores .= "- No envio el campo fecha_pago.".$saltolinea;
    }


    // arma respuesta
    $res_array = [
        'valido' => $valido,
        'errores' => $errores
    ];

    // respuesta
    return $res_array;

}
function movimiento_caja_registra($parametros_array)
{

    global $conexion;
    global $ahora;

    $idventa = intval($parametros_array['idventa']);
    if ($idventa == 0) {
        echo "No se indico el idventa.";
        exit;
    }

    $idcliente = antisqlinyeccion($parametros_array['idcliente'], "int");
    $fecha_pago = antisqlinyeccion($parametros_array['fecha_pago'], "text");
    $factura = antisqlinyeccion($parametros_array['factura'], "text");
    $ruc = antisqlinyeccion($parametros_array['ruc'], "text");
    $idsucursal = antisqlinyeccion($parametros_array['idsucursal'], "int");
    $idventa = antisqlinyeccion($parametros_array['idventa'], "int");
    $idcaja = antisqlinyeccion($parametros_array['idcaja'], "int");
    $idmesa = antisqlinyeccion($parametros_array['idmesa'], "int");
    $idusu = antisqlinyeccion($parametros_array['idusu'], "int");


    $carrito_pagos = $parametros_array['carrito_pagos'];


    // caja vieja
    $insertar = "
    Insert into gest_pagos
    (idcliente,fecha,medio_pago,total_cobrado,chequenum,banco,numtarjeta,montotarjeta,
    factura,recibo,tickete,ruc,tipo_pago,idempresa,sucursal,efectivo,codtransfer,montotransfer,
    montocheque,cajero,idventa,vueltogs,idpedido,delivery,idmesa,idcaja,rendido,tipotarjeta,
    idtipocajamov,tipomovdinero)
    values
    ($idcliente,$fecha_pago,3,0,0,0,0,0,
    $factura,'','','$ruc',1,1,$idsucursal,0,
    0,0,0,$idusu,$idventa,0,0,0,$idmesa,$idcaja,
    'S',1,
    1,'E')";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    $consulta = "
    select max(idpago) as idpago from gest_pagos where idventa = $idventa limit 1
    ";
    $rsmaxpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpago_gest = $rsmaxpag->fields['idpago'];

    $total_sum_pagos = 0;
    foreach ($carrito_pagos as $pago) {
        $monto_pago_det = $pago['monto'];
        $idformapago = $pago['idformapago'];
        $consulta = "
        INSERT INTO gest_pagos_det
        (idpago, monto_pago_det, idformapago) 
        VALUES 
        ($idpago_gest, $monto_pago_det, $idformapago)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $total_sum_pagos += $pago['monto'];
    }


    $consulta = "
    update gest_pagos 
    set
    total_cobrado = $total_sum_pagos
    where
    idpago = $idpago_gest
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $res = ['idpago' => $idpago_gest];
    return $res;
}
function generar_cuenta_credito($parametros_array)
{

    global $saltolinea;
    global $ahora;
    global $conexion;

    $vencimiento_factura = antisqlinyeccion($parametros_array['vencimiento_factura'], "text");
    $registrado_por = antisqlinyeccion($parametros_array['registrado_por'], "int");
    $idventa = intval($parametros_array['idventa']);
    $idadherente = intval($parametros_array['idadherente']);
    $idserviciocom = intval($parametros_array['idserviciocom']);


    $consulta = "
    select * 
    from cliente
    where 
    idcliente = (select idcliente from ventas where idventa = $idventa limit 1)
    limit 1
    ";
    $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcliente = $rscli->fields['idcliente'];


    //Generamos la cuenta
    $insertar = "
    Insert into cuentas_clientes 
    (idempresa,sucursal,deuda_global,saldo_activo,idcliente,estado,registrado_el,registrado_por,idventa,idadherente,idserviciocom,prox_vencimiento)
    select 
    1, ventas.sucursal, ventas.totalcobrar,ventas.totalcobrar,ventas.idcliente,1,'$ahora',$registrado_por,idventa,$idadherente,$idserviciocom,$vencimiento_factura
    from ventas
    where
    idventa = $idventa
    and estado <> 6
    limit 1
    ";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
    //echo $insertar;
    //Credito
    $buscar = "Select max(idcta) as mayor from cuentas_clientes where registrado_por = $registrado_por";
    $rs1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $mayor = intval($rs1->fields['mayor']);

    $insertar = "
    INSERT INTO cuentas_clientes_det
    (idcta, nro_cuota, vencimiento, monto_cuota, cobra_cuota, quita_cuota, saldo_cuota, fch_ult_pago, fch_cancela, dias_atraso, dias_pago, dias_comb, estado) 
    select 
    cuentas_clientes.idcta, 1, $vencimiento_factura, cuentas_clientes.deuda_global, 0, 0, cuentas_clientes.saldo_activo, NULL, NULL, 0, 0, 0, 1
    from cuentas_clientes
    where 
    estado = 1
    and idcta = $mayor
    ";
    //echo $insertar;
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
    //exit;

    $consulta = "
    INSERT INTO adherente_estadocuenta 
    ( fechahora, fechasola, tipomov, idcliente, idadherente, idserviciocom, monto, idventa, idpago, idcta, idempresa, idpagodiscrim)
    SELECT registrado_el as fechahora, date(registrado_el) as fechasola, 'D' as tipomov, idcliente, idadherente,
     idserviciocom, deuda_global as monto, idventa, NULL as idpago, idcta, idempresa , NULL as idpagodiscrim
    from cuentas_clientes 
    where 
    idcta = $mayor
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // actualizar el saldo de sobregiro
    $consulta = "
    update cliente 
    set 
    saldo_sobregiro = COALESCE(linea_sobregiro,0)-COALESCE((select sum(saldo_activo) from cuentas_clientes where idcliente = $idcliente and estado <> 6),0)
    where 
    idcliente = $idcliente 
    and idempresa = 1
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    if ($idadherente > 0) {

        $buscar = "Select *, 
        linea_sobregiro-(select totalcobrar from ventas where idventa = $idventa limit 1) as saldo_sobregiro_posconsumo
        from adherentes 
        where 
        idadherente=$idadherente
         and idempresa=1
         ";
        $rsad = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $saldosobregiro_posconsumo_adh = $rsad->fields['saldo_sobregiro_posconsumo'];

        // actualizar el saldo de sobregiro
        $consulta = "
        update adherentes 
        set 
        disponible = $saldosobregiro_posconsumo_adh
        where 
        idadherente = $idadherente 
        and idempresa = 1
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($idservcom > 0) {
            $buscar = "
            Select *,
            linea_sobregiro-(select totalcobrar from ventas where idventa = $idventa limit 1) as saldosobregiro_posconsumo_adhscom
            from adherentes 
            inner join adherentes_servicioscom on adherentes_servicioscom.idadherente = adherentes_servicioscom.idadherente
            where 
            adherentes.idadherente=$idadherente 
            and adherentes.idempresa=1
            and adherentes_servicioscom.idserviciocom = $idservcom
            and adherentes_servicioscom.idadherente = $idadherente 
            ";
            $rsadcom = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $saldosobregiro_posconsumo_adhscom = $rsad->fields['saldosobregiro_posconsumo_adhscom'];
            // actualizar el saldo de sobregiro
            $consulta = "
            update adherentes_servicioscom 
            set 
            disponibleserv = $saldosobregiro_posconsumo_adhscom
            where 
            idadherente = $idadherente 
            and idserviciocom = $idservcom 
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }
    }
    // paga las cuentas con los saldos a favor
    //acredita_saldoafavor($idcliente);
    // actualiza los saldos disponibles
    //actualiza_saldos_clientes($idcliente,$idadherente,$idservcom);

    // recorre cada concepto y  actualiza los saldos disponibles
    $buscar = "
    SELECT *
    from adherentes 
    inner join adherentes_servicioscom on adherentes_servicioscom.idadherente=adherentes.idadherente 
    inner join servicio_comida on servicio_comida.idserviciocom=adherentes_servicioscom.idserviciocom
    where
    adherentes.idcliente=$idcliente 
    and adherentes.idempresa=1
    order by nomape asc";
    $tad = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    // actualiza saldos
    while (!$tad->EOF) {
        $idserviciocom = intval($tad->fields['idserviciocom']);
        $idadherente = intval($tad->fields['idadherente']);
        actualiza_saldos_clientes($idcliente, $idadherente, $idserviciocom);
        $tad->MoveNext();
    }
}


function descuento_stock_pedidos_no_detallados_ya_facturados($parametros_array_pedidos_stock)
{
    // comprobar existencia de deposito del tipo salon de ventas
    global $idempresa;
    global $conexion;
    global $ahora;
    $idevento = antisqlinyeccion($parametros_array_pedidos_stock['idevento'], "int");
    $idsucursal = antisqlinyeccion($parametros_array_pedidos_stock['idsucursal'], "int");
    $idempresa = antisqlinyeccion($parametros_array_pedidos_stock['idempresa'], "int");
    $iddeposito = antisqlinyeccion($parametros_array_pedidos_stock['iddeposito'], "int");

    // busca los detalles insertados
    $consulta = "
    select * 
    from pedidos_eventos_detalles 
    where 
    idpedidocatering = $idevento
    ";
    $rsvdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // recorre el detalle
    while (!$rsvdet->EOF) {
        $idproducto = $rsvdet->fields['idprodserial'];
        $cantidad = floatval($rsvdet->fields['cantidad']);
        //Paso 1): Traer la receta del producto
        $consulta = "Select * from recetas where idproducto='$idproducto' and recetas.idempresa = $idempresa";
        $rsre = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idreceta = intval($rsre->fields['idreceta']);
        //echo 'cant:'.$cantidad;exit;
        // si tiene receta
        if ($idreceta > 0) {
            //Lista de componenentes de la receta
            $consulta = "
            Select idreceta,idprod,ingrediente,cantidad,insumos_lista.idinsumo,costo,idmedida,tipoiva,
            insumos_lista.mueve_stock as mueve_stock_ins
             from recetas_detalles 
             inner join ingredientes on ingredientes.idingrediente=recetas_detalles.ingrediente
             inner join insumos_lista on insumos_lista.idinsumo=ingredientes.idinsumo
             where 
             recetas_detalles.idprod=$idproducto
            
             and recetas_detalles.idreceta=$idreceta
             and recetas_detalles.idempresa = $idempresa 
             and insumos_lista.idempresa = $idempresa
             ";
            $rsrecu = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            //recorre ingredientes de la receta
            while (!$rsrecu->EOF) {


                $ir = intval($rsrecu->fields['idreceta']);
                $in = intval($rsrecu->fields['ingrediente']);
                $pp = intval($rsrecu->fields['idprod']);
                $insu = intval($rsrecu->fields['idinsumo']);
                $idmedida_med = $rsrecu->fields['idmedida'];
                $mueve_stock_ins = trim($rsrecu->fields['mueve_stock_ins']);
                if (floatval($rsrecu->fields['cantidad']) > 0) {
                    // cantidad receta por cantidad vendida
                    $cant = floatval($rsrecu->fields['cantidad']) * floatval($cantidad);
                } else {
                    // cantidad receta es cero entonces cantidad vendida
                    $cant = floatval($cantidad);
                }


                // descuenta stock general
                if ($mueve_stock != 'N' && $mueve_stock_ins != 'N') {
                    descontar_stock_general($insu, $cant, $iddeposito);
                    $costo_insumo_utilizado = descuenta_stock_vent($insu, $cant, $iddeposito);
                    movimientos_stock($insu, $cant, $iddeposito, 2, '-', $idventa, $ahora);
                    // costo unitario promedio de los insumos utilizados
                    $costo_insumo_unitario = round($costo_insumo_utilizado / $cant, 4);
                    // costo del insumo actual utilizado
                    $costo_acum_insu[$insu] = $costo_insumo_utilizado;
                    // costo acumulado de todos los insumos
                    $costo_acumulado += $costo_insumo_utilizado;
                } else {
                    $costo_insumo_utilizado = 0;
                    //$costo_acum_insu[$insu]=$costo_insumo_utilizado;
                    $costo_insumo_unitario = 0;
                    $costo_acumulado = 0;
                }
                $rsrecu->MoveNext();
            }

        }
        $rsvdet->MoveNext();
    }


}
?>
