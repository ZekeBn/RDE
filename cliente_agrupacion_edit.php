<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "436";
require_once("includes/rsusuario.php");



$idclienteagrupa = intval($_GET['id']);
if ($idclienteagrupa == 0) {
    header("location: cliente_agrupacion.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from cliente_agrupacion 
where 
idclienteagrupa = $idclienteagrupa
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idclienteagrupa = intval($rs->fields['idclienteagrupa']);
if ($idclienteagrupa == 0) {
    header("location: cliente_agrupacion.php");
    exit;
}




if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

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
    $agrupacion = antisqlinyeccion($_POST['agrupacion'], "text");
    $estado = 1;
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");




    if (trim($_POST['agrupacion']) == '') {
        $valido = "N";
        $errores .= " - El campo agrupacion no puede estar vacio.<br />";
    }
    /*
    registrado_por
    */
    /*
    registrado_el
    */
    /*
    borrado_por
    */
    /*
    borrado_el
    */


    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update cliente_agrupacion
		set
			agrupacion=$agrupacion
		where
			idclienteagrupa = $idclienteagrupa
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: cliente_agrupacion.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());



?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Agrupacion de Clientes</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Agrupacion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="agrupacion" id="agrupacion" value="<?php  if (isset($_POST['agrupacion'])) {
	    echo htmlentities($_POST['agrupacion']);
	} else {
	    echo htmlentities($rs->fields['agrupacion']);
	}?>" placeholder="Agrupacion" class="form-control" required="required" />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='cliente_agrupacion.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
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

        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
