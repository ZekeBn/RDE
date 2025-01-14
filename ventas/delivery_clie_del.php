<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "129";
require_once("includes/rsusuario.php");

$telefono_g = '0'.intval($_GET['tel']);
$idclientedel = intval($_GET['id']);


$consulta = "
select * from cliente where borrable = 'N' and idempresa = $idempresa order by idcliente asc limit 1
";
$rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$razon_social_pred = strtoupper(trim($rscli->fields['razon_social']));




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


    $buscar = "Select * from cliente_delivery where idclientedel=$idclientedel and estado=1";
    $rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    //echo $buscar;
    $idcliente = intval($rsf->fields['idcliente']);

    if ($idcliente == 0) {
        $valido = "N";
        $errores .= "- Cliente inexistente.<br />";
    }

    // si todo es correcto actualiza
    if ($valido == "S") {


        $update = "update cliente_delivery set estado=6,anulado_por=$idusu,anulado_el=current_timestamp where idclientedel=$idclientedel and idcliente=$idcliente";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

        $update = "update cliente set estado=6,anulado_por=$idusu,anulado_el='$ahora' where  idcliente=$idcliente";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

        header("location: delivery_pedidos.php");
        exit;

    }

}
// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

$consulta = "
	select *, 
	(select razon_social from cliente where idcliente = cliente_delivery.idcliente and cliente.idempresa = $idempresa) as razon_social,
	(select ruc from cliente where idcliente = cliente_delivery.idcliente and cliente.idempresa = $idempresa) as ruc
	from cliente_delivery
	where
	idclientedel is not null
	and idclientedel = $idclientedel
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

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
                    <h2>Borrar cliente de Delivery</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="telefono" id="telefono" value="<?php  if (isset($_POST['telefono'])) {
	    echo htmlentities($_POST['telefono']);
	} else {
	    echo htmlentities('0'.$rs->fields['telefono']);
	}?>" placeholder="telefono" required class="form-control" disabled />
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombres *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombres" id="nombres" value="<?php  if (isset($_POST['nombres'])) {
	    echo htmlentities($_POST['nombres']);
	} else {
	    echo htmlentities($rs->fields['nombres']);
	}?>" placeholder="nombres" required class="form-control" disabled />
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Apellidos *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="apellidos" id="apellidos" value="<?php  if (isset($_POST['apellidos'])) {
	    echo htmlentities($_POST['apellidos']);
	} else {
	    echo htmlentities($rs->fields['apellidos']);
	}?>" placeholder="apellidos" required class="form-control" disabled />
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">RUC *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="ruc" id="ruc" value="<?php  if (isset($_POST['ruc'])) {
	    echo htmlentities($_POST['ruc']);
	} elseif ($rs->fields['ruc'] != '') {
	    echo htmlentities($rs->fields['ruc']);
	} else {
	    echo htmlentities('44444401-7');
	}?>" placeholder="ruc" required class="form-control" disabled />
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon Social *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="razon_social" id="razon_social" value="<?php  if (isset($_POST['razon_social'])) {
	    echo htmlentities($_POST['razon_social']);
	} elseif ($rs->fields['razon_social'] != '') {
	    echo htmlentities($rs->fields['razon_social']);
	} else {
	    echo htmlentities('Consumidor Final');
	}?>" placeholder="razon_social" required class="form-control" disabled />
	</div>
</div>




<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='delivery_pedidos_dir.php?id=<?php echo $rs->fields['idclientedel']; ?>'"><span class="fa fa-ban"></span> Cancelar</button>
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
