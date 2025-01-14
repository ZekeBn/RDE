 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "130";
require_once("includes/rsusuario.php");


$consulta = "
select *,
(select usuario from usuarios where gest_depositos_ajustes_stock.registrado_por = usuarios.idusu) as registrado_por,
(select descripcion from gest_depositos where iddeposito = gest_depositos_ajustes_stock.iddeposito) as deposito,
(select motivo from motivos_ajuste where idmotivo = gest_depositos_ajustes_stock.idmotivo) as motivo_ajuste
from gest_depositos_ajustes_stock 
where 
 estado = 'A'
order by idajuste asc
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
                    <h2>Ajustar Stock</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



<p><a href="ajustar_stock_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Idajuste</th>

            <th align="center">Deposito</th>

            <th align="center">Motivo</th>
            <th align="center">Registrado el</th>
            <th align="center">Registrado por</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="ajustar_stock_det.php?id=<?php echo $rs->fields['idajuste']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
                    <a href="ajustar_stock_edit.php?id=<?php echo $rs->fields['idajuste']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <a href="ajustar_stock_del.php?id=<?php echo $rs->fields['idajuste']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>

            </td>
            <td align="center"><?php echo intval($rs->fields['idajuste']); ?></td>

            <td align="center"><?php echo antixss($rs->fields['deposito']); ?> [<?php echo intval($rs->fields['iddeposito']); ?>]</td>

            <td align="center"><?php echo antixss($rs->fields['motivo_ajuste']); ?> [<?php echo antixss($rs->fields['idmotivo']); ?>]</td>
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
