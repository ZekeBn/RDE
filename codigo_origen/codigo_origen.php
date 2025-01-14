<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "42";
$submodulo = "617";

$dirsup = "S";
require_once("../includes/rsusuario.php");



$consulta = "
select proveedores_fob.*, proveedores.nombre as proveedor,
(select usuario from usuarios where proveedores_fob.registrado_por = usuarios.idusu) as registrado_por
from proveedores_fob 
INNER JOIN proveedores on proveedores_fob.idproveedor = proveedores.idproveedor
where 
proveedores_fob.estado = 1 
order by idfob asc
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
                    <h2>Codigo Origen</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

   

                  <p>
                    <a href="codigo_origen_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
                    <a href="codigo_origen_importar.php" class="btn btn-sm btn-default"><span class="fa fa-upload"></span> Carga Masiva</a>
                </p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Proveedor</th>
			<th align="center">Codigo articulo</th>
			<th align="center">Precio</th>
			<th align="center">Fecha</th>
			<th align="center">Registrado el</th>
			<th align="center">Registrado por</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="codigo_origen_edit.php?id=<?php echo $rs->fields['idfob']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="codigo_origen_del.php?id=<?php echo $rs->fields['idfob']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['codigo_articulo']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['precio']);  ?></td>
			<td align="center"><?php if ($rs->fields['fecha'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha']));
			}  ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
		</tr>
<?php
$precio_acum += $rs->fields['precio'];

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
