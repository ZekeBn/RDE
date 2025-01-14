 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
$pag = "index";
require_once("includes/rsusuario.php");






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
                    <h2>Decreto Nº 3881</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

El 1 de Agosto 2020 entra en Vigencia el Decreto 3881.<br />
<br />
Te recomendamos que llames a tu contador para averiguar si tu Empresa esta registrada en la SET con alguno de los codigos de actividad mencionados en el decreto Nro 3881 y si aplica a todos los productos/servicios que comercializas.<br />
<br />
De ser asi puedes llamar a nuestro contact center y agendar un soporte para ayudarte en la actualizacion de los parametros de tu sistema al nuevo regimen especial que beneficia a:<br />
Hoteles, Restaurantes, Abastecimiento de Eventos, Paquetes Turisticos, Arrendamiento de Inmuebles, etc.<br />

<br /><br />
Mas Info: <a href="https://www.presidencia.gov.py/archivos/documentos/DECRETO3881_s51otx6f.PDF" target="_blank"  class="btn btn-sm btn-default"><span class="fa fa-file-pdf-o"></span> Decreto 3881</a>

<br /><br />
<br />
Estamos para servirte!<br />
<br />
Equipo de <?php echo $rsco->fields['nombre_sys']; ?><br />

<br /><br /><br /><br />
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
