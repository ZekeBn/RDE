 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "342";
require_once("includes/rsusuario.php");


$consulta = "
select *
from sucursales 
where 
 estado = 1 
order by idsucu asc
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
                    <h2>Sucursales</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">




<p><a href="sucursales_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>


</p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Codigo</th>
            <th align="center">Sucursal</th>
            <th align="center">Fantasia Factura</th>
            <th align="center">Direccion</th>
            <th align="center">Telefono</th>
            <th align="center">Clave Wifi</th>
            <th align="center">Casa Matriz</th>
        </tr>
      </thead>
      <tbody>
<?php
$i = 0;
while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="sucursales_edit.php?id=<?php echo $rs->fields['idsucu']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <a href="sucursales_del.php?id=<?php echo $rs->fields['idsucu']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>

            </td>
            <td align="right"><?php echo formatomoneda($rs->fields['idsucu']);  ?></td>
            <td align="center"><?php echo antixss($rs->fields['nombre']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['fantasia_sucursal']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['direccion']); ?></td>

            <td align="center"><?php echo antixss($rs->fields['telefono']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['wifi']); ?></td>
            <td align="center"><?php echo siono($rs->fields['matriz']); ?></td>
        </tr>
<?php $i++;
    $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />
<strong>Total Sucursales: </strong> <?php echo formatomoneda($i); ?>

<br /><br /><br />
                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
 <?php
 $consulta = "
 select *
 from sucursales 
 where 
  estado = 6
 order by idsucu asc
 ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>     
  <!-- SECCION -->
  <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Sucursales Desactivadas</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Codigo</th>
            <th align="center">Sucursal</th>
            <th align="center">Fantasia Factura</th>
            <th align="center">Direccion</th>
            <th align="center">Telefono</th>
            <th align="center">Clave Wifi</th>
            <th align="center">Casa Matriz</th>
        </tr>
      </thead>
      <tbody>
<?php
$i = 0;
while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    
        <a href="sucursales_res.php?id=<?php echo $rs->fields['idsucu']; ?>" class="btn btn-sm btn-default" title="Restaurar" data-toggle="tooltip" data-placement="right"  data-original-title="Restaurar"><span class="fa fa-recycle"></span></a>
            
                </div>

            </td>
            <td align="right"><?php echo formatomoneda($rs->fields['idsucu']);  ?></td>
            <td align="center"><?php echo antixss($rs->fields['nombre']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['fantasia_sucursal']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['direccion']); ?></td>

            <td align="center"><?php echo antixss($rs->fields['telefono']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['wifi']); ?></td>
            <td align="center"><?php echo siono($rs->fields['matriz']); ?></td>
        </tr>
<?php $i++;
    $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />
<strong>Total Sucursales: </strong> <?php echo formatomoneda($i); ?>

<br /><br /><br />
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
