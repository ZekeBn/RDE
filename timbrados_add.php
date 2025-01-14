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
    $secuencia1 = antisqlinyeccion($_POST['sucursal'], "int");
    $secuencia2 = antisqlinyeccion($_POST['punto_expedicion'], "int");
    $inicio = antisqlinyeccion($_POST['inicio'], "int");
    $fin = antisqlinyeccion($_POST['fin'], "int");
    $punto_expedicion = antisqlinyeccion($_POST['punto_expedicion'], "int");
    $sucursal = antisqlinyeccion($_POST['sucursal'], "int");
    $timbrado = antisqlinyeccion($_POST['timbrado'], "int");
    $valido_desde = antisqlinyeccion($_POST['valido_desde'], "text");
    $valido_hasta = antisqlinyeccion($_POST['valido_hasta'], "text");

    // conversiones
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $cobrador_asignado = antisqlinyeccion('', "int");
    $observaciones = antisqlinyeccion('', "text");
    $estado = antisqlinyeccion('A', "text");
    $asignado_el = antisqlinyeccion($ahora, "text");


    if (intval($_POST['inicio']) == 0) {
        $valido = "N";
        $errores .= " - El campo inicio no puede ser cero o nulo.<br />";
    }

    if (intval($_POST['fin']) == 0) {
        $valido = "N";
        $errores .= " - El campo fin no puede ser cero o nulo.<br />";
    }


    if (intval($_POST['punto_expedicion']) == 0) {
        $valido = "N";
        $errores .= " - El campo punto_expedicion no puede ser cero o nulo.<br />";
    }

    if (intval($_POST['sucursal']) == 0) {
        $valido = "N";
        $errores .= " - El campo sucursal no puede ser cero o nulo.<br />";
    }

    if (intval($_POST['timbrado']) == 0) {
        $valido = "N";
        $errores .= " - El campo timbrado no puede ser cero o nulo.<br />";
    }

    if (trim($_POST['valido_desde']) == '') {
        $valido = "N";
        $errores .= " - El campo valido_desde no puede estar vacio.<br />";
    }
    if (trim($_POST['valido_hasta']) == '') {
        $valido = "N";
        $errores .= " - El campo valido_hasta no puede estar vacio.<br />";
    }


    // valida que ya no exista el timbrado
    $consulta = "
	select * from facturas where timbrado = $timbrado and estado = 'A'
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rs->fields['idtanda']) > 0) {
        $valido = "N";
        $errores .= " - El timbrado que intentas registrar ya existe.<br />";
    }

    // fin no puede ser superior a inicio
    if (intval($_POST['inicio']) >= intval($_POST['fin'])) {
        $valido = "N";
        $errores .= " - El campo inicio no puede ser mayor o igual a fin.<br />";
    }

    // valido hasta no puede ser menor a valido desde
    if (strtotime(date("Y-m-d", strtotime($_POST['valido_desde']))) >= strtotime(date("Y-m-d", strtotime($_POST['valido_hasta'])))) {
        $valido = "N";
        $errores .= " - El campo valido_desde no puede ser mayor o igual a valido_hasta.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		select max(idtanda) as idtanda from facturas
		";
        $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idtanda = intval($rsmax->fields['idtanda']) + 1;

        $consulta = "
		insert into facturas
		(idtanda,secuencia1, secuencia2, inicio, fin, punto_expedicion, sucursal, idempresa, timbrado, valido_desde, valido_hasta, registrado_por, registrado_el, cobrador_asignado, observaciones, estado, asignado_el)
		values
		($idtanda,$secuencia1, $secuencia2, $inicio, $fin, $punto_expedicion, $sucursal, $idempresa, $timbrado, $valido_desde, $valido_hasta, $registrado_por, $registrado_el, $cobrador_asignado, $observaciones, $estado, $asignado_el)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: timbrados.php");
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
	    echo intval($_POST['timbrado']);
	} else {
	    echo intval($rs->fields['timbrado']);
	}?>" placeholder="Timbrado" class="form-control" required="required"  />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Numero Desde *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="inicio" id="inicio" value="<?php  if (isset($_POST['inicio'])) {
	    echo intval($_POST['inicio']);
	} else {
	    echo intval($rs->fields['inicio']);
	}?>" placeholder="Inicio" class="form-control" required="required"  />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Numero Hasta *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="fin" id="fin" value="<?php  if (isset($_POST['fin'])) {
	    echo intval($_POST['fin']);
	} else {
	    echo intval($rs->fields['fin']);
	}?>" placeholder="Fin" class="form-control" required="required"  />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Factura Sucursal *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="sucursal" id="sucursal" value="<?php  if (isset($_POST['sucursal'])) {
	    echo intval($_POST['sucursal']);
	} else {
	    echo intval($rs->fields['sucursal']);
	}?>" placeholder="Sucursal" class="form-control" required="required"  />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Factura Punto expedicion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="punto_expedicion" id="punto_expedicion" value="<?php  if (isset($_POST['punto_expedicion'])) {
	    echo intval($_POST['punto_expedicion']);
	} else {
	    echo intval($rs->fields['punto_expedicion']);
	}?>" placeholder="Punto expedicion" class="form-control" required="required"  />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Valido desde* </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="valido_desde" id="valido_desde" value="<?php  if (isset($_POST['valido_desde'])) {
	    echo htmlentities($_POST['valido_desde']);
	} else {
	    echo htmlentities($rs->fields['valido_desde']);
	}?>" placeholder="Valido desde" class="form-control" required="required"  />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Valido hasta *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="valido_hasta" id="valido_hasta" value="<?php  if (isset($_POST['valido_hasta'])) {
	    echo htmlentities($_POST['valido_hasta']);
	} else {
	    echo htmlentities($rs->fields['valido_hasta']);
	}?>" placeholder="Valido hasta" class="form-control" required="required"  />                    
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
