 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "130";
require_once("includes/rsusuario.php");



$idajuste = intval($_GET['id']);
if ($idajuste == 0) {
    header("location: ajustar_stock.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from gest_depositos_ajustes_stock 
where 
idajuste = $idajuste
and estado = 'A'
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idajuste = intval($rs->fields['idajuste']);
if ($idajuste == 0) {
    header("location: ajustar_stock.php");
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


    // buscar si hay items en el temporal
    $consulta = "
     SELECT * 
     FROM tmp_ajuste
     where
     idajuste = $idajuste 
     ";
    $rsajtmp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $unicaser = intval($rsajtmp->fields['unicaser']);
    if ($unicaser > 0) {
        $errores .= "- No se puede borrar esta tanda por que hay items cargados, borre primero los items.<br />";
        $valido = "N";
    }

    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
         delete from gest_depositos_ajustes_stock
         where
         idajuste = $idajuste 
         and estado = 'A'
         ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: ajustar_stock.php");
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
                    <h2>Editar Tanda de Ajuste de Stock</h2>
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
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Deposito *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT iddeposito, descripcion
FROM gest_depositos
where
estado = 1
and tiposala <> 3
order by descripcion asc
 ";

// valor seleccionado
if (isset($_POST['iddeposito'])) {
    $value_selected = htmlentities($_POST['iddeposito']);
} else {
    $value_selected = htmlentities($rs->fields['iddeposito']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'iddeposito',
    'id_campo' => 'iddeposito',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'iddeposito',

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


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Motivo *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idmotivo, motivo
FROM motivos_ajuste
where
estado = 1
order by motivo asc
 ";

// valor seleccionado
if (isset($_POST['idmotivo'])) {
    $value_selected = htmlentities($_POST['idmotivo']);
} else {
    $value_selected = htmlentities($rs->fields['idmotivo']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idmotivo',
    'id_campo' => 'idmotivo',

    'nombre_campo_bd' => 'motivo',
    'id_campo_bd' => 'idmotivo',

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
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
       <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='ajustar_stock.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />

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
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
