<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "81";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$subcat = intval($_GET['id']);

$consulta = "
SELECT * 
FROM sub_categorias
inner join categorias on categorias.id_categoria = sub_categorias.idcategoria
where
sub_categorias.idsubcate = $subcat
and sub_categorias.estado = 1
and (sub_categorias.idempresa = $idempresa or sub_categorias.borrable = 'N')
and sub_categorias.idsubcate not in (SELECT idsubcate FROM sub_categoria_ocultar where idempresa = $idempresa and mostrar = 'N')
order by sub_categorias.descripcion asc
";
$prod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcategoria = $prod->fields['id_categoria'];

if (intval($prod->fields['id_categoria']) == 0) {
    echo "Error! Sub-categoria Inexistente!";
    exit;
}


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {



    // recibe parametros
    //$descripcion=antisqlinyeccion($_POST['descripcion'],"text");

    $consulta = "
		update sub_categorias 
		set
		estado = 0
		where
		idsubcate = $subcat
		and idempresa = $idempresa
		";
    //echo $consulta;
    //exit;
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    header("location: gest_subcategoria.php?cat=".$prod->fields['id_categoria']);
    exit;



}



?>


<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
	<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
	<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
	<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />

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
                    <h2>Eliminar Sub Categoria</h2>
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
    		<table width="70" border="0">
          <tbody>
            <tr>
              <td width="62"><a href="gest_subcategoria.php?cat=<?php echo trim($prod->fields['id_categoria']) ?>">
                <img src="../img/homeblue.png" height="64" width="64" /></a></td>
            </tr> 
          </tbody>
        </table>
    </div>
 				<div class="divstd">
					<span class="resaltaditomenor">Sub Categorias</span></div>

            <div align="center">
    <p>&nbsp;</p>
    <strong>Esta seguro que desea eliminar esta Sub-Categoria?</strong><br /><br />
    <form id="form1" name="form1" method="post" action="gest_subcategoria_eliminar.php?id=<?php echo $subcat?>">
    
    <table width="700" border="0" class="tablaconborde">
      <?php while (!$prod->EOF) {



          ?>
      <tr>
        <td width="50%" height="27" align="right">Sub Categoria:</td>
        <td width="50%" align="left"><?php echo trim($prod->fields['descripcion']) ?></td>
      </tr>
      <?php $prod->MoveNext();
      } ?>
    </table>
    <br />
    <input type="submit" name="guardar" id="guardar" value="Eliminar" />
    <input type="button" name="volver" id="volver" value="Volver" onmouseup="document.location.href='gest_subcategoria.php?cat=<?php echo $idcategoria; ?>'" />
    <input type="hidden" name="MM_update" value="form1" /></form>
    <br /><br /><br /><br /><br /><br />
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
