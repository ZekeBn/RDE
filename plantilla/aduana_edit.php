<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");






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
    $descripcion = $_POST['descripcion'];
    $idpais = $_POST['idpais'];
    $idempresa = $_POST['idempresa'];
    $idpto = $_POST['idpto'];
    $idciudad = $_POST['idciudad'];
    $registrado_por = $idusu;

    $registrado_el = $ahora;

    $estado = $_POST['estado'];
    $estado = 1;



    $parametros_array = [
        "descripcion" => $descripcion,
        "idpais" => $idpais,
        "idempresa" => $idempresa,
        "idpto" => $idpto,
        "idciudad" => $idciudad,
        "registrado_por" => $registrado_por,
        "registrado_el" => $registrado_el,
        "borrado_por" => $borrado_por,
        "borrado_el" => $borrado_el,
        "estado" => $estado,
        "idaduana" => $idaduana
    ];

    // si todo es correcto actualiza
    if ($valido == "S") {
        $res = aduana_edit($parametros_array);
        if ($res["valido"] == "S") {
            header("location: aduana.php");
            exit;
        } else {
            $errores .= $res["errores"];
        }

    } else {
        $errores .= $res["errores"];
    }


}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

// se puede mover esta funcion al archivo funciones_aduana.php y realizar un require_once
function aduana_edit($parametros_array)
{
    global $conexion;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    $descripcion = antisqlinyeccion($parametros_array['descripcion'], "text");
    $idpais = antisqlinyeccion($parametros_array['idpais'], "int");
    $idempresa = antisqlinyeccion($parametros_array['idempresa'], "int");
    $idpto = antisqlinyeccion($parametros_array['idpto'], "int");
    $idciudad = antisqlinyeccion($parametros_array['idciudad'], "int");
    $registrado_por = antisqlinyeccion($parametros_array['registrado_por'], "int");
    $registrado_el = antisqlinyeccion($parametros_array['registrado_el'], "text");
    $estado = antisqlinyeccion($parametros_array['estado'], "float");
    $estado = antisqlinyeccion($parametros_array['estado'], "int");
    $idaduana = antisqlinyeccion($parametros_array['idaduana'], "int");

    if (trim($parametros_array['descripcion']) == '') {
        $valido = "N";
        $errores .= " - El campo descripcion no puede estar vacio.<br />";
    }
    if (intval($parametros_array['idpais']) == 0) {
        $valido = "N";
        $errores .= " - El campo idpais no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idempresa']) == 0) {
        $valido = "N";
        $errores .= " - El campo idempresa no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idpto']) == 0) {
        $valido = "N";
        $errores .= " - El campo idpto no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idciudad']) == 0) {
        $valido = "N";
        $errores .= " - El campo idciudad no puede ser cero o nulo.<br />";
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
    /*
    estado
        if(floatval($parametros_array['estado']) <= 0){
            $valido="N";
            $errores.=" - El campo estado no puede ser cero o negativo.<br />";
        }
    */


    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update aduana
		set
			descripcion=$descripcion,
			idpais=$idpais,
			idempresa=$idempresa,
			idpto=$idpto,
			idciudad=$idciudad
		where
			idaduana = $idaduana
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


    return ["errores" => $errores,"valido" => $valido];
}



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
                    <h2>Datos Plantilla</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Descripcion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
	    echo htmlentities($_POST['descripcion']);
	} else {
	    echo htmlentities($rs->fields['descripcion']);
	}?>" placeholder="Descripcion" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idpais *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idpais" id="idpais" value="<?php  if (isset($_POST['idpais'])) {
	    echo intval($_POST['idpais']);
	} else {
	    echo intval($rs->fields['idpais']);
	}?>" placeholder="Idpais" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idempresa *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idempresa" id="idempresa" value="<?php  if (isset($_POST['idempresa'])) {
	    echo intval($_POST['idempresa']);
	} else {
	    echo intval($rs->fields['idempresa']);
	}?>" placeholder="Idempresa" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idpto *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idpto" id="idpto" value="<?php  if (isset($_POST['idpto'])) {
	    echo intval($_POST['idpto']);
	} else {
	    echo intval($rs->fields['idpto']);
	}?>" placeholder="Idpto" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idciudad *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idciudad" id="idciudad" value="<?php  if (isset($_POST['idciudad'])) {
	    echo intval($_POST['idciudad']);
	} else {
	    echo intval($rs->fields['idciudad']);
	}?>" placeholder="Idciudad" class="form-control" required="required" />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='aduana.php'"><span class="fa fa-ban"></span> Cancelar</button>
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
