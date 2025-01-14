 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "74";
require_once("includes/rsusuario.php");


$consulta = "
SELECT *, (select count(idmesa) from mesas where idsalon = salon.idsalon) as totalmesas,
(select nombre from sucursales where idsucu = salon.idsucursal) as sucursal
FROM salon
where
estado_salon = 1
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
                    <h2>Salones y Mesas</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



<p><a href="salones_agregar.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Idsalon</th>
            <th align="center">Nombre</th>
            <th align="center">Sucursal</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="salones_mesa.php?salon=<?php echo $rs->fields['idsalon']; ?>" class="btn btn-sm btn-default" title="Mesas" data-toggle="tooltip" data-placement="right"  data-original-title="Mesas"><span class="fa fa-search"></span></a>
                    <a href="salones_editar.php?salon=<?php echo $rs->fields['idsalon']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <a href="salon_del.php?id=<?php echo $rs->fields['idsalon']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>

            </td>
            <td align="center"><?php echo antixss($rs->fields['idsalon']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['nombre']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>


        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />



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
