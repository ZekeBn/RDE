<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
// TODO:PREGUNTAR MODULO SI AGREGAR NOMAS
$modulo = "42";
$submodulo = "615";

$dirsup = "S";
require_once("../includes/rsusuario.php");




$consulta = "
select *,
(select usuario from usuarios where vehiculo.registrado_por = usuarios.idusu) as registrado_por,
(select nombre from vehiculo_propietario where vehiculo.idvehiculo_propietario = vehiculo_propietario.idpropietario ) as propietario,
(select marca from marca where vehiculo.idmarca = marca.idmarca ) as marca
from vehiculo 
where 
 estado = 1 
order by idvehiculo asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




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
                    <h2>Vehiculos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

 

                  <p><a href="vehiculo_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idvehiculo</th>
			<th align="center">Marca</th>
			<th align="center">Modelo</th>
			<th align="center">Chapa</th>
			<th align="center">Nro motor</th>
			<th align="center">Capacidad kg</th>
			<th align="center">Capacidad volumen m3</th>
			<th align="center">Propietario</th>
			<th align="center">Registrado por</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="vehiculo_det.php?id=<?php echo $rs->fields['idvehiculo']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="vehiculo_edit.php?id=<?php echo $rs->fields['idvehiculo']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="vehiculo_del.php?id=<?php echo $rs->fields['idvehiculo']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idvehiculo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['marca']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['modelo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['chapa']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nro_motor']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['capacidad_kg']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['capacidad_volumen_m3']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['propietario']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
		</tr>
<?php
$capacidad_kg_acum += $rs->fields['capacidad_kg'];
    $capacidad_volumen_m3_acum += $rs->fields['capacidad_volumen_m3'];

    $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
	  
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
