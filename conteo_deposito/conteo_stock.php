<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");

$consulta = "
select *
from gest_depositos
where
estado <> 6
order by descripcion desc
limit 100
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			    <?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Conteo de Stock en Depositos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<a href="../deposito/gest_adm_depositos.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
<hr> 
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
    <tr>
      <th>Accion</th>
      <th>Deposito</th>
      </tr>
      </thead>
      <tbody>
<?php
$i = 1;
while (!$rs->EOF) { ?>
    <tr>
      <td>
<?php
$iddeposito = $rs->fields['iddeposito'];

    $mostrarbtn = "S";
    $link = "conteo_stock_detalle.php?id=".$iddeposito;
    $txtbtn = "Abrir";
    $iconbtn = "search";
    $tipoboton = "default";
    if ($mostrarbtn == 'S') {
        ?>
				<div class="btn-group">
					<a href="<?php echo $link; ?>" class="btn btn-sm btn-<?php echo $tipoboton; ?>" title="<?php echo $txtbtn; ?>" data-toggle="tooltip" data-placement="right"  data-original-title="<?php echo $txtbtn; ?>"><span class="fa fa-<?php echo $iconbtn; ?>"></span> <?php echo $txtbtn; ?></a>
				</div>
<?php } ?></td>
    
      <td align="center"><?php echo $rs->fields['descripcion']; ?></td>
      
  </tr>
<?php $i++;
    $rs->MoveNext();
} ?>
  </tbody>
</table>
</div>
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
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
