<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "81";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("./preferencias_categorias.php");
require_once("../importadora/preferencias_importadora.php");



$buscar = "
	SELECT * 
	FROM categorias
	where
	estado = 1
	and idempresa = $idempresa
	order by orden asc, nombre asc
	"	;
$rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

/*
para exportar a excel
SELECT categorias.id_categoria,  sub_categorias.idsubcate, categorias.nombre as categoria, sub_categorias.descripcion as subcategoria FROM `sub_categorias`
inner join categorias on categorias.id_categoria = sub_categorias.idcategoria
WHERE
categorias.estado = 1
and sub_categorias.estado = 1

*/



?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>
/*
function borrar(desc,id){
	if(window.confirm('Esta seguro que desea borrar: '+desc+' ?')){
		//alert('Acceso Denegado! '+id);	
		document.location.href='gest_categoria_productos_borra.php?id='+id; 
	}
}*/
function borrar(desc,id){
	
	var botones='<div class="clearfix"></div><br /><br /><br /><div class="form-group"><div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5"><button type="button" class="btn btn-danger" onMouseUp="borrar_categoria('+id+');"><span class="fa fa-trash-o"></span> Borrar</button><button type="button" class="btn btn-primary" onMouseUp="cerrar_ventana();"><span class="fa fa-ban"></span> Cancelar</button></div></div><div class="clearfix"></div><br />';
	
	$('#modal_ventana').modal('show');
	$("#modal_titulo").html('Borrar Categoria?');
	$("#modal_cuerpo").html('<p align="center"><strong>Esta seguro que desea borrar: "'+desc+'" ?</strong></p>'+'\n'+botones);
	//$("#modal_pie").html(html_botones);
	/*
	Otros usos:
	$('#modal_ventana').modal('show'); // abrir
	$('#modal_ventana').modal('hide'); // cerrar
	*/
	
}
function borrar_categoria(id){
	document.location.href='gest_categoria_productos_borra.php?id='+id; 
}
function cerrar_ventana(){
	$('#modal_ventana').modal('hide');
}
</script>
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
                    <h2>Categorias</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



<p>
<a href="res_agregar_categoria.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
<a href="categorias_xls.php" class="btn btn-sm btn-default"><span class="fa fa-download"></span> Descargar</a>
  <?php if ($carga_masiva_importacion == "S") { ?>
    <a href="categorias_carga_masiva.php" class="btn btn-sm btn-default"><span class="fa fa-sitemap"></span> Carga Masiva</a>
  <?php } ?>

</p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Id</th>
			<th align="center">Categoria</th>
			<th align="center">Orden Categoria</th>
			<th align="center">Orden p/ Food Cost</th>
      <?php if ($margen_seguridad == "S") { ?> 
        <th align="center">Margen seguridad</th>
      <?php } ?>
			<th align="center"></th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) {


    $img = "../tablet/gfx/iconos/cat_".$rs->fields['id_categoria'].".png";
    if (!file_exists($img)) {
        $img = "../tablet/gfx/iconos/cat_0.png";
    }

    ?>

 
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="gest_subcategoria.php?cat=<?php echo $rs->fields['id_categoria']; ?>" class="btn btn-sm btn-default" title="Sub Categorias" data-toggle="tooltip" data-placement="right"  data-original-title="Sub Categorias"><span class="fa fa-sitemap"></span></a>
					<a href="categoria_editar.php?id=<?php echo $rs->fields['id_categoria']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="#" onmouseup="borrar('<?php echo trim($rs->fields['nombre']) ?>','<?php echo $rs->fields['id_categoria']; ?>');" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>

          <a href="categoria_icono.php?id=<?php echo $rs->fields['id_categoria']; ?>" class="btn btn-sm btn-default" title="Cambiar Icono" data-toggle="tooltip" data-placement="right"  data-original-title="Cambiar Icono" ><span class="fa fa-file-image-o"></span></a>
				</div>

			</td>
			<td align="right"><?php echo intval($rs->fields['id_categoria']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombre']); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['orden']); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['ordenfc']); ?></td>
      <?php if ($margen_seguridad == "S") { ?> 
        <td align="center"><?php echo formatomoneda($rs->fields['margen_seguridad']); ?></td>
      <?php } ?>
      <td align="center"><a href="categoria_icono.php?id=<?php echo $rs->fields['id_categoria']; ?>"><img src="<?php echo $img ?>"  border="0" class="img-responsive"  /></a></td>
		</tr>
<?php $rs->MoveNext();
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
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="modal_titulo">Titulo</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo">
						...
                        </div>
                        <div class="modal-footer" id="modal_pie">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                          
                        </div>

                      </div>
                    </div>
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
