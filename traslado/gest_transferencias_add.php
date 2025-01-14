<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "222";
require_once("includes/rsusuario.php");



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
    $fecha_transferencia = antisqlinyeccion($_POST['fecha_transferencia'], "text");
    $origen = antisqlinyeccion($_POST['origen'], "int");
    $destino = antisqlinyeccion($_POST['destino'], "int");
    $estado = 1;




    if (trim($_POST['fecha_transferencia']) == '') {
        $valido = "N";
        $errores .= " - El campo fecha_transferencia no puede estar vacio.<br />";
    }
    if (intval($_POST['origen']) == 0) {
        $valido = "N";
        $errores .= " - El campo origen no puede ser cero o nulo.<br />";
    }
    if (intval($_POST['destino']) == 0) {
        $valido = "N";
        $errores .= " - El campo destino no puede ser cero o nulo.<br />";
    }


    $origen = intval($_POST['origen']);
    $destino = intval($_POST['destino']);
    if ($origen == $destino) {
        $valido = "N";
        $errores .= " - El origen no puede ser el mismo que el destino.<br />";
    }

    // validamos que no exista inventario posterior tanto en origen como en destino
    $consulta = "
	SELECT * FROM inventario where fecha_inicio > $fecha_transferencia and iddeposito = $origen and idempresa = $idempresa
	";
    $rs_ori = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
	SELECT * FROM inventario where fecha_inicio > $fecha_transferencia and iddeposito = $destino and idempresa = $idempresa
	";
    $rs_des = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // validamos
    if (strtotime(date("Y-m-d", strtotime($_POST['fecha_transferencia']))) > strtotime(date("Y-m-d"))) {
        $valido = "N";
        $errores .= " - No puedes iniciar una transferencia con una fecha en el futuro..<br />";
    }
    if ($rs_ori->fields['iddeposito'] > 0) {
        $valido = "N";
        $errores .= " - No puedes iniciar una transferencia con una fecha anterior a un inventario ya cerrado en origen.<br />";
    }
    if ($rs_des->fields['iddeposito'] > 0) {
        $valido = "N";
        $errores .= " - No puedes iniciar una transferencia con una fecha anterior a un inventario ya cerrado en destino.<br />";
    }



    // si todo es correcto inserta
    if ($valido == "S") {

        //buscamos el id
        $buscar = "Select max(idtanda) as mayor from gest_transferencias";
        $idt = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idtanda = intval($idt->fields['mayor']) + 1;

        $consulta = "
		insert into gest_transferencias
		(idtanda,fecha_transferencia, fecha_real, generado_por, origen, destino, idempresa, idsucursal, estado)
		values
		($idtanda,$fecha_transferencia, '$ahora', $idusu, $origen, $destino, $idempresa, $idsucursal, 1)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: gest_transferencias.php");
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
              <!--<div class="title_left">
                <h3>Plain Page</h3>
              </div>-->

              <!--<div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for...">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button">Go!</button>
                    </span>
                  </div>
                </div>
              </div>-->
            </div>

            <div class="clearfix"></div>
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Agregar Traslado</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <!--<li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>-->
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha traslado *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="fecha_transferencia" id="fecha_transferencia" value="<?php  if (isset($_POST['fecha_transferencia'])) {
	    echo htmlentities($_POST['fecha_transferencia']);
	} else {
	    echo htmlentities($rs->fields['fecha_transferencia']);
	}?>" placeholder="Fecha transferencia" class="form-control" required />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Deposito Origen *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
        // consulta
        $consulta = "
		SELECT iddeposito, descripcion
		FROM gest_depositos
		where
		estado = 1
		and tiposala <> 3
		and iddeposito in (select iddeposito from traslados_permisos where idusuario = $idusu and saliente = 'S'  and estado = 1)
		order by descripcion asc
 		";

// valor seleccionado
if (isset($_POST['origen'])) {
    $value_selected = htmlentities($_POST['origen']);
} else {
    $value_selected = htmlentities($rs->fields['origen']);
    //$value_selected=1;
}

// parametros
$parametros_array = [
'nombre_campo' => 'origen',
'id_campo' => 'origen',

'nombre_campo_bd' => 'descripcion',
'id_campo_bd' => 'iddeposito',

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

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Deposito Destino *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
// consulta
$consulta = "
		SELECT iddeposito, descripcion
		FROM gest_depositos
		where
		estado = 1
		and tiposala <> 3
		and iddeposito in (select iddeposito from traslados_permisos where idusuario = $idusu and entrante = 'S' and estado = 1)
		order by descripcion asc
 		";

// valor seleccionado
if (isset($_POST['destino'])) {
    $value_selected = htmlentities($_POST['destino']);
} else {
    $value_selected = htmlentities($rs->fields['destino']);
    //$value_selected=1;
}

// parametros
$parametros_array = [
'nombre_campo' => 'destino',
'id_campo' => 'destino',

'nombre_campo_bd' => 'descripcion',
'id_campo_bd' => 'iddeposito',

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


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_transferencias.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
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
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
