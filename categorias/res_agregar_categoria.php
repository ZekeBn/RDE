<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "81";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("./preferencias_categorias.php");


if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {
    if (trim($_POST['categoria']) != '') {


        // recibe parametros
        $categoria = antisqlinyeccion($_POST['categoria'], "text");
        $margen_seguridad = antisqlinyeccion($_POST['margen_seguridad'], "float");

        // busca el proximo id
        $consulta = "
		select max(id_categoria) as proxid from categorias
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $proxid = intval($rs->fields['proxid']) + 1;


        $consulta = "
		insert into categorias 
		(id_categoria,nombre,ab,orden,textobanner,imgbanner,estado,borrable,especial,idsucursal,idempresa,margen_seguridad)
		values
		($proxid,$categoria,NULL,0,'','',1,'S','N',$idsucursal,$idempresa,$margen_seguridad)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: gest_categoria_productos.php");
        exit;

    }
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
	</script>
	<script src="js/sweetalert.min.js"></script>
	<link rel="stylesheet" type="text/css" href="css/sweetalert.css">


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
                    <h2>Agregar Categoria</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	


				  <?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Categoria </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="categoria" id="categoria" value="<?php  if (isset($_POST['categoria'])) {
	    echo htmlentities($_POST['categoria']);
	} else {
	    echo htmlentities($rs->fields['categoria']);
	}?>" placeholder="categoria" class="form-control"  />                    
	</div>
</div>

<?php if ($margen_seguridad == "S") {?>
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Margen seguridad </label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="margen_seguridad" id="margen_seguridad" value="<?php  if (isset($_POST['margen_seguridad'])) {
			    echo floatval($_POST['margen_seguridad']);
			} else {
			    echo floatval($rs->fields['margen_seguridad']);
			}?>" placeholder="Margen seguridad" class="form-control" required="required" />                    
		</div>
	</div>
<?php } ?>

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='categorias.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
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
