<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
// TODO:PREGUNTAR MODULO SI AGREGAR NOMAS
$modulo = "42";
$submodulo = "615";

$dirsup = "S";
require_once("../includes/rsusuario.php");




$idvehiculo = intval($_GET['id']);
if ($idvehiculo == 0) {
    header("location: vehiculo.php");
    exit;
}

// consulta a la tabla
$consulta = "
select *,
(select usuario from usuarios where vehiculo.registrado_por = usuarios.idusu) as registrado_por,
(select nombre from vehiculo_propietario where vehiculo.idvehiculo_propietario = vehiculo_propietario.idpropietario ) as propietario,
(select marca from marca where vehiculo.idmarca = marca.idmarca ) as marca
from vehiculo 
where 
idvehiculo = $idvehiculo
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idvehiculo = intval($rs->fields['idvehiculo']);
if ($idvehiculo == 0) {
    header("location: vehiculo.php");
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
                    <h2>Detalles Vehiculos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

 




				  <p><a href="vehiculo.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">

		<tr>
			<th align="center">Idvehiculo</th>
			<td align="center"><?php echo intval($rs->fields['idvehiculo']); ?></td>
		</tr>
		
		<tr>
			<th align="center">Nro motor</th>
			<td align="center"><?php echo antixss($rs->fields['nro_motor']); ?></td>
		</tr>
		<tr>
			<th align="center">Capacidad kg</th>
			<td align="center"><?php echo formatomoneda($rs->fields['capacidad_kg']);  ?></td>
		</tr>
		<tr>
			<th align="center">Capacidad volumen m3</th>
			<td align="center"><?php echo formatomoneda($rs->fields['capacidad_volumen_m3']);  ?></td>
		</tr>
		<tr>
			<th align="center">Anho fabricacion</th>
			<td align="center"><?php echo antixss($rs->fields['anho_fabricacion']); ?></td>
		</tr>
		<tr>
			<th align="center">Chapa</th>
			<td align="center"><?php echo antixss($rs->fields['chapa']); ?></td>
		</tr>
		<tr>
			<th align="center">Chasis</th>
			<td align="center"><?php echo antixss($rs->fields['chasis']); ?></td>
		</tr>
		<tr>
			<th align="center">Modelo</th>
			<td align="center"><?php echo antixss($rs->fields['modelo']); ?></td>
		</tr>
		<tr>
			<th align="center">Color</th>
			<td align="center"><?php echo antixss($rs->fields['color']); ?></td>
		</tr>
		<tr>
			<th align="center">Propietario</th>
			<td align="center"><?php echo antixss($rs->fields['propietario']); ?></td>
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
			<th align="center">Marca</th>
			<td align="center"><?php echo antixss($rs->fields['marca']); ?></td>
		</tr>


</table>
 </div>
<br />




<div class="clearfix"></div>

<div class="clearfix"></div>
<br /><br />








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
