<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "224";
require_once("includes/rsusuario.php");


$idtimbrado = intval($_GET['id']);
if ($idtimbrado == 0) {
    header("location: timbrado.php");
    exit;
}

// consulta a la tabla
$consulta = "
select *
from timbrado 
where 
idtimbrado = $idtimbrado
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtimbrado = intval($rs->fields['idtimbrado']);
if ($idtimbrado == 0) {
    header("location: timbrado.php");
    exit;
}


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros
    $timbrado = antisqlinyeccion($_POST['timbrado'], "text");


    // validaciones basicas
    $valido = "S";
    $errores = "";



    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update timbrado
		set
			estado = 6,
			borrado_por = $idusu,
			borrado_el = '$ahora'
		where
			idtimbrado = $idtimbrado
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // inserta en el log
        $consulta = "
		INSERT INTO timbradolog
		(idtimbrado, timbrado, inicio_vigencia, fin_vigencia, registrado_por, registrado_el, borrado_por, borrado_el, 
		editado_por, editado_el, estado, log_registrado_por, log_registrado_el, log_tipomov) 
		SELECT 
		idtimbrado, timbrado, inicio_vigencia, fin_vigencia, registrado_por, registrado_el, borrado_por, borrado_el, 
		editado_por, editado_el, estado, $idusu, '$ahora', 'D'
		from timbrado
		WHERE
		idtimbrado = $idtimbrado
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: timbrados.php");
        exit;

    }

}

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
              <!--<div class="title_left">
                <h3>Plain Page</h3>
              </div>-->

              <!--<div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for...">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button">Go!</button>
                    </span>
                  </div>
                </div>
              </div>-->
            </div>

            <div class="clearfix"></div>
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Administracion de Timbrados</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <!--<li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>-->
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

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idtimbrado *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="" name="idtimbrado" id="idtimbrado" value="<?php  if (isset($_POST['idtimbrado'])) {
	    echo htmlentities($_POST['idtimbrado']);
	} else {
	    echo htmlentities($rs->fields['idtimbrado']);
	}?>" placeholder="Idtimbrado" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Timbrado *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="" name="timbrado" id="timbrado" value="<?php  if (isset($_POST['timbrado'])) {
	    echo htmlentities($_POST['timbrado']);
	} else {
	    echo htmlentities($rs->fields['timbrado']);
	}?>" placeholder="Timbrado" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='timbrados.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />

<br />
</form>



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
