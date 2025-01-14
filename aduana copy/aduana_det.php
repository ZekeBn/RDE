<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "42";
$submodulo = "578";
$dirsup = "S";
require_once("../includes/rsusuario.php");



$idaduana = intval($_GET['id']);
if ($idaduana == 0) {
    header("location: aduana.php");
    exit;
}

// consulta a la tabla
$consulta = "SELECT aduana.idaduana,aduana.descripcion, pa.nombre as pais, dp.descripcion as departamento, cd.nombre as ciudad, us.usuario as registrado_por, aduana.registrado_el,(SELECT usuario from usuarios where aduana.borrado_por = usuarios.idusu) as borrado_por 
FROM aduana 
INNER JOIN paises_propio as pa 
ON aduana.idpais = pa.idpais
INNER JOIN departamentos_propio as dp
ON aduana.idpto = dp.iddepartamento
INNER JOIN ciudades_propio as cd
ON aduana.idciudad = cd.idciudad
INNER JOIN usuarios as us
ON aduana.registrado_por = us.idusu
WHERE idaduana = $idaduana
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// echo json_encode($rs->fields['pais']);exit;
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
                    <h2>Datos Aduana</h2>
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
			<th align="center">Descricripcion</th>
			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
		</tr>
		<tr>
			<th align="center">Pais</th>
			<td align="center"><?php echo antixss($rs->fields['pais']); ?></td>
		</tr>
		
		<tr>
			<th align="center">Departamento</th>
			<td align="center"><?php echo antixss($rs->fields['departamento']); ?></td>
		</tr>
		<tr>
			<th align="center">Ciudad</th>
			<td align="center"><?php echo antixss($rs->fields['ciudad']); ?></td>
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
		<tr>
			<th align="center">Borrado por</th>
			<td align="center"><?php echo antixss($rs->fields['borrado_por']); ?></td>
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
