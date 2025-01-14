 <?php
/*-------------------------------------------
13/02/24: Se agrega preferencia de caja para mostrar
propinas de la caja en totales (solo referencial
 y trabaja con preferencias de caja (muestra_propinas_caja)


-------------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "11";
$submodulo = "467";
require_once("includes/rsusuario.php");

/*
diferencia entre cabecera y detalle de pagos
SELECT idventa, total_cobrado,  (select sum(gest_pagos_det.monto_pago_det) from gest_pagos_det where idpago = gest_pagos.idpago) FROM `gest_pagos` where total_cobrado <> (select sum(gest_pagos_det.monto_pago_det) from gest_pagos_det where idpago = gest_pagos.idpago)
and gest_pagos.idcaja = 2490


// corregir diferencias
update gest_pagos
set
total_cobrado =  COALESCE((select sum(gest_pagos_det.monto_pago_det) from gest_pagos_det where idpago = gest_pagos.idpago),0)
WHERE
total_cobrado <> (select sum(gest_pagos_det.monto_pago_det) from gest_pagos_det where idpago = gest_pagos.idpago)
and gest_pagos.idcaja = 2490
*/
$idcaja = intval($_GET['id']);
if ($idcaja == 0) {
    header("location: informe_caja_new.php");
    exit;
}

if ($idusu != 2 && $idusu != 3) {
    $whereadd2 = "
    and cajero <> 3
    and cajero <> 2
    ";
}
if ($soporte <> 1) {
    $whereadd2 .= "
    and cajero not in (select idusu from usuarios where soporte = 1)
    ";
}
$consulta = "
select * , (select usuario from usuarios where idusu = caja_super.cajero) as cajero_usu,
(select nombre from sucursales where idempresa = $idempresa and idsucu = caja_super.sucursal) as sucursal
from caja_super
where
estado_caja <> 6
and idcaja = $idcaja
$whereadd2
order by caja_super.estado_caja asc, fecha_apertura desc
$limit
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcaja = intval($rs->fields['idcaja']);
$idcaja_compartida = intval($rs->fields['idcaja_compartida']);
$rendido = intval($rs->fields['rendido']);
if ($idcaja == 0) {
    header("location: informe_caja_new.php");
    exit;
}
// recalcular caja
recalcular_caja($idcaja);
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


// cajas secundarias
$consulta = "
SELECT * 
FROM `caja_super` 
WHERE 
idcaja_compartida = $idcaja
and estado_caja <> 6
";
$rscajsec = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcaja_secundaria = intval($rscajsec->fields['idcaja']);



// buscar primero si ya existe registros en esta caja en caja_arqueo_fpagos si no existe ninguno procesar los 3
//delete from  `caja_arqueo_fpagos` where id_unicasspk is not null or  id_registrobill is not null or id_sermone is not null


// marca como rendido o no rendido
if (isset($_GET['r']) && ($_GET['r'] == 's' or $_GET['r'] == 'n')) {

    $r = trim($_GET['r']);
    if ($r == 's') {
        $rendido = "S";
    } else {
        $rendido = "N";
    }

    $consulta = "
    update caja_super
    set rendido = '$rendido'
    where
    caja_super.sucursal in (SELECT idsucu FROM sucursales where idempresa = $idempresa)
    and idcaja = $idcaja
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    header("location: informe_caja_arq_new.php?id=$idcaja");
    exit;
}

$buscar = "Select muestra_propinas_caja from preferencias_caja limit 1";
$rsprefecaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$muestra_propinas_caja = trim($rsprefecaj->fields['muestra_propinas_caja']);
?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
            <?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Informe de Caja</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<?php
$refemenu = "res";
require_once("includes/menu_cajanew.php");

?>

<?php if ($idcaja_compartida > 0) { ?>
<hr />
<div class="alert alert-info alert-dismissible fade in" role="alert">
<h3>Viendo Caja compartida  <span style="color:#CCC;">SECUNDARIA</span>!</h3><br />
Estas visualizando una <strong>caja secundaria</strong> que depende de la Caja Principal Nro.: <?php echo $idcaja_compartida; ?><br />
<a href="informe_caja_arq_new.php?id=<?php echo $idcaja_compartida; ?>" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Caja Princial #<?php echo $idcaja_compartida; ?></a>
</div>
<?php } ?>
<?php if ($idcaja_secundaria > 0) { ?>
<hr />
<div class="alert alert-info alert-dismissible fade in" role="alert">
<h3>Viendo Caja compartida <span style="color:green;">PRINCIPAL</span>!</h3><br />
Estas visualizando una <strong>caja principal</strong> con movimientos que vinieron de cajas secundarias y tambien propios.<br />
<?php while (!$rscajsec->EOF) {
    $idcaja_secundaria = $rscajsec->fields['idcaja'];
    ?>
<a href="informe_caja_arq_new.php?id=<?php echo $idcaja_secundaria; ?>" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Caja Secundaria #<?php echo $idcaja_secundaria; ?></a>
<?php $rscajsec->MoveNext();
} ?>
</div>
<?php } ?>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
            <tr align="center" valign="middle">
        
              <th>Idcaja</th>
              <th>Sucursal</th>
              <th>Apertura</th>
              <th>Cierre</th>
              <th>Estado</th>
              <th>Cajero</th>
              <th>Monto Apertura</th>
              <th>Monto al cierre</th>
              <th>Sobrante</th>
              <th>Faltante</th>
              


            </tr>
    </thead>    
    <tbody>
<?php


$estado = $rs->fields['estado_caja'];
if ($estado == 1) {
    $estadocaja = "Abierta";
} elseif ($estado == 3) {
    $estadocaja = "Cerrada";
} else {
    $estadocaja = "Indeterminada";
}
?>
            <tr align="center" valign="middle">
      
              <td><?php echo $rs->fields['idcaja']; ?></td>
              <td align="left"><?php echo $rs->fields['sucursal']; ?></td>
              <td><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_apertura'])); ?></td>
              <td><?php if ($rs->fields['fecha_cierre'] != '') {
                  echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_cierre']));
              } ?></td>
              <td><?php echo $estadocaja?></td>
              <td><?php echo capitalizar($rs->fields['cajero_usu']); ?></td>
              <td align="right"><?php echo formatomoneda($rs->fields['monto_apertura']); ?></td>
              <td><?php if ($estado == 3) {
                  echo formatomoneda($rs->fields['monto_cierre']);
              } else {
                  echo "Caja Abierta";
              }  ?></td>
              <td align="right"><?php if ($estado == 3) {
                  echo formatomoneda($rs->fields['sobrante']);
              } else {
                  echo "Caja Abierta";
              }  ?></td>
              <td align="right" style="color:#FF0000;"><?php if ($estado == 3) {
                  echo formatomoneda($rs->fields['faltante']);
              } else {
                  echo "Caja Abierta";
              } ?></td>
  
            </tr>

      </tbody>
    </table>
</div>
<br />



<strong>Balance de Caja:</strong>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="left">Totales Sistema</th>

            <th align="center">Monto</th>

        </tr>
      </thead>
      <tbody>
        <tr style="background-color: #CCC;">

            <td align="left">Monto Apertura</td>

            <td align="right"><?php echo formatomoneda($rs->fields['monto_apertura']); ?></td>
        </tr>
<?php
$consulta = "
SELECT formas_pago.descripcion as formapago, count(gest_pagos_det.idpagodet) as cantidad, sum(gest_pagos_det.monto_pago_det) as total
FROM gest_pagos
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
and tipomovdinero = 'E'
group by formas_pago.descripcion
order by formas_pago.descripcion asc
";
$rsing = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<?php while (!$rsing->EOF) { ?>
        <tr>

            <td align="left"> > ING: <?php echo strtoupper($rsing->fields['formapago']); ?></td>
            <td align="right"><?php echo formatomoneda($rsing->fields['total']); ?></td>
        </tr>
<?php

$rsing->MoveNext();
} ?>  
        <tr style="background-color: #CCC;">
<?php
$consulta = "
SELECT sum(gest_pagos.total_cobrado) as total
FROM gest_pagos
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
and gest_pagos.tipomovdinero = 'E'
";
$rsent = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$consulta = "
SELECT sum(gest_pagos.total_cobrado)*-1 as total
FROM gest_pagos
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
and gest_pagos.tipomovdinero = 'S'
";
$rssal = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cantidad_acum = 0;
$total_acum = 0;
//echo $rsent->fields['total'];exit;
$total_sistema = $rsent->fields['total'] + $rssal->fields['total'] + $rs->fields['monto_apertura'];
?>            
            <td align="left">(+) Total Ingresos Sistema</td>

            <td align="right"><?php echo formatomoneda($rsent->fields['total']); ?></td>
        </tr>
<?php
$consulta = "
SELECT formas_pago.descripcion as formapago, count(gest_pagos_det.idpagodet)*-1 as cantidad, sum(gest_pagos_det.monto_pago_det)*-1 as total
FROM gest_pagos
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
and tipomovdinero = 'S'
group by formas_pago.descripcion
order by formas_pago.descripcion asc
";
$rsegr = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<?php while (!$rsegr->EOF) { ?>
        <tr>

            <td align="left"> > EGR: <?php echo strtoupper($rsegr->fields['formapago']); ?></td>
            <td align="right"><?php echo formatomoneda($rsegr->fields['total']); ?></td>
        </tr>
<?php

$rsegr->MoveNext();
} ?>
          
        <tr style="background-color: #CCC;">

            <td align="left">(-) Total Egresos Sistema</td>

            <td align="right"><?php echo formatomoneda($rssal->fields['total']); ?></td>
        </tr>
        <tr style="background-color: #CCC;">

            <td align="left">(=) Total Sistema</td>

            <td align="right"><?php echo formatomoneda($total_sistema); ?></td>
        </tr>
        <?php if ($muestra_propinas_caja == 'S') {
            $buscar = "Select sum(monto_propina) as total,(select descripcion from formas_pago where idforma=mesas_atc_propinas.mediopago) as formapago
            from mesas_atc_propinas where idcaja=$idcaja group by mediopago";
            $rsmp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            //echo $buscar;
            ?>

                <tr style="background-color: #99CC96">
                    <th align="left">Propinas de la caja</th>

                    <th align="center">Monto</th>

                </tr>
                
                <?php while (!$rsmp->EOF) { ?>
                    <tr>
                        <td><span class="fa fa-arrow-right"></span>&nbsp;<?php echo $rsmp->fields['formapago']; ?></td>
                        <td align='right'><?php echo formatomoneda($rsmp->fields['total']); ?></td>
                        
                    </tr>
                <?php     $rsmp->MoveNext();
                }?>
                
            
        
        <?php } ?>

      </tbody>
      
<?php
if ($estado == 3) {

    $consulta = "
select formas_pago.descripcion as formapago, sum(monto) as total
from caja_arqueo_fpagos
inner join formas_pago on formas_pago.idforma = caja_arqueo_fpagos.idformapago
where
caja_arqueo_fpagos.idcaja = $idcaja
and caja_arqueo_fpagos.estado <> 6
group by formas_pago.descripcion
order by formas_pago.descripcion asc
";
    $rsarq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
      <thead>
        <tr>
            <th align="left">Totales Declarados</th>

            <th align="center">Monto</th>

        </tr>
        </thead>
        <tbody>
<?php while (!$rsarq->EOF) { ?>
        <tr>

            <td align="left">(+) <?php echo antixss($rsarq->fields['formapago']); ?></td>

            <td align="right"><?php echo formatomoneda($rsarq->fields['total']); ?></td>
        </tr>
<?php
    $total_dec_acum += $rsarq->fields['total'];
    $rsarq->MoveNext();
} ?>

        <tr style="background-color: #CCC;">

            <td align="left">(=) Totales Declarados</td>
            <td align="right"><?php echo formatomoneda($total_dec_acum); ?></td>
        </tr>
        </tbody>
      <thead>
        <tr>
            <th align="left">Total Declarado - Total Sistema</th>

            <th align="center">Monto</th>

        </tr>
        </thead>
        <tbody>
        <tr>

            <td align="left">(+) Totales Declarado</td>
            <td align="right"><?php echo formatomoneda($total_dec_acum); ?></td>
        </tr>
        <tr>

            <td align="left">(-) Totales Sistema</td>
            <td align="right"><?php echo formatomoneda($total_sistema * -1); ?></td>
        </tr>

        </tbody>
        <tfoot>
        <tr>
<?php

$diferencia = $total_dec_acum - $total_sistema;
    if ($diferencia < 0) {
        $resultado = "FALTANTE";
        $color = "#F00";
    }
    if ($diferencia > 0) {
        $resultado = "SOBRANTE";
        $color = "#00F";
    }
    if ($diferencia == 0) {
        $resultado = "SIN DIFERENCIAS";
        $color = "#090";
    }


    ?>
            <td align="left"  style="color:<?php echo $color ?>;">(=) Diferencia (<?php echo $resultado; ?>) </td>
            <td align="right" style="color:<?php echo $color ?>;"><?php echo formatomoneda($diferencia); ?></td>
        </tr>
        
      </tfoot>
      
<?php } ?>

    </table>
</div>
<br />
<?php if ($muestra_propinas_caja == 'S') {
    $buscar = "Select sum(monto_propina) as total from mesas_atc_propinas where idcaja=$idcaja";
    $rstpropinas = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    ?>
<div class="table-responsive">
            <table width="100%" class="table table-bordered jambo_table bulk_action">
        
                <thead>
                <tr>
                    <th align="left">Calculos Auxiliares</th>

                    <th align="center">Monto</th>

                </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Propinas de la caja</td>
                        <td align='right'><?php echo formatomoneda($rstpropinas->fields['total']); ?></td>
                        
                    </tr>
                    <tr>
                        <td>Sobrante de caja</td>
                        <td align='right'><?php echo formatomoneda($diferencia); ?></td>
                        
                    </tr>
                    <tr style="background-color:yellow;" >
                        <td>Resultante de caja</td>
                        <td align='right'><?php echo formatomoneda($diferencia - $rstpropinas->fields['total']); ?></td>
                        
                    </tr>
                </tbody>
            </table>
</div>            
        <?php } ?>

<?php

if ($estado == 3) {

    $consulta = "
select formas_pago.descripcion as formapago, sum(monto) as total
from caja_arqueo_fpagos
inner join formas_pago on formas_pago.idforma = caja_arqueo_fpagos.idformapago
where
caja_arqueo_fpagos.idcaja = $idcaja
and caja_arqueo_fpagos.estado <> 6
and formas_pago.idforma = 1
group by formas_pago.descripcion
order by formas_pago.descripcion asc
limit 1
";
    $rsarq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
        <thead>
            <tr>

                <th align="left">Efectivo Declarado</th>
                <th align="left">(-) Monto Apertura</th>
                <th align="left">(=) Efectivo Sin Apertura</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td align="right"><?php echo formatomoneda($rsarq->fields['total'], 4, 'N'); ?></td>
                <td align="right"><?php echo formatomoneda($rs->fields['monto_apertura'], 4, 'N'); ?></td>
                <td align="right"><?php echo formatomoneda($rsarq->fields['total'] - $rs->fields['monto_apertura'], 4, 'N'); ?></td>
            </tr>
        </tbody>
   </table>
</div>
<br />

<?php } ?>


<strong>Informaciones Relevantes:</strong>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="left">Informacion</th>
            <th align="center">Cantidad</th>
            <th align="center">Total</th>

        </tr>
      </thead>
      <tbody>

        <tr>
<?php

    //descuento x productos
    $consulta = "
Select count(ventas.idventa) as cantidad,  sum(ventas_detalles.descuento) as total
from ventas_detalles 
inner join ventas on ventas.idventa=ventas_detalles.idventa 
inner join productos on productos.idprod_serial=ventas_detalles.idprod
where 
ventas.estado <> 6
and ventas_detalles.descuento > 0
and ventas.idcaja=$idcaja
";
$rsdescp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
            <td align="left">Descuentos sobre Productos</td>
            <td align="center"><?php echo formatomoneda($rsdescp->fields['cantidad']); ?></td>
            <td align="right"><?php echo formatomoneda($rsdescp->fields['total']); ?></td>
        </tr>
        <tr>
<?php

// descuentos x factura
$consulta = "
select count(ventas.idventa) as cantidad, sum(descneto) as total
from ventas 
where
descneto > 0
and idcaja = $idcaja
and estado <> 6
";
//echo $consulta;
$rsdesctot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
            <td align="left">Descuentos sobre Facturas</td>
            <td align="center"><?php echo formatomoneda($rsdesctot->fields['cantidad']); ?></td>
            <td align="right"><?php echo formatomoneda($rsdesctot->fields['total']); ?></td>
        </tr>
        <tr>
<?php
$consulta = "
select sum(totalcobrar) as total, count(idventa) as cantidad
from ventas 
inner join usuarios on ventas.anulado_por = usuarios.idusu
where
ventas.idcaja = $idcaja
and ventas.estado = 6
";
$rsanul = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
            <td align="left">Ventas Anuladas</td>
            <td align="center"><?php echo formatomoneda($rsanul->fields['cantidad']); ?></td>
            <td align="right"><?php echo formatomoneda($rsanul->fields['total']); ?></td>
        </tr>
<?php
$consulta = "
select sum(totalcobrar) as total, count(idventa) as cantidad
from ventas 
where
ventas.idcaja = $idcaja
and ventas.estado <> 6
and tipo_venta = 2
";
$rsvcred = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
        <tr>
          <td align="left">Ventas a Credito</td>
            <td align="center"><?php echo formatomoneda($rsvcred->fields['cantidad']); ?></td>
            <td align="right"><?php echo formatomoneda($rsvcred->fields['total']); ?></td>
          </tr>
        <tr>
<?php
$consulta = "
select sum(totalcobrar) as total, count(idventa) as cantidad
from ventas 
where
ventas.idcaja = $idcaja
and ventas.estado <> 6
and tipo_venta = 1
";
$rsvcont = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
SELECT formas_pago.descripcion as formapago, count(gest_pagos_det.idpagodet) as cantidad, sum(gest_pagos_det.monto_pago_det) as total
FROM gest_pagos
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
and gest_pagos.tipomovdinero = 'N'
and gest_pagos.idventa is not null
group by formas_pago.descripcion
order by formas_pago.descripcion asc
";
$rsvant = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
        <tr>
          <td align="left">Ventas al Contado</td>
            <td align="center"><?php echo formatomoneda($rsvcont->fields['cantidad']); ?></td>
            <td align="right"><?php echo formatomoneda($rsvcont->fields['total']); ?></td>
          </tr>
        <tr>
        <tr>
          <td align="left">Ventas Totales</td>
            <td align="center"><?php echo formatomoneda($rsvcred->fields['cantidad'] + $rsvcont->fields['cantidad']); ?></td>
            <td align="right"><?php echo formatomoneda($rsvcred->fields['total'] + $rsvcont->fields['total']); ?></td>
          </tr>
        <tr>
        <tr>
          <td align="left">Anticipos Aplicados a Ventas</td>
            <td align="center"><?php echo formatomoneda($rsvant->fields['cantidad']); ?></td>
            <td align="right"><?php echo formatomoneda($rsvant->fields['total']); ?></td>
          </tr>
        <tr>
<?php


$consulta = "
select count(idtmpventares_cab) as cantidad, sum(monto) as total
from tmp_ventares_cab
inner join usuarios on tmp_ventares_cab.anulado_por = usuarios.idusu
where
tmp_ventares_cab.estado = 6
and tmp_ventares_cab.anulado_idcaja = $idcaja
and tmp_ventares_cab.monto > 0
";
//echo $consulta;
$rspedborra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>

            <td align="left">Pedidos Borrados</td>
            <td align="center"><?php echo formatomoneda($rspedborra->fields['cantidad']); ?></td>
            <td align="right"><?php echo formatomoneda($rspedborra->fields['total']); ?></td>
        </tr>
        <tr>
<?php
$consulta = "
select sum(cantidad) as cantidad, sum(subtotal) as total
from 
(
select sum(cantidad) as cantidad, sum(subtotal) as subtotal
from tmp_ventares
inner join usuarios on tmp_ventares.borrado_mozo_por = usuarios.idusu
inner join productos on productos.idprod=tmp_ventares.idproducto
where
tmp_ventares.borrado = 'S'
and tmp_ventares.borrado_mozo = 'S'
and tmp_ventares.borrado_mozo_idcaja = $idcaja

UNION

select  sum(cantidad) as cantidad, sum(subtotal) as subtotal
from tmp_ventares_bak
inner join usuarios on tmp_ventares_bak.borrado_mozo_por = usuarios.idusu
inner join productos on productos.idprod=tmp_ventares_bak.idproducto
where
tmp_ventares_bak.borrado = 'S'
and tmp_ventares_bak.borrado_mozo = 'S'
and tmp_ventares_bak.borrado_mozo_idcaja = $idcaja
) pedbor
";
//echo $consulta;
$rspedborraprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
            <td align="left">Productos Borrados de Pedidos Activos</td>
            <td align="center"><?php echo formatomoneda($rspedborraprod->fields['cantidad']); ?></td>
            <td align="right"><?php echo formatomoneda($rspedborraprod->fields['total']); ?></td>
        </tr>
<?php
$consulta = "
select sum(cantidad_vale)  as cantidad, sum(descneto) as total
from ventas 
where 
idcaja = $idcaja
and cantidad_vale is not null
and estado <> 6
";
//echo $consulta;
$rsvale = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rsvale->fields['cantidad']) > 0) {
    ?>
        <tr>
            <td align="left">Vales</td>
            <td align="center"><?php echo formatomoneda($rsvale->fields['cantidad']); ?></td>
            <td align="right"><?php echo formatomoneda($rsvale->fields['total']); ?></td>
        </tr>
<?php } ?>
      </tbody>

    </table>
</div>
<br />



<br />
<?php
    $consulta = "
SELECT 

CASE WHEN 
    gest_pagos.tipomovdinero = 'S'
THEN
    'SALIDA'
ELSE
    'ENTRADA'
END AS tipomovdinero, 
CASE WHEN 
    gest_pagos.tipomovdinero = 'S'
THEN
    count(gest_pagos.idpago)*-1
ELSE
    count(gest_pagos.idpago)
END as cantidad, 

CASE WHEN 
    gest_pagos.tipomovdinero = 'S'
THEN
    sum(gest_pagos.total_cobrado)*-1
ELSE
    sum(gest_pagos.total_cobrado)
END as total

FROM gest_pagos
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
group by gest_pagos.tipomovdinero
order by gest_pagos.tipomovdinero asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cantidad_acum = 0;
$total_acum = 0;
?>
<strong>Resumen por Entrada/Salida:</strong>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="left">Entrada/Salida</th>
            <th align="center">Cantidad</th>
            <th align="center">Total</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>

            <td align="left"><?php echo $rs->fields['tipomovdinero']; ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['cantidad']); ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['total']); ?></td>
        </tr>
<?php
$cantidad_acum += $rs->fields['cantidad'];
    $total_acum += $rs->fields['total'];
    $rs->MoveNext();
} ?>

      </tbody>
      <tfoot>
        <tr>
            <td>Totales</td>

            <td align="center"><?php echo formatomoneda($cantidad_acum); ?></td>
            <td align="right"><?php echo formatomoneda($total_acum); ?></td>

        </tr>
      </tfoot>
    </table>
</div>
<br />

<?php
$consulta = "
SELECT 
caja_gestion_mov_tipos.tipo_movimiento, 

CASE WHEN 
    gest_pagos.tipomovdinero = 'S'
THEN
    count(gest_pagos.idpago)*-1
ELSE
    count(gest_pagos.idpago)
END as cantidad, 

CASE WHEN 
    gest_pagos.tipomovdinero = 'S'
THEN
    sum(gest_pagos.total_cobrado)*-1
ELSE
    sum(gest_pagos.total_cobrado)
END as total

FROM `gest_pagos`
INNER JOIN caja_gestion_mov_tipos on caja_gestion_mov_tipos.idtipocajamov = gest_pagos.idtipocajamov 
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
group by caja_gestion_mov_tipos.tipo_movimiento
order by caja_gestion_mov_tipos.tipo_movimiento asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cantidad_acum = 0;
$total_acum = 0;
?>
<strong>Resumen por tipos de movimiento:</strong>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="left">Tipo Movimiento</th>
            <th align="center">Cantidad</th>
            <th align="center">Total</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>

            <td align="left"><?php echo strtoupper($rs->fields['tipo_movimiento']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['cantidad']); ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['total']); ?></td>
        </tr>
<?php
$cantidad_acum += $rs->fields['cantidad'];
    $total_acum += $rs->fields['total'];
    $rs->MoveNext();
} ?>
      </tbody>
      <tfoot>
        <tr>
            <td>Totales</td>

            <td align="center"><?php echo formatomoneda($cantidad_acum); ?></td>
            <td align="right"><?php echo formatomoneda($total_acum); ?></td>

        </tr>
      </tfoot>
    </table>
</div>
<br />



<?php
$consulta = "
SELECT formas_pago.descripcion as formapago, count(gest_pagos_det.idpagodet) as cantidad, sum(gest_pagos_det.monto_pago_det) as total
FROM gest_pagos
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
and tipomovdinero = 'E'
group by formas_pago.descripcion
order by formas_pago.descripcion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cantidad_acum = 0;
$total_acum = 0;
?>
<strong>Resumen por formas de pago (Entrantes):</strong>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="left">Forma de Pago</th>
            <th align="center">Cantidad</th>
            <th align="center">Total</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>

            <td align="left"><?php echo strtoupper($rs->fields['formapago']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['cantidad']); ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['total']); ?></td>
        </tr>
<?php
$cantidad_acum += $rs->fields['cantidad'];
    $total_acum += $rs->fields['total'];
    $rs->MoveNext();
} ?>
      </tbody>
      <tfoot>
        <tr>
            <td>Totales</td>

            <td align="center"><?php echo formatomoneda($cantidad_acum); ?></td>
            <td align="right"><?php echo formatomoneda($total_acum); ?></td>

        </tr>
      </tfoot>
    </table>
</div>
<br />



<?php
$consulta = "
SELECT formas_pago.descripcion as formapago, count(gest_pagos_det.idpagodet)*-1 as cantidad, sum(gest_pagos_det.monto_pago_det)*-1 as total
FROM gest_pagos
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
and tipomovdinero = 'S'
group by formas_pago.descripcion
order by formas_pago.descripcion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cantidad_acum = 0;
$total_acum = 0;
?>
<strong>Resumen por formas de pago (Salientes):</strong>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="left">Forma de Pago</th>
            <th align="center">Cantidad</th>
            <th align="center">Total</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>

            <td align="left"><?php echo strtoupper($rs->fields['formapago']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['cantidad']); ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['total']); ?></td>
        </tr>
<?php
$cantidad_acum += $rs->fields['cantidad'];
    $total_acum += $rs->fields['total'];
    $rs->MoveNext();
} ?>
      </tbody>
      <tfoot>
        <tr>
            <td>Totales</td>

            <td align="center"><?php echo formatomoneda($cantidad_acum); ?></td>
            <td align="right"><?php echo formatomoneda($total_acum); ?></td>

        </tr>
      </tfoot>
    </table>
</div>
<br />





<br /><br />

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            

            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
