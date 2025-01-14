<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = "S";
$modulo = "1";
$submodulo = "180";
require_once("../includes/rsusuario.php");


$idcliente = intval($_GET['id']);
if ($idcliente == 0) {
    header("location: cliente.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from cliente 
where 
idcliente = $idcliente
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcliente = intval($rs->fields['idcliente']);
$ruc = $rs->fields['ruc'];
$documento = $rs->fields['documento'];
if ($idcliente == 0) {
    header("location: cliente.php");
    exit;
}

// cliente generico
$consulta = "
select ruc, razon_social
from cliente 
where 
idcliente = $idcliente
limit 1
";
$rscligen = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ruc_pred = $rscligen->fields['ruc'];
$razon_social_pred = $rscligen->fields['razon_social'];

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


    // validar que el ruc no exista excepto si es el ruc generico
    $consulta = "
	select * 
	from cliente 
	where 
	ruc = '$ruc'
	and estado = 1
	and borrable = 'S'
	and idcliente <> $idcliente
	and ruc <> '$ruc_pred'
	limit 1
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['idcliente'] > 0) {
        $valido = "N";
        $errores .= " - Ya existe un cliente con el ruc ingresado, editelo para evitar duplicidad.<br />";
    }
    if ($_POST['documento'] > 0) {
        // validar documento
        $consulta = "
		select * 
		from cliente 
		where 
		documento = $documento
		and estado = 1
		and borrable = 'S'
		and idcliente <> $idcliente
		limit 1
		";
        $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($rsex->fields['idcliente'] > 0) {
            $valido = "N";
            $errores .= " - Ya existe un cliente con el documento ingresado, editelo para evitar duplicidad.<br />";
        }
    }

    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		update cliente
		set
			estado = 1
		where
			idcliente = $idcliente
			and estado = 6
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        header("location: cliente.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());




?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>
function cliente_tipo(tipo){
	// persona fisica
	if(tipo == 1){
		$("#nombre_box").show();
		$("#apellido_box").show();
		$("#fantasia_box").hide();
	// persona juridica
	}else{
		$("#nombre_box").hide();
		$("#apellido_box").hide();
		$("#fantasia_box").show();
	}
	
}
</script>
  </head>

  <body class="nav-md" onLoad="cliente_tipo('<?php echo $rs->fields['tipocliente'] ?>');">
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
                    <h2>Restaurar Cliente</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo cliente *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
    $tipocliente = $rs->fields['tipocliente'];
// consulta
$consulta = "
    SELECT idclientetipo, clientetipo
    FROM cliente_tipo
    where
    estado = 1
	and idclientetipo = $tipocliente
    order by clientetipo asc
     ";

// valor seleccionado
if (isset($_POST['idclientetipo'])) {
    $value_selected = htmlentities($_POST['idclientetipo']);
} else {
    $value_selected = htmlentities($rs->fields['tipocliente']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idclientetipo',
    'id_campo' => 'idclientetipo',

    'nombre_campo_bd' => 'clientetipo',
    'id_campo_bd' => 'idclientetipo',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control" readonly="readonly" disabled="disabled"',
    'acciones' => '  readonly ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon social *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="razon_social" id="razon_social" value="<?php  if (isset($_POST['razon_social'])) {
	    echo htmlentities($_POST['razon_social']);
	} else {
	    echo htmlentities($rs->fields['razon_social']);
	}?>" placeholder="Razon social" class="form-control" readonly="readonly" disabled="disabled" required  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Ruc *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="ruc" id="ruc" value="<?php  if (isset($_POST['ruc'])) {
	    echo htmlentities($_POST['ruc']);
	} else {
	    echo htmlentities($rs->fields['ruc']);
	}?>" placeholder="Ruc" class="form-control" readonly="readonly" disabled="disabled"  required  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group" id="nombre_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre" id="nombre" value="<?php  if (isset($_POST['nombre'])) {
	    echo htmlentities($_POST['nombre']);
	} else {
	    echo htmlentities($rs->fields['nombre']);
	}?>" placeholder="Nombre" class="form-control" readonly="readonly" disabled="disabled"   />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="apellido_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Apellido </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="apellido" id="apellido" value="<?php  if (isset($_POST['apellido'])) {
	    echo htmlentities($_POST['apellido']);
	} else {
	    echo htmlentities($rs->fields['apellido']);
	}?>" placeholder="Apellido" class="form-control" readonly="readonly" disabled="disabled"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="fantasia_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre de Fantasia *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="fantasia" id="fantasia" value="<?php  if (isset($_POST['fantasia'])) {
	    echo htmlentities($_POST['fantasia']);
	} else {
	    echo htmlentities($rs->fields['fantasia']);
	}?>" placeholder="Fantasia" class="form-control" readonly="readonly" disabled="disabled"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Documento </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="documento" id="documento" value="<?php  if (isset($_POST['documento'])) {
	    echo htmlentities($_POST['documento']);
	} else {
	    echo htmlentities($rs->fields['documento']);
	}?>" placeholder="Documento" class="form-control" readonly="readonly" disabled="disabled"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="telefono" id="telefono" value="<?php  if (isset($_POST['telefono'])) {
	    echo htmlentities($_POST['telefono']);
	} else {
	    echo htmlentities($rs->fields['telefono']);
	}?>" placeholder="Telefono" class="form-control" readonly="readonly" disabled="disabled"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Celular </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="celular" id="celular" value="<?php  if (isset($_POST['celular'])) {
	    echo floatval($_POST['celular']);
	} else {
	    echo floatval($rs->fields['celular']);
	}?>" placeholder="Celular" class="form-control" readonly="readonly" disabled="disabled"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Email </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="email" id="email" value="<?php  if (isset($_POST['email'])) {
	    echo htmlentities($_POST['email']);
	} else {
	    echo htmlentities($rs->fields['email']);
	}?>" placeholder="Email" class="form-control" readonly="readonly" disabled="disabled"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="direccion" id="direccion" value="<?php  if (isset($_POST['direccion'])) {
	    echo htmlentities($_POST['direccion']);
	} else {
	    echo htmlentities($rs->fields['direccion']);
	}?>" placeholder="Direccion" class="form-control" readonly="readonly" disabled="disabled"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha nacimiento </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="fechanac" id="fechanac" value="<?php  if (isset($_POST['fechanac'])) {
	    echo htmlentities($_POST['fechanac']);
	} else {
	    echo htmlentities($rs->fields['fechanac']);
	}?>" placeholder="Fechanac" class="form-control" readonly="readonly" disabled="disabled"  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Canal Venta </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
select idcanalventa, canal_venta
from canal_venta 
where 
 estado = 1 
order by canal_venta asc
 ";

// valor seleccionado
if (isset($_POST['idcanalventa'])) {
    $value_selected = htmlentities($_POST['idcanalventa']);
} else {
    $value_selected = htmlentities($rs->fields['idcanalventacli']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idcanalventa',
    'id_campo' => 'idcanalventa',

    'nombre_campo_bd' => 'canal_venta',
    'id_campo_bd' => 'idcanalventa',

    'value_selected' => $value_selected,

    'pricampo_name' => 'SIN CANAL',
    'pricampo_value' => '',
    'style_input' => 'class="form-control" readonly="readonly" disabled="disabled"',
    'acciones' => ' ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Vendedor Asignado </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
select idvendedor, CONCAT(nombres,' ',apellidos) as vendedor
from vendedor 
where 
 estado = 1 
order by CONCAT(nombres,' ',apellidos) asc
 ";

// valor seleccionado
if (isset($_POST['idvendedor'])) {
    $value_selected = htmlentities($_POST['idvendedor']);
} else {
    $value_selected = htmlentities($rs->fields['idvendedor']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idvendedor',
    'id_campo' => 'idvendedor',

    'nombre_campo_bd' => 'vendedor',
    'id_campo_bd' => 'idvendedor',

    'value_selected' => $value_selected,

    'pricampo_name' => 'SIN VENDEDOR',
    'pricampo_value' => '',
    'style_input' => 'class="form-control" readonly="readonly" disabled="disabled"',
    'acciones' => ' ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Comentario </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="comentario" id="comentario" value="<?php  if (isset($_POST['comentario'])) {
	    echo htmlentities($_POST['comentario']);
	} else {
	    echo htmlentities($rs->fields['comentario']);
	}?>" placeholder="Comentario" class="form-control" readonly="readonly" disabled="disabled"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">RUC Especial *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['ruc_especial'])) {
    $value_selected = htmlentities($_POST['ruc_especial']);
} else {
    $value_selected = $rs->fields['diplomatico'];
}
// opciones
$opciones = [
    'NO' => 'N',
    'SI (DIPLOMATICOS, ONG, ETC)' => 'S',
];
// parametros
$parametros_array = [
    'nombre_campo' => 'ruc_especial',
    'id_campo' => 'ruc_especial',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control" readonly="readonly" disabled="disabled"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);

?>
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-recycle"></span> Restaurar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='cliente_borrado.php'"><span class="fa fa-ban"></span> Cancelar</button>
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
