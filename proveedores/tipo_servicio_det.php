<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "24";

require_once("../includes/rsusuario.php");




$idtipo_servicio = intval($_GET['id']);
if ($idtipo_servicio == 0) {
    header("location: tipo_servicio.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * ,
(select usuario from usuarios where tipo_servicio.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where tipo_servicio.actualizado_por = usuarios.idusu) as actualizado_por
from tipo_servicio 
where 
idtipo_servicio = $idtipo_servicio
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtipo_servicio = intval($rs->fields['idtipo_servicio']);
if ($idtipo_servicio == 0) {
    header("location: tipo_servicio.php");
    exit;
}


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
                    <h2>Proveedores</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



 
                  

                  
 
<p><a href="tipo_servicio.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">

		<tr>
			<th align="center">Idtipo servicio</th>
			<td align="center"><?php echo intval($rs->fields['idtipo_servicio']); ?></td>
		</tr>
		<tr>
			<th align="center">Tipo de Servicio</th>
			<td align="center"><?php echo antixss($rs->fields['tipo']); ?></td>
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
			<th align="center">Actualizado por</th>
			<td align="center"><?php echo antixss($rs->fields['actualizado_por']); ?></td>
		</tr>
		<tr>
			<th align="center">Actualizado el</th>
			<td align="center"><?php if ($rs->fields['actualizado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['actualizado_el']));
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

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
