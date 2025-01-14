<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "30";
$submodulo = "632";
$dirsup = "S";
require_once("../includes/rsusuario.php");



$consulta = "
select  mesas.numero_mesa, mesas.idmesa, salon.nombre as salon
from mesas 
inner join salon on salon.idsalon = mesas.idsalon
where 
 estadoex = 1 
order by mesas.numero_mesa asc, salon.nombre asc
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
                    <h2>QR Mesas Smart</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p><a href="mesas_smart.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
	

			<th align="center">Mesa</th>
			<th align="center">Salon</th>
			<th align="center">QR</th>
			<th align="center">Descargar</th>

		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>

			<td align="center">Mesa <?php echo intval($rs->fields['numero_mesa']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['salon']); ?></td>
			<td align="center"><img src="qr_mesas_img.php?id=<?php echo intval($rs->fields['idmesa']); ?>" height="200" style="margin:5px;"   alt="QR Mesas Smart" title="QR Mesas Smart" /></td>
			<td align="center"><a href="qr_mesas_img.php?id=<?php echo intval($rs->fields['idmesa']); ?>&desc=s" class="btn btn-sm btn-default" title="Descargar" data-toggle="tooltip" data-placement="right"  data-original-title="Descargar"><span class="fa fa-download"></span> Descargar</a></td>
		</tr>
<?php

$rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>

    </table>
</div>
<br />
<br />  
					  

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
           
            
            
            
          </div>
        </div>
        <!-- /page content -->
		  
        <!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
            	<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
           		<h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
            	Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
            	<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        
        </div>
    </div>
</div>
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
