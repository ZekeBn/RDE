 <?php
function mesas_atc_edit_pin($parametros_array)
{
    global $conexion;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    $pin = antisqlinyeccion($parametros_array['pin'], "text");
    $idatc = antisqlinyeccion($parametros_array['idatc'], "int");

    if ($parametros_array['pin'] == '') {
        $valido = 'N';
        $errores = "-El pin no puede estar vacio.";
    }
    if (intval($parametros_array['idatc']) == 0) {
        $valido = 'N';
        $errores = "-El idatc no puede estar vacio.";
    }


    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
        update mesas_atc
        set
            pin=$pin
        where
            idatc = $idatc
            and estado = 1
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }

    $consulta = "
    select pin from mesas_atc where idatc = $idatc
    ";
    $rspin = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $pin_actual = $rspin->fields['pin'];

    return ["errores" => $errores, "valido" => $valido, 'pin_actual' => $pin_actual];
}
function mesas_atc_del_pin($parametros_array)
{
    global $conexion;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    $pin = antisqlinyeccion('', "text");
    $idatc = antisqlinyeccion($parametros_array['idatc'], "int");


    if (intval($parametros_array['idatc']) == 0) {
        $valido = 'N';
        $errores = "-El idatc no puede estar vacio.";
    }


    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
        update mesas_atc
        set
            pin=$pin
        where
            idatc = $idatc
            and estado = 1
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }

    $consulta = "
    select pin from mesas_atc where idatc = $idatc
    ";
    $rspin = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $pin_actual = $rspin->fields['pin'];

    return ["errores" => $errores, "valido" => $valido, 'pin_actual' => $pin_actual];
}
function autogenera_pin_mesa($parametros_array)
{
    $valido = "S";
    $errores = "";
    $idatc = $parametros_array['idatc'];
    //echo 'atc:'.intval($parametros_array['idatc']);exit;
    if (intval($parametros_array['idatc']) == 0) {
        $valido = 'N';
        $errores = "-El idatc no puede estar vacio.";
        //echo $errores;exit;
    }
    if ($valido == 'S') {
        // extrae ultimos 4 caractesres del atc
        $pin_arma = substr($idatc, -4);
        // luego agrega 2 caracteres de segundos
        $pin_arma = $pin_arma.date("s");
        // por ultimo si no llega a 6 caracteres rellena con numeros al azar hasta llegar a 6 caracteres,  pero si supera corta los ultimos digitos y deja en 6 caracteres
        $pin_arma = rellena_rand_der($pin_arma, 6);
        //  asigna el pin
        $pin = $pin_arma;
    }
    return ["errores" => $errores, "valido" => $valido, 'pin' => $pin];
}

function saldo_mesa($parametros_array)
{
    global $conexion;
    global $ahora;
    global $saltolinea;

    $idatc = intval($parametros_array['idatc']);


    //total de la mesa
    $buscar = "
    Select sum(subtotal) as tmesa 
    from tmp_ventares
    where 
    tmp_ventares.idtmpventares_cab in ( 
                                        select idtmpventares_cab 
                                        from tmp_ventares_cab 
                                        where 
                                        finalizado = 'S' 
                                        and registrado = 'N' 
                                        and idatc=$idatc 
                                        and estado = 1 
                                        )
    and tmp_ventares.borrado = 'N' 
    ";
    $tmesa = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $deudamesa = ($tmesa->fields['tmesa']);


    //Total de pagos ya registrados
    $buscar = "
    Select sum(montoabonado) as tpago 
    from mesas_cobros_deta 
    where 
    idatc=$idatc 
    and estadopago=1 
    and idventa is NULL
    ";
    $rpago = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tpago = floatval($rpago->fields['tpago']);

    //neto a cancelar
    $saldomesa = $deudamesa - $tpago;

    $res = [
        'monto_mesa' => $deudamesa,
        'pagos_mesa' => $tpago,
        'saldo_mesa' => $saldomesa
    ];
    return $res;
}

function valida_cobromesa($parametros_array)
{
    global $conexion;
    global $ahora;
    global $saltolinea;

    $valido = "S";
    $errores = "";
    $cod_error = 0;

    $idatc = intval($parametros_array['idatc']);
    $formapago = intval($parametros_array['idformapago']);
    $montopago = floatval($parametros_array['montoabonado']);
    $iddenominaciontarjeta = antisqlinyeccion($parametros_array['iddenominaciontarjeta'], "int");
    $banco = antisqlinyeccion($parametros_array['banco'], "int");
    $cheque_numero = antisqlinyeccion($parametros_array['cheque_numero'], "text");
    $otros = antisqlinyeccion($parametros_array['otros_valores'], 'text');
    $idbancardvpos = antisqlinyeccion($parametros_array['idbancardvpos'], 'int'); // opcional
    $idconfirmapago_qr = antisqlinyeccion($parametros_array['idconfirmapago_qr'], 'int'); // opcional
    $idclientesel = floatval($parametros_array['idclientesel']); // opcional
    $idusu = intval($parametros_array['idusu']);
    $propina_facturar = trim($parametros_array['propina_facturar']);



    if ($idusu == 0) {
        $valido = "N";
        $errores .= " - No se indico el usuario.<br />";
    }

    if ($propina_facturar == 'S') {
        $consulta = "
        select idproducto_propina from mesas_preferencias limit 1
        ";
        $rsprodprop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idproducto_propina = intval($rsprodprop->fields['idproducto_propina']);
        if ($idproducto_propina == 0) {
            $valido = "N";
            $errores .= " - No se cargo el producto propina en preferencias de mesas.<br />";
        }

    }

    // si es facturador electronico
    if ($facturador_electronico == 'S') {

        $idformapago = $formapago;
        $consulta = "
        select idforma, idformapago_set 
        from formas_pago 
        where 
        estado <> 6
        and idforma = $idformapago
        ";
        $rsfpagset = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idformapago_set = intval($rsfpagset->fields['idformapago_set']);
        // tarjeta debito y credito
        if ($idformapago_set == 3 or $idformapago_set == 4) {
            // conversiones
            $banco = antisqlinyeccion('', "int");
            $cheque_numero = antisqlinyeccion('', "text");
            // validaciones
            if (intval($_POST['iddenominaciontarjeta']) == 0) {
                $valido = "N";
                $errores .= " - No completaste el tipo de tarjeta.<br />";
            }
        }
        // cheque
        if ($idformapago_set == 2) {
            // conversiones
            $iddenominaciontarjeta = antisqlinyeccion('', "int");
            // validaciones
            if (intval($_POST['banco']) == 0) {
                $valido = "N";
                $errores .= " - No completaste el banco.<br />";
            }
            if (trim($_POST['cheque_numero']) == '') {
                $valido = "N";
                $errores .= " - No completaste el numero de cheque<br />";
            }
        }
    }

    // saldo de la mesa
    $parametros_array_saldo['idatc'] = $idatc;
    $saldo_mesa_res = saldo_mesa($parametros_array_saldo);
    $saldomesa = floatval($saldo_mesa_res['saldo_mesa']);

    // solo si no se trata de una propina a facturar
    if ($propina_facturar != 'S') {
        // si el pago supera el saldo de la mesa
        if ($montopago > $saldomesa) {
            // si la forma de pago no es efectivo entonces no hay vuelto
            if ($formapago != 1) {
                $valido = "N";
                $errores .= " - El pago no puede ser superior al saldo de la mesa.<br />";
                $cod_error = 2;
            }
        }
    }


    $res = [
        'valido' => $valido,
        'errores' => $errores,
        'cod_error' => $cod_error
    ];


    return $res;
}
function registra_cobromesa($parametros_array)
{
    global $conexion;
    global $ahora;
    global $saltolinea;

    $idatc = intval($parametros_array['idatc']);
    $idatcdet = intval($parametros_array['idatcdet']);
    $formapago = intval($parametros_array['idformapago']);
    $montopago = floatval($parametros_array['montoabonado']);
    $iddenominaciontarjeta = antisqlinyeccion($parametros_array['iddenominaciontarjeta'], "int");
    $banco = antisqlinyeccion($parametros_array['banco'], "int");
    $cheque_numero = antisqlinyeccion($parametros_array['cheque_numero'], "text");
    $otros = antisqlinyeccion($parametros_array['otros_valores'], 'text');
    $idbancardvpos = antisqlinyeccion($parametros_array['idbancardvpos'], 'int'); // opcional
    $idconfirmapago_qr = antisqlinyeccion($parametros_array['idconfirmapago_qr'], 'int'); // opcional
    $idclientesel = floatval($parametros_array['idclientesel']); // opcional
    $idusu = intval($parametros_array['idusu']);
    $propina_facturar = antisqlinyeccion($parametros_array['propina_facturar'], "text");
    $propreg = antisqlinyeccion($parametros_array['propreg'], 'int'); // opcional solo si esta vinculado a una propina que se factura
    $idconfirmapago_apppos = antisqlinyeccion($parametros_array['idconfirmapago_apppos'], 'int'); // opcional

    // conversiones
    if (trim($parametros_array['propina_facturar']) != 'S') {
        $propina_facturar = antisqlinyeccion('N', "text");
    }
    if ($idatcdet == 0) {
        $idatcdet = "NULL";
    }

    // por seguridad si se factura
    if (trim($parametros_array['propina_facturar']) == 'S') {
        // verifica el registro de propina
        if (intval($parametros_array['propreg']) == 0) {
            echo "No se recibio el codigo de propina vinculada.";
            exit;
        }
    }

    $consulta = "
    SELECT mesas.idmesa, mesas_atc.idsucursal
    FROM mesas_atc
    inner join mesas on mesas.idmesa = mesas_atc.idmesa
    WHERE
    mesas_atc.idatc = $idatc
    ";
    $rsmesa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idmesa = intval($rsmesa->fields['idmesa']);
    $idsucursal = intval($rsmesa->fields['idsucursal']);


    // saldo de la mesa
    $parametros_array_saldo['idatc'] = $idatc;
    $saldo_mesa_res = saldo_mesa($parametros_array_saldo);
    $saldomesa = floatval($saldo_mesa_res['saldo_mesa']);
    //echo 'saldo:'.$saldomesa;exit;

    // si es una propina a facturar no importa el saldo de la mesa por que va a aumentar
    if (trim($parametros_array['propina_facturar']) == 'S') {
        //Insertamos y recalculamos para mostrar
        $insertar = "
        Insert into mesas_cobros_deta
        (idformapago,montoabonado,estadopago,idcliente,idatc,idatcdet,registrado_el,registrado_por,iddenominaciontarjeta,idbanco,cheque_numero,otros_valores,idbancardvpos,idconfirmapago_qr,propina_facturada,propreg,idconfirmapago_apppos)
        values
        ($formapago,$montopago,1,$idclientesel,$idatc,$idatcdet,'$ahora',$idusu,$iddenominaciontarjeta,$banco,$cheque_numero,$otros,$idbancardvpos,$idconfirmapago_qr,$propina_facturar,$propreg,$idconfirmapago_apppos)
        ";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        // no es una propina
    } else {
        //ver que el monto no supere la deuda
        if (($montopago <= $saldomesa)) {
            //Insertamos y recalculamos para mostrar
            $insertar = "
            Insert into mesas_cobros_deta
            (idformapago,montoabonado,estadopago,idcliente,idatc,idatcdet,registrado_el,registrado_por,iddenominaciontarjeta,idbanco,cheque_numero,otros_valores,idbancardvpos,idconfirmapago_qr,propina_facturada,propreg,idconfirmapago_apppos)
            values
            ($formapago,$montopago,1,$idclientesel,$idatc,$idatcdet,'$ahora',$idusu,$iddenominaciontarjeta,$banco,$cheque_numero,$otros,$idbancardvpos,$idconfirmapago_qr,$propina_facturar,$propreg,$idconfirmapago_apppos)
            ";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        } else {
            if (($montopago > $saldomesa && $formapago == 1)) {

                $vuelto = floatval($montopago - $saldomesa);
                $insertar = "
                Insert into mesas_cobros_deta
                (idformapago,montoabonado,estadopago,idcliente,idatc,idatcdet,registrado_el,registrado_por,vueltogs,iddenominaciontarjeta,idbanco,cheque_numero,otros_valores,idbancardvpos,idconfirmapago_qr,propina_facturada,propreg,idconfirmapago_apppos)
                values
                ($formapago,$saldomesa,1,$idclientesel,$idatc,$idatcdet,'$ahora',$idusu,$vuelto,$iddenominaciontarjeta,$banco,$cheque_numero,$otros,$idbancardvpos,$idconfirmapago_qr,$propina_facturar,$propreg,$idconfirmapago_apppos)
                ";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
            }
        }
    }

    //obtener el idinsertado
    $consulta = "
    select idcobser from mesas_cobros_deta where idatc = $idatc order by idcobser desc limit 1;
    ";
    $rslast = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcobser = intval($rslast->fields['idcobser']);

    // actualizar en el carrito la propina a facturar de la mesa
    if ($parametros_array['propina_facturar'] == 'S') {
        $parametros_array_actuprop['idatc'] = $idatc;
        $parametros_array_actuprop['idusu'] = $idusu;
        actualiza_propina_mesa($parametros_array_actuprop);
    }



    // asignar como estado pago online a la mesa
    if (intval($idbancardvpos) > 0 or $idconfirmapago_qr > 0) {
        $consulta = "
        update mesas
        set 
        estado_mesa = 7
        where
        idmesa = $idmesa
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // generar aviso de cuenta
        $consulta = "
        INSERT INTO 
        `mesas_pedidos`
        (`idatc`, `fecha`, `estado`, `tipo_pedido`, `fechahora_cancelado`, `idmozo`, `fechahora_atendido`) 
        VALUES 
        ($idatc,'$ahora',1,3,NULL,NULL,NULL)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }

    $res = ['idcobser' => $idcobser];

    return $res;
}
function abrir_mesa($parametros_array)
{
    global $conexion;
    global $ahora;
    global $saltolinea;

    // validaciones
    $valido = 'S';
    $errores = '';

    // parametros entrada
    $idmesa = intval($parametros_array['idmesa']);
    $cantidad_adultos = intval($parametros_array['cantidad_adultos']);
    $cantidad_ninhos = intval($parametros_array['cantidad_ninhos']);
    $cantidad_nopagan = intval($parametros_array['cantidad_nopagan']);
    $idmozo = intval($parametros_array['idmozo']);
    $idusu = intval($parametros_array['idusu']);
    $nombre_mesa = antisqlinyeccion($parametros_array['nombre_mesa'], "text");


    // campos obligatorios
    if ($idmesa == 0) {
        $valido = 'N';
        $errores .= '- No se envio el idmesa.'.$saltolinea;
    }
    if ($idusu == 0) {
        $valido = 'N';
        $errores .= '- No se envio el idusu.'.$saltolinea;
    }

    // datos de la mesa
    $consulta = "
    select mesas.idmesa, mesas.idsalon, mesas.idsucursal, mesas.estado_mesa, mesas_estados_lista.permite_abrir_sinohay_atc,
    mesas_estados_lista.descripcion as estado_mesa_txt
    from mesas 
    inner join mesas_estados_lista on mesas_estados_lista.idestadomesa = mesas.estado_mesa 
    where 
    mesas.estadoex=1 
    and mesas.idmesa = $idmesa
    ";
    $rsmesa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idmesa = intval($rsmesa->fields['idmesa']);
    $idsucursal = intval($rsmesa->fields['idsucursal']);
    $estado_mesa = intval($rsmesa->fields['estado_mesa']);
    $permite_abrir_sinohay_atc = trim($rsmesa->fields['permite_abrir_sinohay_atc']);
    $estado_mesa_txt = antixss($rsmesa->fields['estado_mesa_txt']);
    if ($idmesa == 0) {
        $valido = 'N';
        $errores .= '- Mesa inexistente.'.$saltolinea;
    }
    if ($estado_mesa == 8) {
        $valido = 'N';
        $errores .= '- Mesa bloqueada por rendicion pendiente.'.$saltolinea;
    }
    if ($estado_mesa != 1) {
        // busca si tiene atc activo
        $consulta = "
        select idatc from mesas_atc where idmesa = $idmesa and estado = 1 order by idatc desc limit 1
        ";
        $rsatc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idatc = intval($rsatc->fields['idatc']);
        // si tiene atc activo
        if ($idatc > 0) {
            //$valido='N';
            //$errores.='- No se puede abrir una mesa que ya se encuentra abierta.'.$saltolinea;
            // si no tiene atc activo
        } else {
            // si su estado no permite abrir aun no habiendo atc
            if ($permite_abrir_sinohay_atc != 'S') {
                $valido = 'N';
                $errores .= '- No se puede abrir la mesa por que su estado es: '.$estado_mesa_txt.$saltolinea;
            }
        }
    }

    $consulta = "
    select usa_mesa_smart from mesas_preferencias
    ";
    $rsmesapref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $usa_mesa_smart = $rsmesapref->fields['usa_mesa_smart'];


    // si todo es valido
    if ($valido == 'S') {


        // busca si ya hay un atc abierto para esta mesa
        $buscar = "Select * from mesas_atc where idmesa=$idmesa and estado=1 order by registrado_el desc limit 1";
        $rsidatc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idatc = intval($rsidatc->fields['idatc']);
        // si no hay atc abierto, abre
        if ($idatc == 0) {

            // creamos el ATC
            $insertar = "
            Insert into mesas_atc
            (idmesa,fecha_inicio,cant_adultos,cant_ninos,cant_nopaga,idmozo,idgruporeserva,idgrupomesa,estado,registrado_el,registrado_por,idsucursal,nombre_mesa)
            values
            ($idmesa,'$ahora',$cantidad_adultos,$cantidad_ninhos,$cantidad_nopagan,0,0,0,1,current_timestamp,$idusu,$idsucursal,$nombre_mesa)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

            //traemos el id atc generado
            $buscar = "Select idatc from mesas_atc where idmesa=$idmesa and estado=1 order by registrado_el desc limit 1";
            $rsatc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idatc = intval($rsatc->fields['idatc']);

            // cambiamos el estado de la mesa a abierto
            $update = "Update mesas set estado_mesa=2 where idmesa=$idmesa";
            $conexion->Execute($update) or die(errorpg($conexion, $update));

            //Registramos la apertura para el primer mozo
            if ($idmozo > 0) {
                $insertar = "Insert into mesas_atc_mozos (idmozo,idatc,registrado_el,registrado_por,abre_mesa) values ($idmozo,$idatc,'$ahora',$idusu,'S')";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
            }
            // si usa mesa smart
            if ($usa_mesa_smart == 'S') {
                // genera el pin para mesas smart
                $parametros_array_pin['idatc'] = $idatc;
                $respin = autogenera_pin_mesa($parametros_array_pin);
                $pin = $respin['pin'];
                $parametros_array_pin['pin'] = $pin;
                $respinnew = mesas_atc_edit_pin($parametros_array_pin);
                $pin_actual = $respinnew['pin_actual'];
            }

        }


    } // if($valido == 'S'){

    $res = [
        "valido" => $valido,
        "errores" => $errores,
        "idatc" => $idatc,
        "pin" => $pin_actual,
    ];
    return $res;
}

function valida_propinamesa($parametros_array)
{
    global $conexion;
    global $saltolinea;
    global $ahora;

    $valido = 'S';
    $errores = '';

    $idatc = intval($parametros_array['idatc']);
    $monto_propina = floatval($parametros_array['monto_propina']);
    $idusu = intval($parametros_array['idusu']);
    $idformapago = intval($parametros_array['idformapago']);
    $afecta_caja = trim($parametros_array['afecta_caja']);
    $facturar_propina = trim($parametros_array['facturar_propina']);
    $idmozo_propina = intval($parametros_array['idmozo_propina']);

    if ($idatc == 0) {
        $valido = 'N';
        $errores .= "- No se envio el idatc.".$saltolinea;
    }
    if ($idusu == 0) {
        $valido = 'N';
        $errores .= "- No se envio el idusu.".$saltolinea;
    }
    if ($idformapago == 0) {
        $valido = 'N';
        $errores .= "- No se envio el idformapago.".$saltolinea;
    }
    if ($monto_propina <= 0) {
        $valido = 'N';
        $errores .= "- No se envio el monto_propina.".$saltolinea;
    }

    $consulta = "
    select idatc from mesas_atc where idatc = $idatc and estado = 1
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rs->fields['idatc']) == 0) {
        $valido = 'N';
        $errores .= "- El idatc enviado no se encuentra abierto.".$saltolinea;
    }

    // si se factura la propina
    if ($facturar_propina == 'S') {
        $consulta = "
        select idproducto_propina from mesas_preferencias limit 1
        ";
        $rsprodprop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idproducto_propina = intval($rsprodprop->fields['idproducto_propina']);
        if ($idproducto_propina <= 0) {
            $valido = 'N';
            $errores .= "- No se asigno el producto propina a facturar.".$saltolinea;
        }

        $parametros_array['montoabonado'] = floatval($monto_propina);
        $parametros_array['propina_facturar'] = $facturar_propina;
        // validar cobro de mesa
        $res = valida_cobromesa($parametros_array);
        if ($res['valido'] != 'S') {
            $valido = $res['valido'];
            $errores .= $res['errores'];
            $cod_error = $res['cod_error'];
        }

    }





    $res = [
        'valido' => $valido,
        'errores' => $errores,
    ];

    return $res;
}
function registra_propinamesa($parametros_array)
{
    global $conexion;
    global $saltolinea;
    global $ahora;

    $idatc = intval($parametros_array['idatc']);
    $monto_propina = floatval($parametros_array['monto_propina']);
    $idusu = intval($parametros_array['idusu']);
    $idformapago = intval($parametros_array['idformapago']);
    $facturar_propina = trim($parametros_array['facturar_propina']);
    $afecta_caja = antisqlinyeccion($parametros_array['afecta_caja'], "text");
    $idconfirmapago_apppos = antisqlinyeccion($parametros_array['idconfirmapago_apppos'], 'int'); // opcional
    $idmozo_propina = antisqlinyeccion($parametros_array['idmozo_propina'], 'int'); // opcional
    // por defecto no afecta
    if (trim($parametros_array['afecta_caja']) == '') {
        $afecta_caja = antisqlinyeccion('N', "text");
    }
    // si se factura no afecta por que el cobro que se registra ya va a afectar, entonces se duplicaria
    if ($facturar_propina == 'S') {
        $afecta_caja = antisqlinyeccion('N', "text");
    }

    $insertar = "
    Insert into  mesas_atc_propinas 
    (idatc,monto_propina,registrado_por,registrado_el,mediopago,afecta_caja, idconfirmapago_apppos, idmozo)
    values
    ($idatc,$monto_propina,$idusu,'$ahora',$idformapago,$afecta_caja, $idconfirmapago_apppos, $idmozo_propina)
    ";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    $consulta = "
    select propreg from mesas_atc_propinas where registrado_por = $idusu order by propreg desc limit 1
    ";
    $rslast = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $propreg = $rslast->fields['propreg'];

    // si se factura la propina
    if ($facturar_propina == 'S') {
        $parametros_array['montoabonado'] = floatval($monto_propina);
        $parametros_array['propreg'] = floatval($propreg);
        $parametros_array['propina_facturar'] = 'S';
        // validar cobro de mesa
        $res = registra_cobromesa($parametros_array);

    }

    $res = ['propreg' => $propreg];

    return $res;

}
function valida_borra_propinamesa($parametros_array)
{
    global $conexion;
    global $saltolinea;
    global $ahora;

    $valido = 'S';
    $errores = '';

    $idpropina = intval($parametros_array['idpropina']);

    if ($idpropina == 0) {
        $valido = 'N';
        $errores .= "- No se envio el idpropina.".$saltolinea;
    }

    $res = [
        'valido' => $valido,
        'errores' => $errores,
    ];

    return $res;
}
function registra_borra_propinamesa($parametros_array)
{
    global $conexion;
    global $saltolinea;
    global $ahora;

    $idpropina = intval($parametros_array['idpropina']);

    // busca si esta vinculada a un cobro de una mesa (Propina facturada)
    $consulta = "
    select idcobser, estadopago from mesas_cobros_deta where propreg = $idpropina
    ";
    $rscob = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcobser = intval($rscob->fields['idcobser']);
    $estadopago = intval($rscob->fields['estadopago']);
    // si esta vinculado
    if ($idcobser > 0) {
        // si no se elimino el pago por seguridad bloquea
        if ($estadopago <> 6) {
            echo "No se elimino el pago vinculado, eliminelo primero.";
            exit;
        }
    }

    $delete = "delete from mesas_atc_propinas where propreg=$idpropina";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));

}
function mozo_o_cajero($parametros_array)
{
    global $conexion;
    $idusu = intval($parametros_array['idusu']);
    if ($idusu == 0) {
        echo "No se recibio el idusuario.";
        exit;
    }

    //Verificamos si el usuario en cuestion tiene permiso de caja
    $consulta = "
    SELECT idusu
    FROM usuarios
    where
    estado = 1
    and idusu in
    (
        select idusu 
        from modulo_usuario 
        where 
        estado = 1
        and submodulo = 22
    )
    and idusu=$idusu
    limit 1
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rs->fields['idusu']) > 0) {
        $mozo_o_cajero = 'C';
    } else {
        $mozo_o_cajero = 'M';
    }
    $res = [
        'mozo_o_cajero' => $mozo_o_cajero
    ];

    return $res;

}
function valida_elimina_pago($parametros_array)
{
    global $conexion;
    global $saltolinea;
    global $ahora;

    $valido = 'S';
    $errores = '';

    $idcobser = $parametros_array['idcobser'];
    $idusu = $parametros_array['idusu'];
    if ($idcobser == 0) {
        $valido = 'N';
        $errores .= "- No se recibio el idcobser.".$saltolinea;
    }
    if ($idusu == 0) {
        $valido = 'N';
        $errores .= "- No se recibio el idusuario.".$saltolinea;
    }



    $res = [
        'valido' => $valido,
        'errores' => $errores,
    ];

    return $res;
}
function registra_elimina_pago($parametros_array)
{
    global $conexion;
    global $saltolinea;
    global $ahora;

    $idcobser = intval($parametros_array['idcobser']);
    $idusu = intval($parametros_array['idusu']);

    // borra el pago
    $update = "
    update mesas_cobros_deta 
    set estadopago=6,
    anulado_el='$ahora',
    anulado_por=$idusu 
    where 
    idcobser=$idcobser
    ";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    // busca si tiene propina vinculada
    $consulta = "
    select idcobser, propreg, idatc from mesas_cobros_deta where idcobser = $idcobser limit 1
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpropina = intval($rs->fields['propreg']);
    $idatc = intval($rs->fields['idatc']);
    // si tiene propina vinculada
    if ($idpropina > 0) {
        // borra la propina vinculada al pago
        $parametros_array['idpropina'] = $idpropina;
        registra_borra_propinamesa($parametros_array);
    }

    // actualiza propina mesa
    $parametros_array['idatc'] = $idatc;
    $parametros_array['idusu'] = $idusu;
    actualiza_propina_mesa($parametros_array);

    $res = [
        'valido' => 'S',
        'errores' => '',
        'idcobser' => $eliminar
    ];

    return $res;
}
function actualiza_propina_mesa($parametros_array)
{
    global $conexion;
    global $saltolinea;
    global $ahora;

    $idatc = intval($parametros_array['idatc']);
    $idusu = intval($parametros_array['idusu']);

    $consulta = "
    select idproducto_propina from mesas_preferencias limit 1
    ";
    $rsprodprop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idproducto_propina = intval($rsprodprop->fields['idproducto_propina']);
    // por seguridad
    if ($idproducto_propina == 0) {
        echo "No se asigno el producto a facturar";
        exit;
    }

    $consulta = "
    SELECT mesas.idmesa, mesas_atc.idsucursal
    FROM mesas_atc
    inner join mesas on mesas.idmesa = mesas_atc.idmesa
    WHERE
    mesas_atc.idatc = $idatc
    ";
    $rsmesa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idmesa = intval($rsmesa->fields['idmesa']);
    $idsucursal = intval($rsmesa->fields['idsucursal']);

    // busca si hay agregado en la mesa
    $consulta = "
    select tmp_ventares.idventatmp, 
        COALESCE(
            (
                select sum(mesas_cobros_deta.montoabonado) as propina_total
                from mesas_cobros_deta 
                where 
                idatc = $idatc 
                and estadopago <> 6 
                and propina_facturada = 'S'
            )
        ,0) as propina_total
    from tmp_ventares_cab 
    inner join tmp_ventares on tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
    WHERE
    tmp_ventares_cab.estado <> 6
    and tmp_ventares.borrado = 'N'
    and tmp_ventares_cab.idatc = $idatc
    and tmp_ventares.idproducto = $idproducto_propina
    limit 1
    ";
    //echo $consulta;exit;
    $rsprop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idventatmp_prop = intval($rsprop->fields['idventatmp']);
    $propina_total = floatval($rsprop->fields['propina_total']);
    // si hay actualiza
    if ($idventatmp_prop > 0) {
        $consulta = "
        update tmp_ventares
        set
        precio = $propina_total,
        subtotal = $propina_total
        where
        idventatmp = $idventatmp_prop
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // si no hay agrega
    } else {
        // calcula monto propinas
        $consulta = "
        select sum(mesas_cobros_deta.montoabonado) as propina_total
        from mesas_cobros_deta 
        where 
        idatc = $idatc 
        and estadopago <> 6 
        and propina_facturada = 'S'
        ";
        $rsprop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $propina_total = floatval($rsprop->fields['propina_total']);

        $consulta = "
        select ruc, razon_social from cliente where borrable = 'N' and estado <> 6 order by idcliente asc limit 1
        ";
        $rscligen = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $razon_social = $rscligen->fields['razon_social'];
        $ruc = $rscligen->fields['ruc'];
        //Crear cabecera de tmpventarescab
        $consulta = "
        INSERT INTO tmp_ventares_cab
        (razon_social, ruc, chapa, observacion, monto, idusu, fechahora, idsucursal, idempresa,idcanal,delivery,idmesa,
        telefono,delivery_zona,direccion,llevapos,cambio,observacion_delivery,delivery_costo,idatc,idmozo,idterminal) 
        VALUES 
        ('$razon_social', '$ruc', NULL, NULL, 0, $idusu, '$ahora', $idsucursal, 1,4,'N',$idmesa,
        0,0,NULL,'N',0,NULL,0,$idatc,$idusu,NULL)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // buscar ultimo id insertado
        $consulta = "
        select idtmpventares_cab
        from tmp_ventares_cab
        where
        idusu = $idusu
        and idmesa=$idmesa
        and idsucursal = $idsucursal
        order by idtmpventares_cab desc
        limit 1
        ";
        $rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idtmpventares_cab = $rscab->fields['idtmpventares_cab'];

        $consulta = "
        INSERT INTO tmp_ventares
        (idtmpventares_cab, idproducto, cantidad, precio, fechahora, usuario, finalizado, registrado, idsucursal, idempresa, 
        receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal,idmesa) 
        VALUES 
        ($idtmpventares_cab, $idproducto_propina,1,$propina_total, '$ahora',$idusu, 'S', 'N', $idsucursal, 1, 
        'N', 'N', 'N', NULL, NULL,$propina_total,$idmesa)
        ;
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // actualiza monto cabecera
        $consulta = "
        update tmp_ventares_cab
        set
        finalizado = 'S',
        monto = COALESCE((SELECT sum(subtotal) from tmp_ventares where idtmpventares_cab = $idtmpventares_cab and finalizado = 'S' and borrado = 'N'))
        where
        idtmpventares_cab = $idtmpventares_cab
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }

    $res = [
        'propina_total' => $propina_total,
        'idventatmp' => $idventatmp_prop
    ];

    return $res;
}

function liberar_mesa_valida($parametros_array)
{
    global $ahora;
    global $conexion;

    $valido = 'S';
    $errores = '';

    $idatc = intval($parametros_array['idatc']);
    $idsucursal = intval($parametros_array['idsucursal']);
    $idusu = intval($parametros_array['idusu']);


    //Vemos si no hay consumo en la mesa
    $consulta = "
    select tmp_ventares.*, productos.descripcion, sum(cantidad) as total, sum(precio) as totalprecio, sum(subtotal) as subtotal,
    (select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
    (select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado
    from tmp_ventares 
    inner join productos on tmp_ventares.idproducto = productos.idprod_serial
    where 
    tmp_ventares.idtmpventares_cab in ( 
                                        select idtmpventares_cab 
                                        from tmp_ventares_cab 
                                        where 
                                        idsucursal = $idsucursal 
                                        and finalizado = 'S' 
                                        and registrado = 'N' 
                                        and idatc=$idatc 
                                        and idatc > 0
                                        and estado = 1 
                                        )
    and tmp_ventares.borrado = 'N' 
    and tmp_ventares.idsucursal = $idsucursal 
    group by idventatmp,descripcion, receta_cambiada
    ";
    //echo $consulta;exit;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idventatmp = intval($rs->fields['idventatmp']);

    //$tr=$rs->RecordCount();
    if ($idventatmp > 0) {
        $valido = 'N';
        $errores .= '- No se puede liberar por que hay productos cargados.<br />';
    }

    $consulta = "
    select idmesa from mesas_atc where idatc = $idatc and idsucursal = $idsucursal and estado = 1 limit 1
    ";
    $rsatc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idmesa = intval($rsatc->fields['idmesa']);
    if ($idmesa == 0) {
        $valido = 'N';
        $errores .= '- No se puede liberar un atc inactivo.<br />';
    }
    $consulta = "
    select estado_mesa from mesas where idmesa = $idmesa limit 1
    ";
    $rsmesa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $estado_mesa = intval($rsmesa->fields['estado_mesa']);
    if ($estado_mesa == 8) {
        $valido = 'N';
        $errores .= '- No se puede liberar una mesa pendiente de rendicion.<br />';
    }



    return ["errores" => $errores,"valido" => $valido];

}

function liberar_mesa_registra($parametros_array)
{
    global $ahora;
    global $conexion;

    $idatc = intval($parametros_array['idatc']);
    $idsucursal = intval($parametros_array['idsucursal']);
    $idusu = intval($parametros_array['idusu']);

    $consulta = "
    select idmesa, idatc from mesas_atc where idatc = $idatc and idsucursal = $idsucursal and estado = 1 limit 1
    ";
    $rsatc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idmesa = intval($rsatc->fields['idmesa']);
    $idatc = intval($rsatc->fields['idatc']);

    //Uso de la mesa y datos de grupo mesa
    $buscar = "
    select fecha_inicio-now() as raro,
    TIME_TO_SEC(TIMEDIFF(now(),fecha_inicio)) diferencia_segundos,
    (TIME_TO_SEC(TIMEDIFF(now(),fecha_inicio))/60) as dif_minutos,
    idgrupomesa 
    from mesas_atc 
    where 
    idatc = $idatc
    ";
    $rscalculo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tsegundos = intval($rscalculo->fields['diferencia_segundos']);
    $idgrupomesa = intval($rscalculo->fields['idgrupomesa']);


    // anular atc
    $update = "
    Update mesas_atc 
    set 
    estado=6,
    anulado_el='$ahora',
    anulado_por=$idusu
    where 
    idatc=$idatc 
    ";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    //Liberamos la mesa
    $update = "Update mesas set estado_mesa=1 where idmesa=$idmesa ";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    //Vemos si formo parte de algun grupo de mesas
    if ($idgrupomesa > 0) {
        //hacer update de las mesas con el estado 1
        $update = "Update mesas_atc_grupos_cab set estadogrupo=6 where idgrupomesa=$idgrupomesa";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        //Marcamos los componentes
        $update = "Update mesas_atc_grupo_deta set estado=6 where idgrupomesa=$idgrupomesa";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        //liberar mesas del  grupo
        $buscar = "Select * from  mesas_atc_grupo_deta where idgrupomesa=$idgrupomesa";
        $marcar = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        while (!$marcar->EOF) {
            $idmlocal = $marcar->fields['idmesa'];
            $update = "Update mesas  set estado_mesa=1,agrupado_con=NULL where idmesa=$idmlocal";
            $conexion->Execute($update) or die(errorpg($conexion, $update));
            $marcar->MoveNext();
        }
    }

    return ["idmesa" => $idmesa,"idatc" => $idatc];

}


?>
