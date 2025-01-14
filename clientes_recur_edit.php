<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "26";
$submodulo = "298";
require_once("includes/rsusuario.php");


$idcliente = intval($_GET['id']);
if ($idcliente == 0) {
    header("location: clientes_recur.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from cliente 
where 
idcliente = $idcliente
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcliente = intval($rs->fields['idcliente']);
if ($idcliente == 0) {
    header("location: clientes_recur.php");
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

    // recibe variables
    $nombre = antisqlinyeccion($_POST['nombre'], "text");
    $apellido = antisqlinyeccion($_POST['apellido'], "text");
    $documento = antisqlinyeccion($_POST['documento'], "text");
    $ruc = antisqlinyeccion($_POST['ruc'], "text");
    $razon_social = antisqlinyeccion($_POST['razon_social'], "text");
    $email = antisqlinyeccion($_POST['email'], "text");
    $direccion = antisqlinyeccion($_POST['direccion'], "text");
    $celular = antisqlinyeccion($_POST['celular'], "int");
    $telefono = antisqlinyeccion($_POST['telefono'], "int");

    $fantasia = antisqlinyeccion($_POST['fantasia'], "text");
    $contacto = antisqlinyeccion($_POST['contacto'], "text");
    $cargo_contacto = antisqlinyeccion($_POST['cargo_contacto'], "text");
    $telefono_contacto = antisqlinyeccion($_POST['telefono_contacto'], "text");
    $responsable_pago = antisqlinyeccion($_POST['responsable_pago'], "text");
    $telefono_responsablepago = antisqlinyeccion($_POST['telefono_responsablepago'], "text");
    $comentario = antisqlinyeccion($_POST['comentario'], "int");

    //validar
    if (trim($_POST['fantasia']) == '') {
        $errores .= "- Debe indicar el nombre de fantasia, si no tiene es el mismo que la razon social.<br />";
        $valido = "N";
    }
    if (trim($_POST['razon_social']) == '') {
        $errores .= "- Debe indicar la razon social.<br />";
        $valido = "N";
    }
    if (trim($_POST['ruc']) == '') {
        $errores .= "- Debe indicar el ruc.<br />";
        $valido = "N";
    }
    $ruc_ar = explode("-", trim($_POST['ruc']));
    $ruc_pri = intval($ruc_ar[0]);
    $ruc_dv = intval($ruc_ar[1]);
    if ($ruc_pri <= 0) {
        $errores .= "- El ruc no puede ser cero o menor.<br />";
        $valido = "N";
    }
    if (strlen($ruc_dv) <> 1) {
        $errores .= "- El digito verificador del ruc no puede tener 2 numeros.<br />";
        $valido = "N";
    }
    if (calcular_ruc($ruc_pri) <> $ruc_dv) {
        $digitocor = calcular_ruc($ruc_pri);
        $errores .= "- El digito verificador del ruc no corresponde a la cedula el digito debia ser $digitocor para la cedula $ruc_pri.<br />";
        $valido = "N";
    }
    if ($ruc == $ruc_pred && $razon_social <> $razon_social_pred) {
        $errores .= "- La Razon Social debe ser $razon_social_pred si el RUC es $ruc_pred.<br />";
        $valido = "N";
    }
    if (trim($_POST['ruc']) <> $ruc_pred && $razon_social == $razon_social_pred) {
        $errores .= "- El RUC debe ser $ruc_pred si la Razon Social es $razon_social_pred.<br />";
        $valido = "N";
    }
    if (trim($ruc_ar[1]) == '') {
        $errores .= "- El digito verificador del ruc no puede estar vacio.<br />";
        $valido = "N";
    }

    // conversion


    // actualiza
    if ($valido == 'S') {
        $consulta = "
		UPDATE cliente 
		SET 
			nombre=$nombre,
			apellido=$apellido,
			documento=$documento,
			ruc=$ruc,
			telefono=$telefono,
			celular=$celular,
			email=$email,
			direccion=$direccion,
			tipocliente=1,
			razon_social=$razon_social,
			estado=1,
			codclie=0,
			actualizado_el='$ahora',
			sucursal=$idsucursal,
			idclieweb=0,
			idnacion=0,
			fantasia=$fantasia,
			contacto=$contacto,
			cargo_contacto=$cargo_contacto,
			telefono_contacto=$telefono_contacto,
			responsable_pago=$responsable_pago,
			telefono_responsablepago=$telefono_responsablepago,
			comentario=$comentario,
			recurrente=1
		WHERE
			idcliente=$idcliente
			and idempresa=$idempresa
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: clientes_recur.php");
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
                    <h2>Editar Cliente Recurrente</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre Fantasia *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="fantasia" id="fantasia" value="<?php  if (isset($_POST['fantasia'])) {
	    echo htmlentities($_POST['fantasia']);
	} else {
	    echo htmlentities($rs->fields['fantasia']);
	}?>" placeholder="Fantasia" class="form-control" required  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon social *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="razon_social" id="razon_social" value="<?php  if (isset($_POST['razon_social'])) {
	    echo htmlentities($_POST['razon_social']);
	} else {
	    echo htmlentities($rs->fields['razon_social']);
	}?>" placeholder="Razon social" class="form-control" required  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Ruc *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="ruc" id="ruc" value="<?php  if (isset($_POST['ruc'])) {
	    echo htmlentities($_POST['ruc']);
	} else {
	    echo htmlentities($rs->fields['ruc']);
	}?>" placeholder="Ruc" class="form-control" required  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre" id="nombre" value="<?php  if (isset($_POST['nombre'])) {
	    echo htmlentities($_POST['nombre']);
	} else {
	    echo htmlentities($rs->fields['nombre']);
	}?>" placeholder="Nombre" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Apellido </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="apellido" id="apellido" value="<?php  if (isset($_POST['apellido'])) {
	    echo htmlentities($_POST['apellido']);
	} else {
	    echo htmlentities($rs->fields['apellido']);
	}?>" placeholder="Apellido" class="form-control"  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Documento </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="documento" id="documento" value="<?php  if (isset($_POST['documento'])) {
	    echo htmlentities($_POST['documento']);
	} else {
	    echo htmlentities($rs->fields['documento']);
	}?>" placeholder="Documento" class="form-control"  />                    
	</div>
</div>



<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="telefono" id="telefono" value="<?php  if (isset($_POST['telefono'])) {
	    echo htmlentities($_POST['telefono']);
	} else {
	    echo htmlentities($rs->fields['telefono']);
	}?>" placeholder="Telefono" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Celular </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="celular" id="celular" value="<?php  if (isset($_POST['celular'])) {
	    echo floatval($_POST['celular']);
	} else {
	    echo floatval($rs->fields['celular']);
	}?>" placeholder="Celular" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Email </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="email" id="email" value="<?php  if (isset($_POST['email'])) {
	    echo htmlentities($_POST['email']);
	} else {
	    echo htmlentities($rs->fields['email']);
	}?>" placeholder="Email" class="form-control"  />                    
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

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Persona Contacto </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="contacto" id="contacto" value="<?php  if (isset($_POST['contacto'])) {
	    echo htmlentities($_POST['contacto']);
	} else {
	    echo htmlentities($rs->fields['contacto']);
	}?>" placeholder="Contacto" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cargo Persona contacto </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="cargo_contacto" id="cargo_contacto" value="<?php  if (isset($_POST['cargo_contacto'])) {
	    echo htmlentities($_POST['cargo_contacto']);
	} else {
	    echo htmlentities($rs->fields['cargo_contacto']);
	}?>" placeholder="Cargo contacto" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono Persona contacto </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="telefono_contacto" id="telefono_contacto" value="<?php  if (isset($_POST['telefono_contacto'])) {
	    echo htmlentities($_POST['telefono_contacto']);
	} else {
	    echo htmlentities($rs->fields['telefono_contacto']);
	}?>" placeholder="Telefono contacto" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Responsable de pago </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="responsable_pago" id="responsable_pago" value="<?php  if (isset($_POST['responsable_pago'])) {
	    echo htmlentities($_POST['responsable_pago']);
	} else {
	    echo htmlentities($rs->fields['responsable_pago']);
	}?>" placeholder="Responsable pago" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono del responsable de pago </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="telefono_responsablepago" id="telefono_responsablepago" value="<?php  if (isset($_POST['telefono_responsablepago'])) {
	    echo htmlentities($_POST['telefono_responsablepago']);
	} else {
	    echo htmlentities($rs->fields['telefono_responsablepago']);
	}?>" placeholder="Telefono responsablepago" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Comentario </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="comentario" id="comentario" value="<?php  if (isset($_POST['comentario'])) {
	    echo htmlentities($_POST['comentario']);
	} else {
	    echo htmlentities($rs->fields['comentario']);
	}?>" placeholder="Comentario" class="form-control"  />                    
	</div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='clientes_recur.php'"><span class="fa fa-ban"></span> Cancelar</button>
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
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
