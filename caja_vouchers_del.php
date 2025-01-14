<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "269";
require_once("includes/rsusuario.php");





$unicasspk = intval($_GET['id']);
if ($unicasspk == 0) {
    header("location: caja_cierre_edit.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from caja_vouchers 
where 
unicasspk = $unicasspk
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$unicasspk = intval($rs->fields['unicasspk']);
$idcaja = intval($rs->fields['idcaja']);
if ($unicasspk == 0) {
    header("location: caja_cierre_edit.php");
    exit;
}




//$idcaja=24;

$consulta = "
select * from caja_super where estado_caja = 3 and idcaja = $idcaja and rendido = 'N'
";
$rscaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rscaj->fields['idcaja']) == 0) {
    header("location: caja_cierre_edit.php");
    exit;
}

// loguear todos los cambios


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
    $total_vouchers = antisqlinyeccion($_POST['total_vouchers'], "float");





    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update caja_vouchers
		set
			estado = 6
		where
			unicasspk = $unicasspk
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // recalcular caja
        recalcular_caja($idcaja);

        header("location: caja_cierre_edit_edit.php?id=".$idcaja);
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
                    <h2>Borrar Voucher de Caja #<?php echo $idcaja; ?></h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Monto Voucher *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input disabled type="text" name="total_vouchers" id="total_vouchers" value="<?php  if (isset($_POST['total_vouchers'])) {
	    echo floatval($_POST['total_vouchers']);
	} else {
	    echo floatval($rs->fields['total_vouchers']);
	}?>" placeholder="Total vouchers" class="form-control" required />                    
	</div>
</div>



<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='caja_cierre_edit_edit.php?id=<?php echo $idcaja; ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />

<br /><br /><br />
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
