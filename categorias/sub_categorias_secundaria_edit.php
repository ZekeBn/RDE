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

$idsubcate_sec = intval($_GET['id']);
if ($idsubcate_sec == 0) {
    header("location: sub_categorias_secundaria.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from sub_categorias_secundaria 
where 
idsubcate_sec = $idsubcate_sec
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idsubcate_sec = intval($rs->fields['idsubcate_sec']);
if ($idsubcate_sec == 0) {
    header("location: sub_categorias_secundaria.php");
    exit;
}





if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots


    // recibe parametros
    $idsubcate = antisqlinyeccion($_POST['idsubcate'], "int");
    $descripcion = antisqlinyeccion($_POST['descripcion'], "text");
    $margen = antisqlinyeccion($_POST['margen'], "float");



    if (intval($_POST['idsubcate']) == 0) {
        $valido = "N";
        $errores .= " - El campo idsubcate no puede ser cero o nulo.<br />";
    }
    if (trim($_POST['descripcion']) == '') {
        $valido = "N";
        $errores .= " - El campo descripcion no puede estar vacio.<br />";
    }
    if ($idempresa == 0) {
        $valido = "N";
        $errores .= " - El campo idempresa no puede ser cero o nulo.<br />";
    }




    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		update sub_categorias_secundaria
		set
			idsubcate=$idsubcate,
			descripcion=$descripcion,
			margen_seguridad=$margen
		where
			idsubcate_sec = $idsubcate_sec
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $url = "location: sub_categorias_secundaria.php";
        if ($sub_cat > 0) {
            $url .= '?sub_cat='.$sub_cat;
        };
        header($url);
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());





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
                    <h2>Sub Categoria Secundaria</h2>
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



<div class="col-md-6 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Sub Categoria *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php

                // consulta

                $consulta = "
				SELECT idsubcate, descripcion
				FROM sub_categorias
				where
				estado = 1
				order by descripcion asc
				";

// valor seleccionado
if (isset($_POST['idsubcate'])) {
    $value_selected = htmlentities($_POST['idsubcate']);
} else {
    $value_selected = $rs->fields['idsubcate'];
}


// parametros
$parametros_array = [
    'nombre_campo' => 'idsubcate',
    'id_campo' => 'idsubcate',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idsubcate',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="limpiar_datos()" "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
			</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Descripcion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
	    echo htmlentities($_POST['descripcion']);
	} else {
	    echo htmlentities($rs->fields['descripcion']);
	}?>" placeholder="Descripcion" class="form-control" required="required" />                    
	</div>
</div>

<?php if ($margen_seguridad == "S") { ?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Margen Seguridad</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="margen" id="margen" value="<?php  if (isset($_POST['margen'])) {
	    echo floatval($_POST['margen']);
	} else {
	    echo floatval($rs->fields['margen_seguridad']);
	}?>" placeholder="Margen" class="form-control" required="required" />                    
	</div>
</div>
<?php } ?>

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='sub_categorias_secundaria.php<?php if ($sub_cat > 0) {
	       echo "?sub_cat=".$sub_cat;
	   }?>'"><span class="fa fa-ban"></span> Cancelar</button>
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
