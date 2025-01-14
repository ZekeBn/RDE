<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "24";

require_once("../includes/rsusuario.php");


$idproveedor = intval($_GET['id']);
if ($idproveedor == 0) {
    header("location: gest_proveedores.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from proveedores 
where 
idproveedor = $idproveedor
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idproveedor = intval($rs->fields['idproveedor']);
if ($idproveedor == 0) {
    header("location: gest_proveedores.php");
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




    // si todo es correcto inserta
    if ($valido == "S") {


        $consulta = "
		update proveedores
		set
			estado = 6
		where
			idproveedor = $idproveedor
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: gest_proveedores.php");
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
                    <h2>Borrar Proveedor</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Ruc *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input disabled type="text" name="ruc" id="ruc" value="<?php  if (isset($_POST['ruc'])) {
	    echo htmlentities($_POST['ruc']);
	} else {
	    echo htmlentities($rs->fields['ruc']);
	}?>" placeholder="Ruc" class="form-control" required />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon Social *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input disabled type="text" name="nombre" id="nombre" value="<?php  if (isset($_POST['nombre'])) {
	    echo htmlentities($_POST['nombre']);
	} else {
	    echo htmlentities($rs->fields['nombre']);
	}?>" placeholder="Nombre" class="form-control" required  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input disabled type="text" name="direccion" id="direccion" value="<?php  if (isset($_POST['direccion'])) {
	    echo htmlentities($_POST['direccion']);
	} else {
	    echo htmlentities($rs->fields['direccion']);
	}?>" placeholder="Direccion" class="form-control"  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input disabled type="text" name="telefono" id="telefono" value="<?php  if (isset($_POST['telefono'])) {
	    echo htmlentities($_POST['telefono']);
	} else {
	    echo htmlentities($rs->fields['telefono']);
	}?>" placeholder="Telefono" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Email </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input disabled type="text" name="email" id="email" value="<?php  if (isset($_POST['email'])) {
	    echo htmlentities($_POST['email']);
	} else {
	    echo htmlentities($rs->fields['email']);
	}?>" placeholder="Email" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Contacto </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input disabled type="text" name="contacto" id="contacto" value="<?php  if (isset($_POST['contacto'])) {
	    echo htmlentities($_POST['contacto']);
	} else {
	    echo htmlentities($rs->fields['contacto']);
	}?>" placeholder="Contacto" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Area </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input disabled type="text" name="area" id="area" value="<?php  if (isset($_POST['area'])) {
	    echo htmlentities($_POST['area']);
	} else {
	    echo htmlentities($rs->fields['area']);
	}?>" placeholder="Area" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Email conta </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input disabled type="text" name="email_conta" id="email_conta" value="<?php  if (isset($_POST['email_conta'])) {
	    echo htmlentities($_POST['email_conta']);
	} else {
	    echo htmlentities($rs->fields['email_conta']);
	}?>" placeholder="Email conta" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Comentarios </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input disabled type="text" name="comentarios" id="comentarios" value="<?php  if (isset($_POST['comentarios'])) {
	    echo htmlentities($_POST['comentarios']);
	} else {
	    echo htmlentities($rs->fields['comentarios']);
	}?>" placeholder="Comentarios" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Web </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input disabled type="text" name="web" id="web" value="<?php  if (isset($_POST['web'])) {
	    echo htmlentities($_POST['web']);
	} else {
	    echo htmlentities($rs->fields['web']);
	}?>" placeholder="Web" class="form-control"  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Dias de Credito *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input disabled type="text" name="diasvence" id="diasvence" value="<?php  if (isset($_POST['diasvence'])) {
	    echo intval($_POST['diasvence']);
	} else {
	    echo intval($rs->fields['diasvence']);
	}?>" placeholder="Diasvence" class="form-control" required />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Sin Factura *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<select disabled name="incrementa" id="incrementa"  title="Sin Factura" class="form-control" required>
       <option value="" >Seleccionar</option>
       <option value="1" <?php if ($_POST['incremental'] == '1') {?> selected="selected" <?php } ?>>SI</option>
       <option value="0" <?php if ($_POST['incremental'] == '0' or $_POST['incremental'] == '') {?> selected="selected" <?php } ?>>NO</option>
       </select>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Acuerdo comercial *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<select disabled name="acuerdo_comercial" id="acuerdo_comercial"  title="Acuerdo comercial" class="form-control" required>
       <option value="" >Seleccionar</option>
       <option value="1" <?php if ($_POST['acuerdo_comercial'] == '1') {?> selected="selected" <?php } ?>>SI</option>
       <option value="0" <?php if ($_POST['acuerdo_comercial'] == '0' or $_POST['acuerdo_comercial'] == '') {?> selected="selected" <?php } ?>>NO</option>
       </select>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Acuerdo comercial Detalle </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input disabled type="text" name="acuerdo_comercial_coment" id="acuerdo_comercial_coment" value="<?php  if (isset($_POST['acuerdo_comercial_coment'])) {
	    echo htmlentities($_POST['acuerdo_comercial_coment']);
	} else {
	    echo htmlentities($rs->fields['acuerdo_comercial_coment']);
	}?>" placeholder="Acuerdo comercial detalle" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Web </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input disabled type="text" name="web" id="web" value="<?php  if (isset($_POST['web'])) {
	    echo htmlentities($_POST['web']);
	} else {
	    echo htmlentities($rs->fields['web']);
	}?>" placeholder="Web" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Comentarios </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input disabled type="text" name="comentarios" id="comentarios" value="<?php  if (isset($_POST['comentarios'])) {
	    echo htmlentities($_POST['comentarios']);
	} else {
	    echo htmlentities($rs->fields['comentarios']);
	}?>" placeholder="Comentarios" class="form-control"  />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_proveedores.php'"><span class="fa fa-ban"></span> Cancelar</button>
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
