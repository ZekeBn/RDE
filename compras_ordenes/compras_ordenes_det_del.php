<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");





$ocseria = intval($_GET['id']);
if ($ocseria == 0) {
    header("location: compras_ordenes.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from compras_ordenes_detalles
inner join compras_ordenes on compras_ordenes.ocnum = compras_ordenes_detalles.ocnum 
where 
ocseria = $ocseria
and compras_ordenes.estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ocseria = intval($rs->fields['ocseria']);
$ocnum = intval($rs->fields['ocnum']);
if ($ocseria == 0) {
    header("location: compras_ordenes.php");
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


    $consulta = "SELECT count(*) as item, compras.idtran as idtran,compras.idcompra as idcompra
	from compras_detalles
	INNER JOIN compras on compras.idtran = compras_detalles.idtrans
	WHERE compras_detalles.codprod = (
	
									select idprod 
									from compras_ordenes_detalles 
									where compras_ordenes_detalles.ocnum = $ocnum 
									and compras_ordenes_detalles.ocseria = $ocseria 
	) and compras.ocnum = $ocnum";
    $item_factura = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($item_factura->fields['item']) > 0) {
        $errores .= "- No es posible borrar este item ya se encuentra asociado a una compra idcompra = ".$item_factura->fields['idcompra']." idtrans = ".$item_factura->fields['idtran'].".<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots


    // recibe parametros
    //$idprod=antisqlinyeccion($_POST['idprod'],"text");
    $cantidad = antisqlinyeccion($_POST['cantidad'], "float");
    $precio_compra = antisqlinyeccion($_POST['precio_compra'], "float");
    //$ocseria=antisqlinyeccion($_POST['ocseria'],"text");
    //$descripcion=antisqlinyeccion($_POST['descripcion'],"text");



    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		delete from  compras_ordenes_detalles
		where
			ocseria=$ocseria
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		SELECT sum(precio_compra_total) as precio_total FROM `compras_ordenes_detalles` WHERE ocnum = $ocnum
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
		Select *, cotizaciones.cotizacion from compras_ordenes left JOIN cotizaciones on compras_ordenes.idcot = cotizaciones.idcot  where ocnum = $ocnum
		";
        $rscot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $precio_total = floatval($rs->fields['precio_total']) * floatval($rscot->fields['cotizacion']);
        $consulta = "Update compras_ordenes set costo_ref = $precio_total where ocnum = $ocnum";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        header("location: compras_ordenes_det.php?id=$ocnum");
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
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Borrar Detalle de Orden de Compra</h2>
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

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Descripcion </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
	    echo htmlentities($_POST['descripcion']);
	} else {
	    echo htmlentities($rs->fields['descripcion']);
	}?>" placeholder="Descripcion" class="form-control" disabled  />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cantidad *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="cantidad" id="cantidad" value="<?php  if (isset($_POST['cantidad'])) {
	    echo floatval($_POST['cantidad']);
	} else {
	    echo floatval($rs->fields['cantidad']);
	}?>" placeholder="Cantidad" class="form-control" disabled/>                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Precio compra *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="precio_compra" id="precio_compra" value="<?php  if (isset($_POST['precio_compra'])) {
	    echo floatval($_POST['precio_compra']);
	} else {
	    echo floatval($rs->fields['precio_compra']);
	}?>" placeholder="Precio compra" class="form-control" disabled />                    
	</div>
</div>




<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='compras_ordenes_det.php?id=<?php echo $ocnum ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
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
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
