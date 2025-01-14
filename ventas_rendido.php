 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "670";
require_once("includes/rsusuario.php");

/*
rellenar masivamente la primera vez
INSERT INTO `ventas_rendido`
( `idventa`, `estado`, `registrado_por`, `registrado_el`, `anulado_por`, `anulado_el`)
select idventa, 1, 0, NOW(), NULL, NULL
from ventas
*/
//Comprobar apertura de caja en fecha establecida
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal and tipocaja = 1 order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);
$idcaja_compartida = intval($rscaja->fields['idcaja_compartida']);
if ($idcaja_compartida > 0) {
    echo "- Tu caja es compartida, no puede usar esta funcion, deben hacerlo desde la caja principal.";
    exit;
}
if ($idcaja == 0) {
    echo "- La caja debe estar abierta para poder realizar una venta.";
    exit;
}
if ($estadocaja == 3) {
    echo "- La caja debe estar abierta para poder realizar una venta.";
    exit;
}


$consulta = "
select ventas.idventa, ventas.factura, ventas.idcaja, ventas.fecha, usuarios.usuario, ventas.totalcobrar as monto_venta,
mesas.numero_mesa, mesas.idmesa, salon.nombre as salon, mesas_atc.idatc, mesas_atc.pin, sucursales.nombre as sucursal, salon.idsalon, sucursales.idsucu,
mesas_estados_lista.descripcion as estado_mesa
from ventas
inner join mesas_atc on mesas_atc.idatc = ventas.idatc
inner join mesas on mesas.idmesa = mesas_atc.idmesa
inner join salon on salon.idsalon = mesas.idsalon
inner join sucursales on sucursales.idsucu = mesas_atc.idsucursal 
inner join mesas_estados_lista on mesas_estados_lista.idestadomesa = mesas.estado_mesa
inner join usuarios on usuarios.idusu = ventas.registrado_por
where
ventas.estado <> 6
and ventas.sucursal = $idsucursal
and mesas_atc.estado = 3
and ventas.idventa not in (select idventa from ventas_rendido where ventas_rendido.idventa = ventas.idventa and estado = 1)
and ventas.idcaja = $idcaja
and mesas.estado_mesa = 8
order by ventas.fecha asc, mesas.numero_mesa asc, salon.nombre asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
select  mesas.numero_mesa, mesas.idmesa, salon.nombre as salon, mesas_atc.idatc, mesas_atc.pin, sucursales.nombre as sucursal, salon.idsalon, sucursales.idsucu,
mesas_estados_lista.descripcion as estado_mesa
from mesas 
inner join salon on salon.idsalon = mesas.idsalon
inner join mesas_atc on mesas_atc.idmesa = mesas.idmesa
inner join sucursales on sucursales.idsucu = mesas_atc.idsucursal 
inner join mesas_estados_lista on mesas_estados_lista.idestadomesa = mesas.estado_mesa
where 
estadoex = 1 
and sucursales.idsucu = $idsucursal    
and mesas_atc.estado = 1
order by mesas.numero_mesa asc, salon.nombre asc
";
$rsmesa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
select rendir_mesa
from mesas_preferencias
";
$rsprefmesa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$rendir_mesa = $rsprefmesa->fields['rendir_mesa'];

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
                    <h2>Rendir Ventas por Mesa</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<?php if ($rendir_mesa != 'S') {?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<strong>PREFERENCIA NO ACTIVA:</strong><br /> Activar en: gestion > preferencias mesas > rendir mesa
</div>
<?php } else { ?>

<a href="ventas_rendido.php?nc=<?php echo date("YmdHis"); ?>" class="btn btn-sm btn-default"><span class="fa fa-refresh"></span> Actualizar</a>
<a href="ventas_rendido_rendir_mas.php?nc=<?php echo date("YmdHis"); ?>" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Rendir todo Masivamente</a>
<hr />
<strong>Ventas de Mesa sin Rendir de mi Caja:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Mesa Numero</th>
            <th align="center">Salon</th>
            <th align="center">Idventa</th>
            <th align="center">Factura</th>
            <th align="center">Fecha Hora</th>
            <th align="center">Usuario</th>
            <th align="center">Sucursal</th>
            <th align="center">Monto Venta</th>
            <th align="center">Pagos</th>
            <th align="center">Idmesa</th>
            <th align="center">Caja</th>
            <th align="center">Idatc</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) {

    $idventa = $rs->fields['idventa'];
    $consulta = "
select gest_pagos_det.monto_pago_det, formas_pago.descripcion  as medio_pago
from gest_pagos 
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
where 
 gest_pagos.estado = 1 
 and gest_pagos.idventa = $idventa
 group by formas_pago.descripcion
 order by formas_pago.descripcion asc
";
    $rspag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    ?>
        <tr>
            <td <?php if (intval($rs->fields['rendido']) > 0) { ?> style="background-color:green; color:white;" <?php } else { ?> style="background-color:gray; color:white;" <?php } ?>>
                <div class="btn-group">
                <?php if (intval($rs->fields['rendido']) > 0) { ?>
                    <a href="ventas_rendido_rendir.php?id=<?php echo $rs->fields['idventa']; ?>&rend=n" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-trash-o"></span> Desmarcar</a>
                <div class="clearfix"></div><br />
                RENDIDO
                <?php } else { ?>
                <a href="ventas_rendido_rendir.php?id=<?php echo $rs->fields['idventa']; ?>&rend=s" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-check-square-o"></span> Rendir</a>    
                <div class="clearfix"></div><br />
                PENDIENTE RENDICION
                <?php } ?>
                </div>
            </td>
            <td align="center"><?php echo antixss($rs->fields['numero_mesa']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['salon']); ?> <?php echo intval($rs->fields['idsalon']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['idventa']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['factura']); ?></td>
            <td align="center"><?php if (trim($rs->fields['fecha']) != '') {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha']));
            } ?></td>
            <td align="center"><?php echo antixss($rs->fields['usuario']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['monto_venta']); ?></td>
            <td align="center"><?php while (!$rspag->EOF) {
                echo antixss($rspag->fields['medio_pago']);  ?>: <?php echo formatomoneda($rspag->fields['monto_pago_det']);
                echo "<br />";
                $rspag->MoveNext();
            }  ?></td>
            <td align="center"><?php echo antixss($rs->fields['idmesa']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['idcaja']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['idatc']); ?></td>
        </tr>
<?php

$rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>

    </table>
</div>
<br />


<hr />
<strong>Mesas Abiertas:</strong>


<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>

            <th align="center">Mesa Numero</th>
            <th align="center">Salon</th>
            <th align="center">Sucursal</th>
            <th align="center">Estado</th>
            <th align="center">Idmesa</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rsmesa->EOF) { ?>
        <tr>
                <td align="center"><?php echo antixss($rsmesa->fields['numero_mesa']); ?></td>
                <td align="center"><?php echo antixss($rsmesa->fields['salon']); ?> <?php echo intval($rsmesa->fields['idsalon']); ?></td>
                <td align="center"><?php echo antixss($rsmesa->fields['sucursal']); ?></td>
                <td align="center"><?php echo antixss($rsmesa->fields['estado_mesa']); ?></td>
                <td align="center"><?php echo antixss($rsmesa->fields['idmesa']); ?></td>
        </tr>
<?php

$rsmesa->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>

    </table>
</div>
<br /><hr />
<?php
$consulta = "
select ventas.idventa, ventas.factura, ventas.idcaja, ventas.fecha, usuarios.usuario, ventas.totalcobrar as monto_venta,
mesas.numero_mesa, mesas.idmesa, salon.nombre as salon, mesas_atc.idatc, mesas_atc.pin, sucursales.nombre as sucursal, salon.idsalon, sucursales.idsucu,
mesas_estados_lista.descripcion as estado_mesa,
(select usuario from usuarios where idusu = ventas_rendido.registrado_por) as rendido_por, ventas_rendido.registrado_el as rendido_el
from ventas
inner join mesas_atc on mesas_atc.idatc = ventas.idatc
inner join mesas on mesas.idmesa = mesas_atc.idmesa
inner join salon on salon.idsalon = mesas.idsalon
inner join sucursales on sucursales.idsucu = mesas_atc.idsucursal 
inner join mesas_estados_lista on mesas_estados_lista.idestadomesa = mesas.estado_mesa
inner join usuarios on usuarios.idusu = ventas.registrado_por
inner join ventas_rendido on ventas_rendido.idventa =  ventas.idventa
where
ventas.estado <> 6
and ventas_rendido.estado = 1 
and ventas.sucursal = $idsucursal
and mesas_atc.estado = 3
and ventas.idcaja = $idcaja
order by ventas_rendido.idventarendido desc
limit 10
";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>


<strong>Ultimas 10 ventas rendidas:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th>Rendido por</th>
            <th align="center">Mesa Numero</th>
            <th align="center">Salon</th>
            <th align="center">Idventa</th>
            <th align="center">Factura</th>
            <th align="center">Fecha Hora</th>
            <th align="center">Usuario</th>
            <th align="center">Sucursal</th>
            <th align="center">Monto Venta</th>
            <th align="center">Pagos</th>
            <th align="center">Idmesa</th>
            <th align="center">Caja</th>
            <th align="center">Idatc</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) {

    $idventa = $rs->fields['idventa'];
    $consulta = "
select gest_pagos_det.monto_pago_det, formas_pago.descripcion  as medio_pago
from gest_pagos 
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
where 
 gest_pagos.estado = 1 
 and gest_pagos.idventa = $idventa
 group by formas_pago.descripcion
 order by formas_pago.descripcion asc
";
    $rspag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    ?>
        <tr>
            <td >
            <?php echo antixss($rs->fields['rendido_por']); ?><br /><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['rendido_el'])); ?>
            </td>
            <td align="center"><?php echo antixss($rs->fields['numero_mesa']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['salon']); ?> <?php echo intval($rs->fields['idsalon']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['idventa']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['factura']); ?></td>
            <td align="center"><?php if (trim($rs->fields['fecha']) != '') {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha']));
            } ?></td>
            <td align="center"><?php echo antixss($rs->fields['usuario']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['monto_venta']); ?></td>
            <td align="center"><?php while (!$rspag->EOF) {
                echo antixss($rspag->fields['medio_pago']);  ?>: <?php echo formatomoneda($rspag->fields['monto_pago_det']);
                echo "<br />";
                $rspag->MoveNext();
            }  ?></td>
            <td align="center"><?php echo antixss($rs->fields['idmesa']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['idcaja']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['idatc']); ?></td>
        </tr>
<?php

$rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>

    </table>
</div>
<br />

<?php

}  ?>

<div class="clearfix"></div>
<br /><br />


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
