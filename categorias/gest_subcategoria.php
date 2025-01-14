<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "81";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("./preferencias_categorias.php");

$cat = intval($_GET['cat']);

$consulta = "
SELECT * 
FROM categorias
where
id_categoria = $cat
and idempresa = $idempresa
";
$prodcat = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$consulta = "
SELECT *, sub_categorias.margen_seguridad
FROM sub_categorias
inner join categorias on categorias.id_categoria = sub_categorias.idcategoria
where
idcategoria = $cat
and sub_categorias.estado = 1
and (sub_categorias.idempresa = $idempresa or sub_categorias.borrable = 'N')
and sub_categorias.idsubcate not in (SELECT idsubcate FROM sub_categoria_ocultar where idempresa = $idempresa and mostrar = 'N')
order by sub_categorias.descripcion asc
";
$prod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



?>


<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>
    function alertar(titulo,error,tipo,boton){
      swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
      }
    function borrar(desc,id){
      if(window.confirm('Esta seguro que desea borrar: '+desc+' ?')){
        alert('Acceso Denegado! '+id);	
      }
    }
  </script>
  <script src="js/sweetalert.min.js"></script>
  <link rel="stylesheet" type="text/css" href="css/sweetalert.css">
  <script type='text/javascript' src='plugins/ckeditor.js'></script>
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
                    <h2>Sub Categorias</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

    <div class="clear"></div><!-- clear1 -->
			<div class="colcompleto" id="contenedor">
        <div align="center">
          <div class="table-responsive">
            <table width="70" border="0" >
              <tbody>
                <tr>
                  <td width="62"><a href="gest_categoria_productos.php">
                    <img src="../img/homeblue.png" height="64" width="64" /></a></td>
                  <td width="62"><a href="gest_subcategoria_agregar.php?cat=<?php echo $cat; ?>"><img src="../img/pagrega.png" width="64" height="64" title="Agregar Sub-Categoria" style="cursor:pointer" /></a></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
 				<div class="divstd">

            <div align="center">
              <p>&nbsp;</p>
              <div style="border:1px solid #000; text-align:center; width:500px; margin:0px auto; padding:5px;">
              <strong>Categoria:</strong><br />
              <br />
              <h1 align="center" style="font-weight:bold; margin:0px; padding:0px; color:#0A9600;"><?php echo $prodcat->fields['nombre']; ?></h1><br />

              <input type="button" class="btn btn-sm btn-default" name="button" id="button" value="Cambiar" onmouseup="document.location.href='gest_categoria_productos.php'" />
            </div>


            <br /><br />
            <div class="table-responsive">
              <table width="700" border="1" class="table table-bordered jambo_table bulk_action">
                <thead>
                  <tr>
                    <td  align="center" > <strong>Acciones</strong></td>
                    <td  align="center" > <strong>ID Sub-Categoria</strong></td>
                    <td  align="center" > <strong>Sub-Categoria</strong></td>
                    <?php if ($margen_seguridad == "S") { ?>
                      <th align="center">Margen seguridad</th>
                    <?php } ?>
                    <td  align="center" > <strong>Imagen</strong></td>
                    <td  align="center" ></td>
                  </tr>
                </thead>
                <?php while (!$prod->EOF) {?>
                  <tr>
                  <?php
                    $img = "../tablet/gfx/iconos/subcat_".$prod->fields['idsubcate'].".png";
                    if (!file_exists($img)) {
                        $img = "tablet/gfx/iconos/subcat_0.png";
                    }

                    $img2 = "../ecom/gfx/fotosweb/subcat_".$prod->fields['idsubcate'].".png";
                    if (!file_exists($img2)) {
                        $img2 = "../ecom/gfx/fotosweb/subcat_0.png";
                    }
                    ?>
                    <td align="center">
                      <?php if ($sub_categoria_secundaria == "S") { ?>
                      <a href="sub_categorias_secundaria.php?sub_cat=<?php echo trim($prod->fields['idsubcate']) ?>" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Sub Categorias"><span class="fa fa-sitemap"></span></a>
                      <?php } ?>
                      <a href="gest_subcategoria_editar.php?id=<?php echo trim($prod->fields['idsubcate']) ?>" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Detalle"><span class="fa fa-edit"></span></a>
                      <a href="gest_subcategoria_eliminar.php?id=<?php echo trim($prod->fields['idsubcate']) ?>" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Detalle"><span class="fa fa-trash-o"></span></a>
                      <a href="subcategoria_icono.php?id=<?php echo trim($prod->fields['idsubcate']) ?>" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Detalle"><span class="fa fa-file-image-o"></span></a>
                    </td>
                    <td height="27" align="center"><?php echo trim($prod->fields['idsubcate']) ?></td>
                    <td height="27" align="center"><?php echo trim($prod->fields['descripcion']) ?></td>
                    <?php if ($margen_seguridad == "S") { ?> 
                      <td align="center"><?php echo formatomoneda($prod->fields['margen_seguridad']); ?></td>
                    <?php } ?>
                    <td align="center"><img src="<?php echo $img ?>"  border="0" class="img-responsive"  /></td>
                      
                  </tr>
                <?php $prod->MoveNext();
                } ?>
              </table>
            </div>
            <br /><br /><br /><br /><br /><br /><br />
      </div>

    </div> <!-- contenedor -->
  		
 
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
