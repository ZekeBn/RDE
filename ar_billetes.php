 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");

//Total de vouchers de la caja



if (intval($idcaja) == 0) {
    $idcaja = intval($_POST['idcaja']);

}
//print_r($_POST);
$voucherchau = intval($_POST['voucher']);
if ($voucherchau > 0) {
    $update = "update caja_vouchers set estado=6,anulado_el=current_timestamp,anulado_por=$idusu where idcaja=$idcaja and unicasspk=$voucherchau";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
}

$buscar = "Select * from caja_vouchers where idcaja=$idcaja and cajero=$idusu and estado=1";
$rsvou = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tvouchers = floatval($rsvou->fields['total_vouchers']);



$buscar = "select estado_caja,fecha,monto_apertura from caja_super where idcaja=$idcaja and cajero=$idusu";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$estadocaja = intval($rscaja->fields['estado_caja']);

$fechis = date("Y-m-d", strtotime($rscaja->fields['fecha']));
$fechahoy = $fechis;
$chau = intval($_POST['chau']);
//moneda chau
$chamo = intval($_POST['chacu']);
//echo $chau;
if ($chau > 0) {
    $update = "update caja_billetes set estado=6 where registrobill=$chau"    ;
    $conexion->Execute($update) or die(errorpg($conexion, $update));

}
if ($chamo > 0) {
    $update = "update caja_moneda_extra set estado=6 where sermone=$chamo"    ;
    $conexion->Execute($update) or die(errorpg($conexion, $update));

}
//si es moneda
$mone = intval($_POST['mo']);
if ($mone == 1) {
    $moneda = intval($_POST['moneda']);
    $cantidad = intval($_POST['canti']);
    $coti = intval($_POST['coti']);
    //$subtotal=$cantidad*$valor;
    $subtotal = $cantidad * $coti;

    $insertar = "Insert into caja_moneda_extra
    (idcaja,cajero,cantidad,subtotal,moneda,cotiza)
    values
    ($idcaja,$idusu,$cantidad,$subtotal,$moneda,$coti)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

} else {


    $billete = intval($_POST['billete']);
    $buscar = "Select * from gest_billetes where idbillete=$billete";
    $bille1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $valor = intval($bille1->fields['valor']);


    $cantidad = intval($_POST['canti']);
    $subtotal = $cantidad * $valor;
    if ($cantidad > 0) {

        $insertar = "Insert into caja_billetes
        (idcajero,idcaja,idbillete,cantidad,subtotal)
        values
        ($idusu,$idcaja,$billete,$cantidad,$subtotal)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
    }
}


$buscar = "Select valor,cantidad,subtotal,registrobill from caja_billetes
inner join gest_billetes
on gest_billetes.idbillete=caja_billetes.idbillete
where caja_billetes.idcajero=$idusu and idcaja=$idcaja and caja_billetes.estado=1
order by valor asc";
$rsbilletitos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tbilletes = $rsbilletitos->RecordCount();

//Monedas extranjeras
$buscar = "Select descripcion,cantidad,subtotal,sermone from caja_moneda_extra 
inner join tipo_moneda on tipo_moneda.idtipo=caja_moneda_extra.moneda 
where idcaja=$idcaja and cajero=$idusu and caja_moneda_extra.estado=1";
$rsmmone = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tmone = $rsmmone->RecordCount();
?>
<div class="div-izq300" style="width:400px;">
<?php if ($tbilletes > 0 or $tmone > 0) {?>

    <table width="100%">
        <tr>
            <td width="86" height="34" align="center" bgcolor="#C5EAEB"><strong>Valor</strong></td>
            <td width="86" align="center" bgcolor="#C5EAEB"><strong>Cantidad</strong></td>
            <td width="108" align="center" bgcolor="#C5EAEB"><strong>Subtotal</strong></td>
            <td width="50" bgcolor="#C5EAEB"></td>
      </tr>
        <?php while (!$rsbilletitos->EOF) {
            $subtotalnacacum += $rsbilletitos->fields['subtotal'];
            ?>
            <tr>
              <td align="right"><?php echo formatomoneda($rsbilletitos->fields['valor']);?></td>
              <td align="center"><?php echo formatomoneda($rsbilletitos->fields['cantidad']);?></td>
           <td align="center"><?php echo formatomoneda($rsbilletitos->fields['subtotal']);?></td>
            <td align="center"><?php if ($estadocaja == 1) {?><a href="javscript:void(0);" onClick="chb(<?php echo $rsbilletitos->fields['registrobill']?>);"><img src="img/no.PNG" width="16" height="16" alt=""/></a><?php }?></td>
        </tr>
        <?php $rsbilletitos->MoveNext();
        }?>
        <tr>
             <td height="27" colspan="4" align="center">Total: <?php echo formatomoneda($subtotalnacacum); ?></td>
      </tr>
        <?php if ($tmone > 0) {?>
        <tr>
            <td height="27" colspan="4" align="center" bgcolor="#D2F9D6"><strong>Monedas EX</strong></td>
      </tr>
        <?php while (!$rsmmone->EOF) {
            $subtotalextacum += $rsmmone->fields['subtotal'];

            ?>
        <tr>
            <td align="center"><?php echo $rsmmone->fields['descripcion']?></td>
            <td align="center"><?php echo $rsmmone->fields['cantidad']?></td>
          <td align="center"><?php echo formatomoneda($rsmmone->fields['subtotal'])?></td>
          <td><?php if ($estadocaja == 1) {?><a href="javscript:void(0);" onClick="chb2(<?php echo $rsmmone->fields['sermone']?>);"><img src="img/no.PNG" width="16" height="16" alt=""/></a><?php }?></td>
      </tr>
        <tr>
            <td height="27" colspan="4" align="center">Total: <?php echo formatomoneda($subtotalextacum); ?></td>
      </tr>
        <?php $rsmmone->MoveNext();
        }?>
      <?php } ?>
    </table>

<?php } else { ?>

    <span class="resaltarojomini">No se registraron billetes</span>
<?php } ?>
</div>
<div class="div-izq300">
    <?php if ($tvouchers > 0) {?>
    <table width="300">
        <tr>
            <td width="73" height="34" align="center" bgcolor="#C5EAEB"><strong>Monto Voucher</strong></td>
            <td width="99" align="center" bgcolor="#C5EAEB">&nbsp;</td>
            
      </tr>
        <?php while (!$rsvou->EOF) {
            $subtotalv += $rsvou->fields['total_vouchers'];
            ?>
            <tr>
              <td align="right"><?php echo formatomoneda($rsvou->fields['total_vouchers']);?></td>
              
            <td align="center"><?php if ($estadocaja == 1) {?>
                <a href="javscript:void(0);" onClick="chv(<?php echo $rsvou->fields['unicasspk']?>);">
                    <img src="img/no.PNG" width="16" height="16" alt=""/>
                </a><?php }?></td>
        </tr>
        <?php $rsvou->MoveNext();
        }?>
        <tr>
             <td height="27" colspan="2" align="center">Total: <?php echo formatomoneda($subtotalv); ?></td>
      </tr>
        <?php if ($tmone > 0) {?>
        <tr>
            <td height="27" colspan="4" align="center" bgcolor="#D2F9D6"><strong>Monedas EX</strong></td>
      </tr>
        <?php while (!$rsmmone->EOF) {
            $subtotalextacum += $rsmmone->fields['subtotal'];

            ?>
        <tr>
            <td align="center"><?php echo $rsmmone->fields['descripcion']?></td>
            <td align="center"><?php echo $rsmmone->fields['cantidad']?></td>
          <td width="28" align="center"><?php echo formatomoneda($rsmmone->fields['subtotal'])?></td>
          <td width="80"><?php if ($estadocaja == 1) {?><a href="javscript:void(0);" onClick="chb2(<?php echo $rsmmone->fields['sermone']?>);"><img src="img/no.PNG" width="16" height="16" alt=""/></a><?php }?></td>
      </tr>
        <tr>
            <td height="27" colspan="4" align="center">Total: <?php echo formatomoneda($subtotalextacum); ?></td>
      </tr>
        <?php $rsmmone->MoveNext();
        }?>
      <?php } ?>
    </table>

    
    
    <?php } else {?>
    No se totalizaron vouchers
    <?php } ?>
</div>
   <div class="clear"> </div>
 <br />
 <div align="center">
 <?php if ($tipocaja == 'V') { ?>
     <span class="numb">Resumen Final</span>
<?php } ?>
     <?php

        $montobre = $rscaja->fields['monto_apertura'];

//Total de Cobranzas en el dia
$buscar = "Select  sum(total_cobrado) as tcobra from gest_pagos where cajero=$idusu  and estado=1 and idcaja=$idcaja and rendido ='S'";
$rscobro = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tcobranza = floatval($rscobro->fields['tcobra']);

//Total de ventas GENERAL en el dia
$buscar = "Select  sum(totalcobrar) as tventa from ventas where registrado_por=$idusu  and estado=1 and idcaja=$idcaja";
$rsventas = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$tventa = floatval($rsventas->fields['tventa']);

//Total de ventas CREDITO en el dia
$buscar = "Select  sum(totalcobrar) as tventa from ventas where registrado_por=$idusu  and estado=2 and idcaja=$idcaja and tipo_venta=2";
$rscc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$cred = floatval($rscc->fields['tventa']);
// echo $buscar;
//Cobranza en Efectivo
$buscar = "Select  sum(efectivo) as efectivogs from gest_pagos where cajero=$idusu  and estado=1 and idcaja=$idcaja and rendido ='S'";
$rsefe = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tefe = floatval($rsefe->fields['efectivogs']);

//Cobranzas  Tarjeta
/*$buscar="Select  sum(montotarjeta) as tarje from gest_pagos where cajero=$idusu  and estado=1 and idcaja=$idcaja and rendido ='S'";
$rstarje=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
$tarje=floatval($rstarje->fields['tarje']);*/

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

//Cobranza pendiente x delivery
$buscar = "Select  sum(total_cobrado) as totalpend  from gest_pagos where cajero=$idusu  and estado=1 and idcaja=$idcaja and rendido ='N'";
$rspend = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tpendi1 = floatval($rspend->fields['totalpend']);
//Pagos por caja
/*$buscar="Select sum(montogs) as totalp from caja_pagos where idcaja=$idcaja and cajero=$idusu and estado=1 ";
$rspagca=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
$tpagos=floatval($rspagca->fields['totalp']);*/
//Pagos por caja
$consulta = "
            select (COALESCE(sum(cuentas_empresa_pagos.monto_abonado),0)+COALESCE((Select sum(monto_abonado) as mon from pagos_extra where idcaja=$idcaja and idempresa=$idempresa and estado <> 6  and tipocaja = 'R'),0)) as totalp
        from cuentas_empresa_pagos
        inner join cuentas_empresa on cuentas_empresa_pagos.idcta = cuentas_empresa.idcta
        where
        cuentas_empresa_pagos.idcaja = $idcaja
        and cuentas_empresa_pagos.idempresa = $idempresa
        and cuentas_empresa_pagos.estado <> 6
        ORDER BY cuentas_empresa_pagos.fecha_pago asc
        ";
$rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tpagos = floatval($rsv->fields['totalp']);

$consulta = "
        select sum(cuentas_empresa_pagos.monto_abonado) as totalp
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
$tpagosef = floatval($rsvef->fields['totalp']);


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
//Disponible actual caja
$dispo = ($rscaja->fields['monto_apertura'] + $tefe + $trepo) - $tretirosgs - $tpagosef;

$buscar = "
        Select  sum(monto_pago_det) as total_cobrado 
        from gest_pagos
        inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
        inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
        where 
        gest_pagos.estado=1 
        and gest_pagos.idcaja=$idcaja
        and gest_pagos.rendido = 'S'
        and formas_pago.computa_caja = 1
        ";
//echo $buscar;exit;
$rsingresos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

//$totalteorico=$tefe+$montobre+$tarje;
$totalteorico = $rsingresos->fields['total_cobrado'] - $tpagos - $tretirosgs + $trepo + $rscaja->fields['monto_apertura'];
$totalteoricoefe = $tefe + $montobre - $tpagos;


//total en monedas extranjeras pero convertidas a gs
$buscar = "select sum(subtotal) as tmone from caja_moneda_extra where idcaja=$idcaja and cajero=$idusu and estado=1";
$extra = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$textra = floatval($extra->fields['tmone']);



//total en monedas arqueadas
$buscar = "select sum(subtotal) as total from caja_billetes where idcaja=$idcaja and idcajero=$idusu and estado=1";
$tarqueo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tarquegs = intval($tarqueo->fields['total']);

/*
    $neto=$textra+$tarquegs;
    $subtotal=$neto+$tarje+$tcheque+$ttransfer-($tpagos);
    //Vemos el faltante y sobrante

    $sobrante=$neto-$totalteoricoefe;
    $faltante=$totalteoricoefe-$neto;
    if ($sobrante < 0){
        $sobrante=0;
    }
    if ($faltante < 0){
        $faltante=0;
    }

*/
// efectivo en moneda extrangera + moneda nacional
$neto = $textra + $tarquegs;
$totalarqueado = $neto;

// total vouchers
$consulta = "
    select sum(total_vouchers) as totalvouchers 
    from caja_vouchers 
    where 
    estado <> 6 
    and idcaja = $idcaja
    ";
$rsvo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$totalvouchers = floatval($rsvo->fields['totalvouchers']);




//$sobrante=($totalarqueado+$totalvouchers)-($tefe+$tarje+$tcheque+$ttransfer-$tpagos-$tretirosgs+$trepo+$rscaja->fields['monto_apertura']);
//$faltante=($tefe+$tarje+$tcheque+$ttransfer-$tpagos-$tretirosgs+$trepo+$rscaja->fields['monto_apertura'])-($totalarqueado+$totalvouchers);
$sobrante = ($totalarqueado + $totalvouchers) - ($rsingresos->fields['total_cobrado'] - $tpagos - $tretirosgs + $trepo + $rscaja->fields['monto_apertura']);
$faltante = ($rsingresos->fields['total_cobrado'] - $tpagos - $tretirosgs + $trepo + $rscaja->fields['monto_apertura']) - ($totalarqueado + $totalvouchers);
if ($sobrante < 0) {
    $sobrante = 0;
}
if ($faltante < 0) {
    $faltante = 0;
}
$total_ingresos = $rsingresos->fields['total_cobrado'] + $rscaja->fields['monto_apertura'] + $trepo;
?>
   <table width="350" class="tablalinda2">
<?php if ($tipocaja == 'V') { ?>
        <tr>
            <td height="29" colspan="2" align="center" bgcolor="#CAF9C6"><span class="resaltarojomini"><em><strong>INGRESOS TEORICOS</strong></em></span></td>
        </tr>
        <tr>
            <td width="162" height="36" bgcolor="#CAF9C6"><strong>Monto Apertura</strong></td>
            <td width="159" align="right"><?php echo formatomoneda($rscaja->fields['monto_apertura'])?></td>
      </tr>
<?php
$consulta = "
select sum(gest_pagos_det.monto_pago_det) as monto_pago_det, formas_pago.descripcion as formapago, gest_pagos_det.idformapago
from gest_pagos_det
inner join gest_pagos on gest_pagos.idpago = gest_pagos_det.idpago
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
where
gest_pagos.estado = 1
and gest_pagos.idcaja=$idcaja 
and gest_pagos.rendido = 'S'
group by gest_pagos_det.idformapago, formas_pago.descripcion
order by formas_pago.descripcion asc
";
    $rsvenpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    ?><?php while (!$rsvenpag->EOF) { ?>
        <tr>
            <td height="31" bgcolor="#CAF9C6"><strong><?php echo antixss($rsvenpag->fields['formapago']); ?></strong></td>
            <td align="right"><?php echo formatomoneda($rsvenpag->fields['monto_pago_det']); ?></td>
        </tr>
<?php $rsvenpag->MoveNext();
    } ?>
        <tr>
          <td height="29" bgcolor="#CAF9C6"><strong>Recepcion de Valores</strong></td>
          <td align="right"><?php echo formatomoneda($trepo)?></td>
        </tr>
        <tr>
            <td height="29" colspan="2" align="center" bgcolor="#Ccc"><strong>Total Ingresos teorico: <?php echo formatomoneda($total_ingresos); ?></strong></td>
        </tr>
        <tr>
            <td height="29" colspan="2" align="center" bgcolor="#CAF9C6"><span class="resaltarojomini"><em><strong>EGRESOS TEORICOS</strong></em></span></td>
        </tr>
<?php /*?>
        <tr>
          <td height="29" bgcolor="#CAF9C6"><strong>Total Pagos x Caja Efectivo</strong></td>
          <td align="right">-<?php echo formatomoneda($tpagosef,0)?></td>
        </tr><?php */ ?>
        <tr>
          <td height="29" bgcolor="#CAF9C6"><strong>Total Pagos x Caja</strong></td>
          <td align="right">-<?php echo formatomoneda($tpagos, 0)?></td>
        </tr>
        <tr>
          <td height="29" bgcolor="#CAF9C6"><strong>Entrega de Valores</strong></td>
          <td align="right">-<?php echo formatomoneda($tretirosgs)?></td>
        </tr>
        <tr>
            <td height="29" colspan="2" align="center" bgcolor="#Ccc"><strong>Total Egresos teorico: -<?php echo formatomoneda($tpagosef + $tpagos + $tretirosgs); ?></strong></td>
        </tr>
        <tr>
            <td height="29" colspan="2" align="center" bgcolor="#CAF9C6"><span class="resaltarojomini"><em><strong>INFORMATIVO</strong></em></span></td>
        </tr>
        <tr>
          <td height="29" align="left" bgcolor="#CAF9C6"><strong>Total Ventas Cr&eacute;dito</strong></td>
          <td height="29" align="right"><?php echo formatomoneda($cred, 0)?></td>
      </tr>
        <tr>
          <td height="29" bgcolor="#CAF9C6"><strong>Total Pendientes rendici&oacute;n</strong></td>
          <td align="right"><?php echo formatomoneda($tpendi1)?></td>
        </tr>
        <tr>
            <td height="29" colspan="2" align="center" bgcolor="#ccc"><strong>Total (Ingresos-Egresos) Te&oacute;rico: Gs. <?php echo formatomoneda($totalteorico)?></strong></td>
        </tr>
        <tr>
            <td height="29" colspan="2" align="center" bgcolor="#CAF9C6"><span class="resaltarojomini"><em><strong>TOTALES DECLARADOS</strong></em></span></td>
        </tr>
        <tr>
          <td height="37" align="left" bgcolor="#E1E1E1"><strong>Total Moneda Nacional</strong></td>
           <td align="right"  ><?php echo formatomoneda($tarquegs)?></td>
          
     </tr>
        <tr>
            <td height="34" align="left" bgcolor="#E1E1E1"><strong>Total Moneda Extranjera (convertida en Gs)</strong></td>
           <td height="37" align="right"><?php echo formatomoneda($textra)?></td>
        </tr>
         <tr>
           <td height="34" align="left" bgcolor="#E1E1E1"><strong>Total Vouchers:</strong></td>
           <td height="37" align="right"><?php echo formatomoneda($subtotalv)?></td>
         </tr>
      
      <?php
         $totaldeclarado = $tarquegs + $textra + $subtotalv;

    ?>
         <?php /*?><tr>
           <td height="34" align="left" bgcolor="#E1E1E1"><strong>Subtotal: </strong></td>
           <td height="37" align="right"><?php echo formatomoneda($subtotal)?></td>
         </tr><?php */?>
        <tr>
            <td height="29" colspan="2" align="center" bgcolor="#CAF9C6"><span class="resaltarojomini"><em><strong>Total Declarado: Gs. <?php echo formatomoneda($totaldeclarado)?></strong></em></span></td>
        </tr>
         <tr>
            <td height="34" align="left" bgcolor="#E1E1E1"><strong>Faltante :</strong></td>
           <td height="37" align="right"><?php if ($faltante > 0) {?><span class="resaltarojomini"><?php echo '-'.formatomoneda($faltante)?></span><?php } else {
               echo '0';
           }?></td>
          </tr>
       <tr>
            <td height="34" align="left" bgcolor="#E1E1E1"><strong>Sobrante:</strong></td>
           <td height="37" align="right"><?php echo formatomoneda($sobrante)?></td>
          </tr>
        <tr>
            <td height="34" colspan="2" align="center">&nbsp;</td>
        </tr>
<?php } ?>
     <tr>
            <td height="34" align="center" colspan="2">
             <?php if ($idcaja > 0) {?>
               
                       <?php if ($estadocaja == 1) { ?>
                     <form id="cerrarcaja" name="cerrarcaja" action="gest_administrar_caja.php" method="post">
                    <input type="hidden" name="ocidcaja" id="ocidcaja" value="<?php echo $idcaja?>" />
                    <input type="hidden" name="selefe" id="selefe" value="<?php echo $fechahoy ?>" />
                      <input type="hidden" name="cual" id="cual" value="3" />
                    <input type="button" name="cv" value="Cerrar Caja" onclick="chaucaja()" />
                    </form>
                    <?php }?>
               
               <?php } ?>
            
            </td>
            
     </tr>
   </table>
 
 </div>
