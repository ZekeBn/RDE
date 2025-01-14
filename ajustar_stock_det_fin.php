 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "130";
require_once("includes/rsusuario.php");



$idajuste = intval($_GET['id']);
if ($idajuste == 0) {
    header("location: ajustar_stock.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from gest_depositos_ajustes_stock 
where 
idajuste = $idajuste
and estado = 'C'
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idajuste = intval($rs->fields['idajuste']);
if ($idajuste == 0) {
    header("location: ajustar_stock.php");
    exit;
}


$consulta = "
select *,
(select usuario from usuarios where gest_depositos_ajustes_stock.registrado_por = usuarios.idusu) as registrado_por,
(select descripcion from gest_depositos where iddeposito = gest_depositos_ajustes_stock.iddeposito) as deposito,
(select motivo from motivos_ajuste where idmotivo = gest_depositos_ajustes_stock.idmotivo) as motivo_ajuste
from gest_depositos_ajustes_stock 
where 
 estado = 'C'
 and idajuste = $idajuste
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


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
                    <h2>Ajuste realizado</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p><a href="ajustar_stock.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>

            <th align="center">Idajuste</th>

            <th align="center">Deposito</th>

            <th align="center">Motivo</th>
      <th align="center">Fecha Ajuste</th>
            <th align="center">Registrado el</th>
            <th align="center">Registrado por</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>

            <td align="center"><?php echo intval($rs->fields['idajuste']); ?></td>

            <td align="center"><?php echo antixss($rs->fields['deposito']); ?> [<?php echo intval($rs->fields['iddeposito']); ?>]</td>

            <td align="center"><?php echo antixss($rs->fields['motivo_ajuste']); ?> [<?php echo antixss($rs->fields['idmotivo']); ?>]</td>
      <td align="center"><?php if ($rs->fields['fechaajuste'] != "") {
          echo date("d/m/Y", strtotime($rs->fields['fechaajuste']));
      }  ?></td>
            <td align="center"><?php if ($rs->fields['registrado_el'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
            }  ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>

        </tr>
<?php

$rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>

    </table>
</div>
<?php
      $consulta = "
      select *, (select descripcion from insumos_lista where insumos_lista.idinsumo = gest_depositos_ajustes_stock_det.idinsumo) as insumo,
      (select motivo from gest_depositos_ajustes_stock where gest_depositos_ajustes_stock.idajuste = gest_depositos_ajustes_stock_det.idajuste) as motivo_desc,
      (
      select motivos_ajuste.motivo 
      from gest_depositos_ajustes_stock
      inner join motivos_ajuste on motivos_ajuste.idmotivo = gest_depositos_ajustes_stock.idmotivo
       where 
       gest_depositos_ajustes_stock.idajuste = gest_depositos_ajustes_stock_det.idajuste
       ) as motivo,
                  (
                Select sum(disponible) as disponible 
                from gest_depositos_stock_gral
                 where 
                iddeposito=gest_depositos_ajustes_stock_det.iddeposito
                and gest_depositos_stock_gral.idproducto = gest_depositos_ajustes_stock_det.idinsumo
                and idempresa=$idempresa
                ) as disponible
      from gest_depositos_ajustes_stock_det
      where
      idempresa = $idempresa
      and idajuste = $idajuste
      ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>
      </p>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
       <thead>


        <tr>
          <td align="center">Articulo</td>
          <td  align="center" >Cantidad Ajustada</td>
          <td  align="center">Stock Actual</td>
          <td  align="center">Costo Unitario</td>
          <td  align="center">Costo Ajuste</td>
        </tr>
          </thead>
          <tbody>
<?php while (!$rs->EOF) {    ?>
        <tr>
          <td align="left"><?php echo $rs->fields['insumo']; ?> [<?php echo intval($rs->fields['idinsumo']); ?>]</td>
          <td align="right"><?php echo $rs->fields['tipoajuste'].formatomoneda($rs->fields['cantidad_ajuste'], 4, 'N'); ?></td>
          <td align="right"><?php echo formatomoneda($rs->fields['disponible'], 4, 'N'); ?></td>
          <td align="right"><?php echo formatomoneda($rs->fields['precio_costo'], 4, 'N'); ?></td>
          <td align="right"><?php echo formatomoneda($rs->fields['precio_costo'] * $rs->fields['cantidad_ajuste'], 4, 'N'); ?></td>
        </tr>
<?php $rs->MoveNext();
}?>
      </tbody>
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
