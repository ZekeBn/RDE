<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "55";
$dirsup = 'S';
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
    $direccion = antisqlinyeccion($_POST['direccion'], "text");
    $idencargado = antisqlinyeccion($_POST['idencargado'], "int");
    $estado = 1;
    $descripcion = antisqlinyeccion($_POST['descripcion'], "text");
    $orden_nro = antisqlinyeccion($_POST['orden_nro'], "int");
    $idsucursal = antisqlinyeccion($_POST['idsucursal'], "int");



    if (intval($_POST['idencargado']) == 0) {
        $valido = "N";
        $errores .= " - El campo idencargado no puede ser cero o nulo.<br />";
    }

    if (trim($_POST['descripcion']) == '') {
        $valido = "N";
        $errores .= " - El campo deposito no puede estar vacio.<br />";
    }

    if (intval($_POST['idsucursal']) == 0) {
        $valido = "N";
        $errores .= " - El campo sucursal no puede ser cero o nulo.<br />";
    }

    if (intval($_POST['orden_nro']) == 0) {
        $valido = "N";
        $errores .= " - El campo orden_nro no puede ser cero o nulo.<br />";
    }


    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		select max(iddeposito) as maxid from gest_depositos
		";
        $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $iddeposito = $rsmax->fields['maxid'] + 1;

        $consulta = "
		insert into gest_depositos
		(iddeposito, direccion, idencargado, estado, descripcion, tiposala, color, idempresa, idsucursal, borrable, compras, orden_nro)
		values
		($iddeposito, $direccion, $idencargado, 1, $descripcion, 1, '#FFFFFF', 1, $idsucursal, 'S', 0, $orden_nro)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: gest_adm_depositos.php");
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
                    <h2>Agregar Deposito</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Deposito </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
	    echo htmlentities($_POST['descripcion']);
	} else {
	    echo htmlentities($rs->fields['descripcion']);
	}?>" placeholder="Descripcion" class="form-control"  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Orden *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="orden_nro" id="orden_nro" value="<?php  if (isset($_POST['orden_nro'])) {
	    echo intval($_POST['orden_nro']);
	} else {
	    echo 1;
	}?>" placeholder="Orden nro" class="form-control" required />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idsucu, nombre
FROM sucursales
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['idsucursal'])) {
    $value_selected = htmlentities($_POST['idsucursal']);
} else {
    $value_selected = htmlentities($rs->fields['idsucursal']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsucursal',
    'id_campo' => 'idsucursal',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsucu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Encargado *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idusu, usuario
FROM usuarios
where
estado = 1
order by usuario asc
 ";

// valor seleccionado
if (isset($_POST['idencargado'])) {
    $value_selected = htmlentities($_POST['idencargado']);
} else {
    $value_selected = htmlentities($rs->fields['idencargado']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idencargado',
    'id_campo' => 'idencargado',

    'nombre_campo_bd' => 'usuario',
    'id_campo_bd' => 'idusu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
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
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_adm_depositos.php'"><span class="fa fa-ban"></span> Cancelar</button>
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
