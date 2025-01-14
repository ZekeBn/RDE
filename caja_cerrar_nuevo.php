 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");
if (intval($origen) == 2) {
    $idtipocaja = 2;//tesoreria
} else {
    $idtipocaja = 1;//gestion
}


require_once("includes/funciones_caja.php");



$consulta = "SELECT * FROM preferencias_caja WHERE  idempresa = $idempresa ";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$caja_compartida = trim($rsprefcaj->fields['caja_compartida']);
$usar_turnos_caja = trim($rsprefcaj->fields['usa_turnos']);
$obliga_cierremesa_caja = trim($rsprefcaj->fields['obliga_cierremesa_caja']);
$obliga_carry_caja = trim($rsprefcaj->fields['obliga_carry_caja']);
$obliga_deliv_caja = trim($rsprefcaj->fields['obliga_deliv_caja']);
$mail_cierrecaja = trim($rsprefcaj->fields['mail_cierrecaja']);
$mails_cierre_caja_csv = trim($rsprefcaj->fields['mails_cierre_caja_csv']);

if ($mail_cierrecaja == 'S') {
    if (trim($mails_cierre_caja_csv) == '') {
        echo "No se cargaron los correos para el cierre de caja, cargar en: gestion > preferencias ventas.";
        exit;
    }
}


//echo $idtipocaja;exit;
// busca si hay una caja abierta por este usuario
$buscar = "
    Select * 
    from caja_super 
    where 
    estado_caja=1 
    and cajero=$idusu 
    and tipocaja=$idtipocaja
    order by fecha_apertura desc 
    limit 1
    ";
$rscaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = $rscaj->fields['idcaja'];
$idnumeradorcab = $rscaj->fields['idnumeradorcab'];

// si no existe caja abierta redirecciona a impresiones
if ($idcaja == 0) {
    header("location: caja_cerrar_imprime_nuevo.php");
    exit;
}
// si existe caja abierta cierra
if ($idcaja > 0) {
    /*$buscar="Select sum(monto) as total from caja_arqueo_fpagos where estado=1 and idcaja=$idcaja";
    $rstfp=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    $total_otras_formas=floatval($rstfp->fields['total']);
    if ($total_otras_formas > 0){
        $ahora=date("Y-m-d H:i:s");
        //insertamos en caj a_vouchers
        $insertar="insert into caja_vouchers
        (idcaja,cajero,total_vouchers,registrado_el,anulado_por,anulado_el,estado,registrado_adm_por)
        values
        ($idcaja,$idusu,$total_otras_formas,'$ahora',NULL,NULL,1,NULL)
        ";
        $conexion->Execute($insertar) or die(errorpg($conexion,$insertar));

    }*/
    // parche 2
    // centrar nombre de empresa
    $nombreempresa_centrado = corta_nombreempresa($nombreempresa);

    /*--------------------- INICIO CIERRE DE CAJA -------------------------------------------------*/
    if ($obliga_cierremesa_caja == 'S') {
        $consulta = "
            SELECT idmesa, numero_mesa, idsalon,
            (select salon.nombre from salon where idsalon = mesas.idsalon) as salon
            FROM mesas
            where 
            estado_mesa > 1 
            and estadoex = 1 
            and idsucursal = $idsucursal
            limit 1
            ";
        $rsmesval = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rsmesval->fields['idmesa']) > 0) {
            $numero_mesa = $rsmesval->fields['numero_mesa'];
            $salon = $rsmesval->fields['salon'];
            $idsalon = $rsmesval->fields['idsalon'];
            $idmesa = intval($rsmesval->fields['idmesa']);
            echo "No se puede cerrar la caja por que existen <strong>Mesas abiertas</strong> en tu sucursal, Ej: Mesa: $numero_mesa [$idmesa], Salon: $salon [$idsalon] <br />
                Cierrelas primero o desactive el bloqueo en: gestion > preferencias ventas > Bloquea cierre de caja si hay mesas abiertas = NO.";
            exit;
        }
    }
    if ($obliga_carry_caja == 'S') {
        $consulta = "
            select idtmpventares_cab
            from tmp_ventares_cab
            inner join canal on canal.idcanal =  tmp_ventares_cab.idcanal
            where 
            tmp_ventares_cab.estado <> 6
            and tmp_ventares_cab.idcanal = 1
            and tmp_ventares_cab.finalizado = 'S'
            and tmp_ventares_cab.registrado = 'N'
            and tmp_ventares_cab.idsucursal = $idsucursal
            limit 1
            ";
        $rspedval = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rspedval->fields['idtmpventares_cab']) > 0) {
            echo "No se puede cerrar la caja por que existen pedidos de <strong>Carry Out pendientes</strong> de cobro en tu sucursal, cobre o elimine primero.<br />
                o desactive el bloqueo en: gestion > preferencias ventas > Bloquea cierre de caja si hay Carry Out pendientes = NO.";
            exit;
        }
    }
    if ($obliga_deliv_caja == 'S') {
        $consulta = "
            select idtmpventares_cab
            from tmp_ventares_cab
            inner join canal on canal.idcanal =  tmp_ventares_cab.idcanal
            where 
            tmp_ventares_cab.estado <> 6
            and tmp_ventares_cab.idcanal = 3
            and tmp_ventares_cab.finalizado = 'S'
            and tmp_ventares_cab.registrado = 'N'
            and tmp_ventares_cab.idsucursal = $idsucursal
            limit 1
            ";
        $rspedval = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rspedval->fields['idtmpventares_cab']) > 0) {
            echo "No se puede cerrar la caja por que existen pedidos de <strong>Delivery pendientes</strong> de cobro en tu sucursal, cobre o elimine primero.<br />
                o desactive el bloqueo en: gestion > preferencias ventas > Bloquea cierre de caja si hay Delivery pendientes = NO.";
            exit;
        }
    }


    //exit;



    // busca si el salon es una playa
    if ($idsalon_usu > 0) {
        $consulta = "
            select idsalon, playa
            from salon 
            where 
            idsalon = $idsalon_usu
            ";
        $rssalon = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $playa = trim($rssalon->fields['playa']);
    }

    // buscar si tiene turno abierto, si tiene debe obliga a cerrar primero
    if ($usar_turnos_caja == 'S') {
        $consulta = "
            select idturnotanda, idturno, idsucursal 
            from turnos_tandas
            where
            idsucursal = $idsucursal
            and estado=1
            ";
        $rsturtand_ex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idturno_tanda = intval($rsturtand_ex->fields['idturnotanda']);
        if ($idturno_tanda > 0) {
            echo "- Debe cerrar el turno antes de cerrar la caja";
            exit;
        }


    }
    // si es una playa
    if ($playa == 'S') {
        // busca si el numerador de la caja sigue abierto
        $consulta = "
            select * 
            from combustibles_numeradores_cab
            where
            idnumeradorcab = $idnumeradorcab
            and estado = 1
            ";
        $rsnumtand = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idnumeradorcab_ab = intval($rsnumtand->fields['idnumeradorcab']);
        // si sigue abierto
        if ($idnumeradorcab_ab > 0) {
            echo "- Debe cerrar la tanda de numeradores $idnumeradorcab_ab antes de cerrar la caja.";
            exit;
        }
    }
    // si la caja esta vinculada a nuemradores
    if ($idnumeradorcab > 0) {
        // busca si hay diferencia ya sea positiva o negativa
        $consulta = "
            select *
            from 
            (
                select idcombustible, idproducto, combustible,

                COALESCE((
                 select sum(combustibles_numeradores.numerador_final)-sum(combustibles_numeradores.numerador_inicio) 
                 from combustibles_numeradores
                 inner join combustibles_picos on combustibles_numeradores.idpico = combustibles_picos.idpico
                 WHERE
                 combustibles_numeradores.idnumeradorcab = $idnumeradorcab
                 and combustibles_picos.idcombustible = combustibles.idcombustible
                ),0) as litros_despachados,

                COALESCE((
                 select sum(ventas_detalles.cantidad)
                 from ventas
                 inner join ventas_detalles on ventas_detalles.idventa = ventas.idventa
                 inner join caja_super on caja_super.idcaja = ventas.idcaja
                 WHERE
                 caja_super.idnumeradorcab = $idnumeradorcab
                 and ventas.estado <> 6
                 and ventas_detalles.idprod = combustibles.idproducto
                ),0) as litros_facturados,

                COALESCE((
                 select sum(combustibles_numeradores.numerador_final)-sum(combustibles_numeradores.numerador_inicio) 
                 from combustibles_numeradores
                 inner join combustibles_picos on combustibles_numeradores.idpico = combustibles_picos.idpico
                 WHERE
                 combustibles_numeradores.idnumeradorcab = $idnumeradorcab
                 and combustibles_picos.idcombustible = combustibles.idcombustible
                ),0)-COALESCE((
                 select sum(ventas_detalles.cantidad)
                 from ventas
                 inner join ventas_detalles on ventas_detalles.idventa = ventas.idventa
                 inner join caja_super on caja_super.idcaja = ventas.idcaja
                 WHERE
                 caja_super.idnumeradorcab = $idnumeradorcab
                 and ventas.estado <> 6
                 and ventas_detalles.idprod = combustibles.idproducto
                ),0) AS litros_a_facturar

                from combustibles
                WHERE
                combustibles.estado = 1
            ) dif
            where
            litros_a_facturar <> 0
            ";
        $rsdif = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcombustible_dif = intval($rsdif->fields['idcombustible']);
        // si hay diferencia redirecciona
        if ($idcombustible_dif > 0) {
            header('location: surtidor/diferencia_facturar.php');
            exit;
        }

    }

    //echo $idnumeradorcab.'-'.$idcombustible_dif;exit;

    // parche 1
    $consulta = "
        update gest_pagos 
        set 
        cajero = COALESCE((select caja_super.cajero from caja_super where caja_super.idcaja = gest_pagos.idcaja),0)
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
        sucursal = COALESCE((select caja_super.sucursal from caja_super where caja_super.idcaja = gest_pagos.idcaja),0)
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

    //Registramos el cierre del turno en log
    $update = "update caja_turnos_log set finalizado_el='$ahora',finalizado_por=$idusu where idcaja=$idcaja";
    $conexion->Execute($update) or die(errorpg($conexion, $update));






    $consulta = "
            update caja_super
            set 
            estado_caja = 6 
            where
            idcaja_compartida = $idcaja
            and idcaja_compartida is not null
            ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    recalcular_caja($idcaja);

    $fecha_cierre = date("d/m/Y H:i:s");
    $cajero = strtoupper($cajero);



    if ($mail_cierrecaja == 'S') {



        require_once('../clases/mpdf/vendor/autoload.php');
        require_once("includes/funciones_mail.php");



        // INICIO CREAR PDF
        $parametros_array = [
            'idcaja' => $idcaja
        ];
        $res = caja_pdf($parametros_array);
        $html = $res['html'];
        //echo $html;exit;

        $mpdf = new mPDF('', 'Legal-P', 0, 0, 0, 0, 0, 0);
        //$mpdf = new mPDF('','A4',55,'dejavusans');
        //$mpdf = new mPDF('c','A4','100','',32,25,27,25,16,13);
        $mini = date('dmYHis');
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->use_kwt = true;
        //$mpdf->shrink_tables_to_fit = 1;
        $mpdf->shrink_tables_to_fit = 2.5;
        // Write some HTML code:
        $mpdf->WriteHTML($html);

        $directorio_pdf = 'gfx/cajas/';
        $archivopdf = 'caja_'.$idcaja.'_'.date("Ymdhis").'.pdf';
        $ruta_archivo = $directorio_pdf.$archivopdf;

        // crear directoio si no existe
        if (!file_exists($directorio_pdf)) {
            mkdir($directorio_pdf, 0777);
        }

        // Output a PDF file directly to the browser
        //si no se usa el tributo I, no permite usar el nombre indicado y los archivos no sedescargan nunca!!
        //$mpdf->Output('movimiento_caja_arqueo'.$mini.'.pdf','I');
        $mpdf->Output($ruta_archivo, 'F'); // guardar archivo en el servidor

        // FIN CREAR PDF
        //echo date("YmdHis");exit;

        /*$con_parametros_array=array(
            'username' => 'sistema@innovasys.com.py',
            'password' => 'mMIuwJGsKBQ-',
            'host' => 'mail.innovasys.com.py',
            'port' => '587',
            'from' => 'sistema@servidor.com.py',
        );*/


        // parametros para enviar correo
        $parametros_array = [
            'fromName' => 'SISTEMA - '.$nombreempresa,
            'subject' => 'CIERRE DE CAJA NRO. '.$idcaja." - ".antixss($nombresucursal)." - ".antixss($cajero),
            'body' => 'CIERRE DE CAJA ENVIADO EN ADJUNTO.', // FALTA ADJUNTAR ARCHIVO
            'correos_csv' => $mails_cierre_caja_csv,
            'adjunto' => $ruta_archivo,
            'con_especial' => 'N', // S para enviar parametros especiales de correo
            'con_parametros' => $con_parametros_array

        ];
        // enviar correo
        $res = enviar_email($parametros_array);

        // si existe el archivo
        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo); // lo elimina
        }

        // si no envio el correo
        if ($res['valido'] != 'S') {

            echo 'EL MAIL DE CIERRE DE CAJA NO SE PUDO ENVIAR!<BR /><HR /><BR />';
            echo antixss($res['errores']);
            echo '<BR /><HR /><BR />';
            echo "<a href='caja_cerrar_imprime_nuevo.php?ori=$idtipocaja'>[Imprimir Ticket Cierre]</a>";
            echo '<BR />';

            // optimiza tablas al cerrar la caja (hace aca por el exit que va a evitar que haga despues)
            require_once("caja_cerrar_optimiza.php");

            exit;
        }

    }

    // optimiza tablas al cerrar la caja (si ya hizo el de arriba no vuelve a hacer)
    require_once("caja_cerrar_optimiza.php");





    // redirecciona a impresiones
    header("location: caja_cerrar_imprime_nuevo.php?ori=$idtipocaja");
    exit;


    /*--------------------- FIN CIERRE DE CAJA -------------------------------------------------*/







} //if($idcaja > 0){



?>
