 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "11";
$submodulo = "467";
require_once("includes/rsusuario.php");


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
$idnumeradorcab = intval($rs->fields['idnumeradorcab']);
if ($idcaja == 0) {
    header("location: informe_caja_new.php");
    exit;
}

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


// cargar retroactivo vouchers
$consulta = "
INSERT INTO `caja_arqueo_fpagos`
(`idcaja`, `idformapago`, `monto`, `idbanco`, `valor_adicional`, `estado`, `registrado_por`, `registrado_el`, `anulado_por`, `anulado_el`, `id_unicasspk`, `id_registrobill`, `id_sermone`)

select idcaja, 2, total_vouchers, NULL, NULL, 1, cajero, registrado_el, NULL, 
NULL, unicasspk, NULL, NULL
from caja_vouchers
WHERE
unicasspk not in (select id_unicasspk from caja_arqueo_fpagos where id_unicasspk is not null)
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


// cargar retroactivo billetes
$consulta = "
INSERT INTO `caja_arqueo_fpagos`
(`idcaja`, `idformapago`, `monto`, `idbanco`, `valor_adicional`, `estado`, `registrado_por`, `registrado_el`, `anulado_por`, `anulado_el`, `id_unicasspk`, `id_registrobill`, `id_sermone`)

select idcaja, 1, subtotal, NULL, NULL, 1, idcajero, NOW(), NULL,
NULL, NULL,  registrobill, NULL
from caja_billetes 
WHERE
registrobill not in (select id_registrobill from caja_arqueo_fpagos where id_registrobill is not null)
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



// cargar retroactivo moneda extrangera
$consulta = "
INSERT INTO `caja_arqueo_fpagos`
(`idcaja`, `idformapago`, `monto`, `idbanco`, `valor_adicional`, `estado`, `registrado_por`, `registrado_el`, `anulado_por`, `anulado_el`, `id_unicasspk`, `id_registrobill`, `id_sermone`)

select idcaja, 1, subtotal, NULL, NULL, 1, cajero, NOW(), NULL,
NULL, NULL,  NULL, sermone
from caja_moneda_extra 
WHERE
sermone not in (select id_sermone from caja_arqueo_fpagos where id_sermone is not null)
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



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
$refemenu = "det";
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

<?php

$consulta = "
select * 
from ventas 
where
descneto > 0
and idcaja = $idcaja
and estado <> 6
order by fecha asc
";
//echo $consulta;
$rsdesc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>

    <strong>Descuentos s/ Factura:</strong><br /><br />
    <div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
          <th ><strong>Fecha / Hora</strong></th>
          <th >Total Venta</th>
          <th >+ Delivery</th>
          <th ><strong>- Descuento</strong></th>
          <th ><strong>Monto Cobrado</strong></th>
          <th width="40" ><strong>Justificacion</strong></th>
        </tr>
      <thead>
      <tbody>
<?php
          $totdescu = 0;
while (!$rsdesc->EOF) {
    $totdescu = $totdescu + floatval($rsdesc->fields['descneto']);
    ?>
        <tr>
          <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rsdesc->fields['fecha'])); ?></td>
          <td align="right"><?php echo formatomoneda($rsdesc->fields['total_venta'] + $rsdesc->fields['otrosgs']); ?></td>
          <td align="right"><?php echo formatomoneda($rsdesc->fields['otrosgs']); ?></td>
          <td align="right"><?php echo formatomoneda($rsdesc->fields['descneto']); ?></td>
          <td align="right"><?php echo formatomoneda($rsdesc->fields['total_cobrado']); ?></td>
          <td align="center"><?php echo strtolower($rsdesc->fields['obs']); ?></td>
        </tr>
<?php $rsdesc->MoveNext();
} ?>
          <tr>
              <td height="29" colspan="6" align="right"> <strong>Total descuento :<?php echo formatomoneda($totdescu);?></strong></td> 
        </tr>
      </tbody>
      </table>
      </div>
   
        
        <br />

    <strong>Descuentos por productos</strong><br /><br />
    <div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
          <th width="104" ><strong>Fecha / Hora</strong></th>
          <th width="107" >Factura Num</th>
          <th width="79" >Cliente</th>
          <th width="151" ><strong>Articulo Descontado</strong></th>
          <th width="134" ><strong>Monto Descuento</strong></th>
          
        </tr>
      </thead>
      <tbody>
      
<?php
//descuento x productos
    $buscar = "
    Select ventas.fecha,ventas.idventa,factura,
        (select razon_social
                from cliente where idcliente=ventas.idcliente) as cliente ,
                 ventas.ruc, descripcion,sum(ventas_detalles.descuento) as tdescontado 
        from ventas_detalles 
        inner join ventas on ventas.idventa=ventas_detalles.idventa 
        inner join productos on productos.idprod_serial=ventas_detalles.idprod
        where 
        ventas.estado<>6
        and ventas_detalles.descuento >0
        and ventas.idcaja=$idcaja
        group by ventas_detalles.idprod, ventas.idventa
        order by idventa asc
    
    ";
$rsdescp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


$totdescup = 0;
while (!$rsdescp->EOF) {
    $totdescup = $totdescup + floatval($rsdescp->fields['tdescontado']);
    ?>
        <tr>
          <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rsdescp->fields['fecha'])); ?></td>
          <td align="right"><?php echo($rsdescp->fields['factura']); ?> [<?php echo($rsdescp->fields['idventa']); ?>]</td>
          <td align="right"><?php echo($rsdescp->fields['cliente']); ?></td>
          <td align="right"><?php echo($rsdescp->fields['descripcion']); ?></td>
          <td align="right"><?php echo formatomoneda($rsdescp->fields['tdescontado']); ?></td>
          
        </tr>
<?php $rsdescp->MoveNext();
} ?>
          <tr>
              <td height="29" colspan="6" align="right"> <strong>Total descuento :<?php echo formatomoneda($totdescup);?></strong></td> 
        </tr>
      </tbody>
      </table>
      </div>
       <p>&nbsp;</p>
    <br />
<?php
$consulta = "
select * 
from ventas 
inner join usuarios on ventas.anulado_por = usuarios.idusu
where
ventas.idcaja = $idcaja
and ventas.estado = 6
order by ventas.fecha asc
";
//echo $consulta;
$rsanul = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
    <strong>Ventas Anuladas:</strong><br /><br />
           <div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
             <thead>
               <tr>
                 <th ><strong>Fecha / Hora</strong></th>
                 <th >Cod</th>
                 <th >Anulado por</th>
                 <th >Total Venta</th>
                 <th >+ Delivery</th>
                 <th ><strong>- Descuento</strong></th>
                 <th ><strong>Monto Cobrado</strong></th>
               </tr>
             </thead>
             <thead>
             </thead>
             <tbody>
               <?php
                 $anuladast = 0;
while (!$rsanul->EOF) {
    $anuladast = $anuladast + intval($rsanul->fields['total_cobrado']);
    ?>
               <tr>
                 <td align="center"><p>Cobrado: <?php echo date("d/m/Y H:i:s", strtotime($rsanul->fields['fecha'])); ?></p>
                 <p>Anulado: <?php echo date("d/m/Y H:i:s", strtotime($rsanul->fields['anulado_el'])); ?></p></td>
                 <td align="center"><a href="informe_caja_tk.php?id=<?php echo $idcaja; ?>&v=<?php echo $rsanul->fields['idventa']; ?>"><?php echo $rsanul->fields['idventa']; ?></a></td>
                 <td align="center"><?php echo capitalizar($rsanul->fields['nombres']); ?> <?php echo capitalizar($rsanul->fields['apellidos']); ?></td>
                 <td align="right"><?php echo formatomoneda($rsanul->fields['total_venta'] + $rsanul->fields['otrosgs']); ?></td>
                 <td align="right"><?php echo formatomoneda($rsanul->fields['otrosgs']); ?></td>
                 <td align="right"><?php echo formatomoneda($rsanul->fields['descneto']); ?></td>
                 <td align="right"><?php echo formatomoneda($rsanul->fields['total_cobrado']); ?></td>
               </tr>
               <?php $rsanul->MoveNext();
} ?>
                 <tr>
                     <td height="34" colspan="7" align="right"> <strong>Total anulado :
<?php  echo formatomoneda($anuladast);?> 
                     </strong></td>
               </tr>
             </tbody>
           </table>
            </div>
           <p>&nbsp;</p>
        
    <br />

<?php
$consulta = "
select * 
from tmp_ventares_cab
inner join usuarios on tmp_ventares_cab.anulado_por = usuarios.idusu
where
tmp_ventares_cab.estado = 6
/*and date(tmp_ventares_cab.fechahora) >= '$apertura_fec'
and date(tmp_ventares_cab.fechahora) <= '$cierre_fec'*/
and tmp_ventares_cab.anulado_idcaja = $idcaja
and tmp_ventares_cab.monto > 0
and tmp_ventares_cab.idempresa = $idempresa
order by tmp_ventares_cab.fechahora asc
";
//echo $consulta;
$rspedborra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>


    <strong>Pedidos Borrados:</strong><br /><br />
  <div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
      <tr>
        <th >ID Pedido</th>
        <th ><strong>Fecha / Hora</strong></th>
        <th >Borrado por</th>
        <th >Total Venta</th>
        <th >+ Delivery</th>
        </tr>
    </thead>
    <thead>
    </thead>
    <tbody>
      <?php
        $totalborrado = 0;
while (!$rspedborra->EOF) {
    $totalborrado = $totalborrado + intval(($rspedborra->fields['monto']));
    ?>
      <tr>
        <td align="center"><?php echo $rspedborra->fields['idtmpventares_cab']; ?></td>
        <td align="center"><p>Tomado: <?php echo date("d/m/Y H:i:s", strtotime($rspedborra->fields['fechahora'])); ?></p>
          <p>Borrado: <?php echo date("d/m/Y H:i:s", strtotime($rspedborra->fields['anulado_el'])); ?></p></td>
        <td align="center"><?php echo capitalizar($rspedborra->fields['nombres']); ?> <?php echo capitalizar($rspedborra->fields['apellidos']); ?></td>
        <td align="right"><?php echo formatomoneda($rspedborra->fields['monto']); ?></td>
        <td align="right"><?php echo formatomoneda($rspedborra->fields['delivery_costo']); ?></td>
        </tr>
      <?php $rspedborra->MoveNext();
} ?>
        <tr>
            <td height="35" colspan="5" align="right"><strong>Total Pedidos Borrados: <?php echo formatomoneda($totalborrado);?></strong></td>
        </tr>
    </tbody>
  </table>
   </div><br />
<?php


$consulta = "
select * 
from tmp_ventares
inner join usuarios on tmp_ventares.borrado_mozo_por = usuarios.idusu
inner join productos on productos.idprod=tmp_ventares.idproducto
where
tmp_ventares.borrado = 'S'
and tmp_ventares.borrado_mozo = 'S'
and tmp_ventares.borrado_mozo_idcaja = $idcaja



UNION

select * , null, null, null
from tmp_ventares_bak
inner join usuarios on tmp_ventares_bak.borrado_mozo_por = usuarios.idusu
inner join productos on productos.idprod=tmp_ventares_bak.idproducto
where
tmp_ventares_bak.borrado = 'S'
and tmp_ventares_bak.borrado_mozo = 'S'
and tmp_ventares_bak.borrado_mozo_idcaja = $idcaja


order by fechahora asc
";
//echo $consulta;
$rspedborraprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<strong>Productos borrados de pedidos activos:</strong><br /><br />
  <div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
      <tr>
        <th ><strong>Fecha / Hora</strong></th>
        <th >Borrado por</th>
        <th >Producto</th>
        <th >Cantidad</th>
        <th >Subtotal</th>
        <th >ID Pedido Borrado</th>
        </tr>
    </thead>
    <thead>
    </thead>
    <tbody>
      <?php
       $totalborrado = 0;
while (!$rspedborraprod->EOF) {
    $totalborrado = $totalborrado + intval(($rspedborraprod->fields['monto']));
    ?>
      <tr>
        <td align="center"><p>Tomado: <?php echo date("d/m/Y H:i:s", strtotime($rspedborraprod->fields['fechahora'])); ?></p>
          <p>Borrado: <?php echo date("d/m/Y H:i:s", strtotime($rspedborraprod->fields['borrado_mozo_el'])); ?></p></td>
        <td align="center"><?php echo capitalizar($rspedborraprod->fields['nombres']); ?> <?php echo capitalizar($rspedborraprod->fields['apellidos']); ?></td>
        <td align="center"><?php echo capitalizar($rspedborraprod->fields['descripcion']); ?></td>
        <td align="center"><?php echo formatomoneda($rspedborraprod->fields['cantidad'], 2, 'N'); ?></td>
        <td align="right"><?php echo formatomoneda($rspedborraprod->fields['subtotal']); ?></td>
        <td align="right"><?php echo $rspedborraprod->fields['idtmpventares_cab']; ?></td>
        </tr>
      <?php $rspedborraprod->MoveNext();
} ?>
        <tr>
            <td height="35" colspan="6" align="right"><strong>Total Pedidos Borrados: <?php echo formatomoneda($totalborrado);?></strong></td>
        </tr>
    </tbody>
  </table>
   </div>
    <br />
<br />
    <strong>Pagos de la Caja:</strong><br />
    <?php
    $buscar = "Select unis,fecha,monto_abonado,concepto,factura,concepto,
    (select usuario from usuarios where idusu=pagos_extra.idusu) as respon,pagos_extra.estado,pagos_extra.anulado_el,
    (select usuario from  usuarios where idusu=pagos_extra.anulado_por) as anulador,tipocaja,
    (select nombre from proveedores where idproveedor=pagos_extra.idprov) as proveedor
    from pagos_extra
    where idcaja=$idcaja order by fecha asc";
$rsca = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tpa = $rsca->RecordCount();
if ($tpa > 0) {
    ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
  <thead>
    <tr>
      <th>Id</th>
      <th><strong>Fecha</strong></th>
      <th>Factura</th>
      <th>Proveedor</th>
      <th>Monto Abonado</th>
      <th>Observaciones </th>
    
      </tr>
  </thead>
  <tbody>
      <?php
        $totalpaca = 0;
    while (!$rsca->EOF) {
        if ($rsca->fields['anulador'] == '') {
            $totalpaca = $totalpaca + intval($rsca->fields['monto_abonado']);
        }
        ?>
      <tr>
        <th><?php echo $rsca->fields['unis'];?></th>
          <th><?php echo date("d-m-Y H:i:s", strtotime($rsca->fields['fecha']));?></th>
           <th><?php echo $rsca->fields['factura'];?></th>
           <th><?php echo $rsca->fields['proveedor'];?></th>
           <th><?php echo formatomoneda($rsca->fields['monto_abonado']);?></tdh
           ><th><?php echo $rsca->fields['concepto'];?><?php if ($rsca->fields['anulador'] != '') {
               echo  "<br /><span class='resaltarojomini'>".'Anulado:'.$rsca->fields['anulador'].'/'.date("d/m/Y H:i:s", strtotime($rsca->fields['anulado_el']))."</span>";
           }?></th>
      </tr>
        <?php $rsca->MoveNext();
    }?>
      <tr>
            <td height="35" colspan="6" align="right"><strong>Total Pago x caja: <?php echo formatomoneda($totalpaca);?></strong></td>
        </tr>
      </tbody>
    </table>
    </div>
    <?php }?>
    <br />
    <strong>Retiro de Valores:</strong><br />
<?php
$consulta = "
select *,
(select usuario from usuarios where caja_retiros.retirado_por = usuarios.idusu) as retirado_por
from caja_retiros 
where 
 estado = 1 
 and idcaja = $idcaja
order by fecha_retiro asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="center">Codigo</th>
            <th align="center">Monto</th>
            <th align="center">Fecha Retiro</th>
            <th align="center">Retirado por</th>
            <th align="center">Obs</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
        
            <td align="center"><?php echo antixss($rs->fields['regserialretira']); ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['monto_retirado']);  ?></td>
            <td align="center"><?php if ($rs->fields['fecha_retiro'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_retiro']));
            }  ?></td>

            <td align="center"><?php echo antixss($rs->fields['retirado_por']); ?></td>

            <td align="center"><?php echo antixss($rs->fields['obs']); ?></td>

        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />
    
    <br />
    <strong>Recepcion de Valores:</strong><br />
<?php
$consulta = "
select *,
(select usuario from usuarios where caja_reposiciones.entregado_por = usuarios.idusu) as entregado_por
from caja_reposiciones 
where 
 estado = 1 
 and idcaja = $idcaja
order by fecha_reposicion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr >
            <th align="center">Codigo</th>
            <th align="center">Monto</th>
            <th align="center">Fecha Retiro</th>
            <th align="center">Entregado por</th>
            <th align="center">Obs</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
        
            <td align="center"><?php echo antixss($rs->fields['regserialentrega']); ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['monto_recibido']);  ?></td>
            <td align="center"><?php if ($rs->fields['fecha_reposicion'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_reposicion']));
            }  ?></td>

            <td align="center"><?php echo antixss($rs->fields['entregado_por']); ?></td>

            <td align="center"><?php echo antixss($rs->fields['obs']); ?></td>

        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />
    
    <br />
    <strong>Cobranzas de cuentas Credito:</strong><br />
    <?php
    $buscar = "
    SELECT  
    gest_pagos.idcliente, gest_pagos.idpago, gest_pagos_det.monto_pago_det as total_cobrado,
    cliente.razon_social as titular, cuentas_clientes_pagos_cab.registrado_el as registrado_el,
    cuentas_clientes_pagos_cab.recibo, formas_pago.descripcion as formadepago
    from gest_pagos
    inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
    inner join cuentas_clientes_pagos_cab on gest_pagos.idcuentaclientepagcab = cuentas_clientes_pagos_cab.idcuentaclientepagcab
    inner join cliente on cliente.idcliente = cuentas_clientes_pagos_cab.idcliente
    inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
    where 
    gest_pagos.estado <> 6
    and gest_pagos.idcaja = $idcaja
    and cuentas_clientes_pagos_cab.estado <> 6
    ";
$cobrocaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tcobros = $cobrocaja->RecordCount();

?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
        <tr>
            <th><strong>Id Pago</strong></th>
            <th><strong>Titular</strong></th>
            <th><strong>Monto Abonado</strong></th>
            <th><strong>Forma de Pago</strong></th>
            <th><strong>Recibo</strong></th>
            <th><strong>Fecha</strong></th>
        </tr>
        </thead>
        <tbody>
        <?php
    while (!$cobrocaja->EOF) {
        $adhe = intval($cobrocaja->fields['idadherente']);
        $titu = intval($cobrocaja->fields['idcliente']);
        $pag = intval($cobrocaja->fields['idpago']);
        $totalcred = $cobrocaja->fields['total_cobrado'];
        $totalcredacum += $totalcred;
        ?>
        <tr>
            <td height="27" align="left"><a href="informe_caja_cobro_det.php?id=<?php echo $cobrocaja->fields['idcuentaclientepagcab']?>"><?php echo $cobrocaja->fields['idpago']?></a></td>
          <td align="left"><?php echo $cobrocaja->fields['titular'];?></td>
          <td align="right"><?php echo formatomoneda($cobrocaja->fields['total_cobrado']);?></td>
          <td align="center"><?php echo $cobrocaja->fields['formadepago'];?></td>
          <td align="center"><?php echo $cobrocaja->fields['recibo'];?></td>
          <td align="right"><?php echo date("d/m/Y H:i:s", strtotime($cobrocaja->fields['registrado_el']))?></td>
        </tr>
        
        <?php $cobrocaja->MoveNext();
    }?>
        </tbody>
         <tr>
            <td height="45" colspan="7" align="right"><strong>Total cobranzas credito: <?php echo formatomoneda($totalcredacum);?></strong></td>
      </tr>
    </table>
    </div>
    <br />

    


    <strong>Ventas de la Caja:</strong><br />

    <br />
<?php
$consulta = "
select *, 
    (
        SELECT GROUP_CONCAT(formas_pago.descripcion) AS formapago 
        from gest_pagos_det 
        inner join gest_pagos on gest_pagos.idpago = gest_pagos_det.idpago
        inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
        where 
        gest_pagos.idventa = ventas.idventa
        and gest_pagos.estado <> 6
        order by formas_pago.descripcion asc
     ) as formapago
from ventas 
where
idcaja = $idcaja
and idempresa = $idempresa
and estado <> 6
";
$rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>  
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
  <thead>
    <tr>
       <th></th>
      <th><strong>Fecha</strong></th>
      <th>Codvta</th>
      <th>Factura</th>
      <th>RUC</th>
      <th>Razon Social</th>
      <th>Forma Pago</th>
      <th>Condicion Venta</th>
      <th><strong>Total Venta</strong></th>
      </tr>
  </thead>
  <tbody>
<?php while (!$rsv->EOF) {
    $tventaacum += $rsv->fields['total_venta'];
    $tdeliveryacum += $rsv->fields['otrosgs'];
    $tdescuentoacum += $rsv->fields['descneto'];
    $tcobradoacum += $rsv->fields['total_cobrado'];

    ?>
    <tr>
    <td align="center">
    <a href="informe_caja_tk_new.php?id=<?php echo $idcaja; ?>&v=<?php echo $rsv->fields['idventa']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
</td>
      <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rsv->fields['fecha'])); ?></td>
      <td align="center"><a href="informe_caja_tk.php?id=<?php echo $idcaja; ?>&v=<?php echo $rsv->fields['idventa']; ?>"><?php echo $rsv->fields['idventa']; ?></a></td>
      <td align="center"><?php echo $rsv->fields['factura']; ?></td>
      <td align="center"><?php echo $rsv->fields['ruc']; ?></td>
      <td align="center"><?php echo $rsv->fields['razon_social']; ?></td>
      <td align="center"><?php echo $rsv->fields['formapago']; ?></td>
      <td align="center"><?php if ($rsv->fields['tipo_venta'] == 2) {
          echo '<span style="color:#F00;">CREDITO</span>';
      } else {
          echo "CONTADO";
      } ?></td>
      <td align="right"><?php echo formatomoneda($rsv->fields['total_cobrado']); ?></td>
      </tr>
<?php $rsv->MoveNext();
} ?>
</tbody>
<tfoot>
    <tr>
      <td><strong>Totales:</strong></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td><strong style="color:#1A8400;"><?php echo formatomoneda($tcobradoacum); ?></strong></td>
      </tr>
  </tfoot>
</table>
</div>
    <p>&nbsp;</p>
    
<?php

$consulta = "
select productos.descripcion as producto, sum(ventas_detalles.cantidad) as cantidad, sum(ventas_detalles.subtotal) as subtotal
from ventas 
inner join ventas_detalles on ventas.idventa = ventas_detalles.idventa
inner join productos on productos.idprod_serial = ventas_detalles.idprod
where
ventas.estado <> 6
and ventas.idcaja = $idcaja
group by productos.descripcion
order by sum(ventas_detalles.subtotal) desc
limit 1000
";
//echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>  <p>&nbsp;</p> 
<strong>Mix de Ventas de la Caja</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="center">Producto</th>
            <th align="center">Cantidad</th>
            <th align="center">Total</th>
        </tr>
      </thead>
      <tbody>
<?php
$lsub = 0;
while (!$rs->EOF) {
    $lsub = $lsub + $rs->fields['subtotal'];
    ?>
        <tr>
            <td align="left"><?php echo antixss($rs->fields['producto']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['cantidad'], '4', 'N'); ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['subtotal'], '4', 'N'); ?></td>
        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
        <tr>
            <td colspan="3" align="right">Sub total Gs: <?php echo formatomoneda($lsub, 4, 'N'); ?></td>
        
        </tr>
      </tbody>
    </table>
</div>
<br />


<?php
$consulta = "
select ventas.idventa, ventas.fecha, tmp_ventares_cab_pos.*, tarjetas_bancard.tipo as tipo_tarjeta, tarjetas_bancard.marca
from tmp_ventares_cab_pos 
left join tarjetas_bancard on tarjetas_bancard.issuerId = tmp_ventares_cab_pos.issuerId
inner join tmp_ventares_cab on tmp_ventares_cab.idtmpventares_cab = tmp_ventares_cab_pos.idtmpventares_cab
inner join ventas on ventas.idventa = tmp_ventares_cab.idventa
where 
ventas.estado <> 6 
and ventas.idcaja=$idcaja
order by idtmpventares_cab_pos asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>    
<?php if (intval($rs->fields['idtmpventares_cab_pos']) > 0) {?>
<strong>Boletas por Integracion Bancard:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
                        <th align="center">Nroboleta</th>
            <th align="center">Idventa</th>
            <th align="center">Fecha hora</th>
            <th align="center">Metodo</th>
            <th align="center">Codigoautorizacion</th>
            <th align="center">Codigocomercio</th>
            <th align="center">Issuerid</th>
            <th align="center">Tipo Tarjeta</th>
            <th align="center">Marca Tarjeta</th>
            <th align="center">Mensajedisplay</th>
            <th align="center">Montovueltopos</th>
            <th align="center">Nombrecliente</th>
            <th align="center">Nombretarjeta</th>

            <th align="center">Pan</th>
            <th align="center">Saldo</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
                        <td align="center"><?php echo antixss($rs->fields['nroBoleta']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['idventa']); ?></td>
            <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha'])); ?></td>
            <td align="center"><?php echo antixss($rs->fields['tipo']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['codigoAutorizacion']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['codigoComercio']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['issuerId']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['tipo_tarjeta']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['marca']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['mensajeDisplay']); ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['montoVuelto']);  ?></td>
            <td align="center"><?php echo antixss($rs->fields['nombreCliente']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['nombreTarjeta']); ?></td>

            <td align="center"><?php echo antixss($rs->fields['pan']); ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['saldo']);  ?></td>
        </tr>
<?php
$montoVuelto_acum += $rs->fields['montoVuelto'];
    $saldo_acum += $rs->fields['saldo'];

    $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
      <tfoot>
        <tr>
            <td>Totales</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td align="right"><?php echo formatomoneda($montoVuelto_acum);  ?></td>
            <td></td>
            <td></td>
            <td></td>
            <td align="right"><?php echo formatomoneda($saldo_acum);  ?></td>

            

        </tr>
      </tfoot>
    </table>
</div>
<br />                  
<?php } ?>

                          
 <?php
$consulta = "
select *,
(select usuario from usuarios where rrhh_anticipo_caja.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where rrhh_anticipo_caja.borrado_por = usuarios.idusu) as borrado_por,
concat(nombres,' ',apellidos) as funcionario
from rrhh_anticipo_caja
inner join gest_funcionarios on gest_funcionarios.idfunci = rrhh_anticipo_caja.idfuncionario
where 
rrhh_anticipo_caja.estado = 1 
and rrhh_anticipo_caja.idcaja = $idcaja
order by idrhanticipocaja asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

if (intval($rs->fields['idrhanticipocaja']) > 0) {


    ?>
<strong>Anticipos por Caja:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>

            <th align="center">Id</th>
            <th align="center">Funcionario</th>
            <th align="center">Idcaja</th>
            <th align="center">Monto anticipo</th>
            <th align="center">Fechahora</th>

            <th align="center">Registrado por</th>
            <th align="center">Registrado el</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>

            <td align="center"><?php echo intval($rs->fields['idrhanticipocaja']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['funcionario']); ?> [<?php echo intval($rs->fields['idfuncionario']); ?>]</td>
            <td align="center"><?php echo intval($rs->fields['idcaja']); ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['monto_anticipo']);  ?></td>
            <td align="center"><?php if ($rs->fields['fechahora'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fechahora']));
            }  ?></td>

            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
            <td align="center"><?php if ($rs->fields['registrado_el'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
            }  ?></td>
        </tr>
<?php
$monto_anticipo_acum += $rs->fields['monto_anticipo'];

    $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
      <tfoot>
        <tr>
            <td>Totales</td>

            <td></td>
            <td></td>
            <td align="right"><?php echo formatomoneda($monto_anticipo_acum); ?></td>
            <td></td>
        
            <td></td>
            <td></td>

        </tr>
      </tfoot>
    </table>
</div>
<br />  
<?php } ?>  
                  
<br />
<?php
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
<strong>Totales Declarados por Forma de Pago:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="left">Forma de Pago</th>

            <th align="center">Monto</th>

        </tr>
        </thead>
        <tbody>
<?php while (!$rsarq->EOF) { ?>
        <tr>

            <td align="left"> <?php echo antixss($rsarq->fields['formapago']); ?></td>

            <td align="right"><?php echo formatomoneda($rsarq->fields['total']); ?></td>
        </tr>
<?php
$total_dec_acum += $rsarq->fields['total'];
    $rsarq->MoveNext();
} ?>
        </tbody>
        <tfoot>
        <tr>

            <td align="left">Totales Declarados</td>
            <td align="right"><?php echo formatomoneda($total_dec_acum); ?></td>
        </tr>
        </tfoot>
    </table>
</div>



    <p>&nbsp;</p>
      <?php
      $consulta = "
      SELECT idbillete, (select valor from gest_billetes where idbillete = caja_billetes.idbillete) as billete, 
      sum(cantidad) as cantidad, sum(subtotal) as total 
      FROM caja_billetes 
      where 
      estado <> 6 
      and idcaja = $idcaja
       group by idbillete 
       order by (select valor from gest_billetes where idbillete = caja_billetes.idbillete) desc
      ";
$rsarq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>

<br />
    <strong>Arqueo de billetes</strong>
    <strong>(Moneda Nacional):</strong><br />
    <div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
          <th><strong>Billete</strong></th>
          <th>Cantidad</th>
          <th>Total</th>
        </tr>
      </thead>
      <thead>
      </thead>
      <tbody>
        <?php
  $cantacum = 0;
$totaacum = 0;
while (!$rsarq->EOF) {
    $cantacum += $rsarq->fields['cantidad'];
    $totaacum += $rsarq->fields['total'];
    ?>
        <tr>
          <td align="center"><?php echo formatomoneda($rsarq->fields['billete']); ?></td>
          <td align="center"><?php echo formatomoneda($rsarq->fields['cantidad']); ?></td>
          <td align="right"><?php echo formatomoneda($rsarq->fields['total']); ?></td>
        </tr>
        <?php $rsarq->MoveNext();
} ?>
        </tbody>
        <tfoot>
        <tr>
          <td>Total</td>
          <td align="center"><?php echo formatomoneda($cantacum); ?></td>
          <td align="right"><?php echo formatomoneda($totaacum); ?></td>
        </tr>
      </tfoot>
      </table>
      </div>
    <p>&nbsp;</p>
      <?php
      $consulta = "
      Select descripcion as billete,cantidad,subtotal as total,sermone 
      from caja_moneda_extra 
        inner join tipo_moneda on tipo_moneda.idtipo=caja_moneda_extra.moneda 
        where 
        idcaja=$idcaja 
        and caja_moneda_extra.estado=1
      ";
//echo $consulta;
$rsarq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
      <strong>Arqueo de billetes (Moneda Extranjera):</strong><br />
    <div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
          <th><strong>Tipo Moneda</strong></th>
          <th>Cantidad</th>
          <th>Total</th>
        </tr>
      </thead>
      <thead>
      </thead>
      <tbody>
        <?php
  $cantacum = 0;
$totaacum = 0;
while (!$rsarq->EOF) {
    $cantacum += $rsarq->fields['cantidad'];
    $totaacum += $rsarq->fields['total'];
    ?>
        <tr>
          <td align="center"><?php echo $rsarq->fields['billete']; ?></td>
          <td align="center"><?php echo formatomoneda($rsarq->fields['cantidad']); ?></td>
          <td align="right"><?php echo formatomoneda($rsarq->fields['total']); ?></td>
        </tr>
        <?php $rsarq->MoveNext();
} ?>
        </tbody>
        <tfoot>
        <tr>
          <td>Total</td>
          <td align="right"><?php echo formatomoneda($cantacum); ?></td>
          <td align="right"><?php echo formatomoneda($totaacum); ?></td>
        </tr>
      
      </tfoot>
      </table>
      </div>
    <p>&nbsp;</p>
<?php
// si es una caja de combustibles
if ($idnumeradorcab > 0) {

    $consulta = "
    select * ,
    (select expendedor_numero from combustibles_expendedor where combustibles_expendedor.idexpendedor=combustibles_picos.idexpendedor) as expnum,
    (select combustible from combustibles where idcombustible=combustibles_picos.idcombustible) as combu,
    combustibles_picos.pico_numero,combustibles_picos.numerador,combustibles_numeradores.idpico,combustibles_numeradores.idexpendedor,combustibles_picos.isla,combustibles_picos.idcara as cara,combustibles_numeradores.numerador_final, combustibles_numeradores.numerador_inicio
    from combustibles_numeradores
    inner join combustibles_picos on combustibles_picos.idpico = combustibles_numeradores.idpico
    where
    idnumeradorcab=$idnumeradorcab
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    ?>
<strong>Numeradores por Picos (Tanda Nro. <?php  echo formatomoneda($idnumeradorcab); ?>):</strong><br />
<div class="table-responsive">
                    
                    
                    
                        <table width="100%" class="table table-bordered jambo_table bulk_action">
                          <thead>
                            <tr>
                                
                                
                                <th align="center">Expendedor</th>
                                <th align="center">Isla</th>
                                <th align="center">Cara</th>
                                <th align="center">Pico Num</th>
                                <th align="center">Combustible</th>
                                <th align="center">Numerador inicial</th>
                                <th align="center">Numerador Final</th>
                                <th align="center">Diferencia</th>

                                
                                
                            </tr>
                          </thead>
                          <tbody>
                    <?php
                        $i = 0;
    while (!$rs->EOF) {
        $i = $i + 1;
        $reg = intval($rs->fields['reg']);

        $diferencia = $rs->fields['numerador_inicio'] - $rs->fields['numerador_final'];


        ?>
                            <tr>
                                
                                <td align="center"><?php echo antixss($rs->fields['expnum']); ?></td>
                                <td align="center"><?php echo intval($rs->fields['isla']); ?></td>
                                <td align="center"><?php echo intval($rs->fields['cara']); ?></td>
                                <td align="center"><?php echo intval($rs->fields['pico_numero']); ?></td>
                                <td align="center"><?php echo antixss($rs->fields['combu']); ?></td>
                                <td align="center"><?php echo formatomoneda($rs->fields['numerador_inicio'], 4, 'N'); ?>
                                </td>
                                <td align="center"><?php echo formatomoneda($rs->fields['numerador_final'], 4, 'N'); ?>
                                </td>
                                <td align="center"><?php echo formatomoneda($diferencia, 4, 'N'); ?></td>
        
                                
                                
                            </tr>
                    <?php $rs->MoveNext();
    } //$rs->MoveFirst();?>

                          </tbody>
                        </table>
                    </div>          
                  
<?php } ?>
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
