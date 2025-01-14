<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "24";

require_once("../includes/rsusuario.php");



$idtipo_servicio = intval($_GET['id']);
if ($idtipo_servicio == 0) {
    header("location: tipo_servicio.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from tipo_servicio 
where 
idtipo_servicio = $idtipo_servicio
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtipo_servicio = intval($rs->fields['idtipo_servicio']);
if ($idtipo_servicio == 0) {
    header("location: tipo_servicio.php");
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
    $tipo = antisqlinyeccion($_POST['tipo'], "text");
    $actualizado_por = $idusu;
    $actualizado_el = antisqlinyeccion($ahora, "text");




    if (trim($_POST['tipo']) == '') {
        $valido = "N";
        $errores .= " - El campo tipo no puede estar vacio.<br />";
    }



    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		update tipo_servicio
		set
			tipo=$tipo,
			actualizado_por=$actualizado_por,
			actualizado_el=$actualizado_el
		where
			idtipo_servicio = $idtipo_servicio
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: tipo_servicio.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

?><!DOCTYPE html>
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
                    <h2>Proveedores</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo de Servicio *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="tipo" id="tipo" value="<?php  if (isset($_POST['tipo'])) {
	    echo htmlentities($_POST['tipo']);
	} else {
	    echo htmlentities($rs->fields['tipo']);
	}?>" placeholder="Ej: Proveedor Mercaderia" class="form-control" required="required" />                    
	</div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='tipo_servicio.php'"><span class="fa fa-ban"></span> Cancelar</button>
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

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
