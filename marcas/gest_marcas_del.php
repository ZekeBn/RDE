<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "124";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$idmarca = intval($_GET['id']);
if ($idmarca == 0) {
    header("location: gest_marcas.php");
    exit;
}

$consulta = "
	select * from marca where idempresa = $idempresa and idmarca = $idmarca and idestado = 1 order by marca asc

	";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idmarca = intval($rs->fields['idmarca']);
if ($idmarca == 0) {
    header("location: gest_marcas.php");
    exit;
}


//  verificar que no haya productos activos cargado a esa marca para borrar
$buscar = "
	SELECT * 
	FROM productos
	where
	borrado = 'N'
	and idempresa = $idempresa
	and idmarca = $idmarca
	";
$rsprodcat = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
if (intval($rsprodcat->fields['idprod_serial']) > 0) {
    echo "Acceso Denegado!<br />-No se puede borrar la marca por que existen productos asignados.<br />-Para borrar la marca debe eliminar primero esos productos o asignarlos a otra marca.";
    exit;
}



if (isset($_POST['MM_delete']) && $_POST['MM_delete'] == 'form1') {



    // validaciones basicas
    $valido = "S";
    $errores = "";



    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update marca
		set 
		idestado = 6
		where
			idempresa=$idempresa
			and idmarca=$idmarca
			and idestado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: gest_marcas.php");
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
                    <h2>Marca</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idmarca *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="" name="idmarca" id="idmarca" value="<?php  if (isset($_POST['idmarca'])) {
	    echo($_POST['idmarca']);
	} else {
	    echo($rs->fields['idmarca']);
	}?>" placeholder="Idmarca" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Marca *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="" name="marca" id="marca" value="<?php  if (isset($_POST['marca'])) {
	    echo($_POST['marca']);
	} else {
	    echo($rs->fields['marca']);
	}?>" placeholder="Marca" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_marcas.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>


  <input type="hidden" name="MM_delete" value="form1" />
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

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
<div id="preloader-overlay">
<div class="lds-facebook">
	<div></div>
	<div></div>
	<div></div>
</div>
</div>
  </body>
</html>
