<?php



function valida_pago_cuentacliente($datos_ar)
{

    global $conexion;
    global $saltolinea;

    $valido = "S";
    $errores = "";

    //print_r($datos_ar);

    // recibe parametros
    $fecha_pago = antisqlinyeccion($datos_ar['fecha_pago'], "text");
    $registrado_por = antisqlinyeccion($datos_ar['registrado_por'], "int");
    $idcliente = antisqlinyeccion($datos_ar['idcliente'], "int");
    $idcaja_new = antisqlinyeccion($datos_ar['idcaja_new'], "int");
    $idcaja_old = antisqlinyeccion($datos_ar['idcaja_old'], "int");
    $registrado_el = antisqlinyeccion($datos_ar['registrado_el'], "date");
    $recibo = antisqlinyeccion(trim($datos_ar['recibo']), "text"); // formato 001-001-0000001  con guiones
    $sucursal = antisqlinyeccion($datos_ar['idsucursal'], "int");
    $idnotacredito = antisqlinyeccion($datos_ar['idnotacredito'], "int");
    $notacredito = antisqlinyeccion($datos_ar['notacredito'], "text");


    if ($datos_ar['idnotacredito'] > 0) {
        $whereadd = " and notacred = 'S' ";
    } else {
        $whereadd = " and notacred = 'N' ";
    }

    if (trim($datos_ar['fecha_pago']) == '') {
        $valido = "N";
        $errores .= "- No se indico la fecha de pago.".$saltolinea;
    }
    if (intval($datos_ar['idcaja_new']) == 0) {
        $valido = "N";
        $errores .= "- No se indico la caja de gestion.".$saltolinea;
    }
    if (intval($datos_ar['idcaja_old']) == 0) {
        $valido = "N";
        $errores .= "- No se indico la caja.".$saltolinea;
    }
    if (intval($datos_ar['idcliente']) == 0) {
        $valido = "N";
        $errores .= "- No se indico el cliente.".$saltolinea;
    }
    if (intval($datos_ar['registrado_por']) == 0) {
        $valido = "N";
        $errores .= "- No se indico el usuario que registra.".$saltolinea;
    }
    if (trim($datos_ar['registrado_el']) == '') {
        $valido = "N";
        $errores .= "- No se indico la fecha de registro.".$saltolinea;
    }
    /*if(trim($datos_ar['recibo']) == ''){
        $valido="N";
        $errores.="- No se indico el recibo.".$saltolinea;
    }*/
    if (intval($datos_ar['sucursal']) == 0) {
        $valido = "N";
        $errores .= "- No se indico la sucursal.".$saltolinea;
    }
    // valida formato de recibo
    if (trim($datos_ar['recibo']) != '') {
        $recibonum_ar = explode("-", $datos_ar['recibo']);
        if (intval($recibonum_ar[0]) == 0) {
            $valido = "N";
            $errores .= "- La sucursal del recibo es incorrecta.".$saltolinea;
        }
        if (intval($recibonum_ar[1]) == 0) {
            $valido = "N";
            $errores .= "-El punto expedicion del recibo es incorrecto.".$saltolinea;
        }
        if (intval($recibonum_ar[2]) == 0) {
            $valido = "N";
            $errores .= "- El numero del recibo es incorrecto.".$saltolinea;
        }
        $recibo_sql = antisqlinyeccion(trim($datos_ar['recibo']), 'text');
        $consulta = "
		select * from cuentas_clientes_pagos_cab where estado <> 6 and recibo = $recibo_sql limit 1
		";
        $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($rsex->fields['idcuentaclientepagcab'] > 0) {
            $valido = "N";
            $errores .= "- Recibo duplicado, ya existe otro pago con el mismo numero de recibo.".$saltolinea;
        }

    }
    // valida recibo o nota
    if (intval($datos_ar['idnotacredito']) > 0) {
        if (trim($datos_ar['recibo']) != '') {
            $valido = "N";
            $errores .= "- No puede haber recibo cuando es una nota de credito.".$saltolinea;
        }
    }
    if (trim($datos_ar['recibo']) != '') {
        if (intval($datos_ar['idnotacredito']) > 0) {
            $valido = "N";
            $errores .= "- No puede haber nota cuando es un recibo.".$saltolinea;
        }
    }
    if (intval($datos_ar['idnotacredito']) > 0) {
        if (trim($datos_ar['notacredito']) == '') {
            $valido = "N";
            $errores .= "- No indico el numero de nota de credito.".$saltolinea;
        }
    }
    if (trim($datos_ar['notacredito']) != '') {
        if (intval($datos_ar['idnotacredito']) == 0) {
            $valido = "N";
            $errores .= "- No indico el codigo de nota de credito.".$saltolinea;
        }
    }



    // total a abonar
    $consulta = "
	select *,
	(
		select saldo_activo 
		from cuentas_clientes 
		where 
		idcta = tmp_carrito_cobros.idcta
		and estado <> 6
		limit 1
	) as saldo_activo
	from tmp_carrito_cobros 
	where 
	estado = 1 
	and idcliente = $idcliente
	and registrado_por = $registrado_por
	$whereadd
	";
    $rscar = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // recorre el carrito temporal
    while (!$rscar->EOF) {

        $idcta = intval($rscar->fields['idcta']);
        $monto_abonar = floatval($rscar->fields['monto_abonar']);
        $saldo_activo = floatval($rscar->fields['saldo_activo']);


        if ($monto_abonar > $saldo_activo) {
            $valido = "N";
            $errores .= "- El monto a abonar supera el saldo en la idcta: $idcta.".$saltolinea;
        }

        $rscar->MoveNext();
    }

    // total forma de pago
    $consulta = "
	select sum(monto_pago)  as monto_pago
	from tmp_carrito_cobros_fpag 
	where 
	idcliente = $idcliente
	and registrado_por = $registrado_por
	$whereadd
	";
    $rspag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // total facturas
    $consulta = "
	select sum(monto_abonar)  as monto_abonar
	from tmp_carrito_cobros
	where 
	idcliente = $idcliente
	and registrado_por = $registrado_por
	and estado = 1
	$whereadd
	";
    $rsfac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    if (floatval($rsfac->fields['monto_abonar']) != floatval($rspag->fields['monto_pago'])) {
        $monto_abonar_txt = formatomoneda(floatval($rsfac->fields['monto_abonar']), 4, 'N');
        $monto_pago_txt = formatomoneda(floatval($rspag->fields['monto_pago']), 4, 'N');

        $valido = "N";
        $errores .= "- El monto de facturas seleccionadas ($monto_abonar_txt) no coincide con el total de formas de pago ($monto_pago_txt) registradas.".$saltolinea;
    }



    $res = [
        'valido' => $valido,
        'errores' => $errores
    ];

    return $res;

}
function pagar_cuentacliente($datos_ar)
{

    global $conexion;
    global $ahora;


    // recibe parametros
    $fecha_pago = antisqlinyeccion($datos_ar['fecha_pago'], "text");
    //$idcaja=antisqlinyeccion($datos_ar['idcaja'],"int");
    $registrado_por = antisqlinyeccion($datos_ar['registrado_por'], "int");
    $idcliente = antisqlinyeccion($datos_ar['idcliente'], "int");
    $idcaja_new = antisqlinyeccion($datos_ar['idcaja_new'], "int");
    $idcaja_old = antisqlinyeccion($datos_ar['idcaja_old'], "int");
    $registrado_el = antisqlinyeccion($datos_ar['registrado_el'], "date");
    $recibo = antisqlinyeccion(trim($datos_ar['recibo']), "text"); // formato 001-001-0000001  con guiones
    $sucursal = antisqlinyeccion($datos_ar['idsucursal'], "int");
    $idcajamov = antisqlinyeccion($datos_ar['idcajamov'], "int");
    $idnotacredito = antisqlinyeccion($datos_ar['idnotacredito'], "int");
    $notacredito = antisqlinyeccion($datos_ar['notacredito'], "text");
    $actu_glob = substr(strtoupper(trim($datos_ar['actu_glob'])), 0, 1);
    if ($datos_ar['idnotacredito'] > 0) {
        $whereadd = " and notacred = 'S' ";
    } else {
        $whereadd = " and notacred = 'N' ";
    }

    // conversiones
    if (trim($datos_ar['recibo']) != '') {
        $recibonum_ar = explode("-", trim($datos_ar['recibo']));
        $recibonum = solonumeros($recibonum_ar[2]);
        $sucursal_rec = intval($recibonum_ar[0]);
        $puntoexp_rec = intval($recibonum_ar[1]);
    } else {
        $recibonum = antisqlinyeccion('', "text");
        $sucursal_rec = antisqlinyeccion('', "text");
        $puntoexp_rec = antisqlinyeccion('', "text");
    }


    // total a abonar
    $consulta = "
	select sum(monto_abonar) as monto_abonar 
	from tmp_carrito_cobros 
	where 
	estado = 1 
	and idcliente = $idcliente
	and registrado_por = $registrado_por
	$whereadd
	";
    $rstot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $total_abonar = $rstot->fields['monto_abonar'];

    $consulta = "
	select *
	from tmp_carrito_cobros 
	where 
	estado = 1 
	and idcliente = $idcliente
	and registrado_por = $registrado_por
	$whereadd
	";
    $rscar = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    $estado = 0; // estado cero y al final se actualiza a 1 solo si finalizo correcto
    // inserta cabecera de pago
    $consulta = "
	insert into cuentas_clientes_pagos_cab
	(idcliente, monto_abonado, fecha_pago, idcaja_new, idcaja_old, estado, registrado_por, registrado_el, anulado_por, anulado_el, recibo, recibo_num, idcajamov, recibo_suc, recibo_pexp)
	values
	($idcliente, $total_abonar, $fecha_pago, $idcaja_new, $idcaja_old, $estado, $registrado_por, $registrado_el, NULL, NULL, $recibo, $recibonum, $idcajamov, $sucursal_rec, $puntoexp_rec)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // obtiene el id insertado
    $consulta = "
	select max(idcuentaclientepagcab) as idcuentaclientepagcab
	from  cuentas_clientes_pagos_cab 
	where 
	registrado_por = $registrado_por
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcuentaclientepagcab = intval($rs->fields['idcuentaclientepagcab']);


    // recorre el carrito temporal y abona cada cuenta
    while (!$rscar->EOF) {

        $idcta = intval($rscar->fields['idcta']);
        $monto_abonado = floatval($rscar->fields['monto_abonar']);
        $monto_nc = 0;

        // inserta cabecera de cuenta de pagos
        $consulta = "
		insert into cuentas_clientes_pagos
		(idcuentaclientepagcab, idcuenta, fecha_pago, idpago, idpago_afavor, idcliente, monto_abonado, monto_nc, idempresa, efectivogs, chequegs, banco, chequenum, estado, sucursal, idtransaccion, totalgs, registrado_por, registrado_el, anulado_por, anulado_el, idadherente, idserviciocom)
		values
		($idcuentaclientepagcab, $idcta, $fecha_pago, 0, NULL, $idcliente, $monto_abonado, $monto_nc, 1, NULL, NULL, NULL, NULL, 1, $sucursal, $idcuentaclientepagcab, $monto_abonado, $registrado_por, $registrado_el, 0, NULL, 0, 0)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // obtiene el id insertado
        $consulta = "
		select max(idregserial) as idcuentaclientepag
		from cuentas_clientes_pagos 
		where 
		idcuentaclientepagcab = $idcuentaclientepagcab
		";
        $rsmaxp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcuentaclientepag = intval($rsmaxp->fields['idcuentaclientepag']);

        // detalle de la cuenta
        $consulta = "
		select * 
		from cuentas_clientes_det
		where
		idcta = $idcta
		and saldo_cuota > 0
		order by nro_cuota asc
		";
        $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $disponible_abonar_det = $monto_abonado;

        // recorre el detalle
        while (!$rsdet->EOF) {

            $nro_cuota = $rsdet->fields['nro_cuota'];
            $saldo_cuota = $rsdet->fields['saldo_cuota'];

            // si hay disponible
            if ($disponible_abonar_det > 0) {

                if ($disponible_abonar_det > $saldo_cuota) {
                    $monto_abonado = $saldo_cuota;
                } else {
                    $monto_abonado = $disponible_abonar_det;
                }

                $consulta = "
				insert into cuentas_clientes_pagos_det
				(idcuentaclientepag, idcuentaclientepagcab, idcta, nro_cuota, monto_abonado)
				values
				($idcuentaclientepag, $idcuentaclientepagcab, $idcta, $nro_cuota, $monto_abonado)
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


                $disponible_abonar_det = $disponible_abonar_det - $monto_abonado;


            }

            $rsdet->MoveNext();
        }



        // actualiza cuenta
        $datos_cuen_ar = ['idcta' => $idcta, 'actu_glob' => $actu_glob];
        actualiza_cuentacliente($datos_cuen_ar);


        $rscar->MoveNext();
    }

    // actualizar recibo
    if ($recibonum > 0) {
        $consulta = "
		update lastcomprobantes
		set 
			numrec = $recibonum
		where 
		idsuc=$sucursal_rec 
		and pe=$puntoexp_rec
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }

    // marca como finalizado
    $consulta = "
	update cuentas_clientes_pagos_cab 
	set 
	estado = 1 
	where 
	idcuentaclientepagcab = $idcuentaclientepagcab
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $res_ar = [
        'idcuentaclientepagcab' => $idcuentaclientepagcab
    ];


    return $res_ar;

}

function actualiza_cuentacliente($datos_ar)
{
    global $conexion;
    global $saltolinea;


    // recibe parametros
    $idcta = antisqlinyeccion($datos_ar['idcta'], "int");
    $actu_glob = substr(strtoupper(trim($datos_ar['actu_glob'])), 0, 1);
    if ($actu_glob == '') {
        $actu_glob = 'S';
    }

    // siempre primero actualizar detalles y luego cabecera

    // ACTUALIZAR DETALLES
    $consulta = "
	update cuentas_clientes_det
	set
	fch_ult_pago = 
			(
			select max(cuentas_clientes_pagos.fecha_pago)
			from cuentas_clientes_pagos_det 
			inner join cuentas_clientes_pagos on cuentas_clientes_pagos.idregserial = cuentas_clientes_pagos_det.idcuentaclientepag
			where
			cuentas_clientes_pagos_det.idcta = cuentas_clientes_det.idcta
			and cuentas_clientes_pagos_det.nro_cuota = cuentas_clientes_det.nro_cuota
			and cuentas_clientes_pagos.estado <> 6
			),
	cobra_cuota = 
			COALESCE((
			select sum(cuentas_clientes_pagos_det.monto_abonado)
			from cuentas_clientes_pagos_det 
			inner join cuentas_clientes_pagos on cuentas_clientes_pagos.idregserial = cuentas_clientes_pagos_det.idcuentaclientepag
			where
			cuentas_clientes_pagos_det.idcta = cuentas_clientes_det.idcta
			and cuentas_clientes_pagos_det.nro_cuota = cuentas_clientes_det.nro_cuota
			and cuentas_clientes_pagos.estado <> 6
			),0)
	where
	idcta = $idcta
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // saldos en detalles
    $consulta = "
	update cuentas_clientes_det
	set
	saldo_cuota = monto_cuota-cobra_cuota-quita_cuota,
	dias_atraso = COALESCE(DATEDIFF(NOW(),vencimiento),0),
	dias_pago = COALESCE(DATEDIFF(fch_ult_pago,vencimiento),0),
	dias_comb = 
		CASE WHEN 
			COALESCE(DATEDIFF(NOW(),vencimiento),0) > COALESCE(DATEDIFF(fch_ult_pago,vencimiento),0) 
		THEN 
			COALESCE(DATEDIFF(NOW(),vencimiento),0) 
		ELSE 
			COALESCE(DATEDIFF(fch_ult_pago,vencimiento),0) 
		END
	where
	idcta = $idcta
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // cancelaciones en detalles
    $consulta = "
	update cuentas_clientes_det
	set
	fch_cancela = fch_ult_pago,
	estado = 3
	where
	idcta = $idcta
	and saldo_cuota = 0
	and estado <> 6
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // anulaciones en detalles
    $consulta = "
	update cuentas_clientes_det
	set
	fch_cancela = NULL,
	estado = 1
	where
	idcta = $idcta
	and saldo_cuota > 0
	and estado <> 6
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // ACTUALIZAR CABECERA
    $consulta = "
	update cuentas_clientes 
	set 
	prox_vencimiento = (select min(vencimiento) from cuentas_clientes_det where idcta = cuentas_clientes.idcta and saldo_cuota > 0),
	saldo_activo = COALESCE((select sum(saldo_cuota) from cuentas_clientes_det where idcta = cuentas_clientes.idcta),0),
	ultimo_pago=(select max(fch_ult_pago) from cuentas_clientes_det where idcta = cuentas_clientes.idcta)
	where
	idcta = $idcta
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    if ($actu_glob == 'N') {
        $whereadd = "and idcta = $idcta ";
    }

    // cancelaciones en cabecera
    $consulta = "
	update cuentas_clientes 
	set 
	estado = 3
	where
	saldo_activo = 0
	and estado <> 6
	$whereadd
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // anulaciones en cabecera
    $consulta = "
	update cuentas_clientes 
	set 
	estado = 1
	where
	saldo_activo > 0
	and estado <> 6
	$whereadd
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	select idcliente from cuentas_clientes where idcta = $idcta limit 1
	";
    $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcliente = $rscli->fields['idcliente'];

    // actualizar linea del cliente
    $consulta = "
	update cliente
	set 
	saldo_sobregiro = COALESCE(linea_sobregiro,0)-COALESCE((select sum(saldo_activo) from cuentas_clientes where idcliente = cliente.idcliente and estado = 1),0)
	where
	idcliente = $idcliente
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // actualizar saldo mensual
    $hoy = date("Y-m-d");
    $iniciomes = date("Y-m-").'01';
    $consulta = "
	update cliente
	set 
	saldo_mensual = COALESCE(max_mensual,0)-COALESCE((
	select sum(deuda_global) 
	from cuentas_clientes
	inner join ventas on ventas.idventa = cuentas_clientes.idventa 
	where 
	cuentas_clientes.idcliente = cliente.idcliente 
	and ventas.estado <> 6 
	and date(ventas.fecha) <= '$hoy'
	and date(ventas.fecha) >= '$iniciomes'
	),0)
	WHERE
    cliente.idcliente = $idcliente
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}
function paga_recurrente($parametros_cobros)
{

    global $conexion;
    global $ahora;
    global $idusu;

    $idcuentaclientepagcab = $parametros_cobros['idcuentaclientepagcab'];
    $disponible = $parametros_cobros['monto_facturado'];
    $operacion = $parametros_cobros['idoperacion'];
    $nro_cuota = $parametros_cobros['nro_cuota'];
    $fecha_pago = $parametros_cobros['fecha_pago'];
    $idcliente = $parametros_cobros['idcliente'];

    //Traemos los detalles de dicha operacion
    $buscar = "
	Select * 
	from detalle 
	where 
	idoperacion=$operacion 
	and nro_cuota = $nro_cuota
	and saldo_cuota > 0 
	order by nro_cuota asc
	limit 1
	";
    $rsdeta = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    //while($disponible > 0){


    $montoabonado = $disponible;
    $idvendedor = $idusu;

    $paso = $paso + 1;
    if ($paso == 1) {
        //creamos la cabecera
        $insertar = "
			Insert into pagos_cab
			(monto,formapago,factura,recibo,fecha_pago,idoperacion,idcliente,registrado_el,idvendedor,
			idcobrador,cheque,idbanco,tipopago,idcliente_refer,idcuentaclientepagcab)
			values
			($montoabonado,1,'','','$fecha_pago',$operacion,$idcliente,'$ahora',$idvendedor,
			$idusu,'',0,1,NULL,$idcuentaclientepagcab)
			";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        //Traemos el id
        $buscar = "
			Select max(idpagos_cab) as ultimo 
			from pagos_cab 
			where 
			idvendedor=$idvendedor 
			and idcobrador=$idusu
			";
        $rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $mayor = intval($rsf->fields['ultimo']);

    } else {
        $buscar = "
			Select max(idpagos_cab) as ultimo 
			from pagos_cab 
			where 
			idvendedor=$idvendedor 
			and idcobrador=$idusu
			";
        $rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $mayor = intval($rsf->fields['ultimo']);
    }
    //Monto cuota
    $montocuota = floatval($rsdeta->fields['monto_cuota']);
    $numcuota = floatval($rsdeta->fields['nro_cuota']);
    if ($disponible >= $montocuota) {
        $abonado = $montocuota;
    } else {
        $abonado = $disponible;
    }

    $insertar = "
		Insert into pagos_det
		(monto,idpagos_cab,detalle_idoperacion,detalle_nro_cuota,medio_pago,identificador,idbanco)
		values
		($abonado,$mayor,$operacion,$numcuota,1,0,0)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    $consulta = "
		Update detalle 
		set 
		cobra_cuota=COALESCE(
			(
			select sum(pagos_det.monto) 
			from pagos_det 
			 inner join pagos_cab on pagos_cab.idpagos_cab = pagos_det.idpagos_cab
			 where 
			 pagos_cab.tipopago = 1
			 and pagos_cab.estado <> 6
			 and pagos_det.detalle_idoperacion = detalle.idoperacion
			 and pagos_det.detalle_nro_cuota = detalle.nro_cuota
			),0)
		where
		idoperacion  = $operacion
		";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //actualizamos los datos de la operacion en detalles
    //saldo_cuota=(saldo_cuota-$abonado),
    $update = "
		Update detalle 
		set 
		saldo_cuota=COALESCE(monto_cuota,0)-COALESCE(quita_cuota,0)-COALESCE(cobra_cuota,0),
		last_pago='$fecha_pago'
		where 
		idoperacion=$operacion 
		and nro_cuota=$numcuota";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    //Operacion lobal
    $update = "
		update operacion 
		set 
		saldo=(saldo-$abonado) 
		where 
		idoperacion=$operacion
		";
    $conexion->Execute($update) or die(errorpg($conexion, $update));


    $disponible = $disponible - $abonado;



    //$rsdeta->MoveNext(); }

}

function recibo_pago($idcuentaclientepagcab)
{
    global $conexion;
    global $ahora;
    global $idempresa;
    global $saltolinea;

    $consulta = "
	select * from empresas where idempresa = $idempresa
	";
    $rsemp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $razon_social_empresa = trim($rsemp->fields['razon_social']);
    $ruc_empresa = trim($rsemp->fields['ruc']).'-'.trim($rsemp->fields['dv']);
    $direccion_empresa = trim($rsemp->fields['direccion']);
    $nombre_fantasia_empresa = trim($rsemp->fields['empresa']);
    $actividad_economica = trim($rsemp->fields['actividad_economica']);

    $consulta = "
	select * from preferencias where idempresa = $idempresa
	";
    $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $muestra_fantasia_fac = trim($rspref->fields['muestra_fantasia_fac']);
    $muestra_actividad_fac = trim($rspref->fields['muestra_actividad_fac']);

    $consulta = "
	select *, cliente.razon_social as razon_social, cliente.ruc as ruc, cliente.nombre, cliente.apellido, cliente.documento,
	cuentas_clientes_pagos_cab.idpago_afavor
	from cuentas_clientes_pagos_cab 
	inner join cliente on cliente.idcliente = cuentas_clientes_pagos_cab.idcliente
	where 
	idcuentaclientepagcab = $idcuentaclientepagcab
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcuentaclientepagcab = $rs->fields['idcuentaclientepagcab'];
    $idpago_afavor = $rs->fields['idpago_afavor'];
    $recibo_nro = $rs->fields['recibo'];
    $fechahora = date("d/m/Y", strtotime($rs->fields['fecha_pago']));
    $monto_abonado = $rs->fields['monto_abonado'];
    $razon_social = $rs->fields['razon_social'];
    $ruc = trim($rs->fields['ruc']);
    $nombre = $rs->fields['nombre'];
    $apellido = $rs->fields['apellido'];
    $documento = formatomoneda($rs->fields['documento'], 0);
    if ($ruc == '44444401-7') {
        $razon_social = $nombre.' '.$apellido;
        if (trim($nombre) == '') {
            echo "No se cargo el nombre del cliente.";
            exit;
        }
        if (intval($documento) == 0) {
            echo "No se cargo el documento del cliente.";
            exit;
        }
    }

    // concepto
    if ($idpago_afavor > 0) {
        $concepto = "ANTICIPO.";
    } else {
        $concepto = "COBRO DE FACTURAS SEGUN DETALLE.";
    }


    // numeros a letras
    require_once("includes/num2letra.php");
    $total_recibo_txt = strtoupper(num2letras(floatval($monto_abonado)));


    $consulta = "
	select *, monto_abonado as importe, 
	(
	select  factura
	from ventas 
	inner join cuentas_clientes on cuentas_clientes.idventa = ventas.idventa
	where 
	cuentas_clientes.idcta = cuentas_clientes_pagos.idcuenta
	) as factura
	from cuentas_clientes_pagos 
	where 
	idcuentaclientepagcab = $idcuentaclientepagcab
	";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    $recibo = "";
    $recibo .= $saltolinea;
    if ($muestra_fantasia_fac == 'S') {
        if (trim($nombre_fantasia_empresa) != '' && trim($nombre_fantasia_empresa) != trim($razon_social_empresa)) {
            $recibo .= texto_tk(trim($nombre_fantasia_empresa), 40, 'S').$saltolinea;
            $recibo .= texto_tk(trim('DE'), 40, 'S').$saltolinea;
        }
    }
    $recibo .= texto_tk(trim($razon_social_empresa), 40, 'S').$saltolinea;
    $recibo .= texto_tk("RUC: ".trim($ruc_empresa), 40, 'S').$saltolinea;
    if (trim($actividad_economica) != '' && $muestra_actividad_fac == 'S') {
        $recibo .= "Actividad Economica: ".trim($actividad_economica).$saltolinea;
    }
    $recibo .= 'C Matriz: '.trim($direccion_empresa).$saltolinea;
    if ($rssuc->fields['idsucu'] > 0) {
        $recibo .= 'Sucursal: '.trim($rssuc->fields['nombre']).$saltolinea;
        $recibo .= trim($rssuc->fields['direccion']).$saltolinea;
    }
    $recibo .= texto_tk('RECIBO DE DINERO', 40, 'S').$saltolinea;
    $recibo .= texto_tk('Nro: '.$recibo_nro, 40, 'S').$saltolinea;
    $recibo .= texto_tk("Fecha y Hora: ".$fechahora, 40, 'S').$saltolinea;
    $recibo .= '----------------------------------------'.$saltolinea;
    $recibo .= 'RECIBI DE: '.$razon_social.$saltolinea;
    if ($ruc != '44444401-7') {
        $recibo .= 'RUC: '.$ruc.$saltolinea;
    } else {
        $recibo .= 'DOCUMENTO: '.$documento.$saltolinea;
    }
    $recibo .= 'LA SUMA DE Gs. '.formatomoneda($monto_abonado, 2, 'N').'#.'.$saltolinea;
    $recibo .= ''.$total_recibo_txt.' GUARANIES '.$saltolinea;
    $recibo .= 'CONCEPTO:'.$concepto.$saltolinea;
    if ($idpago_afavor == 0) {
        $recibo .= '----------------------------------------'.$saltolinea;
        $recibo .= 'FACTURA         | IMPORTE               '.$saltolinea;
        $recibo .= '----------------------------------------'.$saltolinea;
        while (!$rsdet->EOF) {
            $recibo .= agregaespacio($rsdet->fields['factura'], 15).' | '.agregaespacio(formatomoneda($rsdet->fields['importe'], 2, 'N'), 22).$saltolinea;
            $rsdet->MoveNext();
        }
    }
    /*$recibo.='----------------------------------------'.$saltolinea;
    $recibo.=''.$saltolinea.$saltolinea.$saltolinea;
    $recibo.='        .....................           '.$saltolinea;
    $recibo.='            FIRMA/SELLO                 '.$saltolinea;*/
    $recibo .= '----------------------------------------'.$saltolinea;
    $recibo .= texto_tk('*** GRACIAS POR SU PAGO ***', 40, 'S').$saltolinea;
    $recibo .= 'Original: Cliente'.$saltolinea;
    $recibo .= 'Duplicado: Archivo Tributario'.$saltolinea;
    $recibo .= 'Triplicado: Contabilidad'.$saltolinea;

    return $recibo;
}


function validar_anticipo($parametros_array)
{
    global $ahora;
    global $conexion;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    // recibe parametros
    $idpago_precargado = antisqlinyeccion($parametros_array['idpago_precargado'], "int");
    $idcliente = antisqlinyeccion($parametros_array['idcliente'], "int");
    $idadherente = antisqlinyeccion(intval($parametros_array['idadherente']), "int");
    $idserviciocom = antisqlinyeccion(intval($parametros_array['idserviciocom']), "int");
    $fechahora = antisqlinyeccion($ahora, "text");
    $idusuario = antisqlinyeccion($parametros_array['idusu'], "int");
    $idempresa = antisqlinyeccion(1, "int");
    $estado = 1;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $idusu = antisqlinyeccion($parametros_array['idusu'], "int");
    $idcaja = antisqlinyeccion($parametros_array['idcaja'], "int");
    $idsucursal = antisqlinyeccion($parametros_array['idsucursal'], "int");
    $recibo = antisqlinyeccion($parametros_array['recibo'], "text");
    $fecha_recibo = antisqlinyeccion($parametros_array['fecha_recibo'], "text");
    $idevento = antisqlinyeccion($parametros_array['idevento'], "int");

    if (intval($parametros_array['idcliente']) == 0) {
        $valido = "N";
        $errores .= " - El campo idcliente no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idcaja']) == 0) {
        $valido = "N";
        $errores .= " - El campo idcaja no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idusu']) == 0) {
        $valido = "N";
        $errores .= " - El campo idusu no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idsucursal']) == 0) {
        $valido = "N";
        $errores .= " - El campo idsucursal no puede ser cero o nulo.<br />";
    }
    // valida formato de recibo
    //if(trim($parametros_array['recibo']) != ''){
    $recibonum_ar = explode("-", $parametros_array['recibo']);
    if (intval($recibonum_ar[0]) == 0) {
        $valido = "N";
        $errores .= "- La sucursal del recibo es incorrecta.".$saltolinea;
    }
    if (intval($recibonum_ar[1]) == 0) {
        $valido = "N";
        $errores .= "-El punto expedicion del recibo es incorrecto.".$saltolinea;
    }
    if (intval($recibonum_ar[2]) == 0) {
        $valido = "N";
        $errores .= "- El numero del recibo es incorrecto.".$saltolinea;
    }
    $recibo_sql = antisqlinyeccion(trim($parametros_array['recibo']), 'text');
    $consulta = "
		select * from cuentas_clientes_pagos_cab where estado <> 6 and recibo = $recibo_sql limit 1
		";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['idcuentaclientepagcab'] > 0) {
        $valido = "N";
        $errores .= "- Recibo duplicado [".antixss($parametros_array['recibo'])."], ya existe otro pago con el mismo numero de recibo.".$saltolinea;
    }

    //}


    $consulta = "
	select sum(monto_pago) as monto_pago
	from tmp_carrito_anticipos_fpag
	where
	idcliente = $idcliente
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $monto_pago_total = floatval($rsex->fields['monto_pago']);
    if (floatval($rsex->fields['monto_pago']) <= 0) {
        $valido = "N";
        $errores .= " - No registro ninguna forma de pago.<br />";
    }

    // si es un evento
    if (intval($parametros_array['idevento']) > 0) {
        // valida que el cliente del evento sea el mismo que el anticipo
        $consulta = "
		select id_cliente_solicita, id_cliente_sucu_pedido
		from pedidos_eventos 
		where 
		regid = $idevento 
		limit 1
		";
        $rsev = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcliente_evento = intval($rsev->fields['id_cliente_solicita']);
        $id_cliente_sucu_pedido = intval($rsev->fields['id_cliente_sucu_pedido']);
        if (intval($parametros_array['idcliente']) != $idcliente_evento) {
            $valido = "N";
            $errores .= "- El cliente seleccionado no es el mismo que el evento.".$saltolinea;
        }
        if ($id_cliente_sucu_pedido == 0) {
            $valido = "N";
            $errores .= "- No se indico la sucursal del cliente para el evento.".$saltolinea;
        }
    }

    // si hay paggo precargado
    if (intval($idpago_precargado) > 0) {

        // busca que existe en gest_pagos
        $consulta = "
		select idpago from gest_pagos where estado <> 6 limit 1
		";
        $rspag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rspag->fields['idpago']) > 0) {
            $valido = "N";
            $errores .= "- El pago que intenta aplicar al anticipo no existe o fue anulado.".$saltolinea;
        }

        // busca que exista en el carrito de pagos eventos


    }


    $res = ['valido' => $valido, 'errores' => $errores];

    return $res;
}
function registrar_anticipo($parametros_array)
{

    global $ahora;
    global $conexion;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    // recibe parametros
    $idpago_precargado = antisqlinyeccion($parametros_array['idpago_precargado'], "int");
    $idcliente = antisqlinyeccion($parametros_array['idcliente'], "int");
    $idadherente = antisqlinyeccion(intval($parametros_array['idadherente']), "int");
    $idserviciocom = antisqlinyeccion(intval($parametros_array['idserviciocom']), "int");

    $idusuario = antisqlinyeccion($parametros_array['idusu'], "int");
    $idempresa = antisqlinyeccion(1, "int");
    $estado = 1;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $idusu = antisqlinyeccion($parametros_array['idusu'], "int");
    $idcaja = antisqlinyeccion($parametros_array['idcaja'], "int");
    $idsucursal = antisqlinyeccion($parametros_array['idsucursal'], "int");
    $recibo = antisqlinyeccion($parametros_array['recibo'], "text");
    $idevento = antisqlinyeccion($parametros_array['idevento'], "int");
    $monto_aplicar = antisqlinyeccion($parametros_array['monto_aplicar'], "float"); // solo para anticipo multiple
    //echo $idevento;exit;

    // conversiones
    if (trim($parametros_array['recibo']) != '') {
        $recibonum_ar = explode("-", trim($parametros_array['recibo']));
        $recibonum = solonumeros($recibonum_ar[2]);
        $sucursal_rec = intval($recibonum_ar[0]);
        $puntoexp_rec = intval($recibonum_ar[1]);
    } else {
        $recibonum = antisqlinyeccion('', "text");
        $sucursal_rec = antisqlinyeccion('', "text");
        $puntoexp_rec = antisqlinyeccion('', "text");
    }

    if (trim($parametros_array['fecha_recibo']) == '') {
        $fechahora = antisqlinyeccion($ahora, "text");
    } else {
        $fechahora = antisqlinyeccion(trim($parametros_array['fecha_recibo']), "text");
    }

    if (floatval($parametros_array['monto_aplicar']) == 0) {
        $consulta = "
		select sum(monto_pago) as monto_pago
		from tmp_carrito_anticipos_fpag
		where
		idcliente = $idcliente
		";
        $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $monto_pago_total = floatval($rsex->fields['monto_pago']);
    } else {
        $monto_pago_total = floatval($parametros_array['monto_aplicar']);
    }

    // estado cero y al final se actualiza a 1 solo si finalizo correcto
    // inserta cabecera de recibos
    $consulta = "
	insert into cuentas_clientes_pagos_cab
	(idcliente, monto_abonado, fecha_pago, idcaja_new, idcaja_old, estado, registrado_por, registrado_el, anulado_por, anulado_el, recibo, recibo_num, idcajamov, recibo_suc, recibo_pexp)
	values
	($idcliente, $monto_pago_total, $fechahora, 0, $idcaja, 0, $idusu, $registrado_el, NULL, NULL, $recibo, $recibonum, 0, $sucursal_rec, $puntoexp_rec)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
	select idcuentaclientepagcab 
	from cuentas_clientes_pagos_cab
	where
	registrado_por = $idusu
	order by idcuentaclientepagcab desc
	limit 1
	";
    $rsult = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcuentaclientepagcab = $rsult->fields['idcuentaclientepagcab'];

    // insertar en caja si no viene el pago desde afuera (para  un evento que tiene un pago para varios anticipos)
    if (intval($idpago_precargado) == 0) {
        $insertar = "
		Insert into gest_pagos
		(idcliente,fecha,medio_pago,total_cobrado,chequenum,banco,numtarjeta,montotarjeta,
		factura,recibo,tickete,ruc,tipo_pago,idempresa,sucursal,efectivo,
		codtransfer,montotransfer,montocheque,cajero,idventa,vueltogs,idpedido,
		delivery,idmesa,idcaja,rendido,tipotarjeta,
		idtipocajamov,tipomovdinero,idcuentaclientepagcab)
		values
		($idcliente,'$ahora',0,$monto_pago_total,NULL,NULL,0,0,
		NULL,'','',NULL,0,1,$idsucursal,0,
		NULL,0,0,$idusu,0,0,0,
		0,NULL,$idcaja,'S',0,
		10,'E',$idcuentaclientepagcab)
		";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


        $consulta = "
		select max(idpago) as idpago from gest_pagos where cajero = $idusu and idcaja = $idcaja limit 1
		";
        $rsmaxpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idpago_gest = $rsmaxpag->fields['idpago'];

        // recorre las formas de pago e inserta
        $consulta = "
		select monto_pago, idformapago, transfer_numero, tarjeta_boleta, cheque_numero, boleta_deposito, retencion_numero, idbanco, idbanco_propio
		from tmp_carrito_anticipos_fpag
		where
		idcliente = $idcliente
		order by idcarritoanticiposfpag asc
		";
        $rsdetpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        while (!$rsdetpag->EOF) {

            $monto_pago = $rsdetpag->fields['monto_pago'];
            $idformapago = $rsdetpag->fields['idformapago'];
            $transfer_numero = antisqlinyeccion($rsdetpag->fields['transfer_numero'], "text");
            $tarjeta_boleta = antisqlinyeccion($rsdetpag->fields['tarjeta_boleta'], "text");
            $cheque_numero = antisqlinyeccion($rsdetpag->fields['cheque_numero'], "text");
            $boleta_deposito = antisqlinyeccion($rsdetpag->fields['boleta_deposito'], "text");
            $retencion_numero = antisqlinyeccion($rsdetpag->fields['retencion_numero'], "text");
            $idbanco = antisqlinyeccion($rsdetpag->fields['idbanco'], "int");
            $idbanco_propio = antisqlinyeccion($rsdetpag->fields['idbanco_propio'], "int");

            $consulta = "
			INSERT INTO gest_pagos_det
			(idpago, monto_pago_det, idformapago) 
			VALUES 
			($idpago_gest, $monto_pago, $idformapago)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $consulta = "
			select max(idpagodet) as idpagodet from gest_pagos_det where idpago = $idpago_gest 
			";
            $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idpagodet = intval($rsmax->fields['idpagodet']);

            $consulta = "
			INSERT INTO gest_pagos_det_datos
			(idpagodet, idbanco, idbanco_propio, transfer_numero, tarjeta_boleta, cheque_numero, boleta_deposito, retencion_numero)
			VALUES 
			($idpagodet, $idbanco, $idbanco_propio, $transfer_numero, $tarjeta_boleta, $cheque_numero, $boleta_deposito, $retencion_numero)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $rsdetpag->MoveNext();
        }
        // si viene un pago precargado
    } else {
        // se debe validar por fuera de la funcion que coincidan los montos
        $idpago_gest = $idpago_precargado;

    }

    $consulta = "
	insert into pagos_afavor_adh
	(idpago, monto, saldo, idcliente, idadherente, idserviciocom, fechahora, idusuario, idcaja, idempresa, idsucursal,
	estado, registrado_el, idevento)
	values
	($idpago_gest, $monto_pago_total, $monto_pago_total, $idcliente, $idadherente, $idserviciocom, $fechahora, $idusuario, $idcaja, 1, $idsucursal, 
	0, $registrado_el, $idevento)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	select idpago_afavor from pagos_afavor_adh where idpago = $idpago_gest order by idpago_afavor desc limit 1
	";
    $rspag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpago_afavor = intval($rspag->fields['idpago_afavor']);

    if (floatval($parametros_array['monto_aplicar']) == 0) {
        // borra el carrito
        $consulta = "
		delete
		from tmp_carrito_anticipos_fpag
		where
		idcliente = $idcliente
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
    // borra el carrito
    $consulta = "
	delete
	from tmp_carrito_anticipos_fpag
	where
	idcliente = $idcliente
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // actualizar recibo
    if ($recibonum > 0) {
        $consulta = "
		update lastcomprobantes
		set 
			numrec = $recibonum
		where 
		idsuc=$sucursal_rec 
		and pe=$puntoexp_rec
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }

    // actualiza saldo de la linea de credito del cliente
    actualiza_saldos_clientes($idcliente, $idadherente, $idserviciocom);

    // si es un evento actualiza su saldo
    if (intval($parametros_array['idevento']) > 0) {
        $parametros_array_evento['idevento'] = intval($parametros_array['idevento']);
        actualiza_saldo_evento($parametros_array_evento);
    }

    // marca como finalizado enn ambas tablas
    $consulta = "
	update pagos_afavor_adh 
	set 
	estado = 1
	where 
	idpago_afavor=$idpago_afavor
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
	update cuentas_clientes_pagos_cab 
	set 
	estado = 1,
	idpago_afavor=$idpago_afavor
	where 
	idcuentaclientepagcab = $idcuentaclientepagcab
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    $res = ['valido' => $valido, 'errores' => $errores, 'idpago_afavor' => $idpago_afavor];

    return $res;
}

function valida_anulacion_anticipo($parametros_array)
{
    global $ahora;
    global $conexion;

    // validaciones basicas
    $valido = "S";
    $errores = "";

    $idpago_afavor = intval($parametros_array['idpago_afavor']);
    $anulado_por = intval($parametros_array['anulado_por']);
    $anulado_el = intval($parametros_array['anulado_el']);

    if (intval($parametros_array['anulado_por']) == 0) {
        $valido = "N";
        $errores .= "- Debe indicar el usuario que anula.<br />";
    }
    if (trim($parametros_array['anulado_el']) == '') {
        $valido = "N";
        $errores .= "- Debe indicar la fecha de anulacion.<br />";
    }


    $consulta = "
	select idpago_afavor from pagos_afavor_adh where idpago_afavor = $idpago_afavor and estado <> 6 limit 1
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rs->fields['idpago_afavor']) == 0) {
        $valido = "N";
        $errores .= "- El pago no existe o ya fue anulado.<br />";
    }


    $res = ['valido' => $valido, 'errores' => $errores];

    return $res;
}
function registra_anulacion_anticipo($parametros_array)
{
    global $ahora;
    global $conexion;

    // validaciones basicas
    $valido = "S";
    $errores = "";

    $idpago_afavor = antisqlinyeccion($parametros_array['idpago_afavor'], "int");
    $anulado_por = antisqlinyeccion($parametros_array['anulado_por'], "int");
    $anulado_el = antisqlinyeccion($parametros_array['anulado_el'], "text");

    $consulta = "
	select idpago_afavor, idcliente, idpago, idadherente, idserviciocom
	from pagos_afavor_adh 
	where 
	idpago_afavor = $idpago_afavor 
	and estado <> 6
	limit 1
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpago = intval($rs->fields['idpago']);
    $idcliente = intval($rs->fields['idcliente']);
    $idadherente = intval($rs->fields['idadherente']);
    $idserviciocom = intval($rs->fields['idserviciocom']);
    if ($idpago == 0) {
        echo  "Pago no encontrado.";
        exit;
    }


    $consulta = "
	select idcaja from gest_pagos where idpago = $idpago limit 1
	";
    $rscaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcaja = intval($rscaj->fields['idcaja']);
    if ($idcaja == 0) {
        echo  "Caja inexistente!";
        exit;
    }

    $consulta = "
	delete from adherente_estadocuenta 
	where 
	idpago_afavor = $idpago_afavor
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	update pagos_afavor_adh 
	set 
	estado = 6, 
	anulado_por = $anulado_por,
	anulado_el = $anulado_el
	where 
	idpago_afavor = $idpago_afavor
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	update cuentas_clientes_pagos_cab 
	set 
	estado = 6, 
	anulado_por = $anulado_por,
	anulado_el = $anulado_el
	where 
	idpago_afavor = $idpago_afavor
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	update gest_pagos 
	set 
	estado = 6, 
	anulado_por = $anulado_por,
	anulado_el = $anulado_el
	where 
	idpago = $idpago
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // actualiza saldo de la linea de credito del cliente
    actualiza_saldos_clientes($idcliente, $idadherente, $idserviciocom);

    recalcular_caja($idcaja);



    $res = ['valido' => $valido, 'errores' => $errores];

    return $res;
}
function recibo_pago_pre($idcuentaclientepagcab)
{
    global $conexion;
    global $ahora;
    global $idempresa;
    global $saltolinea;

    $consulta = "
	select * from empresas where idempresa = $idempresa
	";
    $rsemp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $razon_social_empresa = trim($rsemp->fields['razon_social']);
    $ruc_empresa = trim($rsemp->fields['ruc']).'-'.trim($rsemp->fields['dv']);
    $direccion_empresa = trim($rsemp->fields['direccion']);
    $nombre_fantasia_empresa = trim($rsemp->fields['empresa']);
    $actividad_economica = trim($rsemp->fields['actividad_economica']);



    $consulta = "
	select * from preferencias where idempresa = $idempresa
	";
    $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $muestra_fantasia_fac = trim($rspref->fields['muestra_fantasia_fac']);
    $muestra_actividad_fac = trim($rspref->fields['muestra_actividad_fac']);

    $consulta = "
	select *, cliente.razon_social as razon_social, cliente.ruc as ruc, cliente.nombre, cliente.apellido, cliente.documento,
	CASE WHEN 
		cuentas_clientes_pagos_cab.idsucursal_clie IS NOT NULL
	THEN
		(select direccion from sucursal_cliente where idsucursal_clie = cuentas_clientes_pagos_cab.idsucursal_clie) 
	ELSE
		cliente.direccion
	END  as direccion_cli,
	CASE WHEN 
		cuentas_clientes_pagos_cab.idsucursal_clie IS NOT NULL
	THEN
		(select sucursal from sucursal_cliente where idsucursal_clie = cuentas_clientes_pagos_cab.idsucursal_clie) 
	ELSE
		''
	END AS sucursal_cli,
	cliente.razon_social as razon_social_cli,
	cuentas_clientes_pagos_cab.recibo as recibo, cuentas_clientes_pagos_cab.idpago_afavor
	from cuentas_clientes_pagos_cab 
	inner join cliente on cliente.idcliente = cuentas_clientes_pagos_cab.idcliente
	where 
	cuentas_clientes_pagos_cab.idcuentaclientepagcab = $idcuentaclientepagcab
	and cuentas_clientes_pagos_cab.estado = 1
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcuentaclientepagcab = $rs->fields['idcuentaclientepagcab'];
    $idpago_afavor = intval($rs->fields['idpago_afavor']);


    // concepto
    if ($idpago_afavor > 0) {
        $concepto = "ANTICIPO.";
    } else {
        $concepto = "COBRO DE FACTURAS SEGUN DETALLE.";
    }

    if (intval($idcuentaclientepagcab) == 0) {
        echo "RECIBO ANULADO!";
        exit;
    }
    $idcajamov = $rs->fields['idcajamov'];
    $recibo_nro = $rs->fields['recibo'];
    $fechahora = date("d/m/Y", strtotime($rs->fields['fecha_pago']));
    $monto_abonado = $rs->fields['monto_abonado'];
    $razon_social = $rs->fields['razon_social'];
    $ruc = trim($rs->fields['ruc']);
    $nombre = $rs->fields['nombre'];
    $apellido = $rs->fields['apellido'];
    $documento = formatomoneda($rs->fields['documento'], 0);
    if ($ruc == '44444401-7') {
        $razon_social = $nombre.' '.$apellido;
        if (trim($nombre) == '') {
            echo "No se cargo el nombre del cliente.";
            exit;
        }
        if (intval($documento) == 0) {
            echo "No se cargo el documento del cliente.";
            exit;
        }
    }




    // numeros a letras
    require_once("includes/num2letra.php");
    $total_recibo_txt = strtoupper(num2letras(floatval($monto_abonado)));


    $consulta = "
	select *, monto_abonado as importe, 
	(
	select  factura
	from ventas 
	inner join cuentas_clientes on cuentas_clientes.idventa = ventas.idventa
	where 
	cuentas_clientes.idcta = cuentas_clientes_pagos.idcuenta
	) as factura, 
	(
	select  ventas.idventa
	from ventas 
	inner join cuentas_clientes on cuentas_clientes.idventa = ventas.idventa
	where 
	cuentas_clientes.idcta = cuentas_clientes_pagos.idcuenta
	) as idventa ,
	(
	select  date(ventas.fecha) as fecha_emision
	from ventas 
	inner join cuentas_clientes on cuentas_clientes.idventa = ventas.idventa
	where 
	cuentas_clientes.idcta = cuentas_clientes_pagos.idcuenta
	) as fecha_emision,
	(
	select  date(cuentas_clientes.prox_vencimiento) as fecha_prox_vencimiento
	from cuentas_clientes 
	where 
	cuentas_clientes.idcta = cuentas_clientes_pagos.idcuenta
	) as fecha_prox_vencimiento
	from cuentas_clientes_pagos 
	where 
	idcuentaclientepagcab = $idcuentaclientepagcab
	";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    while (!$rsdet->EOF) {

        $recibo_det[] = [
            'idventa' => $rsdet->fields['idventa'],
            'factura' => $rsdet->fields['factura'],
            'monto' => floatval($rsdet->fields['importe']),
            'fecha_emision' => $rsdet->fields['fecha_emision'],
            'fecha_prox_vencimiento' => $rsdet->fields['fecha_prox_vencimiento'],
        ];

        $rsdet->MoveNext();
    }

    if ($idpago_afavor > 0) {
        $consulta = "
		SELECT formas_pago.descripcion as forma_de_pago, monto_pago_det as monto_movimiento 
		FROM gest_pagos_det
		inner join gest_pagos on gest_pagos.idpago = gest_pagos_det.idpago
		inner join pagos_afavor_adh on pagos_afavor_adh.idpago = gest_pagos.idpago
		inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
		where 
		pagos_afavor_adh.idpago_afavor = $idpago_afavor
		order by formas_pago.descripcion asc
		";
        $rsform = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    } else {
        $consulta = "
		SELECT formas_pago.idforma, formas_pago.descripcion as forma_de_pago, sum(monto_pago_det) as monto_movimiento
		FROM gest_pagos
		inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
		inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
		where 
		gest_pagos.idcuentaclientepagcab = $idcuentaclientepagcab
		group by formas_pago.idforma, formas_pago.descripcion
		order by formas_pago.descripcion asc
		";
        $rsform = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }

    while (!$rsform->EOF) {

        $recibo_pag[] = [
            'idformapago' => $rsform->fields['idforma'],
            'forma_de_pago' => $rsform->fields['forma_de_pago'],
            'monto_pago' => floatval($rsform->fields['monto_movimiento']),
        ];

        $rsform->MoveNext();
    }

    $recibo_completo = $rs->fields['recibo'];
    //echo $recibo_completo;
    $recibo_cab = [
        'idcuentaclientepagcab' => $idcuentaclientepagcab,
        'recibo' => $recibo_completo,
        'concepto' => $concepto,
        'fantasia_emp' => trim($nombre_fantasia_empresa),
        'razon_social_emp' => limpiar_txt_fac(trim($razon_social_empresa)),
        'ruc_emp' => trim($ruc_empresa),
        'actividad_emp' => limpiar_txt_fac(trim($actividad_economica)),
        'direccion_emp' => limpiar_txt_fac(trim($direccion_empresa)),
        /*'sucursal_emp' => limpiar_txt_fac(trim($rssuc->fields['nombre'])),*/

        'direccion_cli' => limpiar_txt_fac(trim($rs->fields['direccion'])),
        'sucursal_cli' => limpiar_txt_fac(trim($rs->fields['sucursal_cli'])),
        'razon_social_cli' => limpiar_txt_fac(trim($rs->fields['razon_social_cli'])),
        'ruc_cli' => trim($rs->fields['ruc']),
        'fecha_recibo' => (trim($rs->fields['fecha_pago'])),
        'fec_impreso' => (trim($ahora)),
        'documento_cli' => limpiar_txt_fac(trim($rs->fields['documento'])),

        'monto_recibo' => limpiar_txt_fac(floatval($rs->fields['monto_abonado'])),
        'monto_recibo_txt' => limpiar_txt_fac(trim($total_recibo_txt)),

        'detalle_recibo' => $recibo_det,
        'pagos_recibo' => $recibo_pag

    ];

    //print_r($recibo_cab);
    // convierte a formato json
    $recibo_cab = json_encode($recibo_cab, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

    return $recibo_cab;
}
function actualiza_saldo_evento_carrito_tmp_facturar($parametros_array)
{
    global $conexion;
    $idevento = intval($parametros_array['idevento']);

    // calcula monto  de evento
    //saldo_evento,monto_pedido,cobrado_evento
    $consulta = "
	update tmp_detalles_eventos_factu 
	set 
	monto_pedido  = 
	COALESCE((select monto_pedido from pedidos_eventos where regid = $idevento ),0),
	cobrado_pedido =
	COALESCE((select cobrado_evento from pedidos_eventos where regid = $idevento ),0),
	saldo =
	COALESCE((select monto_pedido-cobrado_evento from pedidos_eventos where regid = $idevento ),0)
	WHERE 
	idevento = $idevento
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
function actualiza_saldo_evento($parametros_array)
{

    global $conexion;

    $idevento = intval($parametros_array['idevento']);
    if ($idevento == 0) {
        echo "No se recibio el idevento";
        exit;
    }

    // calcula monto  de evento
    $consulta = "
	update pedidos_eventos 
	set 
	monto_evento = 
	COALESCE((SELECT sum(subtotal) from tmp_carrito_pedidos where idtransaccion = pedidos_eventos.idtransaccion),0)
	-COALESCE(descuento_neto,0)
	WHERE 
	regid = $idevento
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    /*$consulta="
    update pedidos_eventos
    set
    monto_evento =
    COALESCE((SELECT sum(subtotal) from pedidos_eventos_detalles where idpedidocatering = pedidos_eventos.regid ),0)
    -COALESCE(descuento_neto,0)
    WHERE
    regid = $idevento
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/

    // un solo anticipo se podra hacer por eveneto
    // una venta si se puede aplicar a varios eventos
    // pero un pago se puede aplicar a varios anticipos (gest_pagos a varios pagos_avfavor)
    // calcula cobrado evento
    /*
    // metodo viejo
    $consulta="
    update  pedidos_eventos
    set
    cobrado_evento =
    COALESCE((
        SELECT SUM(pagos_afavor_adh.saldo) as saldo_anticipo
        from pagos_afavor_adh
        where
        pagos_afavor_adh.idevento = pedidos_eventos.regid
        and pagos_afavor_adh.estado <> 6
    ),0)
    +COALESCE((
        SELECT SUM(ventas.totalcobrar) as facturas_contado
        from ventas
        inner join ventas_datosextra on ventas_datosextra.idventa = ventas.idventa
        where
        ventas_datosextra.idevento = pedidos_eventos.regid
        and ventas.estado <> 6
        and ventas.tipo_venta = 1
    ),0)
    +COALESCE((
        SELECT SUM(cuentas_clientes.saldo_activo) as facturas_credito_saldo
        from cuentas_clientes
        inner join ventas on ventas.idventa = cuentas_clientes.idventa
        inner join ventas_datosextra on ventas_datosextra.idventa = ventas.idventa
        where
        ventas_datosextra.idevento = pedidos_eventos.regid
        and ventas.estado <> 6
        and ventas.tipo_venta = 2
    ),0)
    where
    pedidos_eventos.regid = $idevento
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    */
    // metodo nuevo



    //Consulta de actualizacion de cobrados y monto de pedidos anterior 20/09/2023
    /*$consulta="
    update  pedidos_eventos
    set
    cobrado_evento =
    COALESCE((
        SELECT SUM(pagos_afavor_adh.saldo) as saldo_anticipo
        from pagos_afavor_adh
        where
        pagos_afavor_adh.idevento = pedidos_eventos.regid
        and pagos_afavor_adh.estado <> 6
    ),0)
    +COALESCE((
        SELECT SUM(totalcobrar) as totalcobrar
        from ventas
        where
        ventas.estado <> 6
        and ventas.tipo_venta = 1
        and ventas.idventa in (
            select idventa
            from ventas_pedidos_eventos
            where
            ventas_pedidos_eventos.idevento = pedidos_eventos.regid
        )
    ),0)
    +COALESCE((
        SELECT SUM(deuda_global)-SUM(cuentas_clientes.saldo_activo) as facturas_credito_cobrado
        from cuentas_clientes
        inner join ventas on ventas.idventa = cuentas_clientes.idventa
        where
        ventas.estado <> 6
        and ventas.tipo_venta = 2
        and ventas.idventa in (
            select idventa
            from ventas_pedidos_eventos
            where
            ventas_pedidos_eventos.idevento = pedidos_eventos.regid
        )
    ),0)
    where
    pedidos_eventos.regid = $idevento
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    */





    $consulta = "
	update  pedidos_eventos
	set 
	cobrado_evento = 
	COALESCE((
		SELECT SUM(pagos_afavor_adh.saldo) as saldo_anticipo 
		from pagos_afavor_adh 
		where 
		pagos_afavor_adh.idevento = pedidos_eventos.regid
		and pagos_afavor_adh.estado <> 6
	),0)
	+COALESCE((
		SELECT SUM(monto_aplicado) as totalcobrar
		from ventas_pedidos_eventos 
		inner join ventas on ventas.idventa = ventas_pedidos_eventos.idventa
        where ventas.estado <> 6
		and ventas.tipo_venta = 1 and ventas_pedidos_eventos.idevento= pedidos_eventos.regid
	),0)
	+COALESCE((
		SELECT SUM(deuda_global)-SUM(cuentas_clientes.saldo_activo) as facturas_credito_cobrado
		from cuentas_clientes 
		inner join ventas on ventas.idventa = cuentas_clientes.idventa
		where 
		ventas.estado <> 6
		and ventas.tipo_venta = 2
        and ventas.idventa in (
			select idventa 
			from ventas_pedidos_eventos
			where
			ventas_pedidos_eventos.idevento = pedidos_eventos.regid
        )
	),0)
	where
	pedidos_eventos.regid = $idevento
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    $consulta = "
	update pedidos_eventos
	set 
	monto_sin_descuento = COALESCE(monto_evento,0)+COALESCE(descuento_neto,0)
	where
	pedidos_eventos.regid = $idevento
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // calcula saldo evento
    $consulta = "
	update pedidos_eventos
	set 
	saldo_evento = COALESCE(monto_evento,0)-COALESCE(cobrado_evento,0)
	where
	pedidos_eventos.regid = $idevento
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //por cada actualizacion validamos el
    //carrito con los datos actualizados de monto_evento,cobrado_evento y saldo_evento


    $res = [
        'valido' => 'S',
        'errores' => ''
    ];

    return $res;


}
function actualiza_saldo_anticipo($parametros_array)
{

    global $conexion;
    $idpago_afavor = intval($parametros_array['idpago_afavor']);
    // saldo = monto anticipo - ventas al contado aplicadas - ventas a  credito aplicadas
    // falta agregar a credito a esta consulta
    $consulta = "
	update pagos_afavor_adh
	SET
		saldo = 
		COALESCE(monto,0)
		-COALESCE((
			select sum(gest_pagos_det.monto_pago_det) as total
			from gest_pagos 
			inner join ventas on ventas.idventa = gest_pagos.idventa
			inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago 
			inner join gest_pagos_det_datos on gest_pagos_det_datos.idpagodet = gest_pagos_det.idpagodet
			WHERE
			gest_pagos.estado <> 6
			and ventas.estado <> 6
			and gest_pagos_det_datos.idpago_afavor = pagos_afavor_adh.idpago_afavor
		 ),0)
	WHERE
	idpago_afavor = $idpago_afavor
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
