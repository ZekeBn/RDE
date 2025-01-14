 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");

//Total de Cobranzas en el dia
$buscar = "Select  sum(total_cobrado) as tcobra from gest_pagos where cajero=$idusu  and estado=1 and idcaja=$idcaja and rendido ='S'";
//echo $buscar;
$rscobro = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tcobranza = floatval($rscobro->fields['tcobra']);

//Total de ventas en el dia
$buscar = "Select  sum(totalcobrar) as tventa from ventas where registrado_por=$idusu  and estado=1 and idcaja=$idcaja";
$rsventas = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tventa = floatval($rsventas->fields['tventa']);
//Cobranza en Efectivo
$buscar = "Select  sum(efectivo) as efectivogs from gest_pagos where cajero=$idusu  and estado=1 and idcaja=$idcaja and rendido ='S'";
$rsefe = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tefe = floatval($rsefe->fields['efectivogs']);
//Cobranza pendiente x delivery-otros
$buscar = "Select  sum(efectivo) as efectivogs,sum(montotarjeta) as tarjeta  from gest_pagos where cajero=$idusu  and estado=1 and idcaja=$idcaja and rendido ='N'";
$rspend = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tpendi1 = floatval($rspend->fields['efectivogs']) + floatval($rspend->fields['tarjeta']);


$tefe = floatval($rsefe->fields['efectivogs']);
//Cobranzas  Tarjeta
$buscar = "Select  sum(montotarjeta) as tarje from gest_pagos where cajero=$idusu  and estado=1 and idcaja=$idcaja and rendido ='S'";
$rstarje = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tarje = floatval($rstarje->fields['tarje']);

//Pagos por caja
/*$buscar="Select sum(montogs) as totalp from caja_pagos where idcaja=$idcaja and cajero=$idusu and estado=1";
$rspagca=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));*/
$consulta = "
select sum(cuentas_empresa_pagos.monto_abonado) as totalp
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
select (COALESCE(sum(cuentas_empresa_pagos.monto_abonado),0)+COALESCE((Select sum(monto_abonado) as mon from pagos_extra where idcaja=$idcaja and idempresa=$idempresa),0)) as totalp
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


?><div align="center">
 <table width="400" >
          <tr>
              <td height="31" align="center" bgcolor="#E8E6E6"><strong>ID Caja</strong></td>
            <td align="center" bgcolor="#E8E6E6"><strong>Fecha Apertura</strong></td>
            <td align="center" bgcolor="#E8E6E6"><strong>Estado Caja</strong></td>
            <td align="center" bgcolor="#E8E6E6"><strong>Fecha Cierre</strong></td>
        </tr>
        <?php if ($abrircaja == 0) {?>
        <tr> 
            <td height="29" align="center"><?php echo $rscaja->fields['idcaja']?></td>
               <td align="center"><?php echo date("d-m-Y H:i:s", strtotime($rscaja->fields['registrado_el']));?></td>
            <td align="center"><?php if ($rscaja->fields['estado_caja'] == 1) {
                echo "<span class='resaltaverdemini'>ABIERTA</span>";
            } else {
                echo "CERRADA";
            } ?></td>
            <td align="center">
            <?php
            if ($rscaja->fields['fecha_cierre'] != '') {
                echo date("d-m-Y H:i:s", strtotime($rscaja->fields['fecha_cierre']));
            }
            ?>
            </td>
          </tr>
        <?php } ?>
        
       
  </table>
</div>
<br /><br />
<hr />
<!-------------------------------------------------RESUMENES---------------------------->
<div style="height:auto;">
  <div class="div-izq250">
          <table width="218" height="213">
              <tr>
                <td width="113" height="34" align="right" bgcolor="#E5E5E5"><strong>Apertura Inicial Gs </strong></td>
                <td width="14"></td>
                <td width="75" align="right"><?php echo formatomoneda($rscaja->fields['monto_apertura'], 0)?></td>
              </tr>
              <tr hidden="hidden">
                 <td width="113" height="34" align="right" bgcolor="#E5E5E5"><strong>Total Ventas Gs (global)</strong></td>
                 <td width="14"></td>
                 <td width="75" align="right"><?php echo formatomoneda($tventa, 0)?></td>
              </tr>
              <tr>
                 <td height="33" align="right" bgcolor="#E5E5E5"><strong>Total Cobros Gs (global)</strong></td>
                 <td></td>
                  <td align="right"><?php echo formatomoneda($tcobranza, 0)?></td>
             </tr>
             <tr>
               <td height="32" align="right" bgcolor="#E5E5E5"><strong>Total Cobros pendientes (delivery/otros)</strong></td>
               <td></td>
               <td align="right"><?php echo formatomoneda($tpendi1, 0) ?></td>
             </tr>
             <tr>
               <td height="32" align="right" bgcolor="#E5E5E5"><strong>Total Pagos x Caja Efectivo</strong></td>
               <td></td>
               <td align="right"><?php echo formatomoneda($tpagosef, 0)?></td>
             </tr>
             <tr>
                 <td height="32" align="right" bgcolor="#E5E5E5"><strong>Total Pagos x Caja</strong></td>
                 <td></td>
                 <td align="right"><?php echo formatomoneda($tpagos, 0)?></td>
            </tr>                               
          </table>
  </div>                             
</div>
 
 <!--------------------------------------------------------2---------------------------->
 <div class="div-izq300">
      <table width="271" >
               <tr>
               <td width="137" height="37" align="right" bgcolor="#E5E5E5"><strong>Incidencias de Entrega EF</strong></td>
               <td width="32"></td>
               <td width="86" align="right"><?php echo formatomoneda($tretiros, 0)?></td>
            </tr>
              <tr>
               <td height="32" align="right" bgcolor="#E5E5E5"><strong>Monto Entregado Gs</strong></td>
               <td></td>
               <td align="right"><?php echo formatomoneda($tretirosgs, 0)?></td>
            </tr>
            <tr>
               <td height="32" align="right" bgcolor="#E5E5E5"><strong>Reposiciones Gs</strong></td>
               <td></td>
               <td align="right"><?php echo formatomoneda($trepo, 0)?></td>
            </tr>
            <tr>
               <td height="32" align="right" bgcolor="#E5E5E5"><strong><?php if ($estadocaja == 1) {?>Disponible Actual caja Gs (solo efectivo)<?php } else {?>Monto Final cierre<?php } ?></strong></td>
               <td></td>
               <td align="right"><span class="resaltarojomini"><?php echo formatomoneda($dispo, 0)?></span></td>
            </tr>
      </table>
      <br />
     
      
</div>

<!------------------------------------3 ------------------------------->
<div class="clear"></div>
<div align="center">

  <strong>Facturas Anuladas</strong>
                    <br />
                  <table width="541">
        <tr>
            <td width="183" height="35" align="center" bgcolor="#E5E5E5"><strong><em>Factura</em></strong></td>
            <td width="193" align="center" bgcolor="#E5E5E5"><strong><em>Motivo</em></strong></td>
            <td width="149" align="center" bgcolor="#E5E5E5"><strong><em>Hora</em></strong></td>
        </tr>
        <?php while (!$rsfal->EOF) {?>
        <tr>
            <td height="32"><?php echo $rsfal->fields['numero']?></td>
            <td><?php echo $rsfal->fields['motivo']?></td>
            <td><?php echo $rsfal->fields['fechahora']?></td>
        </tr>
        
        <?php $rsfal->MoveNext();
        }?>
        </table>
</div>
