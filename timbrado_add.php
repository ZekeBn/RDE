<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "224";
require_once("includes/rsusuario.php");




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
    $timbrado = antisqlinyeccion($_POST['timbrado'], "text");
    $inicio_vigencia = antisqlinyeccion($_POST['inicio_vigencia'], "text");
    $fin_vigencia = antisqlinyeccion($_POST['fin_vigencia'], "text");
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $editado_por = antisqlinyeccion('', "int");
    $editado_el = antisqlinyeccion('', "text");
    $estado = 1;




    if (trim($_POST['timbrado']) == '') {
        $valido = "N";
        $errores .= " - El campo timbrado no puede estar vacio.<br />";
    }
    if (trim($_POST['inicio_vigencia']) == '') {
        $valido = "N";
        $errores .= " - El campo inicio_vigencia no puede estar vacio.<br />";
    }
    if (trim($_POST['fin_vigencia']) == '') {
        $valido = "N";
        $errores .= " - El campo fin_vigencia no puede estar vacio.<br />";
    }



    // valida que ya no exista el timbrado
    $consulta = "
	select * from timbrado where timbrado = $timbrado and estado = 1
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rs->fields['idtimbrado']) > 0) {
        $valido = "N";
        $errores .= " - El timbrado que intentas registrar ya existe.<br />";
    }

    // valida que ya no exista el timbrado
    $consulta = "
	select * from timbrado where timbrado = $timbrado and estado = 6
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rs->fields['idtimbrado']) > 0) {
        $valido = "N";
        $errores .= " - El timbrado que intentas registrar ya existe pero fue borrado, reactivelo.<br />";
    }

    // valido hasta no puede ser menor a valido desde
    if (strtotime(date("Y-m-d", strtotime($_POST['inicio_vigencia']))) >= strtotime(date("Y-m-d", strtotime($_POST['fin_vigencia'])))) {
        $valido = "N";
        $errores .= " - El campo inicio_vigencia no puede ser mayor o igual a fin_vigencia.<br />";
    }




    /*
    borrado_por
    */
    /*
    borrado_el
    */
    /*
    editado_por
        if(intval($_POST['editado_por']) == 0){
            $valido="N";
            $errores.=" - El campo editado_por no puede ser cero o nulo.<br />";
        }
    */
    /*
    editado_el
        if(trim($_POST['editado_el']) == ''){
            $valido="N";
            $errores.=" - El campo editado_el no puede estar vacio.<br />";
        }
    */


    // si todo es correcto inserta
    if ($valido == "S") {


        $consulta = "
		insert into timbrado
		(timbrado, inicio_vigencia, fin_vigencia, registrado_por, registrado_el, editado_por, editado_el, estado)
		values
		($timbrado, $inicio_vigencia, $fin_vigencia, $registrado_por, $registrado_el, $editado_por, $editado_el, $estado)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		select max(idtimbrado) as idtimbrado from timbrado where registrado_por = $registrado_por
		";
        $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idtimbrado = intval($rsmax->fields['idtimbrado']);

        // inserta en el log
        $consulta = "
		INSERT INTO timbradolog
		(idtimbrado, timbrado, inicio_vigencia, fin_vigencia, registrado_por, registrado_el, borrado_por, borrado_el, 
		editado_por, editado_el, estado, log_registrado_por, log_registrado_el, log_tipomov) 
		SELECT 
		idtimbrado, timbrado, inicio_vigencia, fin_vigencia, registrado_por, registrado_el, borrado_por, borrado_el, 
		editado_por, editado_el, estado, $idusu, '$ahora', 'I'
		from timbrado
		WHERE
		idtimbrado = $idtimbrado
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: timbrados_det_add.php?id=".$idtimbrado);
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Timbrado *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="timbrado" id="timbrado" value="<?php  if (isset($_POST['timbrado'])) {
	    echo htmlentities($_POST['timbrado']);
	} else {
	    echo htmlentities($rs->fields['timbrado']);
	}?>" placeholder="Timbrado" class="form-control" required />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Inicio vigencia *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="inicio_vigencia" id="inicio_vigencia" value="<?php  if (isset($_POST['inicio_vigencia'])) {
	    echo htmlentities($_POST['inicio_vigencia']);
	} else {
	    echo htmlentities($rs->fields['inicio_vigencia']);
	}?>" placeholder="Inicio vigencia" class="form-control" required />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fin vigencia *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="fin_vigencia" id="fin_vigencia" value="<?php  if (isset($_POST['fin_vigencia'])) {
	    echo htmlentities($_POST['fin_vigencia']);
	} else {
	    echo htmlentities($rs->fields['fin_vigencia']);
	}?>" placeholder="Fin vigencia" class="form-control" required />                    
	</div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='timbrados.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<hr />
<strong>Donde encontrar esta informacion:</strong>
<br /><br />
<img src="img/partes_factura.jpg" class="img-thumbnail" />
<br /><br />
<img src="img/partes_factura_abajo.jpg" class="img-thumbnail" />
<br /><br />
<img src="img/timbset1.jpg" class="img-thumbnail" />
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
