<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "129";
require_once("includes/rsusuario.php");

$iddomicilio = intval($_GET['id']);
if ($iddomicilio == 0) {
    header("location: delivery_pedidos.php");
    exit;
}

// busca clientes
$consulta = "
select * 
from cliente_delivery
inner join cliente_delivery_dom on cliente_delivery_dom.idclientedel =  cliente_delivery.idclientedel
where
cliente_delivery_dom.iddomicilio = $iddomicilio
and cliente_delivery.idempresa = $idempresa
limit 1
";
$rscab_old = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idclientedel = intval($rscab_old->fields['idclientedel']);
if ($idclientedel == 0) {
    header("location: delivery_pedidos.php");
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

        //cliente delivery domicilio
        $update = "
		update cliente_delivery_dom 
		set 
		estado=6,
		anulado_por=$idusu,
		anulado_el='$ahora'
		where 
		iddomicilio=$iddomicilio
		";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

        header("location: delivery_pedidos_dir.php?id=$idclientedel");
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
                    <h2>Editar Domicilio</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
              <thead>
                <tr>

                  <th >Nombre y Apellido</th>
                  <th >Telefono</th>
                </tr>
              </thead>
              <tbody>
                <?php while (!$rscab_old->EOF) {
                    $idclientedel = $rscab_old->fields['idclientedel'];

                    ?>
                <tr>

                  <td align="left"><?php echo $rscab_old->fields['nombres']; ?> <?php echo $rscab_old->fields['apellidos']; ?></td>
                   <td align="center">0<?php echo $rscab_old->fields['telefono']; ?></td>
                </tr>
                <?php $rscab_old->MoveNext();
                } $rscab_old->MoveFirst(); ?>
              </tbody>
            </table>
</div>


<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Lugar *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre_domicilio" id="nombre_domicilio" value="<?php  if (isset($_POST['nombre_domicilio'])) {
	    echo htmlentities($_POST['nombre_domicilio']);
	} elseif (trim($rscab_old->fields['nombre_domicilio']) != '') {
	    echo htmlentities($rscab_old->fields['nombre_domicilio']);
	} else {
	    echo "";
	}?>" placeholder="Casa, Trabajo, etc" class="form-control" disabled />                    
	</div>
</div>



<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="direccion" id="direccion" value="<?php  if (isset($_POST['direccion'])) {
	    echo htmlentities($_POST['direccion']);
	} else {
	    echo htmlentities($rscab_old->fields['direccion']);
	}?>" placeholder="Direccion" class="form-control" disabled />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Referencia </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="referencia" id="referencia" value="<?php  if (isset($_POST['referencia'])) {
	    echo htmlentities($_POST['referencia']);
	} else {
	    echo htmlentities($rscab_old->fields['referencia']);
	}?>" placeholder="Referencia" class="form-control"  disabled />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='delivery_pedidos_dir.php?id=<?php echo $idclientedel ?>'"><span class="fa fa-ban"></span> Cancelar</button>
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
