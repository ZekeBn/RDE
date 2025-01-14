<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");



$idciudad = intval($_GET['id']);
if ($idciudad == 0) {
    header("location: ciudades.php");
    exit;
}

$consulta = "
SELECT ciudades_propio.idciudad, ciudades_propio.iddistrito, ciudades_propio.idpais, ciudades_propio.nombre, ciudades_propio.estado, ciudades_propio.registrado_el, ciudades_propio.anulado_por, ciudades_propio.anulado_el, distrito_propio.distrito, paises_propio.nombre as pais,(select usuario from usuarios where ciudades_propio.registrado_por = usuarios.idusu) as registrado_por FROM ciudades_propio
LEFT JOIN distrito_propio ON distrito_propio.iddistrito = ciudades_propio.iddistrito
LEFT JOIN departamentos_propio ON departamentos_propio.iddepartamento = distrito_propio.iddepartamento
LEFT JOIN paises_propio ON paises_propio.idpais = departamentos_propio.idpais
WHERE 
ciudades_propio.idciudad = $idciudad
and ciudades_propio.estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idciudad = intval($rs->fields['idciudad']);
if ($idciudad == 0) {
    header("location: ciudades.php");
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
                    <h2>Borrar Ciudad</h2>
                    <ul class="nav navbar-right panel_toolbox">
						<li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
					</li>
				</ul>
				<div class="clearfix"></div>
			</div>
			<div class="x_content">
				

	

   


			<p><a href="ciudades.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">

		<tr>
			<th align="center">Idciudad</th>
			<td align="center"><?php echo antixss($rs->fields['idciudad']); ?></td>
		</tr>
		<tr>
			<th align="center">Distrito</th>
			<td align="center"><?php echo antixss($rs->fields['distrito']); ?></td>
		</tr>
		<tr>
			<th align="center">Pais</th>
			<td align="center"><?php echo antixss($rs->fields['pais']); ?></td>
		</tr>
		<tr>
			<th align="center">Nombre</th>
			<td align="center"><?php echo antixss($rs->fields['nombre']); ?></td>
		</tr>
		
		<tr>
			<th align="center">Registrado por</th>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
		</tr>
		<tr>
			<th align="center">Registrado el</th>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
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
