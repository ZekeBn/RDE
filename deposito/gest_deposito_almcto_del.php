<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "55";
$dirsup = 'S';
require_once("../includes/rsusuario.php");

$idalmacto = intval($_GET['idalmacto']);
if ($idalmacto > 0) {
    $consulta = "SELECT gest_deposito_almcto_grl.nombre FROM gest_deposito_almcto_grl WHERE gest_deposito_almcto_grl.idalmacto = $idalmacto";
    $rs_almacto_nombre = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $nombre_almacenamiento = $rs_almacto_nombre->fields['nombre'];
}

$iddeposito = intval($_GET['idpo']);
if ($iddeposito > 0) {
    $consulta = "SELECT gest_depositos.descripcion FROM gest_depositos WHERE gest_depositos.iddeposito = $iddeposito";
    $rs_depo_name = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $nombre_deposito = $rs_depo_name->fields['descripcion'];
}

$idalm = intval($_GET['id']);
if ($idalm == 0) {
    $location_add = "";
    if (($idalmacto) > 0) {
        $location_add .= "?idalmacto=$idalmacto";
    }
    if ($iddeposito > 0 && ($idalmacto) > 0) {
        $location_add .= "&idpo=$iddeposito";
    }
    if ($iddeposito > 0 && ($idalmacto) <= 0) {
        $location_add .= "?idpo=$iddeposito";
    }
    header("location: gest_deposito_almcto.php".$location_add);
    exit;
}

// consulta a la tabla
$consulta = "
select *
from gest_deposito_almcto 
where 
idalm = $idalm
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idalm = intval($rs->fields['idalm']);
if ($idalm == 0) {
    $location_add = "";
    if (($idalmacto) > 0) {
        $location_add .= "?idalmacto=$idalmacto";
    }
    if ($iddeposito > 0 && ($idalmacto) > 0) {
        $location_add .= "&idpo=$iddeposito";
    }
    if ($iddeposito > 0 && ($idalmacto) <= 0) {
        $location_add .= "?idpo=$iddeposito";
    }
    header("location: gest_deposito_almcto.php".$location_add);
    exit;
}


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros


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





    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update gest_deposito_almcto
		set
			estado = 6,
			anulado_por = $idusu,
			anulado_el = '$ahora'
		where
			idalm = $idalm
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $location_add = "";
        if (($idalmacto) > 0) {
            $location_add .= "?idalmacto=$idalmacto";
        }
        if ($iddeposito > 0 && ($idalmacto) > 0) {
            $location_add .= "&idpo=$iddeposito";
        }
        if ($iddeposito > 0 && ($idalmacto) <= 0) {
            $location_add .= "?idpo=$iddeposito";
        }
        header("location: gest_deposito_almcto.php".$location_add);
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
  <script>
    function tipo_almacenamiento(value){
      var cara = $("#cara");
      if (value == 2){
        cara.attr("disabled",true);
      }
      if (value == 1){
        cara.attr("disabled",false);
      }
    }
  </script>
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
                    <h2>Almacenamiento detalles  <?php if (isset($nombre_almacenamiento)) { ?> para <?php echo $nombre_almacenamiento ?> <?php } ?></h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idalm *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="" name="idalm" id="idalm" value="<?php  if (isset($_POST['idalm'])) {
	    echo($_POST['idalm']);
	} else {
	    echo($rs->fields['idalm']);
	}?>" placeholder="Idalm" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idalmacto *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="" name="idalmacto" id="idalmacto" value="<?php  if (isset($_POST['idalmacto'])) {
	    echo($_POST['idalmacto']);
	} else {
	    echo($rs->fields['idalmacto']);
	}?>" placeholder="Idalmacto" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_deposito_almcto.php<?php if (isset($iddeposito)) { ?>?idpo=<?php echo $iddeposito;
	   } ?><?php if (isset($iddeposito)) { ?>&idalmacto=<?php echo $idalmacto;
	   } ?>'"><span class="fa fa-ban"></span> Cancelar</button>
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
