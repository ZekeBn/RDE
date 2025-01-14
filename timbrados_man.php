<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "224";
require_once("includes/rsusuario.php");

// desactivar facturas de timbrados inactivos
$consulta = "
update facturas 
set
estado = 'I'
where
estado = 'A'
and idtimbrado not in (select idtimbrado from timbrado where estado = 1)
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
              <!--<div class="title_left">
                <h3>Plain Page</h3>
              </div>-->

              <!--<div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for...">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button">Go!</button>
                    </span>
                  </div>
                </div>
              </div>-->
            </div>

            <div class="clearfix"></div>
			<?php require_once("includes/lic_gen.php");?>
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Manual de Timbrados</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <!--<li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>-->
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<p>
<a href="timbrados.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>

</p>
<hr />
<strong>Si es un Nuevo Timbrado:</strong><br />
(Puede hacerlo remotamente desde cualquier PC o celular)<br />
<br />
- Paso 1:<br />
Crear el timbrado con los datos Numero, Inicio de Vigencia y Fin de Vigencia, el fin de vigencia debe ser superior a hoy para que permita facturar.<br />
- Paso 2:<br />
Entrar a ese timbrado y crear los diferentes puntos de expedicion tanto para facturas como para notas de credito.<br />
Con los datos: Numero Desde, Numero Hasta, Factura Sucursal, Factura Punto expedicion.<br />
- Paso 3:<br />
Borrar el timbrado vencido.<br />

<hr />
<strong>Si ya existe el timbrado:</strong><br />
(Puede hacerlo remotamente desde cualquier PC o celular)<br />
Si el timbrado sigue vigente y existe en el sistema pero se acabo la numeracion para alguno de los puntos de expedicion.<br />
<br />
- Paso 1:<br />
Entrar al timbrado con el boton de la LUPA.<br />
- Paso 2: <br />
Editar el punto de expedicion donde se acabo la numeracion, editar solo el campo Numero Hasta.<br />

<hr />
<strong>En ambos casos: </strong><br />
(Solo si cambio la sucursal o punto de expedicion, no se puede realizar remotamente):<br />
(Se debe hacer solamente desde la PC asignada a ese punto de expedicion)<br /><br />
- Paso 1: <br />
Enrar a Gestion > Asignar pc a sucursal (Debe hacerlo desde la PC que imprime la factura)<br />

- Paso 2: <br />
Poner al sucursal y punto de expedicion a la cual estara asignada esa computadora.<br />

- Paso 3: <br />
Entrar a Contabilidad > Proxima Factura y poner el proximo numero a ser impreso, fijarse que debe ser superior al numero desde e inferior al numero hasta.

<br /><br /><br /><br /><br />
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
