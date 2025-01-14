 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "670";
require_once("includes/rsusuario.php");


$idventa = intval($_GET['id']);
$rendir = trim($_GET['rendir']);


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
and ventas.idventa = $idventa
and mesas.estado_mesa = 8
order by ventas.fecha asc, mesas.numero_mesa asc, salon.nombre asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idventa = intval($rs->fields['idventa']);
$idmesa = intval($rs->fields['idmesa']);
if ($idventa == 0) {
    header('location: ventas_rendido.php');
    exit;
}




if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots

    // BUSCA SI ya no esta Rendido
    $consulta = "
    select idventarendido from ventas_rendido where idventa = $idventa and estado = 1
    ";
    $rsrend = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idventarendido = intval($rsrend->fields['idventarendido']);
    if ($idventarendido > 0) {
        $errores .= "- No se puede rendir por que ya fue rendido anteriormente.<br />";
        $valido = "N";
    }

    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
        INSERT INTO `ventas_rendido`
        (`idventa`, `estado`, `registrado_por`, `registrado_el`, `anulado_por`, `anulado_el`) 
        VALUES 
        ($idventa,1,$idusu,'$ahora',NULL,NULL)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // cambiar estado de la Mesa
        $consulta = "
        update mesas 
        set 
        estado_mesa = 1
        where
        idmesa = $idmesa
        and estado_mesa = 8
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: ventas_rendido.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());



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



<p><a href="ventas_rendido.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>

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
<?php

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

      </tbody>

    </table>
</div>
<br />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>


            <th align="center">Forma Pago</th>
            <th align="center">Monto</th>

        </tr>
      </thead>
      <tbody>
<?php

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
while (!$rspag->EOF) {
    ?>
        <tr>

            <td align="center"><?php   echo antixss($rspag->fields['medio_pago']);  ?></td>
            <td align="center"><?php echo formatomoneda($rspag->fields['monto_pago_det']);  ?></td>

        </tr>
<?php $rspag->MoveNext();
} ?>
      </tbody>

    </table>
</div>

<strong>Monto Total:</strong> <?php echo formatomoneda($rs->fields['monto_venta']); ?><br /><br />

<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">

<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">


<p align="center"><strong>Marcar como Rendido?</strong></p><br />

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='ventas_rendido.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />


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
