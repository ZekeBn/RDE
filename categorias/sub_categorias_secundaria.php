<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("./preferencias_categorias.php");

$sub_cat = intval($_GET['sub_cat']);
$cat = null;
$add = "";
$subcate_nombre = "";
if ($sub_cat > 0) {
    $consulta = "
    SELECT idcategoria ,descripcion as subcate FROM `sub_categorias` WHERE idsubcate = $sub_cat
  ";
    $rs_nombre_subcate = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $subcate_nombre = $rs_nombre_subcate -> fields['subcate'];
    $cat = intval($rs_nombre_subcate -> fields['idcategoria']);
    $add = " and sub_categorias_secundaria.idsubcate= $sub_cat ";
}
$consulta = "
select *, sub_categorias_secundaria.margen_seguridad,sub_categorias_secundaria.descripcion as nombre_sub_cate_sec, categorias.nombre as categoria, sub_categorias.descripcion as subcategoria,
(select usuario from usuarios where sub_categorias_secundaria.registrado_por = usuarios.idusu) as registrado_por
from sub_categorias_secundaria 
INNER JOIN sub_categorias on sub_categorias.idsubcate = sub_categorias_secundaria.idsubcate
INNER JOIN categorias on categorias.id_categoria = sub_categorias.idcategoria
where 
sub_categorias_secundaria.estado = 1 
$add
order by idsubcate_sec asc
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
                    <h2>Sub Categoria Secundaria<?php if ($sub_cat > 0) {
                        echo ": ".$subcate_nombre;
                    }?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

   
                  

                  
<p><a href="sub_categorias_secundaria_add.php<?php if ($sub_cat > 0) {
    echo "?sub_cat=".$sub_cat;
}?>" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a></p>
<?php if ($cat > 0) {?>
  <p><a href="gest_subcategoria.php<?php if ($cat > 0) {
      echo "?cat=".$cat;
  }?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<?php } ?>

<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idsubcate sec</th>
			<th align="center">Categoria</th>
			<th align="center">Sub Categoria</th>
			<th align="center">Sub Categoria Secundaria</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
      <?php if ($margen_seguridad == "S") { ?>
			  <th align="center">Margen</th>
      <?php } ?>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="sub_categorias_secundaria_det.php?id=<?php echo $rs->fields['idsubcate_sec']; ?><?php if ($sub_cat > 0) {
					    echo "&sub_cat=".$sub_cat;
					}?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="sub_categorias_secundaria_edit.php?id=<?php echo $rs->fields['idsubcate_sec']; ?><?php if ($sub_cat > 0) {
					    echo "&sub_cat=".$sub_cat;
					}?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="sub_categorias_secundaria_del.php?id=<?php echo $rs->fields['idsubcate_sec']; ?><?php if ($sub_cat > 0) {
					    echo "&sub_cat=".$sub_cat;
					}?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idsubcate_sec']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['categoria']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['subcategoria']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombre_sub_cate_sec']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
			<?php if ($margen_seguridad == "S") { ?>
        <td align="right"><?php echo formatomoneda($rs->fields['margen_seguridad']);  ?></td>
      <?php } ?>
		</tr>
<?php
$recarga_porc_acum += $rs->fields['recarga_porc'];
    $margen_acum += $rs->fields['margen'];

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
