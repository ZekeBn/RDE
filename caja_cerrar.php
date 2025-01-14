<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");

if (isset($_POST['cual']) && (intval($_POST['cual']) == 3)) {
    // busca si hay una caja abierta por este usuario
    $buscar = "
	Select * 
	from caja_super 
	where 
	estado_caja=1 and tipocaja=1
	and cajero=$idusu 
	order by fecha_apertura desc 
	limit 1
	";
    $rscaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idcaja = $rscaj->fields['idcaja'];
    // si no existe caja abierta redirecciona a impresiones
    if ($idcaja == 0) {
        header("location: caja_cerrar_imprime.php");
        exit;
    }
    // si existe caja abierta cierra
    if ($idcaja > 0) {
        // centrar nombre de empresa
        $nombreempresa_centrado = corta_nombreempresa($nombreempresa);

        /*--------------------- INICIO CIERRE DE CAJA -------------------------------------------------*/

        // parche 1
        $consulta = "
		update gest_pagos 
		set 
		cajero = (select caja_super.cajero from caja_super where caja_super.idcaja = gest_pagos.idcaja)
		 where 
		cajero = 0
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // parche 2
        $consulta = "
		update gest_pagos 
		set 
		tipotarjeta = 1
		 where 
		tipotarjeta = 0
		and montotarjeta > 0
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // parche 3
        $consulta = "
		update gest_pagos 
		set 
		sucursal = (select caja_super.sucursal from caja_super where caja_super.idcaja = gest_pagos.idcaja)
		 where 
		sucursal = 0
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //cierre de caja
        //Cierre efectivo de caja
        //$idcaja=floatval($_POST['ocidcaja']);
        //$faltante=floatval($_POST['falta']);
        //$sobrante=floatval($_POST['sobrante']);
        $sel = $_POST['selefe'];
        $sel = str_replace("'", "", $sel);

        $fechahoy2 = antisqlinyeccion($sel, 'date');
        $caja_chica_cierre = floatval($_POST['caja_chica_cierre']);

        //Total de Cobranzas en el dia
        $buscar = "Select  sum(total_cobrado) as tcobra from gest_pagos where cajero=$idusu and estado=1 and idcaja=$idcaja and rendido='S'";
        $rscobro = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $tcobranza = floatval($rscobro->fields['tcobra']);

        //Total de ventas EFEC en el dia
        $buscar = "Select  sum(totalcobrar) as tventa from ventas where registrado_por=$idusu and estado=1 and idcaja=$idcaja";
        $rsventas = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $tventa = floatval($rsventas->fields['tventa']);

        //Total de ventas CREDITO en el dia
        $buscar = "Select  sum(totalcobrar) as tventa from ventas where registrado_por=$idusu and estado=2 and idcaja=$idcaja and tipo_venta=2";
        $rscc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $cred = floatval($rscc->fields['tventa']);

        //Cobranza en Efectivo
        $buscar = "Select  sum(efectivo) as efectivogs from gest_pagos 
		where
		 cajero=$idusu and estado=1 and idcaja=$idcaja and rendido='S'";
        $rsefe = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $tefe = floatval($rsefe->fields['efectivogs']);

        //Cobranzas  Tarjeta  Credito
        $buscar = "Select  sum(montotarjeta) as tarje from gest_pagos 
		where 
		cajero=$idusu and estado=1 and idcaja=$idcaja  and rendido='S' and tipotarjeta = 1";
        $rstarje = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


        //Cobranzas  Tarjeta debito
        $buscar = "Select  sum(montotarjeta) as tarje from gest_pagos 
		where 
		cajero=$idusu and estado=1 and idcaja=$idcaja  and rendido='S' and tipotarjeta = 2";
        $rstarjedeb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        // total tarjeta
        $tarje = floatval($rstarje->fields['tarje']) + floatval($rstarjedeb->fields['tarje']);

        //Cobranzas  cheque
        $buscar = "Select  sum(montocheque) as cheque from gest_pagos 
		where 
		cajero=$idusu and estado=1 and idcaja=$idcaja  and rendido='S'";
        $rscheque = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



        //Cobranzas  transferencia
        $buscar = "Select  sum(montotransfer) as transfer from gest_pagos 
		where 
		cajero=$idusu and estado=1 and idcaja=$idcaja  and rendido='S'";
        $rstransfer = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        //Pagos por caja
        /*$buscar="Select sum(montogs) as totalp from caja_pagos where idcaja=$idcaja and cajero=$idusu and estado=1";
        $rspagca=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));*/
        $consulta = "
		select (COALESCE(sum(cuentas_empresa_pagos.monto_abonado),0)+COALESCE((Select sum(monto_abonado) as mon from pagos_extra where idcaja=$idcaja and idempresa=$idempresa and estado <> 6 and tipocaja = 'R'),0)) as totalp_r,
		(COALESCE(sum(cuentas_empresa_pagos.monto_abonado),0)+COALESCE((Select sum(monto_abonado) as mon from pagos_extra where idcaja=$idcaja and idempresa=$idempresa and estado <> 6  and tipocaja = 'C'),0)) as totalp
		from cuentas_empresa_pagos
		inner join cuentas_empresa on cuentas_empresa_pagos.idcta = cuentas_empresa.idcta
		where
		cuentas_empresa_pagos.idcaja = $idcaja
		and cuentas_empresa_pagos.idempresa = $idempresa
		and cuentas_empresa_pagos.estado <> 6
		ORDER BY cuentas_empresa_pagos.fecha_pago asc
		";
        $rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $tpagos = floatval($rsv->fields['totalp_r']);
        $tpagos_ch = floatval($rsv->fields['totalp']);

        $consulta = "
		select (COALESCE(sum(cuentas_empresa_pagos.monto_abonado),0)+COALESCE((Select sum(monto_abonado) as mon from pagos_extra where idcaja=$idcaja and idempresa=$idempresa and estado <> 6  and tipocaja = 'R'),0)) as totalp_r,
		(COALESCE(sum(cuentas_empresa_pagos.monto_abonado),0)+COALESCE((Select sum(monto_abonado) as mon from pagos_extra where idcaja=$idcaja and idempresa=$idempresa and estado <> 6  and tipocaja = 'C'),0)) as totalp
		from cuentas_empresa_pagos
		inner join cuentas_empresa on cuentas_empresa_pagos.idcta = cuentas_empresa.idcta
		where
		cuentas_empresa_pagos.idcaja = $idcaja
		and cuentas_empresa_pagos.idempresa = $idempresa
		and cuentas_empresa_pagos.estado <> 6
		and cuentas_empresa_pagos.mediopago = 1
		ORDER BY cuentas_empresa_pagos.fecha_pago asc
		";
        $rsvef = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $tpagosef = floatval($rsvef->fields['totalp_r']);
        $tpagosef_ch = floatval($rsv->fields['totalp']);

        //Retiros(entrega de plata)desde el cajero al supervisor
        $buscar = "Select count(*) as cantidad,sum(monto_retirado) as tretira from caja_retiros
		where idcaja=$idcaja and cajero=$idusu and estado=1";
        $rsretiros = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $tretiros = intval($rsretiros->fields['cantidad']);
        $tretirosgs = intval($rsretiros->fields['tretira']);

        //Reposiciones de Dinero (desde el tesorero al cajero
        $buscar = "Select  sum(monto_recibido) as recibe from caja_reposiciones where idcaja=$idcaja and cajero=$idusu and estado=1";
        $rsrepo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $trepo = floatval($rsrepo->fields['recibe']);


        // valores caja abierta
        //$monto_apertura=$rscaja->fields['monto_apertura'];
        //$tpagos=floatval($rsv->fields['totalp']);
        //$tefe=floatval($rsefe->fields['efectivogs']);
        $tarjecred = floatval($rstarje->fields['tarje']);
        $tarjedeb = floatval($rstarjedeb->fields['tarje']);
        //$tdesc=$rsdesctot->fields['total_descuento'];
        $tcheque = floatval($rscheque->fields['cheque']);
        $ttransfer = floatval($rstransfer->fields['transfer']);
        //$tventacred=floatval($rsventacred->fields['total_venta_cred']);

        //Disponible actual caja
        $totalteorico = $tefe + $montobre + $tarje + $tcheque + $ttransfer - $tpagos;
        $totalteoricoefe = $tefe + $montobre - $tpagos;
        //echo $totalteorico;

        //total en monedas extranjeras pero convertidas a gs
        $buscar = "select sum(subtotal) as tmone from caja_moneda_extra where idcaja=$idcaja and cajero=$idusu and estado=1";
        $extra = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $textra = floatval($extra->fields['tmone']);



        //total en monedas arqueadas
        $buscar = "select sum(subtotal) as total from caja_billetes where idcaja=$idcaja and idcajero=$idusu and estado=1";
        $tarqueo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $tarquegs = intval($tarqueo->fields['total']);

        //Cobranza pendiente x delivery
        $buscar = "Select  sum(total_cobrado) as totalpend  from gest_pagos 
			where 
			cajero=$idusu  
			and estado=1 
			and idcaja=$idcaja 
			and rendido ='N'";
        $rspend = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $tpendi1 = floatval($rspend->fields['totalpend']);



        // efectivo en moneda extrangera + moneda nacional
        $neto = $textra + $tarquegs;

        // todo el efectivo de todas las monedas + tarjetas de credito + cheque + transfer
        $subtotal = $neto + $tarje + $tcheque + $ttransfer - ($tpagos);

        //Vemos el faltante y sobrante
        $sobrante = $neto - $totalteoricoefe;
        $faltante = $totalteoricoefe - $neto;
        if ($sobrante < 0) {
            $sobrante = 0;
        }
        if ($faltante < 0) {
            $faltante = 0;
        }

        //echo $tretirosgs;
        //$dispo=(floatval($rscaja->fields['monto_apertura'])+$tefe+$trepo+$textra+$tarquegs+$tarje)-$tretirosgs;
        $dispo = ($subtotal + $trepo) - $tretirosgs;
        //echo floatval($tefe);
        //echo $dispo;
        $ape = $rscaja->fields['monto_apertura'];
        $ape_ch = $rscaja->fields['caja_chica'];
        $montocierre = floatval($textra) + floatval($tarquegs);

        $dispo = floatval($dispo);
        $ahora = date("Y-m-d H:i:s");

        //Registramos
        $update = "
			update caja_super set 
			estado_caja=3,monto_cierre=$montocierre,total_cobros_dia=$tcobranza,total_pagos_dia=$tpagos,
			fecha_cierre='$ahora',faltante=$faltante,sobrante=$sobrante,total_efectivo=$tefe,total_tarjeta=$tarjecred,
			total_tarjeta_debito=$tarjedeb,total_cheque=$tcheque,total_transfer=$ttransfer,
			total_global_gs=$dispo,total_entrega_gs=$tretirosgs,total_credito=$cred,
			total_reposiciones_gs=$trepo, total_pend = $tpendi1, caja_chica_cierre=$caja_chica_cierre, 
			total_pagos_dia_ch = $tpagos_ch
			where 
			idcaja=$idcaja and cajero=$idusu";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        //echo $update;
        //exit;

        $now = date("d-m-Y H:i:s")	;
        //Armamos el tickete de Cierre
        $buscar = "select * from caja_super where estado_caja=3 and cajero=$idusu and idcaja=$idcaja and tipocaja=1";
        $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $montoaper = $rsb->fields['monto_apertura'];
        $fecha_apertura = date("d/m/Y H:i:s", strtotime($rsb->fields['fecha_apertura']));
        $tefec = $rsb->fields['total_efectivo'];

        $buscar = "Select valor,cantidad,subtotal,registrobill from caja_billetes
			inner join gest_billetes
			on gest_billetes.idbillete=caja_billetes.idbillete
			where caja_billetes.idcajero=$idusu and idcaja=$idcaja and caja_billetes.estado=1
			order by valor asc";
        $rsbilletitos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $tbilletes = $rsbilletitos->RecordCount();
        //echo $buscar;

        if ($tbilletes > 0) {
            $tg = 0;
            $add1 = '';
            while (!$rsbilletitos->EOF) {
                $valor = trim($rsbilletitos->fields['valor']);
                $cantidad = trim($rsbilletitos->fields['cantidad']);
                $subtotal = trim($rsbilletitos->fields['subtotal']);
                $tg = $tg + $subtotal;
                $add1 = $add1."  $cantidad         ".formatomoneda($valor)."         ".formatomoneda($subtotal)." \n";

                $rsbilletitos->MoveNext();
            }


        }
        //Monedas extranjeras
        $buscar = "Select descripcion,cantidad,subtotal,sermone from caja_moneda_extra 
			inner join tipo_moneda on tipo_moneda.idtipo=caja_moneda_extra.moneda 
			where idcaja=$idcaja and cajero=$idusu and caja_moneda_extra.estado=1";
        $rsmmone = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $tmone = $rsmmone->RecordCount();

        $teoricogs = $montoaper + $tefec + $tarje + $tcheque + $ttransfer - $tpagos;
        //Cobranza pendiente x delivery otros no rendidas
        /*$buscar="Select  sum(efectivo) as efectivogs,sum(montotarjeta) as tarjeta, sum(montocheque) as cheque, sum(montotransfer) as transfer
          from gest_pagos where cajero=$idusu  and estado=1 and idcaja=$idcaja and rendido ='N'";
        $rspend=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
        $norendidas=floatval($rspend->fields['efectivogs'])+floatval($rspend->fields['tarjeta'])+floatval($rspend->fields['cheque'])+floatval($rspend->fields['transfer']);*/

        // mover datos de tabla carrito a tabla bak para optimizar velocidad

        // marca registros viejos como borrados
        $consulta = "
			update tmp_ventares_cab 
			set 
			estado = 6 
			where 
			idventa is null 
			and date(fechahora) < date_add(date(NOW()), INTERVAL -60 DAY)
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
			update  tmp_ventares 
			set borrado = 'S'
			WHERE
			idtmpventares_cab is null
			 and registrado = 'N'
			 and fechahora < date_add(date(NOW()), INTERVAL -60 DAY) 
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // pimero mover
        $consulta = "
			insert into tmp_ventares_bak
			select * from tmp_ventares 
			where 
			date(fechahora) < date_add(date(NOW()), INTERVAL -3 DAY) 
			and (registrado = 'S' or borrado = 'S')
			and tmp_ventares.idventatmp not in (select idventatmp from tmp_ventares_bak where tmp_ventares_bak.idventatmp = tmp_ventares.idventatmp)
			limit 100000
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
			insert into tmp_ventares_bak
			select * from tmp_ventares 
			where 
			date(fechahora) < date_add(date(NOW()), INTERVAL -3 DAY) 
			and idtmpventares_cab in (select idtmpventares_cab from tmp_ventares_cab where estado = 6)
			and tmp_ventares.idventatmp not in (select idventatmp from tmp_ventares_bak where tmp_ventares_bak.idventatmp = tmp_ventares.idventatmp)
			limit 100000
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //and tmp_ventares.idventatmp > (select max(idventatmp) from tmp_ventares_bak)

        // luego borrar
        $consulta = "
			delete from  tmp_ventares
			where 
			date(fechahora) < date_add(date(NOW()), INTERVAL -3 DAY) 
			and (registrado = 'S' or borrado = 'S')
			and tmp_ventares.idventatmp in (select idventatmp from tmp_ventares_bak where tmp_ventares_bak.idventatmp = tmp_ventares.idventatmp)
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
			delete from  tmp_ventares
			where 
			date(fechahora) < date_add(date(NOW()), INTERVAL -3 DAY) 
			and idtmpventares_cab in (select idtmpventares_cab from tmp_ventares_cab where estado = 6)
			and tmp_ventares.idventatmp in (select idventatmp from tmp_ventares_bak where tmp_ventares_bak.idventatmp = tmp_ventares.idventatmp)
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // primero mover
        $consulta = "
			INSERT INTO costo_productos_bak
			select * from costo_productos 
			where 
			disponible <= 0
			and ficticio = 0
			and idseriepkcos not in (select idseriepkcos from costo_productos_bak);
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // luego borrar
        $consulta = "
			delete from  costo_productos
			where
			idseriepkcos in (select idseriepkcos from costo_productos_bak);
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // primero mover
        $consulta = "
			INSERT INTO gest_depositos_stock_bak
			select * from gest_depositos_stock 
			where 
			disponible <= 0
			and ficticio = 0
			and idregseriedptostk not in (select idregseriedptostk from gest_depositos_stock_bak);
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // luego borrar
        $consulta = "
			delete from  gest_depositos_stock
			where
			idregseriedptostk in (select idregseriedptostk from gest_depositos_stock_bak);
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // optimizar tabla stock_movimiento
        $consulta = "  
			insert into stock_movimientos_bak
			select * from stock_movimientos
			WHERE
			date(fechahora) < date_add(date(NOW()), INTERVAL -365 DAY)
			and stock_movimientos.idstockmov not in (select idstockmov from stock_movimientos_bak where stock_movimientos_bak.idstockmov = stock_movimientos.idstockmov)
			limit 100000
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "  
			delete from stock_movimientos 
			where 
			date(fechahora) < date_add(date(NOW()), INTERVAL -365 DAY)
			and idstockmov in (select idstockmov from stock_movimientos_bak);
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // optimizar venta receta
        $consulta = "
			insert into venta_receta_bak 
			select * from venta_receta 
			where 
			date(fechahora) < date_add(date(NOW()), INTERVAL -365 DAY)
			and venta_receta.idventareceta not in (select idventareceta from venta_receta_bak where venta_receta_bak.idventareceta = venta_receta.idventareceta)
			limit 100000
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
			delete from venta_receta 
			where 
			date(fechahora) < date_add(date(NOW()), INTERVAL -365 DAY)
			and venta_receta.idventareceta in (select idventareceta from venta_receta_bak);
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $fecha_cierre = date("d/m/Y H:i:s");
        $cajero = strtoupper($cajero);

        recalcular_caja($idcaja);

        // redirecciona a impresiones
        header("location: caja_cerrar_imprime.php");
        exit;


        /*--------------------- FIN CIERRE DE CAJA -------------------------------------------------*/







    } //if($idcaja > 0){

} // if (isset($_POST['cual']) && (intval($_POST['cual'])==3)){
