<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
// TODO:PREGUNTAR MODULO SI AGREGAR NOMAS
$modulo = "42";
$submodulo = "616";

$dirsup = "S";
require_once("../includes/rsusuario.php");





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
    $nombre = antisqlinyeccion($_POST['nombre'], "text");
    $documento = antisqlinyeccion($_POST['documento'], "text");
    $celular = antisqlinyeccion($_POST['celular'], "text");
    $emial = antisqlinyeccion($_POST['emial'], "text");
    $direccion = antisqlinyeccion($_POST['direccion'], "text");
    $estado = 1;
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");




    if (trim($_POST['nombre']) == '') {
        $valido = "N";
        $errores .= " - El campo nombre no puede estar vacio.<br />";
    }
    /*
    documento
        if(trim($_POST['documento']) == ''){
            $valido="N";
            $errores.=" - El campo documento no puede estar vacio.<br />";
        }
    */
    /*
    celular
        if(trim($_POST['celular']) == ''){
            $valido="N";
            $errores.=" - El campo celular no puede estar vacio.<br />";
        }
    */
    /*
    emial
        if(trim($_POST['emial']) == ''){
            $valido="N";
            $errores.=" - El campo emial no puede estar vacio.<br />";
        }
    */
    /*
    direccion
        if(trim($_POST['direccion']) == ''){
            $valido="N";
            $errores.=" - El campo direccion no puede estar vacio.<br />";
        }
    */
    /*
    registrado_por
    */
    /*
    registrado_el
    */


    // si todo es correcto inserta
    if ($valido == "S") {
        $idpropietario = select_max_id_suma_uno("vehiculo_propietario", "idpropietario")["idpropietario"];
        $consulta = "
		insert into vehiculo_propietario
		(idpropietario,nombre, documento, celular, emial, direccion, estado, registrado_por, registrado_el)
		values
		($idpropietario, $nombre, $documento, $celular, $emial, $direccion, $estado, $registrado_por, $registrado_el)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: vehiculo_propietario.php");
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
                    <h2>Propietarios Vehiculo</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre" id="nombre" value="<?php  if (isset($_POST['nombre'])) {
	    echo htmlentities($_POST['nombre']);
	} else {
	    echo htmlentities($rs->fields['nombre']);
	}?>" placeholder="Nombre" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Documento </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="documento" id="documento" value="<?php  if (isset($_POST['documento'])) {
	    echo htmlentities($_POST['documento']);
	} else {
	    echo htmlentities($rs->fields['documento']);
	}?>" placeholder="Documento" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Celular </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="celular" id="celular" value="<?php  if (isset($_POST['celular'])) {
	    echo htmlentities($_POST['celular']);
	} else {
	    echo htmlentities($rs->fields['celular']);
	}?>" placeholder="Celular" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Emial </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="emial" id="emial" value="<?php  if (isset($_POST['emial'])) {
	    echo htmlentities($_POST['emial']);
	} else {
	    echo htmlentities($rs->fields['emial']);
	}?>" placeholder="Emial" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="direccion" id="direccion" value="<?php  if (isset($_POST['direccion'])) {
	    echo htmlentities($_POST['direccion']);
	} else {
	    echo htmlentities($rs->fields['direccion']);
	}?>" placeholder="Direccion" class="form-control"  />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='vehiculo_propietario.php'"><span class="fa fa-ban"></span> Cancelar</button>
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
