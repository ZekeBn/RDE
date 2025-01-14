 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "21";
$submodulo = "414";
require_once("includes/rsusuario.php");




$idtandamas = intval($_GET['id']);
if ($idtandamas == 0) {
    header("location: pedidos_masivo_tanda.php");
    exit;
}

// consulta a la tabla
$consulta = "
select *,
(select usuario from usuarios where pedidos_masivo_tanda.registrado_por = usuarios.idusu) as registrado_por,
(select count(*) from pedidos_masivo_cab where estado = 1 and idtandamas = pedidos_masivo_tanda.idtandamas) as total_pedidos,
(select count(*) from pedidos_masivo_cab inner join pedidos_masivo_det on pedidos_masivo_det.idpedidomas =pedidos_masivo_cab.idpedidomas  where estado = 1 and idtandamas = pedidos_masivo_tanda.idtandamas) as total_productos
from pedidos_masivo_tanda 
where 
 estado = 1 
 and idtandamas = $idtandamas
order by idtandamas desc
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtandamas = intval($rs->fields['idtandamas']);
$registrado_por = intval($rs->fields['registrado_por']);
$idsucursal = intval($rs->fields['idsucursal']);
if ($idtandamas == 0) {
    header("location: pedidos_masivo_tanda.php");
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


    // recibe parametros
    $archivo = antisqlinyeccion($_POST['archivo'], "text");
    $estado = 1;
    //$registrado_por=$idusu;
    //$registrado_el=antisqlinyeccion($ahora,"text");
    //$idsucursal=antisqlinyeccion($_POST['idsucursal'],"int");




    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
        select *, 
        (select razon_social from cliente where idcliente = pedidos_masivo_cab.idcliente) as razon_social_sys
        
        from pedidos_masivo_cab 
        where 
         estado = 1 
         and idtandamas = $idtandamas
        order by idpedidomas asc
        ";
        $rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        while (!$rscab->EOF) {
            $idpedidomas = $rscab->fields['idpedidomas'];
            $fechahora = $rscab->fields['fechahora'];
            $consulta = "
            INSERT INTO tmp_ventares_cab
            (
            razon_social, ruc, chapa, observacion, monto, idusu, fechahora, idsucursal, 
            idempresa,idcanal,delivery,idmesa,
            telefono,delivery_zona,direccion,llevapos,cambio,observacion_delivery,
            delivery_costo,iddomicilio,idclientedel,nombre_deliv,apellido_deliv,idatc,
            idclienteped,idtrans
            ) 
            select 
            (select razon_social from cliente where idcliente = pedidos_masivo_cab.idcliente), ruc, NULL, NULL, 0, $registrado_por, fechahora, $idsucursal,
            1, 1, 'N', 0,
            0, NULL, NULL, 'N', 0, NULL,
            0, NULL, NULL, NULL, NULL, 0,
            idcliente,idpedidomas
            from pedidos_masivo_cab
            where
            estado = 1
            and idpedidomas = $idpedidomas
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // obtiene el insertado
            $consulta = "
            select idtmpventares_cab from tmp_ventares_cab where idtrans = $idpedidomas order by idtmpventares_cab desc limit 1
            ";
            $rsult = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idtmpventares_cab = $rsult->fields['idtmpventares_cab'];

            // registra detalle

            $consulta = "
            INSERT INTO tmp_ventares
            (
            idproducto, idtipoproducto, cantidad, 
            precio, 
            fechahora, usuario, registrado, idsucursal,
             idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,
             subtotal, 
             idtrans, idtmpventares_cab
             ) 
            select 
            idproducto, 1, 1, 
            COALESCE((select precio from productos_sucursales where idproducto = pedidos_masivo_det.idproducto and idsucursal = $idsucursal),0), 
            '$fechahora', $registrado_por, 'N', $idsucursal ,
            1, 'N', 'N', 'N', NULL, NULL, 
            COALESCE((select precio from productos_sucursales where idproducto = pedidos_masivo_det.idproducto and idsucursal = $idsucursal),0), 
            idpedidomasdet, $idtmpventares_cab
            from pedidos_masivo_det
            where
            idpedidomas = $idpedidomas
            order by idpedidomasdet asc
            ;
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // actualiza precios cabecera
            $consulta = "
            update tmp_ventares_cab
            set 
            monto = COALESCE((
                select sum(subtotal) 
                from tmp_ventares 
                where 
                idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
                ),0),
            finalizado = 'S'
            WHERE
            tmp_ventares_cab.idtmpventares_cab = $idtmpventares_cab
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            $rscab->MoveNext();
        }

        // marca como procesado
        $consulta = "
        update pedidos_masivo_tanda
        set
            estado = 2,
            procesado_por=$idusu,
            procesado_el='$ahora'
        where
            idtandamas = $idtandamas
            and estado = 1
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: pedidos_masivo_tanda.php");
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
                    <h2>Carga Masiva de Pedidos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p><a href="pedidos_masivo_tanda.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>

            <th align="center">Idtandamas</th>
            <th align="center">Total Pedidos</th>
            <th align="center">Total Productos</th>
            <th align="center">Total Clientes</th>
            <th align="center">Archivo</th>
            <th align="center">Registrado por</th>
            <th align="center">Registrado el</th>


        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) {

    $idtandamas = $rs->fields['idtandamas'];
    $consulta = "
select count(idcliente) as total_clientes
from (
        select count(idcliente), idcliente 
        from pedidos_masivo_cab
        where 
        estado = 1 
        and idtandamas = $idtandamas 
        group by idcliente
     ) tt 
";
    $rsc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
        <tr>
            <td align="center"><?php echo intval($rs->fields['idtandamas']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['total_pedidos']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['total_productos']); ?></td>
            <td align="center"><?php echo formatomoneda($rsc->fields['total_clientes']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['archivo']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
            <td align="center"><?php if ($rs->fields['registrado_el'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
            }  ?></td>


        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />
<?php

$consulta = "
select *, 
(select razon_social from cliente where idcliente = pedidos_masivo_cab.idcliente) as razon_social_sys,
    (
        SELECT GROUP_CONCAT(productos.descripcion) AS productos 
        from productos 
        inner join pedidos_masivo_det on pedidos_masivo_det.idproducto = productos.idprod_serial
        where 
        pedidos_masivo_det.idpedidomas = pedidos_masivo_cab.idpedidomas
        and pedidos_masivo_cab.estado <> 6
        order by productos.descripcion asc
     ) as productos

from pedidos_masivo_cab 
where 
 estado = 1 
 and idtandamas = $idtandamas
order by idpedidomas asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>

            <th align="center">Idpedidomas</th>
            <th align="center">Fechahora</th>
            <th align="center">Idcliente</th>
            <th align="center">Ruc (Cargado)</th>
            <th align="center">Razon social (Cargado)</th>
            <th align="center">Razon social (Sistema)</th>
            <th align="center">Productos</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>

            <td align="center"><?php echo intval($rs->fields['idpedidomas']); ?></td>
            <td align="center"><?php if ($rs->fields['fechahora'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fechahora']));
            }  ?></td>
            <td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social_sys']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['productos']); ?></td>
        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />




<br />
<?php

$consulta = "
select * 
from 
    (
    SELECT date(fechahora) as fecha, ruc, count(*) as total
     FROM pedidos_masivo_cab
     where
     idtandamas = $idtandamas
     group by date(fechahora), ruc 
     order by count(*) desc
    ) duplic
WHERE 
total > 1
order by total desc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>


            <th align="center">Fechahora</th>
            <th align="center">Ruc (Cargado)</th>
            <th align="center">Total pedidos</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>


            <td align="center"><?php if ($rs->fields['fecha'] != "") {
                echo date("d/m/Y", strtotime($rs->fields['fecha']));
            }  ?></td>
            <td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['total']); ?></td>
        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />









<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">
<div class="clearfix"></div>
<br />
<h2>ESTA ACCION NO SE PUEDE DESHACER, ESTA SEGURO?</h2>
<br /><br />
    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Procesar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='pedidos_masivo_tanda.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
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
