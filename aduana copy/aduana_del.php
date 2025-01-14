<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "42";
$submodulo = "578";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("./funciones_aduana.php");

$idaduana = intval($_GET['id']);
if ($idaduana == 0) {
    header("location: aduana.php");
    exit;
}

// consulta a la tabla
$consulta = "
select *
from aduana 
where 
idaduana = $idaduana
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idaduana = intval($rs->fields['idaduana']);
if ($idaduana == 0) {
    header("location: aduana.php");
    exit;
}


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros
    $descripcion = antisqlinyeccion($_POST['descripcion'], "text");


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

    $parametros_array = [
    "idaduana" => $idaduana
  ];

    if ($valido == "S") {
        $res = aduana_delete($parametros_array);
        if ($res["valido"] == "S") {
            header("location: aduana.php");
            exit;
        }
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
                    <h2>Datos Aduana</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idaduana *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="" name="idaduana" id="idaduana" value="<?php  if (isset($_POST['idaduana'])) {
	    echo($_POST['idaduana']);
	} else {
	    echo($rs->fields['idaduana']);
	}?>" placeholder="Idaduana" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Descripcion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="" name="descripcion" id="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
	    echo($_POST['descripcion']);
	} else {
	    echo($rs->fields['descripcion']);
	}?>" placeholder="Descripcion" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='aduana.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>


  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo antixss($_SESSION['form_control']); ?>">
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
