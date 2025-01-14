<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");



$idaduana = intval($_GET['id']);
if ($idaduana == 0) {
    header("location: aduana.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from aduana 
where 
idaduana = $idaduana
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idaduana = intval($rs->fields['idaduana']);
if ($idaduana == 0) {
    header("location: aduana.php");
    exit;
}



?>
<!DOCTYPE html>
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
                    <h2>Datos Plantilla</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">




				  
<p><a href="aduana.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">

		<tr>
			<th align="center">Idaduana</th>
			<td align="center"><?php echo intval($rs->fields['idaduana']); ?></td>
		</tr>
		<tr>
			<th align="center">Descripcion</th>
			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
		</tr>
		<tr>
			<th align="center">Idpais</th>
			<td align="center"><?php echo intval($rs->fields['idpais']); ?></td>
		</tr>
		<tr>
			<th align="center">Idempresa</th>
			<td align="center"><?php echo intval($rs->fields['idempresa']); ?></td>
		</tr>
		<tr>
			<th align="center">Idpto</th>
			<td align="center"><?php echo intval($rs->fields['idpto']); ?></td>
		</tr>
		<tr>
			<th align="center">Idciudad</th>
			<td align="center"><?php echo intval($rs->fields['idciudad']); ?></td>
		</tr>
		<tr>
			<th align="center">Registrado por</th>
			<td align="center"><?php echo intval($rs->fields['registrado_por']); ?></td>
		</tr>
		<tr>
			<th align="center">Registrado el</th>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
		</tr>
		<tr>
			<th align="center">Borrado por</th>
			<td align="center"><?php echo intval($rs->fields['borrado_por']); ?></td>
		</tr>
		<tr>
			<th align="center">Borrado el</th>
			<td align="center"><?php if ($rs->fields['borrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['borrado_el']));
			}  ?></td>
		</tr>
		<tr>
			<th align="center">Estado</th>
			<td align="right"><?php echo formatomoneda($rs->fields['estado']);  ?></td>
		</tr>


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
