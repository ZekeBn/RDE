<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");



$ocnum = intval($_GET['id']);
if ($ocnum == 0) {
    header("location: compras_ordenes.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from compras_ordenes 
where 
ocnum = $ocnum
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ocnum = intval($rs->fields['ocnum']);
if ($ocnum == 0) {
    header("location: compras_ordenes.php");
    exit;
}


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    $facturas_compras = 0;
    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $consulta = "
	select count(*) as facturas_compras
	from compras 
	where 
	ocnum = $ocnum
	and estado = 1
	limit 1
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $facturas_compras = intval($rs->fields['facturas_compras']);
    //echo $facturas_compras;exit;
    if (trim($facturas_compras) > 0) {
        $valido = "N";
        $errores .= "- No es posible eliminar esta orden debido a que existen compras asociadas realizadas a la misma.<br />";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots




    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		update compras_ordenes
		set
			estado = 6
		where
			ocnum = $ocnum
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: compras_ordenes.php");
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
                    <h2>Borrar Orden de Compra</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Orden *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="fecha" id="fecha" value="<?php  if (isset($_POST['fecha'])) {
	    echo htmlentities($_POST['fecha']);
	} else {
	    echo date("Y-m-d", strtotime($rs->fields['fecha']));
	}?>" placeholder="Fecha" class="form-control" disabled />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipocompra *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<select name="tipocompra" class="form-control" disabled>
	  <option value="0">Seleccionar...</option>
      <option value="1" <?php


      if ($_POST['tipocompra'] == 1) {
          echo "selected";
      } else {
          if ($rs->fields['tipocompra'] == 1) {
              echo "selected";
          }
      }




?>>CONTADO</option>
      <option value="2" <?php

      if ($_POST['tipocompra'] == 2) {
          echo "selected";
      } else {
          if ($rs->fields['tipocompra'] == 2) {
              echo "selected";
          }
      }


?>>CREDITO</option>
	</select>
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha entrega </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="fecha_entrega" id="fecha_entrega" value="<?php  if (isset($_POST['fecha_entrega'])) {
	    echo htmlentities($_POST['fecha_entrega']);
	} else {
	    echo htmlentities($rs->fields['fecha_entrega']);
	}?>" placeholder="Fecha entrega" class="form-control" disabled  />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedor </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idproveedor, nombre
FROM proveedores
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['idproveedor'])) {
    $value_selected = htmlentities($_POST['idproveedor']);
} else {
    $value_selected = htmlentities($rs->fields['idproveedor']);
}
// parametros
$parametros_array = [
    'nombre_campo' => 'idproveedor',
    'id_campo' => 'idproveedor',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idproveedor',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' disabled ',
    'autosel_1registro' => 'S'

];
// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>



<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='compras_ordenes.php'"><span class="fa fa-ban"></span> Cancelar</button>
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