 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "28";
$submodulo = "335";
require_once("includes/rsusuario.php");

$consulta = "
select menu_online 
from empresas 
order by idempresa asc
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
                    <h2>Menu Digital</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



QR para que el cliente vea La Carta del local en su propio telefono:
<br />
<p align="left">
<img src="qr_menudigital.php" height="200" style="margin:5px;"   alt="QR Menu Digital" title="QR Menu Digital" />
</p>
<strong>URL Seleccionada:</strong> <a href="<?php echo antixss($rs->fields['menu_online']); ?>" target="_blank"><?php echo antixss($rs->fields['menu_online']); ?></a> <br />
<br />
<a href="menu_digital_conf_edit.php" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span> Editar URL</a>
<a href="qr_menudigital.php?desc=s" class="btn btn-sm btn-default" title="Descargar" data-toggle="tooltip" data-placement="right"  data-original-title="Descargar"><span class="fa fa-download"></span> Descargar</a>
<hr />
Ejemplo:<br /><br />
<img src="img/qr_menu.jpg" class="img-thumbnail" /><br /><br /><br />


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
