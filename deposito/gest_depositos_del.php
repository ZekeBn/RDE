<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "55";
$dirsup = 'S';
require_once("../includes/rsusuario.php");


$iddeposito = intval($_GET['id']);
if ($iddeposito == 0) {
    header("location: gest_adm_depositos.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from gest_depositos 
where 
iddeposito = $iddeposito
and estado = 1
and tiposala = 1
and borrable = 'S'
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rs->fields['iddeposito']);
if ($iddeposito == 0) {
    header("location: gest_adm_depositos.php");
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
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots


    // recibe parametros
    $direccion = antisqlinyeccion($_POST['direccion'], "text");
    $idencargado = antisqlinyeccion($_POST['idencargado'], "int");
    $estado = 1;
    $descripcion = antisqlinyeccion($_POST['descripcion'], "text");
    $orden_nro = antisqlinyeccion($_POST['orden_nro'], "int");

    $consulta = "
	select sum(gest_depositos_stock_gral.disponible) as total 
	from  gest_depositos_stock_gral
	inner join insumos_lista on insumos_lista.idinsumo = gest_depositos_stock_gral.idproducto
	where
	insumos_lista.estado = 'A'
	and gest_depositos_stock_gral.iddeposito = $iddeposito
	";
    $rstot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $totstock = floatval($rstot->fields['total']);

    if ($totstock > 0) {
        $errores .= "- No se puede borrar el deposito por que existen [$totstock] productos con stock dentro.<br />
		&nbsp;&nbsp;&nbsp;&nbsp;> Posibles Soluciones:<br />
		&nbsp;&nbsp;&nbsp;&nbsp;> Traslade todos los productos a otro deposito.<br />
		&nbsp;&nbsp;&nbsp;&nbsp;> Realice un inventario poniendo en cero todos los articulos del deposito.<br />
		&nbsp;&nbsp;&nbsp;&nbsp;> Registrar un ajuste para darlos de baja todos los articulos del deposito..";
        $valido = "N";
    }


    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update gest_depositos
		set
			estado = 6
		where
			iddeposito = $iddeposito
			and tiposala = 1
			and borrable = 'S'
			and estado = 1
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
                    <h2>Borrar Deposito</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Descripcion </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
	    echo htmlentities($_POST['descripcion']);
	} else {
	    echo htmlentities($rs->fields['descripcion']);
	}?>" placeholder="Descripcion" class="form-control" disabled  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Orden *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="orden_nro" id="orden_nro" value="<?php  if (isset($_POST['orden_nro'])) {
	    echo intval($_POST['orden_nro']);
	} else {
	    echo intval($rs->fields['orden_nro']);
	}?>" placeholder="Orden nro" class="form-control" required disabled />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="direccion" id="direccion" value="<?php  if (isset($_POST['direccion'])) {
	    echo htmlentities($_POST['direccion']);
	} else {
	    echo htmlentities($rs->fields['direccion']);
	}?>" placeholder="Direccion" class="form-control" disabled  />                    
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
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_adm_depositos.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>


  <input type="hidden" name="MM_update" value="form1" />
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
