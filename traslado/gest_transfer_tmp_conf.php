<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "222";
require_once("includes/rsusuario.php");




$idtanda = intval($_GET['idtanda']);
if ($idtanda == 0) {
    echo "No se indico la tanda.";
    exit;
}

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {




    // validaciones basicas
    $valido = "S";
    $errores = "";



    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update tmp_transfer 
		set
		confirmado = 'S',
		confirmado_por = $idusu,
		confirmado_el = '$ahora'
		where 
		idtanda = $idtanda
		and (confirmado = 'N' or confirmado is null)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        header("location: gest_transferencias_det.php?id=$idtanda");
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
            </div>
            <div class="clearfix"></div>
			<?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Confirmar todos los articulos de la Transferencia #<?php echo $idtanda; ?></h2>
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


<strong>Esta accion no se puede deshacer, esta seguro?</strong><br /><br />
<form id="form1" name="form1" method="post" action="">

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idtanda *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="" name="idtanda" id="idtanda" value="<?php  if (isset($_POST['idtanda'])) {
	    echo htmlentities($_POST['idtanda']);
	} else {
	    echo htmlentities($idtanda);
	}?>" placeholder="Idtanda" class="form-control" required readonly disabled="disabled" />                    
	</div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-o"></span> Confirmar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_transferencias_det.php?id=<?php echo $idtanda ?>'"><span class="fa fa-ban"></span> Cancelar</button>
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
